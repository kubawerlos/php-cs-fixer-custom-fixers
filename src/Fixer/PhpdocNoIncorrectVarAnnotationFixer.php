<?php

declare(strict_types = 1);

namespace PhpCsFixerCustomFixers\Fixer;

use PhpCsFixer\DocBlock\DocBlock;
use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\FixerDefinition\FixerDefinitionInterface;
use PhpCsFixer\Preg;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;
use PhpCsFixerCustomFixers\TokenRemover;

final class PhpdocNoIncorrectVarAnnotationFixer extends AbstractFixer
{
    public function getDefinition(): FixerDefinitionInterface
    {
        return new FixerDefinition(
            '`@var` must be correct in the code.',
            [new CodeSample('<?php
/** @var Foo $foo */
$bar = new Foo();
')]
        );
    }

    public function getPriority(): int
    {
        // must be run before NoEmptyPhpdocFixer, NoExtraBlankLinesFixer, NoUnusedImportsFixer, PhpdocTrimConsecutiveBlankLineSeparationFixer and PhpdocTrimFixer
        return 6;
    }

    public function isCandidate(Tokens $tokens): bool
    {
        return $tokens->isTokenKindFound(T_DOC_COMMENT);
    }

    public function isRisky(): bool
    {
        return false;
    }

    public function fix(\SplFileInfo $file, Tokens $tokens): void
    {
        for ($index = $tokens->count() - 1; $index > 0; $index--) {
            if (!$this->isTokenCandidate($tokens[$index])) {
                continue;
            }

            // remove ones not having type at the beginning
            $this->removeVarAnnotationNotMatchingPattern($tokens, $index, '/@var\s+[\?\\\\a-zA-Z_\x7f-\xff]/');

            $nextIndex = $tokens->getNextMeaningfulToken($index);

            if ($nextIndex === null) {
                $this->removeVarAnnotationNotMatchingPattern($tokens, $index, null);

                return;
            }

            if ($tokens[$nextIndex]->isGivenKind([T_PRIVATE, T_PROTECTED, T_PUBLIC, T_VAR, T_STATIC])) {
                continue;
            }

            if ($tokens[$nextIndex]->isGivenKind(T_VARIABLE)) {
                $this->removeVarAnnotation($tokens, $index, [$tokens[$nextIndex]->getContent()]);
                continue;
            }

            if ($tokens[$nextIndex]->isGivenKind([T_FOR, T_FOREACH, T_IF, T_SWITCH, T_WHILE])) {
                $this->removeVarAnnotationForControl($tokens, $index, $nextIndex);
                continue;
            }

            $this->removeVarAnnotationNotMatchingPattern($tokens, $index, null);
        }
    }

    private function isTokenCandidate(Token $token): bool
    {
        return $token->isGivenKind(T_DOC_COMMENT) && \stripos($token->getContent(), '@var') !== false;
    }

    /**
     * @param string[] $allowedVariables
     */
    private function removeVarAnnotation(Tokens $tokens, int $index, array $allowedVariables): void
    {
        $this->removeVarAnnotationNotMatchingPattern(
            $tokens,
            $index,
            '/(\Q' . \implode('\E|\Q', $allowedVariables) . '\E)\b/i'
        );
    }

    private function removeVarAnnotationForControl(Tokens $tokens, int $commentIndex, int $controlIndex): void
    {
        /** @var int $index */
        $index = $tokens->getNextMeaningfulToken($controlIndex);

        $endIndex = $tokens->findBlockEnd(Tokens::BLOCK_TYPE_PARENTHESIS_BRACE, $index);

        $variables = [];

        for ($index = $index + 1; $index < $endIndex; $index++) {
            $token = $tokens[$index];

            if ($token->isGivenKind(T_VARIABLE)) {
                $variables[] = $token->getContent();
            }
        }

        $this->removeVarAnnotation($tokens, $commentIndex, $variables);
    }

    private function removeVarAnnotationNotMatchingPattern(Tokens $tokens, int $index, ?string $pattern): void
    {
        $doc = new DocBlock($tokens[$index]->getContent());

        foreach ($doc->getAnnotationsOfType(['var']) as $annotation) {
            if ($pattern === null || Preg::match($pattern, $annotation->getContent()) !== 1) {
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
        $tokens[$index] = new Token([T_DOC_COMMENT, $this->ensureDocCommentContent($content)]);
    }

    private function ensureDocCommentContent(string $content): string
    {
        if (\strpos($content, '/**') !== 0) {
            $content = '/** ' . $content;
        }
        if (\strpos($content, '*/') === false) {
            $content .= \str_replace(\ltrim($content), '', $content) . '*/';
        }

        return $content;
    }
}
