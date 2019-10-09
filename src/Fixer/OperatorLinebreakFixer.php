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

final class OperatorLinebreakFixer extends AbstractFixer implements ConfigurationDefinitionFixerInterface, DeprecatingFixerInterface
{
    private const CONFIG_ONLY_BOOLEANS = 'only_booleans';
    private const CONFIG_POSITION = 'position';

    private const CONFIG_POSITION_BEGINNING = 'beginning';
    private const CONFIG_POSITION_END = 'end';

    private const OPERATORS_BOOLEANS = [
        '&&' => true,
        '||' => true,
        'and' => true,
        'or' => true,
        'xor' => true,
    ];

    private const OPERATORS_NON_BOOLEANS = [
        '=' => true,
        '.' => true,
        '*' => true,
        '/' => true,
        '%' => true,
        '<' => true,
        '>' => true,
        '|' => true,
        '^' => true,
        '+' => true,
        '-' => true,
        '&' => true,
        '&=' => true,
        '.=' => true,
        '/=' => true,
        '=>' => true,
        '==' => true,
        '>=' => true,
        '===' => true,
        '!=' => true,
        '<>' => true,
        '!==' => true,
        '<=' => true,
        '-=' => true,
        '%=' => true,
        '*=' => true,
        '|=' => true,
        '+=' => true,
        '<<' => true,
        '<<=' => true,
        '>>' => true,
        '>>=' => true,
        '^=' => true,
        '**' => true,
        '**=' => true,
        '<=>' => true,
        '??' => true,
        '?' => true,
        ':' => true,
    ];

    /** @var string */
    private $position = self::CONFIG_POSITION_BEGINNING;

    /** @var array<string, true> */
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
            (new FixerOptionBuilder(self::CONFIG_ONLY_BOOLEANS, 'whether to limit operators to only boolean ones'))
                ->setDefault(false)
                ->setAllowedTypes(['bool'])
                ->getOption(),
            (new FixerOptionBuilder(self::CONFIG_POSITION, 'whether to place operators at the beginning or at the end of the line'))
                ->setDefault($this->position)
                ->setAllowedValues([self::CONFIG_POSITION_BEGINNING, self::CONFIG_POSITION_END])
                ->getOption(),
        ]);
    }

    public function configure(?array $configuration = null): void
    {
        $this->operators = self::OPERATORS_BOOLEANS;
        if (!isset($configuration[self::CONFIG_ONLY_BOOLEANS]) || $configuration[self::CONFIG_ONLY_BOOLEANS] !== true) {
            $this->operators = \array_merge($this->operators, self::OPERATORS_NON_BOOLEANS);
        }

        $this->position = $configuration[self::CONFIG_POSITION] ?? $this->position;
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
        if ($this->position === self::CONFIG_POSITION_BEGINNING) {
            $this->fixMoveToTheBeginning($tokens);
        } else {
            $this->fixMoveToTheEnd($tokens);
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

    private function fixMoveToTheBeginning(Tokens $tokens): void
    {
        for ($index = 0; $index < $tokens->count(); $index++) {
            $indices = $this->getOperatorIndices($tokens, $index);
            if ($indices === null) {
                continue;
            }

            /** @var int $prevIndex */
            $prevIndex = $tokens->getNonEmptySibling(\min($indices), -1);

            /** @var int $nextIndex */
            $nextIndex = $tokens->getNextMeaningfulToken(\max($indices));

            for ($i = $nextIndex - 1; $i > $index; $i--) {
                if ($tokens[$i]->isWhitespace() && Preg::match('/\R/u', $tokens[$i]->getContent()) === 1) {
                    $isWhitespaceBefore = $tokens[$prevIndex]->isWhitespace();
                    $inserts = $this->getReplacementsAndClear($tokens, $indices, -1);
                    if ($isWhitespaceBefore) {
                        $inserts[] = new Token([T_WHITESPACE, ' ']);
                    }
                    $tokens->insertAt($nextIndex, $inserts);

                    break;
                }
            }
            $index = $nextIndex;
        }
    }

    private function fixMoveToTheEnd(Tokens $tokens): void
    {
        for ($index = $tokens->count() - 1; $index > 0; $index--) {
            $indices = $this->getOperatorIndices($tokens, $index);
            if ($indices === null) {
                continue;
            }

            /** @var int $prevIndex */
            $prevIndex = $tokens->getPrevMeaningfulToken(\min($indices));

            /** @var int $nextIndex */
            $nextIndex = $tokens->getNonEmptySibling(\max($indices), 1);

            for ($i = $prevIndex + 1; $i < $index; $i++) {
                if ($tokens[$i]->isWhitespace() && Preg::match('/\R/u', $tokens[$i]->getContent()) === 1) {
                    $isWhitespaceAfter = $tokens[$nextIndex]->isWhitespace();
                    $inserts = $this->getReplacementsAndClear($tokens, $indices, 1);
                    if ($isWhitespaceAfter) {
                        \array_unshift($inserts, new Token([T_WHITESPACE, ' ']));
                    }
                    $tokens->insertAt($prevIndex + 1, $inserts);

                    break;
                }
            }
            $index = $prevIndex;
        }
    }

    /**
     * @return null|int[]
     */
    private function getOperatorIndices(Tokens $tokens, int $index): ?array
    {
        if (!isset($this->operators[\strtolower($tokens[$index]->getContent())])) {
            return null;
        }

        if (isset($this->operators['?']) && $tokens[$index]->getContent() === '?') {
            /** @var int $nextIndex */
            $nextIndex = $tokens->getNextMeaningfulToken($index);
            if ($tokens[$nextIndex]->getContent() === ':') {
                return [$index, $nextIndex];
            }
        }

        if (isset($this->operators[':']) && $tokens[$index]->getContent() === ':') {
            /** @var int $prevIndex */
            $prevIndex = $tokens->getPrevMeaningfulToken($index);
            if ($tokens[$prevIndex]->getContent() === '?') {
                return [$prevIndex, $index];
            }
            $prevIndex = $tokens->getPrevTokenOfKind($prevIndex, [[T_CASE], '?']);
            if ($prevIndex === null || $tokens[$prevIndex]->isGivenKind(T_CASE)) {
                return null;
            }
        }

        return [$index];
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
}
