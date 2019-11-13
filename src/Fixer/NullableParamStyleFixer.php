<?php

declare(strict_types = 1);

namespace PhpCsFixerCustomFixers\Fixer;

use PhpCsFixer\Fixer\ConfigurationDefinitionFixerInterface;
use PhpCsFixer\FixerConfiguration\FixerConfigurationResolver;
use PhpCsFixer\FixerConfiguration\FixerOptionBuilder;
use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\FixerDefinition\FixerDefinitionInterface;
use PhpCsFixer\Tokenizer\CT;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;

final class NullableParamStyleFixer extends AbstractFixer implements ConfigurationDefinitionFixerInterface
{
    /** @var string */
    private $style = 'with_question_mark';

    public function getDefinition(): FixerDefinitionInterface
    {
        return new FixerDefinition(
            'Nullable parameters must be written in the consistent style.',
            [new CodeSample('<?php
function foo(int $x = null) {
}
')]
        );
    }

    public function getConfigurationDefinition(): FixerConfigurationResolver
    {
        return new FixerConfigurationResolver([
            (new FixerOptionBuilder('style', 'whether nullable parameter type should be prefixed or not with question mark'))
                ->setDefault($this->style)
                ->setAllowedValues(['with_question_mark', 'without_question_mark'])
                ->getOption(),
        ]);
    }

    public function configure(?array $configuration = null): void
    {
        if (isset($configuration['style'])) {
            /** @var string $style */
            $style = $configuration['style'];
            $this->style = $style;
        }
    }

    public function getPriority(): int
    {
        // must be run before NoUnreachableDefaultArgumentValueFixer
        return 1;
    }

    public function isCandidate(Tokens $tokens): bool
    {
        return $tokens->isAllTokenKindsFound([T_FUNCTION]);
    }

    public function isRisky(): bool
    {
        return false;
    }

    public function fix(\SplFileInfo $file, Tokens $tokens): void
    {
        for ($index = $tokens->count() - 1; $index > 0; $index--) {
            if (!$tokens[$index]->isGivenKind(T_FUNCTION)) {
                continue;
            }

            /** @var int $paramBlockStartIndex */
            $paramBlockStartIndex = $tokens->getNextTokenOfKind($index, ['(']);

            $paramBlockEndIndex = $tokens->findBlockEnd(Tokens::BLOCK_TYPE_PARENTHESIS_BRACE, $paramBlockStartIndex);

            $this->fixFunction($tokens, $paramBlockStartIndex, $paramBlockEndIndex);
        }
    }

    private function fixFunction(Tokens $tokens, int $paramBlockStartIndex, int $paramBlockEndIndex): void
    {
        for ($i = $paramBlockEndIndex; $i > $paramBlockStartIndex; $i--) {
            if (!$tokens[$i]->equals([T_STRING, 'null'], false)) {
                continue;
            }

            /** @var int $variableIndex */
            $variableIndex = $tokens->getPrevTokenOfKind($i, [[T_VARIABLE]]);

            /** @var int $typeIndex */
            $typeIndex = $tokens->getPrevMeaningfulToken($variableIndex);

            if (!$tokens[$typeIndex]->isGivenKind([CT::T_ARRAY_TYPEHINT, T_CALLABLE, T_STRING])) {
                continue;
            }

            /** @var int $separatorIndex */
            $separatorIndex = $tokens->getPrevTokenOfKind($typeIndex, ['(', ',']);

            /** @var int $nullableIndex */
            $nullableIndex = $tokens->getNextMeaningfulToken($separatorIndex);

            if ($this->style === 'with_question_mark') {
                if (!$tokens[$nullableIndex]->isGivenKind(CT::T_NULLABLE_TYPE)) {
                    $tokens->insertAt($nullableIndex, new Token([CT::T_NULLABLE_TYPE, '?']));
                }
                continue;
            }

            if (!$tokens[$nullableIndex]->isGivenKind(CT::T_NULLABLE_TYPE)) {
                continue;
            }

            $tokens->clearAt($nullableIndex);
        }
    }
}
