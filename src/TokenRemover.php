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

            $wasNewlineRemoved = self::handleWhitespaceBefore($tokens, $prevIndex);

            $nextIndex = $tokens->getNonEmptySibling($index, 1);
            if ($nextIndex !== null) {
                self::handleWhitespaceAfter($tokens, $nextIndex, $wasNewlineRemoved);
            }
        }

        $tokens->clearTokenAndMergeSurroundingWhitespace($index);
    }

    private static function isTokenOnlyMeaningfulInLine(Tokens $tokens, int $index): bool
    {
        return !self::hasMeaningTokenInLineBefore($tokens, $index) && !self::hasMeaningTokenInLineAfter($tokens, $index);
    }

    private static function hasMeaningTokenInLineBefore(Tokens $tokens, int $index): bool
    {
        /** @var int $prevIndex */
        $prevIndex = $tokens->getNonEmptySibling($index, -1);
        if (!$tokens[$prevIndex]->isGivenKind([T_OPEN_TAG, T_WHITESPACE])) {
            return true;
        }

        if ($tokens[$prevIndex]->isGivenKind(T_OPEN_TAG) && Preg::match('/\R$/', $tokens[$prevIndex]->getContent()) !== 1) {
            return true;
        }

        if (Preg::match('/\R/', $tokens[$prevIndex]->getContent()) !== 1) {
            $prevPrevIndex = $tokens->getNonEmptySibling($prevIndex, -1);
            if (!$tokens[$prevPrevIndex]->isGivenKind(T_OPEN_TAG) || Preg::match('/\R$/', $tokens[$prevPrevIndex]->getContent()) !== 1) {
                return true;
            }
        }

        return false;
    }

    private static function hasMeaningTokenInLineAfter(Tokens $tokens, int $index): bool
    {
        $nextIndex = $tokens->getNonEmptySibling($index, 1);
        if ($nextIndex === null) {
            return false;
        }
        if (!$tokens[$nextIndex]->isGivenKind(T_WHITESPACE)) {
            return true;
        }

        return Preg::match('/\R/', $tokens[$nextIndex]->getContent()) !== 1;
    }

    private static function handleWhitespaceBefore(Tokens $tokens, int $index): bool
    {
        if (!$tokens[$index]->isGivenKind(T_WHITESPACE)) {
            return false;
        }
        $contentWithoutTrailingSpaces = Preg::replace('/\h+$/', '', $tokens[$index]->getContent());

        $contentWithoutTrailingSpacesAndNewline = Preg::replace('/\R$/', '', $contentWithoutTrailingSpaces, 1);

        if ($contentWithoutTrailingSpacesAndNewline === '') {
            $tokens->clearAt($index);
        } else {
            $tokens[$index] = new Token([T_WHITESPACE, $contentWithoutTrailingSpacesAndNewline]);
        }

        return $contentWithoutTrailingSpaces !== $contentWithoutTrailingSpacesAndNewline;
    }

    private static function handleWhitespaceAfter(Tokens $tokens, int $index, bool $wasNewlineRemoved): void
    {
        $pattern = $wasNewlineRemoved ? '/^\h+/' : '/^\h*\R/';

        $newContent = Preg::replace($pattern, '', $tokens[$index]->getContent());

        if ($newContent === '') {
            $tokens->clearAt($index);

            return;
        }

        $tokens[$index] = new Token([T_WHITESPACE, $newContent]);
    }
}
