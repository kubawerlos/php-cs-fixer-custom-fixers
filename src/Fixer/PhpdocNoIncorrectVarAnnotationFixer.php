<?php declare(strict_types=1);

/*
 * This file is part of PHP CS Fixer: custom fixers.
 *
 * (c) 2018 Kuba Werłos
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace PhpCsFixerCustomFixers\Fixer;

use PhpCsFixer\DocBlock\DocBlock;
use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\FixerDefinition\FixerDefinitionInterface;
use PhpCsFixer\Preg;
use PhpCsFixer\Tokenizer\CT;
use PhpCsFixer\Tokenizer\FCT;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;
use PhpCsFixerCustomFixers\TokenRemover;

/**
 * @no-named-arguments
 */
final class PhpdocNoIncorrectVarAnnotationFixer extends AbstractFixer
{
    public function getDefinition(): FixerDefinitionInterface
    {
        return new FixerDefinition(
            'The `@var` annotations must be used correctly in code.',
            [new CodeSample('<?php
/** @var Foo $foo */
$bar = new Foo();
')],
            '',
        );
    }

    /**
     * Must run before NoEmptyPhpdocFixer, NoExtraBlankLinesFixer, NoUnusedImportsFixer, PhpdocTrimConsecutiveBlankLineSeparationFixer, PhpdocTrimFixer.
     */
    public function getPriority(): int
    {
        return 4;
    }

    public function isCandidate(Tokens $tokens): bool
    {
        return $tokens->isTokenKindFound(\T_DOC_COMMENT);
    }

    public function isRisky(): bool
    {
        return false;
    }

    public function fix(\SplFileInfo $file, Tokens $tokens): void
    {
        for ($index = $tokens->count() - 1; $index > 0; $index--) {
            if (!$tokens[$index]->isGivenKind(\T_DOC_COMMENT)) {
                continue;
            }

            // remove ones not having type at the beginning
            self::removeVarAnnotationNotMatchingPattern($tokens, $index, '/@var\\s+[\\?\\\\a-zA-Z_\\x7f-\\xff]/');

            $nextIndex = self::getIndexAfterPhpDoc($tokens, $index);

            if ($nextIndex === null) {
                self::removeVarAnnotationNotMatchingPattern($tokens, $index, null);
                continue;
            }

            if ($tokens[$nextIndex]->isGivenKind([\T_PRIVATE, \T_PROTECTED, \T_PUBLIC, \T_VAR, \T_STATIC, CT::T_CONSTRUCTOR_PROPERTY_PROMOTION_PUBLIC, CT::T_CONSTRUCTOR_PROPERTY_PROMOTION_PROTECTED, CT::T_CONSTRUCTOR_PROPERTY_PROMOTION_PRIVATE])) {
                self::removeForClassElement($tokens, $index, $nextIndex);
                continue;
            }

            if ($tokens[$nextIndex]->isGivenKind(\T_VARIABLE)) {
                self::removeVarAnnotation($tokens, $index, [$tokens[$nextIndex]->getContent()]);
                continue;
            }

            if ($tokens[$nextIndex]->isGivenKind([\T_FOR, \T_FOREACH, \T_IF, \T_SWITCH, \T_WHILE])) {
                self::removeVarAnnotationForControl($tokens, $index, $nextIndex);
                continue;
            }

            self::removeVarAnnotationNotMatchingPattern($tokens, $index, null);
        }
    }

    private static function getIndexAfterPhpDoc(Tokens $tokens, int $index): ?int
    {
        $nextIndex = $tokens->getNextMeaningfulToken($index);

        while ($nextIndex !== null && $tokens[$nextIndex]->isGivenKind(FCT::T_ATTRIBUTE)) {
            $nextIndex = $tokens->findBlockEnd(Tokens::BLOCK_TYPE_ATTRIBUTE, $nextIndex);
            $nextIndex = $tokens->getNextMeaningfulToken($nextIndex);
        }

        return $nextIndex;
    }

    private static function removeForClassElement(Tokens $tokens, int $index, int $propertyStartIndex): void
    {
        $variableIndex = $tokens->getTokenNotOfKindsSibling($propertyStartIndex, 1, [\T_NS_SEPARATOR, \T_STATIC, \T_STRING, \T_WHITESPACE, CT::T_ARRAY_TYPEHINT, CT::T_NULLABLE_TYPE, CT::T_TYPE_ALTERNATION, CT::T_TYPE_INTERSECTION, FCT::T_READONLY]);
        \assert(\is_int($variableIndex));

        if (!$tokens[$variableIndex]->isGivenKind(\T_VARIABLE)) {
            self::removeVarAnnotationNotMatchingPattern($tokens, $index, null);

            return;
        }

        if (Preg::match('/@var\\h+(.+\\h+)?\\$[a-zA-Z_\\x7f-\\xff][a-zA-Z0-9_\\x7f-\\xff]*/', $tokens[$index]->getContent())) {
            self::removeVarAnnotation($tokens, $index, [$tokens[$variableIndex]->getContent()]);
        }
    }

    /**
     * @param list<string> $allowedVariables
     */
    private static function removeVarAnnotation(Tokens $tokens, int $index, array $allowedVariables): void
    {
        self::removeVarAnnotationNotMatchingPattern(
            $tokens,
            $index,
            '/(\\Q' . \implode('\\E|\\Q', $allowedVariables) . '\\E)\\b/i',
        );
    }

    private static function removeVarAnnotationForControl(Tokens $tokens, int $commentIndex, int $controlIndex): void
    {
        $index = $tokens->getNextMeaningfulToken($controlIndex);
        \assert(\is_int($index));

        $endIndex = $tokens->findBlockEnd(Tokens::BLOCK_TYPE_PARENTHESIS_BRACE, $index);

        $variables = [];

        while ($index < $endIndex) {
            $index++;

            if ($tokens[$index]->isGivenKind(\T_VARIABLE)) {
                $variables[] = $tokens[$index]->getContent();
            }
        }

        self::removeVarAnnotation($tokens, $commentIndex, $variables);
    }

    private static function removeVarAnnotationNotMatchingPattern(Tokens $tokens, int $index, ?string $pattern): void
    {
        $doc = new DocBlock($tokens[$index]->getContent());

        foreach ($doc->getAnnotationsOfType(['var']) as $annotation) {
            if ($pattern === null || !Preg::match($pattern, $annotation->getContent())) {
                $annotation->remove();
            }
        }

        $content = $doc->getContent();

        if ($content === $tokens[$index]->getContent()) {
            return;
        }

        if ($content === '') {
            TokenRemover::removeWithLinesIfPossible($tokens, $index);

            return;
        }
        $tokens[$index] = new Token([\T_DOC_COMMENT, $content]);
    }
}
