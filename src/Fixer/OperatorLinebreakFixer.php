<?php

declare(strict_types = 1);

namespace PhpCsFixerCustomFixers\Fixer;

use PhpCsFixer\Fixer\ConfigurationDefinitionFixerInterface;
use PhpCsFixer\FixerConfiguration\FixerConfigurationResolver;
use PhpCsFixer\FixerConfiguration\FixerOptionBuilder;
use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;

final class OperatorLinebreakFixer extends AbstractFixer implements ConfigurationDefinitionFixerInterface
{
    private const BOOLEAN_OPERATORS = [
        '&&',
        '||',
        'and',
        'or',
        'xor',
    ];

    private const NON_BOOLEAN_OPERATORS = [
        '=',
        '*',
        '/',
        '%',
        '<',
        '>',
        '|',
        '^',
        '+',
        '-',
        '&',
        '&=',
        '.=',
        '/=',
        '=>',
        '==',
        '>=',
        '===',
        '!=',
        '<>',
        '!==',
        '<=',
        '-=',
        '%=',
        '*=',
        '|=',
        '+=',
        '<<',
        '<<=',
        '>>',
        '>>=',
        '^=',
        '**',
        '**=',
        '<=>',
        '??',
    ];

    /** @var string[] */
    private $operators;

    /** @var string */
    private $position = 'beginning';

    public function __construct()
    {
        $this->operators = \array_merge(self::BOOLEAN_OPERATORS, self::NON_BOOLEAN_OPERATORS);
    }

    public function getDefinition(): FixerDefinition
    {
        return new FixerDefinition(
            'Binary operators must always be at the beginning or at the end of the line.',
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
                ->setDefault(false)
                ->setAllowedTypes(['bool'])
                ->getOption(),
            (new FixerOptionBuilder('position', 'whether to place operators at the beginning or at the end of the line'))
                ->setDefault($this->position)
                ->setAllowedTypes(['beginning', 'end'])
                ->getOption(),
        ]);
    }

    public function configure(array $configuration = null): void
    {
        if (isset($configuration['only_booleans']) && $configuration['only_booleans']) {
            $this->operators = self::BOOLEAN_OPERATORS;
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

    public function fix(\SplFileInfo $file, Tokens $tokens): void
    {
        if ($this->position === 'beginning') {
            $this->fixMoveToTheBeginning($tokens);
        } else {
            $this->fixMoveToTheEnd($tokens);
        }
    }

    public function getPriority(): int
    {
        return 0;
    }

    private function fixMoveToTheBeginning(tokens $tokens): void
    {
        for ($index = 0; $index < $tokens->count(); $index++) {
            $tokenContent = \strtolower($tokens[$index]->getContent());

            if (!\in_array($tokenContent, $this->operators, true)) {
                continue;
            }

            $nextIndex = $tokens->getNextMeaningfulToken($index);
            for ($i = $nextIndex - 1; $i > $index; $i--) {
                if ($tokens[$i]->isWhitespace() && \preg_match('/\R/u', $tokens[$i]->getContent()) === 1) {
                    $operator = clone $tokens[$index];
                    $tokens->clearAt($index);
                    if ($tokens[$index - 1]->isWhitespace()) {
                        $tokens->clearTokenAndMergeSurroundingWhitespace($index - 1);
                    }
                    $tokens->insertAt($nextIndex, [$operator, new Token([T_WHITESPACE, ' '])]);
                    break;
                }
            }
            $index = $nextIndex;
        }
    }

    private function fixMoveToTheEnd(tokens $tokens): void
    {
        for ($index = $tokens->count() - 1; $index > 0; $index--) {
            $tokenContent = \strtolower($tokens[$index]->getContent());

            if (!\in_array($tokenContent, $this->operators, true)) {
                continue;
            }

            $prevIndex = $tokens->getPrevMeaningfulToken($index);
            for ($i = $prevIndex + 1; $i < $index; $i++) {
                if ($tokens[$i]->isWhitespace() && \preg_match('/\R/u', $tokens[$i]->getContent()) === 1) {
                    $operator = clone $tokens[$index];
                    $tokens->clearAt($index);
                    if ($tokens[$index + 1]->isWhitespace()) {
                        $tokens->clearTokenAndMergeSurroundingWhitespace($index + 1);
                    }
                    $tokens->insertAt($prevIndex + 1, [new Token([T_WHITESPACE, ' ']), $operator]);
                    break;
                }
            }
            $index = $prevIndex;
        }
    }
}
