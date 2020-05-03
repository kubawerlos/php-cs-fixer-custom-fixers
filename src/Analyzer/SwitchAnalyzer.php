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

use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;
use PhpCsFixerCustomFixers\Analyzer\Analysis\CaseAnalysis;
use PhpCsFixerCustomFixers\Analyzer\Analysis\SwitchAnalysis;

/**
 * @internal
 */
final class SwitchAnalyzer
{
    public function getSwitchAnalysis(Tokens $tokens, int $switchIndex): SwitchAnalysis
    {
        /** @var Token $switchToken */
        $switchToken = $tokens[$switchIndex];

        if (!$switchToken->isGivenKind(T_SWITCH)) {
            throw new \InvalidArgumentException(\sprintf('Index %d is not "switch".', $switchIndex));
        }

        $casesStartIndex = $this->getCasesStart($tokens, $switchIndex);
        $casesEndIndex = $this->getCasesEnd($tokens, $casesStartIndex);

        $cases = [];
        $ternaryOperatorDepth = 0;
        $index = $casesStartIndex;
        while ($index < $casesEndIndex) {
            $index++;

            /** @var Token $token */
            $token = $tokens[$index];

            if ($token->isGivenKind(T_SWITCH)) {
                $index = (new self())->getSwitchAnalysis($tokens, $index)->getCasesEnd();
                continue;
            }
            if ($token->equals('?')) {
                $ternaryOperatorDepth++;
                continue;
            }
            if (!$token->equals(':')) {
                continue;
            }
            if ($ternaryOperatorDepth > 0) {
                $ternaryOperatorDepth--;
                continue;
            }
            $cases[] = new CaseAnalysis($index);
        }

        return new SwitchAnalysis($casesStartIndex, $casesEndIndex, $cases);
    }

    private function getCasesStart(Tokens $tokens, int $switchIndex): int
    {
        /** @var int $parenthesisStartIndex */
        $parenthesisStartIndex = $tokens->getNextMeaningfulToken($switchIndex);
        $parenthesisEndIndex = $tokens->findBlockEnd(Tokens::BLOCK_TYPE_PARENTHESIS_BRACE, $parenthesisStartIndex);

        $casesStartIndex = $tokens->getNextMeaningfulToken($parenthesisEndIndex);
        \assert(\is_int($casesStartIndex));

        return $casesStartIndex;
    }

    private function getCasesEnd(Tokens $tokens, int $casesStartIndex): int
    {
        /** @var Token $casesStartToken */
        $casesStartToken = $tokens[$casesStartIndex];

        if ($casesStartToken->equals('{')) {
            return $tokens->findBlockEnd(Tokens::BLOCK_TYPE_CURLY_BRACE, $casesStartIndex);
        }

        $depth = 1;
        $index = $casesStartIndex;
        while ($depth > 0) {
            /** @var int $index */
            $index = $tokens->getNextMeaningfulToken($index);

            /** @var Token $token */
            $token = $tokens[$index];

            if ($token->isGivenKind(T_ENDSWITCH)) {
                $depth--;
                continue;
            }

            if (!$token->isGivenKind(T_SWITCH)) {
                continue;
            }

            $index = $this->getCasesStart($tokens, $index);

            /** @var Token $token */
            $token = $tokens[$index];

            if ($token->equals(':')) {
                $depth++;
            }
        }

        /** @var int $afterEndswitchIndex */
        $afterEndswitchIndex = $tokens->getNextMeaningfulToken($index);

        /** @var Token $afterEndswitchToken */
        $afterEndswitchToken = $tokens[$afterEndswitchIndex];

        return $afterEndswitchToken->equals(';') ? $afterEndswitchIndex : $index;
    }
}
