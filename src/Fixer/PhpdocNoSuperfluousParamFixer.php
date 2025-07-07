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
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;

/**
 * @no-named-arguments
 */
final class PhpdocNoSuperfluousParamFixer extends AbstractFixer
{
    public function getDefinition(): FixerDefinitionInterface
    {
        return new FixerDefinition(
            'There must be no superfluous parameters in PHPDoc.',
            [new CodeSample('<?php
/**
 * @param bool $b
 * @param int $i
 * @param string $s this is string
 * @param string $s duplicated
 */
function foo($b, $s) {}
')],
            '',
        );
    }

    /**
     * Must run before NoEmptyPhpdocFixer.
     * Must run after CommentToPhpdocFixer.
     */
    public function getPriority(): int
    {
        return 4;
    }

    public function isCandidate(Tokens $tokens): bool
    {
        return $tokens->isAllTokenKindsFound([\T_DOC_COMMENT, \T_FUNCTION]);
    }

    public function isRisky(): bool
    {
        return false;
    }

    public function fix(\SplFileInfo $file, Tokens $tokens): void
    {
        for ($index = 0; $index < $tokens->count(); $index++) {
            if (!$tokens[$index]->isGivenKind(\T_DOC_COMMENT)) {
                continue;
            }

            $functionIndex = $tokens->getTokenNotOfKindSibling($index, 1, [[\T_ABSTRACT], [\T_COMMENT], [\T_FINAL], [\T_PRIVATE], [\T_PROTECTED], [\T_PUBLIC], [\T_STATIC], [\T_WHITESPACE]]);

            if ($functionIndex === null) {
                return;
            }

            if (!$tokens[$functionIndex]->isGivenKind(\T_FUNCTION)) {
                continue;
            }

            $paramNames = self::getParamNames($tokens, $functionIndex);

            $newContent = self::getFilteredDocComment($tokens[$index]->getContent(), $paramNames);

            if ($newContent === $tokens[$index]->getContent()) {
                continue;
            }

            if ($newContent === '') {
                $tokens->clearTokenAndMergeSurroundingWhitespace($index);
            } else {
                $tokens[$index] = new Token([\T_DOC_COMMENT, $newContent]);
            }
        }
    }

    /**
     * @return list<string>
     */
    private static function getParamNames(Tokens $tokens, int $functionIndex): array
    {
        $paramBlockStartIndex = $tokens->getNextTokenOfKind($functionIndex, ['(']);
        \assert(\is_int($paramBlockStartIndex));

        $paramBlockEndIndex = $tokens->findBlockEnd(Tokens::BLOCK_TYPE_PARENTHESIS_BRACE, $paramBlockStartIndex);

        $paramNames = [];
        for ($index = $paramBlockStartIndex; $index < $paramBlockEndIndex; $index++) {
            if ($tokens[$index]->isGivenKind(\T_VARIABLE)) {
                $paramNames[] = $tokens[$index]->getContent();
            }
        }

        return $paramNames;
    }

    /**
     * @param list<string> $paramNames
     */
    private static function getFilteredDocComment(string $comment, array $paramNames): string
    {
        $doc = new DocBlock($comment);

        $foundParamNames = [];
        foreach ($doc->getAnnotationsOfType('param') as $annotation) {
            $paramName = $annotation->getVariableName();

            if (\in_array($paramName, $paramNames, true) && !\in_array($paramName, $foundParamNames, true)) {
                $foundParamNames[] = $paramName;
                continue;
            }

            $annotation->remove();
        }

        return $doc->getContent();
    }
}
