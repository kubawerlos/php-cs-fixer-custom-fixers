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

namespace PhpCsFixerCustomFixers\Fixer;

use PhpCsFixer\Fixer\ConfigurationDefinitionFixerInterface;
use PhpCsFixer\FixerConfiguration\FixerConfigurationResolver;
use PhpCsFixer\FixerConfiguration\FixerOptionBuilder;
use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\FixerDefinition\FixerDefinitionInterface;
use PhpCsFixer\Preg;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;
use PhpCsFixerCustomFixers\Analyzer\Analysis\CaseAnalysis;
use PhpCsFixerCustomFixers\Analyzer\ReferenceAnalyzer;
use PhpCsFixerCustomFixers\Analyzer\SwitchAnalyzer;

final class OperatorLinebreakFixer extends AbstractFixer implements ConfigurationDefinitionFixerInterface, DeprecatingFixerInterface
{
    private const BOOLEAN_OPERATORS = [[T_BOOLEAN_AND], [T_BOOLEAN_OR], [T_LOGICAL_AND], [T_LOGICAL_OR], [T_LOGICAL_XOR]];
    private const NON_BOOLEAN_OPERATORS = ['%', '&', '*', '+', '-', '.', '/', ':', '<', '=', '>', '?', '^', '|', [T_AND_EQUAL], [T_COALESCE], [T_CONCAT_EQUAL], [T_DIV_EQUAL], [T_DOUBLE_ARROW], [T_IS_EQUAL], [T_IS_GREATER_OR_EQUAL], [T_IS_IDENTICAL], [T_IS_NOT_EQUAL], [T_IS_NOT_IDENTICAL], [T_IS_SMALLER_OR_EQUAL], [T_MINUS_EQUAL], [T_MOD_EQUAL], [T_MUL_EQUAL], [T_OBJECT_OPERATOR], [T_OR_EQUAL], [T_PAAMAYIM_NEKUDOTAYIM], [T_PLUS_EQUAL], [T_POW], [T_POW_EQUAL], [T_SL], [T_SL_EQUAL], [T_SPACESHIP], [T_SR], [T_SR_EQUAL], [T_XOR_EQUAL]];

    /** @var string */
    private $position = 'beginning';

    /** @var array<array<int|string>|string> */
    private $operators = [];

    public function __construct()
    {
        $this->operators = \array_merge(self::BOOLEAN_OPERATORS, self::NON_BOOLEAN_OPERATORS);
    }

