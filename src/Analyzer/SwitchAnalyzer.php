<?php

declare(strict_types = 1);

namespace PhpCsFixerCustomFixers\Analyzer;

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
        if (!$tokens[$switchIndex]->isGivenKind(T_SWITCH)) {
            throw new \InvalidArgumentException(\sprintf('Index %d is not "switch".', $switchIndex));
        }

        $casesStartIndex = $this->getCasesStart($tokens, $switchIndex);
        $casesEndIndex = $this->getCasesEnd($tokens, $casesStartIndex);

        $cases = [];
        $ternaryOperatorDepth = 0;
        $index = $casesStartIndex;
        while ($index < $casesEndIndex) {
            $index++;
            if ($tokens[$index]->isGivenKind(T_SWITCH)) {
                $index = (new self())->getSwitchAnalysis($tokens, $index)->getCasesEnd();
                continue;
            }
            if ($tokens[$index]->equals('?')) {
                $ternaryOperatorDepth++;
                continue;
            }
            if (!$tokens[$index]->equals(':')) {
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
        if ($tokens[$casesStartIndex]->equals('{')) {
            return $tokens->findBlockEnd(Tokens::BLOCK_TYPE_CURLY_BRACE, $casesStartIndex);
        }

        $depth = 1;
        $index = $casesStartIndex;
        while ($depth > 0) {
            /** @var int $index */
            $index = $tokens->getNextMeaningfulToken($index);

            if ($tokens[$index]->isGivenKind(T_ENDSWITCH)) {
                $depth--;
                continue;
            }

            if (!$tokens[$index]->isGivenKind(T_SWITCH)) {
                continue;
            }
            $index = $this->getCasesStart($tokens, $index);
            if ($tokens[$index]->equals(':')) {
                $depth++;
            }
        }

        /** @var int $afterEndswitchIndex */
        $afterEndswitchIndex = $tokens->getNextMeaningfulToken($index);

        return $tokens[$afterEndswitchIndex]->equals(';') ? $afterEndswitchIndex : $index;
    }
}
