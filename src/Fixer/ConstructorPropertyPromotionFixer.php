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

use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\FixerDefinition\FixerDefinitionInterface;
use PhpCsFixer\FixerDefinition\VersionSpecification;
use PhpCsFixer\FixerDefinition\VersionSpecificCodeSample;
use PhpCsFixer\Tokenizer\CT;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;
use PhpCsFixer\Tokenizer\TokensAnalyzer;
use PhpCsFixerCustomFixers\TokenRemover;

final class ConstructorPropertyPromotionFixer extends AbstractFixer
{
    public function getDefinition(): FixerDefinitionInterface
    {
        return new FixerDefinition(
            'Constructor Property Promotion should be used if possible.',
            [
                new VersionSpecificCodeSample(
                    '<?php
class Foo {
    private string $bar;
    public function __construct(string $bar) {
        $this->bar = $bar;
    }
}
',
                    new VersionSpecification(80000)
                ),
            ]
        );
    }

    /**
     * Must run before ClassAttributesSeparationFixer.
     */
    public function getPriority(): int
    {
        return 56;
    }

    public function isCandidate(Tokens $tokens): bool
    {
        return \PHP_VERSION_ID >= 80000 && $tokens->isAllTokenKindsFound([\T_CLASS, \T_VARIABLE]);
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

            if (!$token->isGivenKind(\T_CLASS)) {
                continue;
            }

            /** @var int $classStartIndex */
            $classStartIndex = $tokens->getNextTokenOfKind($index, ['{']);
            $classEndIndex = $tokens->findBlockEnd(Tokens::BLOCK_TYPE_CURLY_BRACE, $classStartIndex);

            $constructorIndex = $this->findConstructor($tokens, $classStartIndex, $classEndIndex);
            if ($constructorIndex === null) {
                continue;
            }

            $tokensAnalyzer = new TokensAnalyzer($tokens);
            $methodAttributes = $tokensAnalyzer->getMethodAttributes($constructorIndex);
            if ($methodAttributes['abstract']) {
                continue;
            }

            $properties = $this->getProperties($tokens, $classStartIndex, $classEndIndex);

            $this->promoteProperties($tokens, $constructorIndex, $properties);
        }
    }

    private function findConstructor(Tokens $tokens, int $classStartIndex, int $classEndIndex): ?int
    {
        for ($index = $classStartIndex + 1; $index < $classEndIndex; $index++) {
            /** @var Token $token */
            $token = $tokens[$index];

            if ($token->equals('{')) {
                $index = $tokens->findBlockEnd(Tokens::BLOCK_TYPE_CURLY_BRACE, $index);
                continue;
            }

            if (!$token->isGivenKind(\T_FUNCTION)) {
                continue;
            }

            /** @var int $functionNameIndex */
            $functionNameIndex = $tokens->getNextTokenOfKind($index, [[\T_STRING]]);

            /** @var Token $functionNameToken */
            $functionNameToken = $tokens[$functionNameIndex];

            if ($functionNameToken->equals([\T_STRING, '__construct'], false)) {
                return $index;
            }
        }

        return null;
    }

    /**
     * @param array<string, int> $properties
     */
    private function promoteProperties(Tokens $tokens, int $constructorIndex, array $properties): void
    {
        /** @var int $parametersStartIndex */
        $parametersStartIndex = $tokens->getNextTokenOfKind($constructorIndex, ['(']);

        /** @var int $parametersEndIndex */
        $parametersEndIndex = $tokens->findBlockEnd(Tokens::BLOCK_TYPE_PARENTHESIS_BRACE, $parametersStartIndex);

        /** @var int $bodyStartIndex */
        $bodyStartIndex = $tokens->getNextTokenOfKind($parametersEndIndex, ['{']);

        /** @var int $bodyEndIndex */
        $bodyEndIndex = $tokens->findBlockEnd(Tokens::BLOCK_TYPE_CURLY_BRACE, $bodyStartIndex);

        for ($index = $parametersEndIndex - 1; $index > $parametersStartIndex; $index--) {
            /** @var Token $token */
            $token = $tokens[$index];

            if (!$token->isGivenKind(\T_VARIABLE)) {
                continue;
            }

            /** @var int $prevIndex */
            $prevIndex = $tokens->getPrevMeaningfulToken($index);
            /** @var Token $prevToken */
            $prevToken = $tokens[$prevIndex];
            if (!$prevToken->isGivenKind([\T_STRING, CT::T_ARRAY_TYPEHINT])) {
                continue;
            }

            $variableAssignmentIndex = $this->getAssignedPropertyIndex($tokens, $token->getContent(), $bodyStartIndex, $bodyEndIndex);
            if ($variableAssignmentIndex === null) {
                continue;
            }
            /** @var Token $variableAssignmentToken */
            $variableAssignmentToken = $tokens[$variableAssignmentIndex];

            if (!isset($properties[$variableAssignmentToken->getContent()])) {
                continue;
            }
            $propertyIndex = $properties[$variableAssignmentToken->getContent()];
            $propertyName = $tokens[$propertyIndex]->getContent();

            $propertyVisibility = $this->removePropertyAndReturnVisibility($tokens, $propertyIndex);
            if ($propertyVisibility === null) {
                continue;
            }

            $this->removeAssigment($tokens, $variableAssignmentIndex);

            $tokens[$index] = new Token([\T_VARIABLE, $propertyName]);
            $this->promoteProperty($tokens, $index, $propertyVisibility);
        }
    }

    private function getAssignedPropertyIndex(Tokens $tokens, string $property, int $bodyStartIndex, int $bodyEndIndex): ?int
    {
        for ($index = $bodyStartIndex + 1; $index < $bodyEndIndex; $index++) {
            /** @var Token $token */
            $token = $tokens[$index];

            if (!$token->equals([\T_VARIABLE, $property], false)) {
                continue;
            }

            /** @var int $semicolonIndex */
            $semicolonIndex = $tokens->getNextMeaningfulToken($index);
            /** @var Token $semicolonToken */
            $semicolonToken = $tokens[$semicolonIndex];
            if (!$semicolonToken->equals(';')) {
                return null;
            }

            /** @var int $assignmentIndex */
            $assignmentIndex = $tokens->getPrevMeaningfulToken($index);
            /** @var Token $assignmentToken */
            $assignmentToken = $tokens[$assignmentIndex];
            if (!$assignmentToken->equals('=')) {
                return null;
            }

            /** @var int $propertyIndex */
            $propertyIndex = $tokens->getPrevMeaningfulToken($assignmentIndex);

            /** @var int $objectOperatorIndex */
            $objectOperatorIndex = $tokens->getPrevMeaningfulToken($propertyIndex);

            /** @var int $thisIndex */
            $thisIndex = $tokens->getPrevMeaningfulToken($objectOperatorIndex);
            /** @var Token $thisToken */
            $thisToken = $tokens[$thisIndex];
            if (!$thisToken->equals([\T_VARIABLE, '$this'], false)) {
                continue;
            }

            /** @var int $prevThisIndex */
            $prevThisIndex = $tokens->getPrevMeaningfulToken($thisIndex);
            /** @var Token $prevThisToken */
            $prevThisToken = $tokens[$prevThisIndex];
            if (!$prevThisToken->equalsAny(['{', ';'])) {
                return null;
            }

            return $propertyIndex;
        }

        return null;
    }

    private function removePropertyAndReturnVisibility(Tokens $tokens, int $propertyIndex): ?Token
    {
        /** @var int $prevElementIndex */
        $prevElementIndex = $tokens->getPrevTokenOfKind($propertyIndex, ['{', '}', ';']);

        /** @var int $propertyStartIndex */
        $propertyStartIndex = $tokens->getNextMeaningfulToken($prevElementIndex);

        /** @var int $propertyEndIndex */
        $propertyEndIndex = $tokens->getNextTokenOfKind($propertyIndex, [';']);

        /** @var Token $visibilityToken */
        $visibilityToken = $tokens[$propertyStartIndex];

        if (!$visibilityToken->isGivenKind([\T_PRIVATE, \T_PROTECTED, \T_PUBLIC])) {
            return null;
        }

        /** @var int $prevPropertyStartIndex */
        $prevPropertyStartIndex = $tokens->getPrevNonWhitespace($propertyStartIndex);
        /** @var Token $prevPropertyStartToken */
        $prevPropertyStartToken = $tokens[$prevPropertyStartIndex];

        if ($prevPropertyStartToken->isGivenKind(\T_DOC_COMMENT)) {
            $propertyStartIndex = $prevPropertyStartIndex;
        }

        $tokens->clearRange($propertyStartIndex + 1, $propertyEndIndex);
        TokenRemover::removeWithLinesIfPossible($tokens, $propertyStartIndex);

        return $visibilityToken;
    }

    private function removeAssigment(Tokens $tokens, int $variableAssignmentIndex): void
    {
        /** @var int $thisIndex */
        $thisIndex = $tokens->getPrevTokenOfKind($variableAssignmentIndex, [[\T_VARIABLE]]);

        /** @var int $propertyEndIndex */
        $propertyEndIndex = $tokens->getNextTokenOfKind($variableAssignmentIndex, [';']);

        $tokens->clearRange($thisIndex + 1, $propertyEndIndex);
        TokenRemover::removeWithLinesIfPossible($tokens, $thisIndex);
    }

    private function promoteProperty(Tokens $tokens, int $index, Token $propertyVisibility): void
    {
        /** @var int $prevElementIndex */
        $prevElementIndex = $tokens->getPrevTokenOfKind($index, ['(', ',']);

        /** @var int $propertyStartIndex */
        $propertyStartIndex = $tokens->getNextMeaningfulToken($prevElementIndex);

        $insertTokens = [];
        if ($propertyVisibility->isGivenKind(\T_PRIVATE)) {
            $insertTokens[] = new Token([CT::T_CONSTRUCTOR_PROPERTY_PROMOTION_PRIVATE, $propertyVisibility->getContent()]);
        } elseif ($propertyVisibility->isGivenKind(\T_PROTECTED)) {
            $insertTokens[] = new Token([CT::T_CONSTRUCTOR_PROPERTY_PROMOTION_PROTECTED, $propertyVisibility->getContent()]);
        } elseif ($propertyVisibility->isGivenKind(\T_PUBLIC)) {
            $insertTokens[] = new Token([CT::T_CONSTRUCTOR_PROPERTY_PROMOTION_PUBLIC, $propertyVisibility->getContent()]);
        }
        $insertTokens[] = new Token([\T_WHITESPACE, ' ']);

        $tokens->insertAt($propertyStartIndex, $insertTokens);
    }

    /**
     * @return array<string, int>
     */
    private function getProperties(Tokens $tokens, int $classStartIndex, int $classEndIndex): array
    {
        $properties = [];
        $tokensAnalyzer = new TokensAnalyzer($tokens);

        /**
         * @var int          $index
         * @var array{token: Token, type: string} $element
         */
        foreach ($tokensAnalyzer->getClassyElements() as $index => $element) {
            if ($index < $classStartIndex || $classEndIndex < $index) {
                continue;
            }

            if ($element['type'] !== 'property') {
                continue;
            }

            $properties[\substr($element['token']->getContent(), 1)] = $index;
        }

        return $properties;
    }
}
