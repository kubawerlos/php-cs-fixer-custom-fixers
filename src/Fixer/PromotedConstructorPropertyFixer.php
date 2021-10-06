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
use PhpCsFixerCustomFixers\Analyzer\ConstructorAnalyzer;
use PhpCsFixerCustomFixers\TokenRemover;

final class PromotedConstructorPropertyFixer extends AbstractFixer
{
    /** @var array<int, array<Token>> */
    private $tokensToInsert;

    public function getDefinition(): FixerDefinitionInterface
    {
        return new FixerDefinition(
            'Constructor properties must be promoted if possible.',
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
        $constructorAnalyzer = new ConstructorAnalyzer();
        $this->tokensToInsert = [];

        for ($index = $tokens->count() - 1; $index > 0; $index--) {
            if (!$tokens[$index]->isGivenKind(\T_CLASS)) {
                continue;
            }

            $constructorAnalysis = $constructorAnalyzer->findNonAbstractConstructor($tokens, $index);
            if ($constructorAnalysis === null) {
                continue;
            }

            $constructorPromotableParameters = $constructorAnalysis->getConstructorPromotableParameters();
            $constructorPromotableAssignments = $constructorAnalysis->getConstructorPromotableAssignments();

            $properties = $this->getClassProperties($tokens, $index);

            foreach ($constructorPromotableParameters as $constructorParameterIndex => $constructorParameterName) {
                if (!isset($constructorPromotableAssignments[$constructorParameterName])) {
                    continue;
                }
                $this->promoteProperty(
                    $tokens,
                    $constructorParameterIndex,
                    $constructorPromotableAssignments[$constructorParameterName],
                    $properties
                );
            }
        }

        \krsort($this->tokensToInsert);

        /**
         * @var int          $index
         * @var array<Token> $tokensToInsert
         */
        foreach ($this->tokensToInsert as $index => $tokensToInsert) {
            $tokens->insertAt($index, $tokensToInsert);
        }
    }

    /**
     * @param array<int> $properties
     */
    private function promoteProperty(Tokens $tokens, int $parameterIndex, int $assignmentIndex, array $properties): void
    {
        $promotedPropertyName = $this->removeAssignmentAndReturnPropertyName($tokens, $assignmentIndex);
        $propertyVisibility = null;
        foreach ($properties as $propertyName => $propertyIndex) {
            if ($promotedPropertyName !== $propertyName) {
                continue;
            }
            $propertyVisibility = $this->removePropertyAndReturnVisibility($tokens, $propertyIndex, $parameterIndex);
        }
        $this->addVisibilityToParameter($tokens, $parameterIndex, $propertyVisibility ?? new Token([\T_PUBLIC, 'public']));
    }

    /**
     * @return array<string, int>
     */
    private function getClassProperties(Tokens $tokens, int $classIndex): array
    {
        $properties = [];
        $tokensAnalyzer = new TokensAnalyzer($tokens);

        /**
         * @var int                                                $index
         * @var array{token: Token, type: string, classIndex: int} $element
         */
        foreach ($tokensAnalyzer->getClassyElements() as $index => $element) {
            if ($element['classIndex'] !== $classIndex) {
                continue;
            }
            if ($element['type'] !== 'property') {
                continue;
            }

            $properties[\substr($element['token']->getContent(), 1)] = $index;
        }

        return $properties;
    }

    private function removePropertyAndReturnVisibility(Tokens $tokens, int $propertyIndex, int $parameterIndex): ?Token
    {
        $tokens[$parameterIndex] = $tokens[$propertyIndex];

        $prevPropertyIndex = $this->getTokenOfKindSibling($tokens, -1, $propertyIndex, ['{', '}', ';', ',']);

        /** @var int $propertyStartIndex */
        $propertyStartIndex = $tokens->getNextMeaningfulToken($prevPropertyIndex);

        $propertyEndIndex = $this->getTokenOfKindSibling($tokens, 1, $propertyIndex, [';', ',']);

        $prevVisibilityIndex = $this->getTokenOfKindSibling($tokens, -1, $propertyIndex, ['{', '}', ';']);

        /** @var int $visibilityIndex */
        $visibilityIndex = $tokens->getNextMeaningfulToken($prevVisibilityIndex);

        $visibilityToken = $tokens[$visibilityIndex];

        if ($tokens[$visibilityIndex]->isGivenKind(\T_VAR)) {
            $visibilityToken = null;
        }

        /** @var int $prevPropertyStartIndex */
        $prevPropertyStartIndex = $tokens->getPrevNonWhitespace($propertyStartIndex);

        if ($tokens[$prevPropertyStartIndex]->isGivenKind(\T_DOC_COMMENT)) {
            $propertyStartIndex = $prevPropertyStartIndex;
        }

        $removeFrom = $propertyStartIndex;
        $removeTo = $propertyEndIndex;
        if ($tokens[$prevPropertyIndex]->equals(',')) {
            /** @var int $removeFrom */
            $removeFrom = $tokens->getPrevMeaningfulToken($propertyStartIndex);
            $removeTo = $propertyEndIndex - 1;
        } elseif ($tokens[$propertyEndIndex]->equals(',')) {
            /** @var int $removeFrom */
            $removeFrom = $tokens->getNextMeaningfulToken($visibilityIndex);
            $removeTo = $propertyEndIndex + 1;
        }

        $tokens->clearRange($removeFrom + 1, $removeTo);
        TokenRemover::removeWithLinesIfPossible($tokens, $removeFrom);

        return $visibilityToken;
    }

    /**
     * @param array<string> $tokenKinds
     */
    private function getTokenOfKindSibling(Tokens $tokens, int $direction, int $index, array $tokenKinds): int
    {
        while (true) {
            $index += $direction;

            if ($tokens[$index]->equalsAny($tokenKinds)) {
                break;
            }

            /** @var null|array{isStart: bool, type: int} $blockType */
            $blockType = Tokens::detectBlockType($tokens[$index]);
            if ($blockType !== null && $blockType['isStart']) {
                $index = $tokens->findBlockEnd($blockType['type'], $index);
            }
        }

        return $index;
    }

    private function removeAssignmentAndReturnPropertyName(Tokens $tokens, int $variableAssignmentIndex): string
    {
        /** @var int $propertyIndex */
        $propertyIndex = $tokens->getPrevTokenOfKind($variableAssignmentIndex, [[\T_STRING]]);

        $name = $tokens[$propertyIndex]->getContent();

        /** @var int $thisIndex */
        $thisIndex = $tokens->getPrevTokenOfKind($variableAssignmentIndex, [[\T_VARIABLE]]);

        /** @var int $propertyEndIndex */
        $propertyEndIndex = $tokens->getNextTokenOfKind($variableAssignmentIndex, [';']);

        $tokens->clearRange($thisIndex + 1, $propertyEndIndex);
        TokenRemover::removeWithLinesIfPossible($tokens, $thisIndex);

        return $name;
    }

    private function addVisibilityToParameter(Tokens $tokens, int $index, Token $visibilityToken): void
    {
        /** @var int $prevElementIndex */
        $prevElementIndex = $tokens->getPrevTokenOfKind($index, ['(', ',', [CT::T_ATTRIBUTE_CLOSE]]);

        /** @var int $propertyStartIndex */
        $propertyStartIndex = $tokens->getNextMeaningfulToken($prevElementIndex);

        $insertTokens = [];
        if ($visibilityToken->isGivenKind(\T_PRIVATE)) {
            $insertTokens[] = new Token([CT::T_CONSTRUCTOR_PROPERTY_PROMOTION_PRIVATE, $visibilityToken->getContent()]);
        } elseif ($visibilityToken->isGivenKind(\T_PROTECTED)) {
            $insertTokens[] = new Token([CT::T_CONSTRUCTOR_PROPERTY_PROMOTION_PROTECTED, $visibilityToken->getContent()]);
        } elseif ($visibilityToken->isGivenKind(\T_PUBLIC)) {
            $insertTokens[] = new Token([CT::T_CONSTRUCTOR_PROPERTY_PROMOTION_PUBLIC, $visibilityToken->getContent()]);
        }
        $insertTokens[] = new Token([\T_WHITESPACE, ' ']);

        $this->tokensToInsert[$propertyStartIndex] = $insertTokens;
    }
}
