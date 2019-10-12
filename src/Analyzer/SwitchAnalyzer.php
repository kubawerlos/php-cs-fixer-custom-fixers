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

        /** @var int $indexParenthesisStart */
        $indexParenthesisStart = $tokens->getNextMeaningfulToken($switchIndex);
        $indexParenthesisEnd = $tokens->findBlockEnd(Tokens::BLOCK_TYPE_PARENTHESIS_BRACE, $indexParenthesisStart);

        /** @var int $indexCurlyBracesStart */
        $indexCurlyBracesStart = $tokens->getNextMeaningfulToken($indexParenthesisEnd);
        $indexCurlyBracesEnd = $tokens->findBlockEnd(Tokens::BLOCK_TYPE_CURLY_BRACE, $indexCurlyBracesStart);

        $cases = [];
        $ternaryOperatorDepth = 0;
        $index = $indexCurlyBracesStart;
        while ($index < $indexCurlyBracesEnd) {
            $index++;
            if ($tokens[$index]->isGivenKind(T_SWITCH)) {
                $index = (new self())->getSwitchAnalysis($tokens, $index)->getCurlyBracesEnd();
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

        return new SwitchAnalysis($indexCurlyBracesStart, $indexCurlyBracesEnd, $cases);
    }
}
