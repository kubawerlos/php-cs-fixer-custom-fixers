<?php

declare(strict_types = 1);

namespace PhpCsFixerCustomFixers;

use PhpCsFixer\Preg;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;

/**
 * @internal
 */
final class TokenRemover
{
    public static function removeWithLinesIfPossible(Tokens $tokens, int $index): void
    {
        self::removeTrailingHorizontalWhitespaces($tokens, $tokens->getNonEmptySibling($index, -1));

        self::removeLeadingNewline($tokens, $tokens->getNonEmptySibling($index, 1));

        $tokens->clearTokenAndMergeSurroundingWhitespace($index);
    }

    private static function removeTrailingHorizontalWhitespaces(Tokens $tokens, int $index): void
    {
        if (!$tokens[$index]->isGivenKind(T_WHITESPACE)) {
            return;
        }

        $newContent = Preg::replace('/\h+$/', '', $tokens[$index]->getContent());

        if ($newContent === '') {
            $tokens->clearAt($index);

            return;
        }

        if ($newContent === $tokens[$index]->getContent()) {
            return;
        }

        $tokens[$index] = new Token([T_WHITESPACE, $newContent]);
    }

    private static function removeLeadingNewline(Tokens $tokens, int $index): void
    {
        if (!$tokens[$index]->isGivenKind(T_WHITESPACE)) {
            return;
        }

        $newContent = Preg::replace('/^\h*\R/', '', $tokens[$index]->getContent());

        if ($newContent === $tokens[$index]->getContent()) {
            return;
        }

        if ($newContent === '') {
            $tokens->clearAt($index);

            return;
        }

        $tokens[$index] = new Token([T_WHITESPACE, $newContent]);
    }
}
