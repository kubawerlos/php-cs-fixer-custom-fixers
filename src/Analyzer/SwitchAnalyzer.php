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

        if (!$switchToken->isGivenKind(\T_SWITCH)) {
            throw new \InvalidArgumentException(\sprintf('Index %d is not "switch".', $switchIndex));
        }

        $casesStartIndex = $this->getCasesStart($tokens, $switchIndex);
        $casesEndIndex = $this->getCasesEnd($tokens, $casesStartIndex);

        $cases = [];
        $index = $casesStartIndex;
        while ($index < $casesEndIndex) {
            $index = $this->getNextSameLevelToken($tokens, $index);

            /** @var Token $token */
            $token = $tokens[$index];

            if (!$token->isGivenKind([\T_CASE, \T_DEFAULT])) {
                continue;
            }

            $caseAnalysis = $this->getCaseAnalysis($tokens, $index);

            $cases[] = $caseAnalysis;
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

        $index = $casesStartIndex;
        while ($index < $tokens->count()) {
            /** @var int $index */
            $index = $this->getNextSameLevelToken($tokens, $index);

            /** @var Token $token */
            $token = $tokens[$index];

            if ($token->isGivenKind(\T_ENDSWITCH)) {
                break;
            }
        }

        /** @var int $afterEndswitchIndex */
        $afterEndswitchIndex = $tokens->getNextMeaningfulToken($index);

        /** @var Token $afterEndswitchToken */
        $afterEndswitchToken = $tokens[$afterEndswitchIndex];

        return $afterEndswitchToken->equals(';') ? $afterEndswitchIndex : $index;
    }

    private function getCaseAnalysis(Tokens $tokens, int $index): CaseAnalysis
    {
        while ($index < $tokens->count()) {
            $index = $this->getNextSameLevelToken($tokens, $index);

            /** @var Token $token */
            $token = $tokens[$index];

            if ($token->equalsAny([':', ';'])) {
                break;
            }
        }

        return new CaseAnalysis($index);
    }

    private function getNextSameLevelToken(Tokens $tokens, int $index): int
    {
        /** @var int $index */
        $index = $tokens->getNextMeaningfulToken($index);

        /** @var Token $token */
        $token = $tokens[$index];

        if ($token->isGivenKind(\T_SWITCH)) {
            return (new self())->getSwitchAnalysis($tokens, $index)->getCasesEnd();
        }

        /** @var null|array{isStart: bool, type: int} $blockType */
        $blockType = Tokens::detectBlockType($token);
        if ($blockType !== null && $blockType['isStart']) {
            return $tokens->findBlockEnd($blockType['type'], $index) + 1;
        }

        return $index;
    }
}
