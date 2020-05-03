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

namespace PhpCsFixerCustomFixers\Analyzer;

use PhpCsFixer\Tokenizer\CT;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;
use PhpCsFixerCustomFixers\Analyzer\Analysis\ArrayElementAnalysis;

/**
 * @internal
 */
final class ArrayAnalyzer
{
    /**
     * @return ArrayElementAnalysis[]
     */
    public function getElements(Tokens $tokens, int $index): array
    {
        /** @var Token $token */
        $token = $tokens[$index];

        if ($token->isGivenKind(CT::T_ARRAY_SQUARE_BRACE_OPEN)) {
            /** @var int $arrayContentStartIndex */
            $arrayContentStartIndex = $tokens->getNextMeaningfulToken($index);

            /** @var int $arrayContentEndIndex */
            $arrayContentEndIndex = $tokens->getPrevMeaningfulToken($tokens->findBlockEnd(Tokens::BLOCK_TYPE_ARRAY_SQUARE_BRACE, $index));

            return $this->getElementsForArrayContent($tokens, $arrayContentStartIndex, $arrayContentEndIndex);
        }

        if ($token->isGivenKind(T_ARRAY)) {
            /** @var int $arrayOpenBraceIndex */
            $arrayOpenBraceIndex = $tokens->getNextTokenOfKind($index, ['(']);

            /** @var int $arrayContentStartIndex */
            $arrayContentStartIndex = $tokens->getNextMeaningfulToken($arrayOpenBraceIndex);

            /** @var int $arrayContentEndIndex */
            $arrayContentEndIndex = $tokens->getPrevMeaningfulToken($tokens->findBlockEnd(Tokens::BLOCK_TYPE_PARENTHESIS_BRACE, $arrayOpenBraceIndex));

            return $this->getElementsForArrayContent($tokens, $arrayContentStartIndex, $arrayContentEndIndex);
        }

        throw new \InvalidArgumentException(\sprintf('Index %d is not an array.', $index));
    }

    /**
     * @return ArrayElementAnalysis[]
     */
    private function getElementsForArrayContent(Tokens $tokens, int $startIndex, int $endIndex): array
    {
        $elements = [];

        $index = $startIndex;
        while ($endIndex >= $index = $this->nextCandidateIndex($tokens, $index)) {
            /** @var Token $token */
            $token = $tokens[$index];

            if (!$token->equals(',')) {
                continue;
            }

            /** @var int $elementEndIndex */
            $elementEndIndex = $tokens->getPrevMeaningfulToken($index);

            $elements[] = $this->createArrayElementAnalysis($tokens, $startIndex, $elementEndIndex);

            /** @var int $startIndex */
            $startIndex = $tokens->getNextMeaningfulToken($index);
        }

        if ($startIndex <= $endIndex) {
            $elements[] = $this->createArrayElementAnalysis($tokens, $startIndex, $endIndex);
        }

        return $elements;
    }

    private function createArrayElementAnalysis(Tokens $tokens, int $startIndex, int $endIndex): ArrayElementAnalysis
    {
        $index = $startIndex;
        while ($endIndex > $index = $this->nextCandidateIndex($tokens, $index)) {
            /** @var Token $token */
            $token = $tokens[$index];

            if (!$token->isGivenKind(T_DOUBLE_ARROW)) {
                continue;
            }

            /** @var int $keyEndIndex */
            $keyEndIndex = $tokens->getPrevMeaningfulToken($index);

            /** @var int $valueStartIndex */
            $valueStartIndex = $tokens->getNextMeaningfulToken($index);

            return new ArrayElementAnalysis($startIndex, $keyEndIndex, $valueStartIndex, $endIndex);
        }

        return new ArrayElementAnalysis(null, null, $startIndex, $endIndex);
    }

    private function nextCandidateIndex(Tokens $tokens, int $index): int
    {
        /** @var Token $token */
        $token = $tokens[$index];

        if ($token->equals('{')) {
            return $tokens->findBlockEnd(Tokens::BLOCK_TYPE_CURLY_BRACE, $index) + 1;
        }

        if ($token->equals('(')) {
            return $tokens->findBlockEnd(Tokens::BLOCK_TYPE_PARENTHESIS_BRACE, $index) + 1;
        }

        if ($token->isGivenKind(CT::T_ARRAY_SQUARE_BRACE_OPEN)) {
            return $tokens->findBlockEnd(Tokens::BLOCK_TYPE_ARRAY_SQUARE_BRACE, $index) + 1;
        }

        if ($token->isGivenKind(T_ARRAY)) {
            /** @var int $arrayOpenBraceIndex */
            $arrayOpenBraceIndex = $tokens->getNextTokenOfKind($index, ['(']);

            return $tokens->findBlockEnd(Tokens::BLOCK_TYPE_PARENTHESIS_BRACE, $arrayOpenBraceIndex) + 1;
        }

        return $index + 1;
    }
}
