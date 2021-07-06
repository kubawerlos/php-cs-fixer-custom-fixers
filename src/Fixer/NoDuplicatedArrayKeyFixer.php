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

use PhpCsFixer\Fixer\ConfigurableFixerInterface;
use PhpCsFixer\FixerConfiguration\FixerConfigurationResolver;
use PhpCsFixer\FixerConfiguration\FixerConfigurationResolverInterface;
use PhpCsFixer\FixerConfiguration\FixerOptionBuilder;
use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\FixerDefinition\FixerDefinitionInterface;
use PhpCsFixer\Preg;
use PhpCsFixer\Tokenizer\CT;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;
use PhpCsFixerCustomFixers\Analyzer\Analysis\ArrayElementAnalysis;
use PhpCsFixerCustomFixers\Analyzer\ArrayAnalyzer;
use PhpCsFixerCustomFixers\TokenRemover;

final class NoDuplicatedArrayKeyFixer extends AbstractFixer implements ConfigurableFixerInterface
{
    /** @var bool */
    private $ignoreExpressions = true;

    public function getDefinition(): FixerDefinitionInterface
    {
        return new FixerDefinition(
            'There can be no duplicate array keys.',
            [new CodeSample('<?php
$x = [
    "foo" => 1,
    "bar" => 2,
    "foo" => 3,
];
')]
        );
    }

    public function getConfigurationDefinition(): FixerConfigurationResolverInterface
    {
        return new FixerConfigurationResolver([
            (new FixerOptionBuilder('ignore_expressions', 'whether to keep duplicated expressions (as they might return different values) or not'))
                ->setAllowedTypes(['bool'])
                ->setDefault($this->ignoreExpressions)
                ->getOption(),
        ]);
    }

    /**
     * @param null|array<string, bool> $configuration
     */
    public function configure(?array $configuration = null): void
    {
        if (isset($configuration['ignore_expressions'])) {
            $this->ignoreExpressions = $configuration['ignore_expressions'];
        }
    }

    public function getPriority(): int
    {
        return 0;
    }

    public function isCandidate(Tokens $tokens): bool
    {
        return $tokens->isAnyTokenKindsFound([\T_ARRAY, CT::T_ARRAY_SQUARE_BRACE_OPEN]);
    }

    public function isRisky(): bool
    {
        return false;
    }

    public function fix(\SplFileInfo $file, Tokens $tokens): void
    {
        for ($index = $tokens->count() - 1; $index > 0; $index--) {
            /** @var Token $token */
            $token = $tokens[$index];

            if (!$token->isGivenKind([\T_ARRAY, CT::T_ARRAY_SQUARE_BRACE_OPEN])) {
                continue;
            }

            $this->fixArray($tokens, $index);
        }
    }

    private function fixArray(Tokens $tokens, int $index): void
    {
        $arrayAnalyzer = new ArrayAnalyzer();

        $keys = [];
        foreach (\array_reverse($arrayAnalyzer->getElements($tokens, $index)) as $arrayElementAnalysis) {
            $key = $this->getKeyContentIfPossible($tokens, $arrayElementAnalysis);
            if ($key === null) {
                continue;
            }
            if (isset($keys[$key])) {
                /** @var int $startIndex */
                $startIndex = $arrayElementAnalysis->getKeyStartIndex();

                /** @var int $endIndex */
                $endIndex = $tokens->getNextMeaningfulToken($arrayElementAnalysis->getValueEndIndex());

                /** @var Token $afterEndToken */
                $afterEndToken = $tokens[$endIndex + 1];

                if ($afterEndToken->isWhitespace() && Preg::match('/^\h+$/', $afterEndToken->getContent()) === 1) {
                    $endIndex++;
                }

                $tokens->clearRange($startIndex + 1, $endIndex);
                TokenRemover::removeWithLinesIfPossible($tokens, $startIndex);
            }
            $keys[$key] = true;
        }
    }

    private function getKeyContentIfPossible(Tokens $tokens, ArrayElementAnalysis $arrayElementAnalysis): ?string
    {
        if ($arrayElementAnalysis->getKeyStartIndex() === null) {
            return null;
        }

        /** @var int $keyEndIndex */
        $keyEndIndex = $arrayElementAnalysis->getKeyEndIndex();

        $content = '';
        for ($index = $keyEndIndex; $index >= $arrayElementAnalysis->getKeyStartIndex(); $index--) {
            /** @var Token $token */
            $token = $tokens[$index];

            if ($token->isWhitespace() || $token->isComment()) {
                continue;
            }

            if ($this->ignoreExpressions && $token->equalsAny([[\T_VARIABLE], '('])) {
                return null;
            }

            $content .= $token->getContent();
        }

        return $content;
    }
}
