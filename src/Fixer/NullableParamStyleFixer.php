<?php

declare(strict_types = 1);

namespace PhpCsFixerCustomFixers\Fixer;

use PhpCsFixer\Fixer\ConfigurationDefinitionFixerInterface;
use PhpCsFixer\FixerConfiguration\FixerConfigurationResolver;
use PhpCsFixer\FixerConfiguration\FixerOptionBuilder;
use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\Tokenizer\CT;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;

final class NullableParamStyleFixer extends AbstractFixer implements ConfigurationDefinitionFixerInterface
{
    private const TYPEHINT_KINDS = [
        CT::T_ARRAY_TYPEHINT,
        T_CALLABLE,
        T_NS_SEPARATOR,
        T_STRING,
    ];

    /** @var string */
    private $style = 'with_question_mark';

    public function getDefinition(): FixerDefinition
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
            $this->style = $configuration['style'];
        }
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

            $paramBlockStartIndex = $tokens->getNextTokenOfKind($index, ['(']);
            $paramBlockEndIndex = $tokens->findBlockEnd(Tokens::BLOCK_TYPE_PARENTHESIS_BRACE, $paramBlockStartIndex);

            for ($i = $paramBlockEndIndex; $i > $paramBlockStartIndex; $i--) {
                if (!$tokens[$i]->equals([T_STRING, 'null'], false)) {
                    continue;
                }

                $variableIndex = $tokens->getPrevTokenOfKind($i, [[T_VARIABLE]]);

                $typeIndex = $tokens->getPrevMeaningfulToken($variableIndex);
                if (!$tokens[$typeIndex]->isGivenKind(self::TYPEHINT_KINDS)) {
                    continue;
                }

                $separatorIndex = $tokens->getPrevTokenOfKind($typeIndex, ['(', ',']);
                $nullableIndex = $tokens->getNextMeaningfulToken($separatorIndex);

                if ($this->style === 'with_question_mark' && !$tokens[$nullableIndex]->isGivenKind(CT::T_NULLABLE_TYPE)) {
                    $tokens->insertAt($nullableIndex, new Token([CT::T_NULLABLE_TYPE, '?']));
                } elseif ($this->style === 'without_question_mark' && $tokens[$nullableIndex]->isGivenKind(CT::T_NULLABLE_TYPE)) {
                    $tokens->clearAt($nullableIndex);
                }
            }
        }
    }

    public function getPriority(): int
    {
        // mus be run before NoUnreachableDefaultArgumentValueFixer
        return 1;
    }
}
