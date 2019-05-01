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
        if (self::isTokenOnlyMeaningfulInLine($tokens, $index)) {
            /** @var int $prevIndex */
            $prevIndex = $tokens->getNonEmptySibling($index, -1);

            self::handleWhitespaceBefore($tokens, $prevIndex);

            $nextIndex = $tokens->getNonEmptySibling($index, 1);
            if ($nextIndex !== null) {
                self::handleWhitespaceAfter($tokens, $nextIndex);
            }
        }

        $tokens->clearTokenAndMergeSurroundingWhitespace($index);
    }

    private static function isTokenOnlyMeaningfulInLine(Tokens $tokens, int $index): bool
    {
        /** @var int $prevIndex */
        $prevIndex = $tokens->getNonEmptySibling($index, -1);
        if (!$tokens[$prevIndex]->isGivenKind([T_OPEN_TAG, T_WHITESPACE])) {
            return false;
        }

        if ($tokens[$prevIndex]->isGivenKind(T_OPEN_TAG) && Preg::match('/\R$/', $tokens[$prevIndex]->getContent()) !== 1) {
            return false;
        }

        if (Preg::match('/\R/', $tokens[$prevIndex]->getContent()) !== 1) {
            $prevPrevIndex = $tokens->getNonEmptySibling($prevIndex, -1);
            if (!$tokens[$prevPrevIndex]->isGivenKind(T_OPEN_TAG) || Preg::match('/\R$/', $tokens[$prevPrevIndex]->getContent()) !== 1) {
                return false;
            }
        }

        $nextIndex = $tokens->getNonEmptySibling($index, 1);
        if ($nextIndex === null) {
            return true;
        }
        if (!$tokens[$nextIndex]->isGivenKind(T_WHITESPACE)) {
            return false;
        }

        return Preg::match('/\R/', $tokens[$nextIndex]->getContent()) === 1;
    }

    private static function handleWhitespaceBefore(Tokens $tokens, int $index): void
    {
        if (!$tokens[$index]->isGivenKind(T_WHITESPACE)) {
            return;
        }

        $content = $tokens[$index]->getContent();

        $prevIndex = $tokens->getNonEmptySibling($index, -1);
        if ($tokens[$prevIndex]->isGivenKind(T_OPEN_TAG)) {
            $content = \substr($tokens[$prevIndex]->getContent(), 5) . $content;
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

    private static function handleWhitespaceAfter(Tokens $tokens, int $index): void
    {
        $newContent = Preg::replace('/^\h*\R/', '', $tokens[$index]->getContent());

        if ($newContent === '') {
            $tokens->clearAt($index);

            return;
        }

        $tokens[$index] = new Token([T_WHITESPACE, $newContent]);
    }
}
