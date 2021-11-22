<?php declare(strict_types=1);

/*
 * This file is part of PHP CS Fixer: custom fixers.
 *
 * (c) 2018 Kuba Werłos
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace PhpCsFixerCustomFixers\Analyzer\Analysis;

use PhpCsFixer\Tokenizer\CT;
use PhpCsFixer\Tokenizer\Tokens;

/**
 * @internal
 */
final class ConstructorAnalysis
{
    /** @var Tokens */
    private $tokens;

    /** @var int */
    private $constructorIndex;

    public function __construct(Tokens $tokens, int $constructorIndex)
    {
        $this->tokens = $tokens;
        $this->constructorIndex = $constructorIndex;
    }

    public function getConstructorIndex(): int
    {
        return $this->constructorIndex;
    }

    /**
     * @return array<int, string>
     */
    public function getConstructorPromotableParameters(): array
    {
        /** @var int $openParenthesis */
        $openParenthesis = $this->tokens->getNextTokenOfKind($this->constructorIndex, ['(']);
        $closeParenthesis = $this->tokens->findBlockEnd(Tokens::BLOCK_TYPE_PARENTHESIS_BRACE, $openParenthesis);

        $constructorPromotableParameters = [];
        for ($index = $openParenthesis + 1; $index < $closeParenthesis; $index++) {
            if (!$this->tokens[$index]->isGivenKind(\T_VARIABLE)) {
                continue;
            }

            /** @var int $typeIndex */
            $typeIndex = $this->tokens->getPrevMeaningfulToken($index);
            if ($this->tokens[$typeIndex]->equalsAny(['(', ',', [\T_CALLABLE]])) {
                continue;
            }

            /** @var int $visibilityIndex */
            $visibilityIndex = $this->tokens->getPrevTokenOfKind(
                $index,
                [
                    '(',
                    ',',
                    [CT::T_CONSTRUCTOR_PROPERTY_PROMOTION_PRIVATE],
                    [CT::T_CONSTRUCTOR_PROPERTY_PROMOTION_PROTECTED],
                    [CT::T_CONSTRUCTOR_PROPERTY_PROMOTION_PUBLIC],
                ]
            );
            if (!$this->tokens[$visibilityIndex]->equalsAny(['(', ','])) {
                continue;
            }

            $constructorPromotableParameters[$index] = \substr($this->tokens[$index]->getContent(), 1);
        }

        return $constructorPromotableParameters;
    }

    /**
     * @return array<string, int>
     */
    public function getConstructorPromotableAssignments(): array
    {
        /** @var int $openParenthesis */
        $openParenthesis = $this->tokens->getNextTokenOfKind($this->constructorIndex, ['(']);
        $closeParenthesis = $this->tokens->findBlockEnd(Tokens::BLOCK_TYPE_PARENTHESIS_BRACE, $openParenthesis);

        /** @var int $openBrace */
        $openBrace = $this->tokens->getNextTokenOfKind($closeParenthesis, ['{']);
        $closeBrace = $this->tokens->findBlockEnd(Tokens::BLOCK_TYPE_CURLY_BRACE, $openBrace);

        $variables = [];
        $properties = [];
        $propertyToVariableMap = [];

        for ($index = $openBrace + 1; $index < $closeBrace; $index++) {
            if (!$this->tokens[$index]->isGivenKind(\T_VARIABLE)) {
                continue;
            }

            /** @var int $semicolonIndex */
            $semicolonIndex = $this->tokens->getNextMeaningfulToken($index);
            if (!$this->tokens[$semicolonIndex]->equals(';')) {
                continue;
            }

            $propertyIndex = $this->getPropertyIndex($index);
            if ($propertyIndex === null) {
                continue;
            }

            $properties[$propertyIndex] = $this->tokens[$propertyIndex]->getContent();
            $variables[$index] = \substr($this->tokens[$index]->getContent(), 1);
            $propertyToVariableMap[$propertyIndex] = $index;
        }

        foreach ($this->getDuplicatesIndices($properties) as $duplicate) {
            unset($variables[$propertyToVariableMap[$duplicate]]);
        }

        foreach ($this->getDuplicatesIndices($variables) as $duplicate) {
            unset($variables[$duplicate]);
        }

        return \array_flip($variables);
    }

    private function getPropertyIndex(int $index): ?int
    {
        /** @var int $assignmentIndex */
        $assignmentIndex = $this->tokens->getPrevMeaningfulToken($index);
        if (!$this->tokens[$assignmentIndex]->equals('=')) {
            return null;
        }

        /** @var int $propertyIndex */
        $propertyIndex = $this->tokens->getPrevMeaningfulToken($assignmentIndex);

        /** @var int $objectOperatorIndex */
        $objectOperatorIndex = $this->tokens->getPrevMeaningfulToken($propertyIndex);

        /** @var int $thisIndex */
        $thisIndex = $this->tokens->getPrevMeaningfulToken($objectOperatorIndex);
        if (!$this->tokens[$thisIndex]->equals([\T_VARIABLE, '$this'], false)) {
            return null;
        }

        /** @var int $prevThisIndex */
        $prevThisIndex = $this->tokens->getPrevMeaningfulToken($thisIndex);
        if (!$this->tokens[$prevThisIndex]->equalsAny(['{', ';'])) {
            return null;
        }

        return $propertyIndex;
    }

    /**
     * @param array<int, string> $array
     *
     * @return array<int>
     */
    private function getDuplicatesIndices(array $array): array
    {
        $duplicates = [];
        $values = [];
        foreach ($array as $key => $value) {
            if (isset($values[$value])) {
                $duplicates[$values[$value]] = $values[$value];
                $duplicates[$key] = $key;
            }
            $values[$value] = $key;
        }

        return $duplicates;
    }
}
