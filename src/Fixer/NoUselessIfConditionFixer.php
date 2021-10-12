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

namespace PhpCsFixerCustomFixers\Fixer;

use PhpCsFixer\Fixer\Basic\BracesFixer;
use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\FixerDefinition\FixerDefinitionInterface;
use PhpCsFixer\Tokenizer\Tokens;
use PhpCsFixerCustomFixers\TokenRemover;

final class NoUselessIfConditionFixer extends AbstractFixer
{
    public function getDefinition(): FixerDefinitionInterface
    {
        return new FixerDefinition(
            'There can be no useless if conditions.',
            [new CodeSample('<?php
if (true) {
    return 42;
}
')]
        );
    }

    /**
     * Must run before NoTrailingWhitespaceFixer.
     */
    public function getPriority(): int
    {
        return 1;
    }

    public function isCandidate(Tokens $tokens): bool
    {
        return $tokens->isTokenKindFound(\T_IF);
    }

    public function isRisky(): bool
    {
        return false;
    }

    public function fix(\SplFileInfo $file, Tokens $tokens): void
    {
        for ($index = 0; $index < $tokens->count(); $index++) {
            if (!$tokens[$index]->isGivenKind([\T_IF])) {
                continue;
            }

            /** @var int $openParenthesesIndex */
            $openParenthesesIndex = $tokens->getNextMeaningfulToken($index);

            /** @var int $booleanIndex */
            $booleanIndex = $tokens->getNextMeaningfulToken($openParenthesesIndex);
            if ($tokens[$booleanIndex]->isGivenKind(\T_NS_SEPARATOR)) {
                /** @var int $booleanIndex */
                $booleanIndex = $tokens->getNextMeaningfulToken($booleanIndex);
            }

            if ($tokens[$booleanIndex]->equals([\T_STRING, 'true'], false)) {
                $removeCodeInCondition = false;
            } elseif ($tokens[$booleanIndex]->equals([\T_STRING, 'false'], false)) {
                $removeCodeInCondition = true;
            } else {
                continue;
            }

            /** @var int $closeParenthesesIndex */
            $closeParenthesesIndex = $tokens->getNextMeaningfulToken($booleanIndex);
            if (!$tokens[$closeParenthesesIndex]->equals(')')) {
                continue;
            }

            $this->removeCondition($tokens, $index, $closeParenthesesIndex, $removeCodeInCondition);
        }
    }

    private function removeCondition(Tokens $tokens, int $index, int $closeParenthesesIndex, bool $removeCodeInCondition): void
    {
        /** @var int $openBraceIndex */
        $openBraceIndex = $tokens->getNextMeaningfulToken($closeParenthesesIndex);

        if ($tokens[$openBraceIndex]->equals('{')) {
            $conditionEndIndex = $openBraceIndex;
            $conditionCodeEndIndex = $tokens->findBlockEnd(Tokens::BLOCK_TYPE_CURLY_BRACE, $openBraceIndex);
            TokenRemover::removeWithLinesIfPossible($tokens, $conditionCodeEndIndex);
        } elseif ($tokens[$openBraceIndex]->equals(':')) {
            $conditionEndIndex = $openBraceIndex;
            $conditionCodeEndIndex = $this->findStatementEnd($tokens, $closeParenthesesIndex);

            /** @var int $endifIndex */
            $endifIndex = $tokens->getNextMeaningfulToken($conditionCodeEndIndex);

            /** @var int $afterEndifIndex */
            $afterEndifIndex = $tokens->getNextMeaningfulToken($endifIndex);

            $this->clearRange($tokens, $endifIndex, $afterEndifIndex);
        } else {
            $conditionEndIndex = $closeParenthesesIndex;
            $conditionCodeEndIndex = $this->findStatementEnd($tokens, $closeParenthesesIndex);
        }

        $this->clearRange($tokens, $index, $removeCodeInCondition ? $conditionCodeEndIndex : $conditionEndIndex);
    }

    private function findStatementEnd(Tokens $tokens, int $index): int
    {
        /** @var null|\Closure(Tokens, int): int $findStatementEnd */
        static $findStatementEnd = null;

        if ($findStatementEnd === null) {
            /** @var \Closure(Tokens, int): int $findStatementEnd */
            $findStatementEnd = \Closure::bind(function (Tokens $tokens, int $index): int {
                return $this->findStatementEnd($tokens, $index);
            }, new BracesFixer(), BracesFixer::class);
        }

        return $findStatementEnd($tokens, $index);
    }

    private function clearRange(Tokens $tokens, int $startIndex, int $endIndex): void
    {
        $tokens->clearRange(
            $startIndex + 1,
            $endIndex
        );
        TokenRemover::removeWithLinesIfPossible($tokens, $startIndex);
    }
}
