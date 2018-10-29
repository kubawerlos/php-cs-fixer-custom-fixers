<?php

declare(strict_types = 1);

namespace PhpCsFixerCustomFixers\Fixer;

use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\Tokenizer\CT;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;

final class EmptyFixer extends AbstractFixer
{
    public function getDefinition(): FixerDefinition
    {
        return new FixerDefinition(
            'Replaces `empty($var)` expression with `[] == $var`.',
            [
                new CodeSample("<?php\n\$x = empty(\$var);\n"),
            ]
        );
    }

    public function getPriority(): int
    {
        // must be run before ArraySyntaxFixer, StrictComparisonFixer and YodaStyleFixer
        return 2;
    }

    public function isCandidate(Tokens $tokens): bool
    {
        return $tokens->isTokenKindFound(T_EMPTY);
    }

    public function isRisky(): bool
    {
        return false;
    }

    public function fix(\SplFileInfo $file, Tokens $tokens): void
    {
        for ($index = $tokens->count() - 1; $index > 0; $index--) {
            if (!$tokens[$index]->isGivenKind(T_EMPTY)) {
                continue;
            }

            $openingParenthesisIndex = $tokens->getNextMeaningfulToken($index);
            $closingParenthesisIndex = $tokens->findBlockEnd(Tokens::BLOCK_TYPE_PARENTHESIS_BRACE, $openingParenthesisIndex);

            $isNegated = $this->isNegated($tokens, $index);
            $toKeepParentheses = $this->toKeepParentheses($tokens, $index, $closingParenthesisIndex);
            $toWrapWithParentheses = $this->toWrapWithParentheses($tokens, $index, $closingParenthesisIndex);

            $replacement = [
                new Token([CT::T_ARRAY_SQUARE_BRACE_OPEN, '[']),
                new Token([CT::T_ARRAY_SQUARE_BRACE_CLOSE, ']']),
                new Token([T_WHITESPACE, ' ']),
                new Token($isNegated ? [T_IS_NOT_EQUAL, '!='] : [T_IS_EQUAL, '==']),
                new Token([T_WHITESPACE, ' ']),
            ];

            if ($toWrapWithParentheses) {
                $tokens->insertAt($closingParenthesisIndex, new Token(')'));
            }
            if (!$toKeepParentheses) {
                $tokens->clearAt($closingParenthesisIndex);
                $tokens->removeLeadingWhitespace($closingParenthesisIndex);
                $tokens->clearAt($openingParenthesisIndex);
                $tokens->removeTrailingWhitespace($openingParenthesisIndex);
            }

            $tokens->clearAt($index);
            $tokens->removeTrailingWhitespace($index);
            $tokens->insertAt($index, $replacement);

            if ($toWrapWithParentheses) {
                $tokens->insertAt($index, new Token('('));
            }
        }
    }

    private function isNegated(Tokens $tokens, int $index): bool
    {
        $negationIndex = $tokens->getPrevMeaningfulToken($index);
        $isNegated = $tokens[$negationIndex]->equals('!');
        if ($isNegated) {
            $tokens->removeTrailingWhitespace($negationIndex);
            $tokens->clearAt($negationIndex);
        }

        return $isNegated;
    }

    private function toKeepParentheses(Tokens $tokens, int $startIndex, int $endIndex): bool
    {
        for ($index = $startIndex; $index < $endIndex; $index++) {
            if (\in_array($tokens[$index]->getContent(), ['?', '?:', '='], true)) {
                return true;
            }
        }

        return false;
    }

    private function toWrapWithParentheses(Tokens $tokens, int $startIndex, int $endIndex): bool
    {
        $parentOperations = [T_IS_EQUAL, T_IS_NOT_EQUAL, T_IS_IDENTICAL, T_IS_NOT_IDENTICAL];

        $prevIndex = $tokens->getPrevMeaningfulToken($startIndex);
        if ($tokens[$prevIndex]->isGivenKind($parentOperations)) {
            return true;
        }

        $nextIndex = $tokens->getNextMeaningfulToken($endIndex);
        if ($tokens[$nextIndex]->isGivenKind($parentOperations)) {
            return true;
        }

        return false;
    }
}
