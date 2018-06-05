<?php

declare(strict_types = 1);

namespace PhpCsFixerCustomFixers\Fixer;

use PhpCsFixer\DocBlock\DocBlock;
use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;

final class PhpdocNoIncorrectVarAnnotationFixer extends AbstractFixer
{
    public function getDefinition() : FixerDefinition
    {
        return new FixerDefinition(
            '`@var` should be correct in the code.',
            [new CodeSample('<?php
/** @var Foo $foo */
$bar = new Foo();
')]
        );
    }

    public function isCandidate(Tokens $tokens) : bool
    {
        return $tokens->isAnyTokenKindsFound([T_COMMENT, T_DOC_COMMENT]);
    }

    public function fix(\SplFileInfo $file, Tokens $tokens) : void
    {
        foreach ($tokens as $index => $token) {
            if (!$token->isGivenKind([T_COMMENT, T_DOC_COMMENT])) {
                continue;
            }

            if (\stripos($token->getContent(), '@var') === false) {
                continue;
            }

            // remove ones not having type at the beginning
            $this->removeVarAnnotationNotMatchingPattern($tokens, $index, '/@var\s+[\?\\\\a-zA-Z_\x7f-\xff]/');

            $nextIndex = $tokens->getNextMeaningfulToken($index);

            if ($nextIndex === null) {
                $this->removeVarAnnotationNotMatchingPattern($tokens, $index, null);
                continue;
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

    public function getPriority() : int
    {
        // must be run before NoEmptyCommentFixer, NoEmptyPhpdocFixer, NoExtraBlankLinesFixer, NoTrailingWhitespaceFixer, NoUnusedImportsFixer and NoWhitespaceInBlankLineFixer
        // must be after PhpdocVarAnnotationCorrectOrderFixer
        return 6;
    }

    private function removeVarAnnotation(Tokens $tokens, int $index, array $allowedVariables) : void
    {
        $this->removeVarAnnotationNotMatchingPattern(
            $tokens,
            $index,
            '/' . \implode(
                '|',
                \array_map(
                    static function (string $variable) : string {
                        return \preg_quote($variable, '/') . '\b';
                    },
                    $allowedVariables
                )
            ) . '/i'
        );
    }

    private function removeVarAnnotationForControl(Tokens $tokens, int $commentIndex, int $controlIndex) : void
    {
        $index    = $tokens->getNextMeaningfulToken($controlIndex);
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

    private function removeVarAnnotationNotMatchingPattern(Tokens $tokens, int $index, ?string $pattern) : void
    {
        $doc = new DocBlock($tokens[$index]->getContent());

        foreach ($doc->getAnnotationsOfType(['var']) as $annotation) {
            if ($pattern === null || \preg_match($pattern, $annotation->getContent()) !== 1) {
                $annotation->remove();
            }
        }

        if ($doc->getContent() === '') {
            $tokens->clearTokenAndMergeSurroundingWhitespace($index);
        } else {
            $tokens[$index] = new Token([$tokens[$index]->getId(), $doc->getContent()]);
        }
    }
}
