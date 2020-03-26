<?php

declare(strict_types=1);

namespace PhpCsFixerCustomFixers\Analyzer;

use PhpCsFixer\Tokenizer\CT;
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
        if ($tokens[$index]->isGivenKind(CT::T_ARRAY_SQUARE_BRACE_OPEN)) {
            /** @var int $arrayContentStartIndex */
            $arrayContentStartIndex = $tokens->getNextMeaningfulToken($index);

            /** @var int $arrayContentEndIndex */
            $arrayContentEndIndex = $tokens->getPrevMeaningfulToken($tokens->findBlockEnd(Tokens::BLOCK_TYPE_ARRAY_SQUARE_BRACE, $index));

            return $this->getElementsForArrayContent($tokens, $arrayContentStartIndex, $arrayContentEndIndex);
        }

        if ($tokens[$index]->isGivenKind(T_ARRAY)) {
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
            if (!$tokens[$index]->equals(',')) {
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
            if (!$tokens[$index]->isGivenKind(T_DOUBLE_ARROW)) {
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
        if ($tokens[$index]->equals('{')) {
            $index = $tokens->findBlockEnd(Tokens::BLOCK_TYPE_CURLY_BRACE, $index);
        }

        if ($tokens[$index]->equals('(')) {
            $index = $tokens->findBlockEnd(Tokens::BLOCK_TYPE_PARENTHESIS_BRACE, $index);
        }

        if ($tokens[$index]->isGivenKind(CT::T_ARRAY_SQUARE_BRACE_OPEN)) {
            $index = $tokens->findBlockEnd(Tokens::BLOCK_TYPE_ARRAY_SQUARE_BRACE, $index);
        }

        if ($tokens[$index]->isGivenKind(T_ARRAY)) {
            /** @var int $arrayOpenBraceIndex */
            $arrayOpenBraceIndex = $tokens->getNextTokenOfKind($index, ['(']);

            $index = $tokens->findBlockEnd(Tokens::BLOCK_TYPE_PARENTHESIS_BRACE, $arrayOpenBraceIndex);
        }

        return $index + 1;
    }
}