    public function getDefinition(): FixerDefinitionInterface
    {
        return new FixerDefinition(
            'Operators must always be at the beginning or at the end of the line.',
            [new CodeSample('<?php
function foo() {
    return $bar ||
        $baz;
}
')]
        );
    }

    public function getConfigurationDefinition(): FixerConfigurationResolver
    {
        return new FixerConfigurationResolver([
            (new FixerOptionBuilder('only_booleans', 'whether to limit operators to only boolean ones'))
                ->setAllowedTypes(['bool'])
                ->setDefault(false)
                ->getOption(),
            (new FixerOptionBuilder('position', 'whether to place operators at the beginning or at the end of the line'))
                ->setAllowedValues(['beginning', 'end'])
                ->setDefault($this->position)
                ->getOption(),
        ]);
    }

    public function configure(?array $configuration = null): void
    {
        if (isset($configuration['only_booleans']) && $configuration['only_booleans'] === true) {
            $this->operators = self::BOOLEAN_OPERATORS;
        }

        if (isset($configuration['position'])) {
            /** @var string $position */
            $position = $configuration['position'];
            $this->position = $position;
        }
    }

    public function getPriority(): int
    {
        return 0;
    }

    public function getPullRequestId(): int
    {
        return 4021;
    }

    public function isCandidate(Tokens $tokens): bool
    {
        return true;
    }

    public function isRisky(): bool
    {
        return false;
    }

    public function fix(\SplFileInfo $file, Tokens $tokens): void
    {
        $referenceAnalyzer = new ReferenceAnalyzer();

        $excludedIndices = $this->getExcludedIndices($tokens);

        $index = $tokens->count();
        while ($index > 1) {
            $index--;

            /** @var Token $token */
            $token = $tokens[$index];

            if (!$token->equalsAny($this->operators, false)) {
                continue;
            }

            if ($referenceAnalyzer->isReference($tokens, $index)) {
                continue;
            }

            if (\in_array($index, $excludedIndices, true)) {
                continue;
            }

            $operatorIndices = [$index];
            if ($token->equals(':')) {
                /** @var int $prevIndex */
                $prevIndex = $tokens->getPrevMeaningfulToken($index);

                /** @var Token $prevToken */
                $prevToken = $tokens[$prevIndex];

                if ($prevToken->equals('?')) {
                    $operatorIndices = [$prevIndex, $index];
                    $index = $prevIndex;
                }
            }

            $this->fixOperatorLinebreak($tokens, $operatorIndices);
        }
    }

    /**
     * Currently only colons from "switch".
     *
     * @return int[]
     */
    private function getExcludedIndices(Tokens $tokens): array
    {
        $indices = [];
        for ($index = $tokens->count() - 1; $index > 0; $index--) {
            /** @var Token $token */
            $token = $tokens[$index];

            if ($token->isGivenKind(T_SWITCH)) {
                $indices = \array_merge($indices, $this->getCasesColonsForSwitch($tokens, $index));
            }
        }

        return $indices;
    }

    /**
     * @return int[]
     */
    private function getCasesColonsForSwitch(Tokens $tokens, int $switchIndex): array
    {
        return \array_map(
            static function (CaseAnalysis $caseAnalysis): int {
                return $caseAnalysis->getColonIndex();
            },
            (new SwitchAnalyzer())->getSwitchAnalysis($tokens, $switchIndex)->getCases()
        );
    }

    /**
     * @param int[] $operatorIndices
     */
    private function fixOperatorLinebreak(Tokens $tokens, array $operatorIndices): void
    {
        /** @var int $prevIndex */
        $prevIndex = $tokens->getPrevMeaningfulToken(\min($operatorIndices));
        $indexStart = $prevIndex + 1;

        /** @var int $nextIndex */
        $nextIndex = $tokens->getNextMeaningfulToken(\max($operatorIndices));
        $indexEnd = $nextIndex - 1;

        if (!$this->isMultiline($tokens, $indexStart, $indexEnd)) {
            return; // operator is not surrounded by multiline whitespaces, do not touch it
        }

        if ($this->position === 'beginning') {
            if (!$this->isMultiline($tokens, \max($operatorIndices), $indexEnd)) {
                return; // operator already is placed correctly
            }
            $this->fixMoveToTheBeginning($tokens, $operatorIndices);

            return;
        }

        if (!$this->isMultiline($tokens, $indexStart, \min($operatorIndices))) {
            return; // operator already is placed correctly
        }
        $this->fixMoveToTheEnd($tokens, $operatorIndices);
    }

    /**
     * @param int[] $operatorIndices
     */
    private function fixMoveToTheBeginning(Tokens $tokens, array $operatorIndices): void
    {
        /** @var int $prevIndex */
        $prevIndex = $tokens->getNonEmptySibling(\min($operatorIndices), -1);

        /** @var Token $prevToken */
        $prevToken = $tokens[$prevIndex];

        /** @var int $nextIndex */
        $nextIndex = $tokens->getNextMeaningfulToken(\max($operatorIndices));

        for ($i = $nextIndex - 1; $i > \max($operatorIndices); $i--) {
            /** @var Token $token */
            $token = $tokens[$i];

            if ($token->isWhitespace() && Preg::match('/\R/u', $token->getContent()) === 1) {
                $isWhitespaceBefore = $prevToken->isWhitespace();
                $inserts = $this->getReplacementsAndClear($tokens, $operatorIndices, -1);
                if ($isWhitespaceBefore) {
                    $inserts[] = new Token([T_WHITESPACE, ' ']);
                }
                $tokens->insertAt($nextIndex, $inserts);

                break;
            }
        }
    }

    /**
     * @param int[] $operatorIndices
     */
    private function fixMoveToTheEnd(Tokens $tokens, array $operatorIndices): void
    {
        /** @var int $prevIndex */
        $prevIndex = $tokens->getPrevMeaningfulToken(\min($operatorIndices));

        /** @var int $nextIndex */
        $nextIndex = $tokens->getNonEmptySibling(\max($operatorIndices), 1);

        /** @var Token $nextToken */
        $nextToken = $tokens[$nextIndex];

        for ($i = $prevIndex + 1; $i < \max($operatorIndices); $i++) {
            /** @var Token $token */
            $token = $tokens[$i];

            if ($token->isWhitespace() && Preg::match('/\R/u', $token->getContent()) === 1) {
                $isWhitespaceAfter = $nextToken->isWhitespace();
                $inserts = $this->getReplacementsAndClear($tokens, $operatorIndices, 1);
                if ($isWhitespaceAfter) {
                    \array_unshift($inserts, new Token([T_WHITESPACE, ' ']));
                }
                $tokens->insertAt($prevIndex + 1, $inserts);

                break;
            }
        }
    }

    /**
     * @param int[] $indices
     *
     * @return Token[]
     */
    private function getReplacementsAndClear(Tokens $tokens, array $indices, int $direction): array
    {
        return \array_map(
            static function (int $index) use ($tokens, $direction): Token {
                /** @var Token $token */
                $token = $tokens[$index];

                /** @var Token $siblingToken */
                $siblingToken = $tokens[$index + $direction];

                if ($siblingToken->isWhitespace()) {
                    $tokens->clearAt($index + $direction);
                }
                $tokens->clearAt($index);

                return $token;
            },
            $indices
        );
    }

    private function isMultiline(Tokens $tokens, int $indexStart, int $indexEnd): bool
    {
        for ($index = $indexStart; $index <= $indexEnd; $index++) {
            /** @var Token $token */
            $token = $tokens[$index];

            if (\strpos($token->getContent(), "\n") !== false) {
                return true;
            }
        }

        return false;
    }
}
