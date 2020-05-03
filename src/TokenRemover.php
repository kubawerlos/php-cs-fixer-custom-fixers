<?php

declare(strict_types=1);

/*
 * This file is part of PHP CS Fixer: custom fixers.
 *
 * (c) 2018-2020 Kuba Werłos
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

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

        /** @var Token $prevToken */
        $prevToken = $tokens[$prevIndex];

        if (!$prevToken->isGivenKind([T_OPEN_TAG, T_WHITESPACE])) {
            return true;
        }

        if ($prevToken->isGivenKind(T_OPEN_TAG) && Preg::match('/\R$/', $prevToken->getContent()) !== 1) {
            return true;
        }

        if (Preg::match('/\R/', $prevToken->getContent()) !== 1) {
            /** @var int $prevPrevIndex */
            $prevPrevIndex = $tokens->getNonEmptySibling($prevIndex, -1);

            /** @var Token $prevPrevToken */
            $prevPrevToken = $tokens[$prevPrevIndex];

            if (!$prevPrevToken->isGivenKind(T_OPEN_TAG) || Preg::match('/\R$/', $prevPrevToken->getContent()) !== 1) {
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

        /** @var Token $nextToken */
        $nextToken = $tokens[$nextIndex];

        if (!$nextToken->isGivenKind(T_WHITESPACE)) {
            return true;
        }

        return Preg::match('/\R/', $nextToken->getContent()) !== 1;
    }

    private static function handleWhitespaceBefore(Tokens $tokens, int $index): bool
    {
        /** @var Token $token */
        $token = $tokens[$index];

        if (!$token->isGivenKind(T_WHITESPACE)) {
            return false;
        }
        $contentWithoutTrailingSpaces = Preg::replace('/\h+$/', '', $token->getContent());

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
        /** @var Token $token */
        $token = $tokens[$index];

        $pattern = $wasNewlineRemoved ? '/^\h+/' : '/^\h*\R/';

        $newContent = Preg::replace($pattern, '', $token->getContent());

        if ($newContent === '') {
            $tokens->clearAt($index);

            return;
        }

        $tokens[$index] = new Token([T_WHITESPACE, $newContent]);
    }
}
