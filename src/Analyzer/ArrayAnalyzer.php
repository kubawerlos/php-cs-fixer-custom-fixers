<?php

declare(strict_types = 1);

namespace PhpCsFixerCustomFixers\Analyzer;

use PhpCsFixer\Tokenizer\CT;
use PhpCsFixer\Tokenizer\Tokens;
use PhpCsFixerCustomFixers\Analyzer\Analysis\ArrayArgumentAnalysis;

/**
 * @internal
 */
final class ArrayAnalyzer
{
    /**
     * @return ArrayArgumentAnalysis[]
     */
    public function getArguments(Tokens $tokens, int $index): array
    {
        if ($tokens[$index]->isGivenKind(CT::T_ARRAY_SQUARE_BRACE_OPEN)) {
            /** @var int $arrayContentStartIndex */
            $arrayContentStartIndex = $tokens->getNextMeaningfulToken($index);

            /** @var int $arrayContentEndIndex */
            $arrayContentEndIndex = $tokens->getPrevMeaningfulToken($tokens->findBlockEnd(Tokens::BLOCK_TYPE_ARRAY_SQUARE_BRACE, $index));

            return $this->getArgumentsForArrayContent($tokens, $arrayContentStartIndex, $arrayContentEndIndex);
        }

        if ($tokens[$index]->isGivenKind(T_ARRAY)) {
            /** @var int $arrayOpenBraceIndex */
            $arrayOpenBraceIndex = $tokens->getNextTokenOfKind($index, ['(']);

            /** @var int $arrayContentStartIndex */
            $arrayContentStartIndex = $tokens->getNextMeaningfulToken($arrayOpenBraceIndex);

            /** @var int $arrayContentEndIndex */
            $arrayContentEndIndex = $tokens->getPrevMeaningfulToken($tokens->findBlockEnd(Tokens::BLOCK_TYPE_PARENTHESIS_BRACE, $arrayOpenBraceIndex));

            return $this->getArgumentsForArrayContent($tokens, $arrayContentStartIndex, $arrayContentEndIndex);
        }

        throw new \InvalidArgumentException(\sprintf('Index %d is not an array.', $index));
    }

    /**
     * @return ArrayArgumentAnalysis[]
     */
    private function getArgumentsForArrayContent(Tokens $tokens, int $startIndex, int $endIndex): array
    {
        if ($tokens[$tokens->getNextMeaningfulToken($startIndex - 1)]->isGivenKind(CT::T_ARRAY_SQUARE_BRACE_CLOSE)) {
            return [];
        }
        $arguments = [];
        $argumentStart = $startIndex;
        $index = $startIndex - 1;
        while ($index < $endIndex) {
            $index++;
            $skippedIndex = $this->skipBlock($tokens, $index);
            if ($skippedIndex !== $index) {
                $index = $skippedIndex;
                continue;
            }

            if (!$tokens[$index]->equals(',')) {
                continue;
            }

            $arguments[] = $this->createArrayArgumentAnalysis($tokens, $argumentStart, $index - 1);
            $argumentStart = $index + 1;
        }

        if ($argumentStart <= $endIndex) {
            $arguments[] = $this->createArrayArgumentAnalysis($tokens, $argumentStart, $endIndex);
        }

        return $arguments;
    }

    private function createArrayArgumentAnalysis(Tokens $tokens, int $startIndex, int $endIndex): ArrayArgumentAnalysis
    {
        $keyStartIndex = null;
        $keyEndIndex = null;

        /** @var int $argumentStartIndex */
        $argumentStartIndex = $tokens->getNextNonWhitespace($startIndex - 1);

        /** @var int $argumentEndIndex */
        $argumentEndIndex = $tokens->getPrevNonWhitespace($endIndex + 1);

        $index = $startIndex - 1;
        while ($index < $endIndex) {
            $index++;
            $skippedIndex = $this->skipBlock($tokens, $index);
            if ($skippedIndex !== $index) {
                $index = $skippedIndex;
                continue;
            }

            if ($tokens[$index]->isGivenKind(T_DOUBLE_ARROW)) {
                $keyStartIndex = $argumentStartIndex;

                /** @var int $keyEndIndex */
                $keyEndIndex = $tokens->getPrevMeaningfulToken($index);

                /** @var int $argumentStartIndex */
                $argumentStartIndex = $tokens->getNextMeaningfulToken($index);
            }
        }

        return new ArrayArgumentAnalysis($keyStartIndex, $keyEndIndex, $argumentStartIndex, $argumentEndIndex);
    }

    private function skipBlock(Tokens $tokens, int $index): int
    {
        if ($tokens[$index]->equals('{')) {
            return $tokens->findBlockEnd(Tokens::BLOCK_TYPE_CURLY_BRACE, $index);
        }
        if ($tokens[$index]->equals('(')) {
            return $tokens->findBlockEnd(Tokens::BLOCK_TYPE_PARENTHESIS_BRACE, $index);
        }
        if ($tokens[$index]->isGivenKind(CT::T_ARRAY_SQUARE_BRACE_OPEN)) {
            return $tokens->findBlockEnd(Tokens::BLOCK_TYPE_ARRAY_SQUARE_BRACE, $index);
        }
        if ($tokens[$index]->isGivenKind(T_ARRAY)) {
            /** @var int $arrayOpenBraceIndex */
            $arrayOpenBraceIndex = $tokens->getNextTokenOfKind($index, ['(']);

            return $tokens->findBlockEnd(Tokens::BLOCK_TYPE_PARENTHESIS_BRACE, $arrayOpenBraceIndex);
        }

        return $index;
    }
}
