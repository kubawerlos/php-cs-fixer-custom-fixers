<?php

declare(strict_types = 1);

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
    /** @var string */
    private $position = 'beginning';

    /** @var Token[] */
    private $operators = [];

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
        $this->operators = [
            new Token([T_LOGICAL_AND, 'and']),
            new Token([T_LOGICAL_OR, 'or']),
            new Token([T_LOGICAL_XOR, 'xor']),
            new Token('&&'),
            new Token('||'),
        ];
        if (!isset($configuration['only_booleans']) || $configuration['only_booleans'] === false) {
            $this->operators = \array_merge(
                $this->operators,
                [
                    new Token('+'),
                    new Token('-'),
                    new Token('*'),
                    new Token('/'),
                    new Token('%'),
                    new Token([T_POW, '**']),
                    new Token([T_PLUS_EQUAL, '+=']),
                    new Token([T_MINUS_EQUAL, '-=']),
                    new Token([T_MUL_EQUAL, '*=']),
                    new Token([T_DIV_EQUAL, '/=']),
                    new Token([T_MOD_EQUAL, '%=']),
                    new Token([T_POW_EQUAL, '**=']),
                    new Token('='),
                    new Token('&'),
                    new Token('|'),
                    new Token('^'),
                    new Token([T_SL, '<<']),
                    new Token([T_SR, '>>']),
                    new Token([T_AND_EQUAL, '&=']),
                    new Token([T_OR_EQUAL, '|=']),
                    new Token([T_XOR_EQUAL, '^=']),
                    new Token([T_SL_EQUAL, '<<=']),
                    new Token([T_SR_EQUAL, '>>=']),
                    new Token([T_IS_EQUAL, '==']),
                    new Token([T_IS_IDENTICAL, '===']),
                    new Token([T_IS_NOT_EQUAL, '!=']),
                    new Token([T_IS_NOT_EQUAL, '<>']),
                    new Token([T_IS_NOT_IDENTICAL, '!==']),
                    new Token('<'),
                    new Token('>'),
                    new Token([T_IS_SMALLER_OR_EQUAL, '<=']),
                    new Token([T_IS_GREATER_OR_EQUAL, '>=']),
                    new Token([T_SPACESHIP, '<=>']),
                    new Token('and'),
                    new Token('or'),
                    new Token('xor'),
                    new Token([T_BOOLEAN_AND, '&&']),
                    new Token([T_BOOLEAN_OR, '||']),
                    new Token('.'),
                    new Token([T_CONCAT_EQUAL, '.=']),
                    new Token([T_COALESCE, '??']),
                    new Token([T_DOUBLE_ARROW, '=>']),
                    new Token([T_OBJECT_OPERATOR, '->']),
                    new Token([T_PAAMAYIM_NEKUDOTAYIM, '::']),
                    new Token('?'),
                    new Token(':'),
                ]
            );
        }

        $this->position = $configuration['position'] ?? $this->position;
    }

    public function isCandidate(Tokens $tokens): bool
    {
        return true;
    }

    public function isRisky(): bool
    {
        return false;
    }

    public function getPriority(): int
    {
        return 0;
    }

    public function getPullRequestId(): int
    {
        return 4021;
    }

    public function fix(\SplFileInfo $file, Tokens $tokens): void
    {
        $referenceAnalyzer = new ReferenceAnalyzer();

        $excludedIndices = $this->getExcludedIndices($tokens);

        $index = $tokens->count();
        while ($index > 1) {
            $index--;

            if (!$tokens[$index]->equalsAny($this->operators, false)) {
                continue;
            }

            if ($referenceAnalyzer->isReference($tokens, $index)) {
                continue;
            }

            if (\in_array($index, $excludedIndices, true)) {
                continue;
            }

            $operatorIndices = [$index];
            if ($tokens[$index]->equals(':')) {
                /** @var int $prevIndex */
                $prevIndex = $tokens->getPrevMeaningfulToken($index);
                if ($tokens[$prevIndex]->equals('?')) {
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
            if ($tokens[$index]->isGivenKind(T_SWITCH)) {
                $indices += $this->getCasesColonsForSwitch($tokens, $index);
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

        /** @var int $nextIndex */
        $nextIndex = $tokens->getNextMeaningfulToken(\max($operatorIndices));

        for ($i = $nextIndex - 1; $i > \max($operatorIndices); $i--) {
            if ($tokens[$i]->isWhitespace() && Preg::match('/\R/u', $tokens[$i]->getContent()) === 1) {
                $isWhitespaceBefore = $tokens[$prevIndex]->isWhitespace();
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

        for ($i = $prevIndex + 1; $i < \max($operatorIndices); $i++) {
            if ($tokens[$i]->isWhitespace() && Preg::match('/\R/u', $tokens[$i]->getContent()) === 1) {
                $isWhitespaceAfter = $tokens[$nextIndex]->isWhitespace();
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
                $clone = $tokens[$index];
                if ($tokens[$index + $direction]->isWhitespace()) {
                    $tokens->clearAt($index + $direction);
                }
                $tokens->clearAt($index);

                return $clone;
            },
            $indices
        );
    }

    private function isMultiline(Tokens $tokens, int $indexStart, int $indexEnd): bool
    {
        for ($index = $indexStart; $index <= $indexEnd; $index++) {
            if (\strpos($tokens[$index]->getContent(), "\n") !== false) {
                return true;
            }
        }

        return false;
    }
}
