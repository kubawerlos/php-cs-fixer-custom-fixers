<?php

/*
 * This file is part of PHP CS Fixer: custom fixers.
 *
 * (c) 2018 Kuba Werłos
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

namespace PhpCsFixerCustomFixers\Analyzer;

use PhpCsFixer\Tokenizer\Tokens;
use PhpCsFixerCustomFixers\Analyzer\Analysis\ArgumentAnalysis;

/**
 * @internal
 */
final class FunctionAnalyzer
{
    /**
     * @return array<ArgumentAnalysis>
     */
    public function getFunctionArguments(Tokens $tokens, int $index): array
    {
        $argumentsRange = $this->getArgumentsRange($tokens, $index);
        if ($argumentsRange === null) {
            return [];
        }

        [$argumentStartIndex, $argumentsEndIndex] = $argumentsRange;

        $arguments = [];
        $index = $currentArgumentStart = $argumentStartIndex;
        while ($index < $argumentsEndIndex) {
            /** @var null|array{isStart: bool, type: int} $blockType */
            $blockType = Tokens::detectBlockType($tokens[$index]);
            if ($blockType !== null && $blockType['isStart']) {
                $index = $tokens->findBlockEnd($blockType['type'], $index);
                continue;
            }

            /** @var int $index */
            $index = $tokens->getNextMeaningfulToken($index);

            if (!$tokens[$index]->equals(',')) {
                continue;
            }

            /** @var int $currentArgumentEnd */
            $currentArgumentEnd = $tokens->getPrevMeaningfulToken($index);

            $arguments[] = $this->createArgumentAnalysis($tokens, $currentArgumentStart, $currentArgumentEnd);

            /** @var int $currentArgumentStart */
            $currentArgumentStart = $tokens->getNextMeaningfulToken($index);
        }

        $arguments[] = $this->createArgumentAnalysis($tokens, $currentArgumentStart, $argumentsEndIndex);

        return $arguments;
    }

    /**
     * @return null|array{int, int}
     */
    private function getArgumentsRange(Tokens $tokens, int $index): ?array
    {
        if (!$tokens[$index]->isGivenKind(\T_STRING)) {
            throw new \InvalidArgumentException(\sprintf('Index %d is not "function".', $index));
        }

        /** @var int $openParenthesis */
        $openParenthesis = $tokens->getNextMeaningfulToken($index);
        if (!$tokens[$openParenthesis]->equals('(')) {
            throw new \InvalidArgumentException(\sprintf('Index %d is not "function".', $index));
        }

        $closeParenthesis = $tokens->findBlockEnd(Tokens::BLOCK_TYPE_PARENTHESIS_BRACE, $openParenthesis);

        /** @var int $argumentsEndIndex */
        $argumentsEndIndex = $tokens->getPrevMeaningfulToken($closeParenthesis);

        if ($openParenthesis === $argumentsEndIndex) {
            return null;
        }
        if ($tokens[$argumentsEndIndex]->equals(',')) {
            /** @var int $argumentsEndIndex */
            $argumentsEndIndex = $tokens->getPrevMeaningfulToken($argumentsEndIndex);
        }

        /** @var int $argumentStartIndex */
        $argumentStartIndex = $tokens->getNextMeaningfulToken($openParenthesis);

        return [$argumentStartIndex, $argumentsEndIndex];
    }

    private function createArgumentAnalysis(Tokens $tokens, int $startIndex, int $endIndex): ArgumentAnalysis
    {
        return new ArgumentAnalysis($startIndex, $endIndex, $this->isConstant($tokens, $startIndex, $endIndex));
    }

    private function isConstant(Tokens $tokens, int $startIndex, int $endIndex): bool
    {
        for ($index = $startIndex; $index <= $endIndex; $index++) {
            if ($tokens[$index]->isGivenKind(\T_VARIABLE)) {
                return false;
            }
            if ($tokens[$index]->equals('(')) {
                /** @var int $prevParenthesisIndex */
                $prevParenthesisIndex = $tokens->getPrevMeaningfulToken($index);

                if (!$tokens[$prevParenthesisIndex]->isGivenKind(\T_ARRAY)) {
                    return false;
                }
            }
        }

        return true;
    }
}
