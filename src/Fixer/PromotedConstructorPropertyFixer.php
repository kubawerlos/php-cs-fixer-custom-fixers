<?php declare(strict_types=1);

/*
 * This file is part of PHP CS Fixer: custom fixers.
 *
 * (c) 2018 Kuba Werłos
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace PhpCsFixerCustomFixers\Fixer;

use PhpCsFixer\DocBlock\DocBlock;
use PhpCsFixer\Fixer\ConfigurableFixerInterface;
use PhpCsFixer\FixerConfiguration\FixerConfigurationResolver;
use PhpCsFixer\FixerConfiguration\FixerConfigurationResolverInterface;
use PhpCsFixer\FixerConfiguration\FixerOptionBuilder;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\FixerDefinition\FixerDefinitionInterface;
use PhpCsFixer\FixerDefinition\VersionSpecification;
use PhpCsFixer\FixerDefinition\VersionSpecificCodeSample;
use PhpCsFixer\Preg;
use PhpCsFixer\Tokenizer\CT;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;
use PhpCsFixer\Tokenizer\TokensAnalyzer;
use PhpCsFixerCustomFixers\Analyzer\Analysis\ConstructorAnalysis;
use PhpCsFixerCustomFixers\Analyzer\ConstructorAnalyzer;
use PhpCsFixerCustomFixers\TokenRemover;

final class PromotedConstructorPropertyFixer extends AbstractFixer implements ConfigurableFixerInterface
{
    /** @var array<int, array<Token>> */
    private $tokensToInsert;

    /** @var bool */
    private $promoteOnlyExistingProperties = false;

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

    public function getConfigurationDefinition(): FixerConfigurationResolverInterface
    {
        return new FixerConfigurationResolver([
            (new FixerOptionBuilder('promote_only_existing_properties', 'whether to promote only properties that are defined in class'))
                ->setAllowedTypes(['bool'])
                ->setDefault($this->promoteOnlyExistingProperties)
                ->getOption(),
        ]);
    }

    /**
     * @param array<string, bool> $configuration
     */
    public function configure(array $configuration): void
    {
        if (\array_key_exists('promote_only_existing_properties', $configuration)) {
            $this->promoteOnlyExistingProperties = $configuration['promote_only_existing_properties'];
        }
    }

    /**
     * Must run before BracesFixer, ClassAttributesSeparationFixer, ConstructorEmptyBracesFixer, MultilinePromotedPropertiesFixer, NoExtraBlankLinesFixer, NoUnusedImportsFixer.
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

            $this->promoteProperties($tokens, $index, $constructorAnalysis);
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

    private function promoteProperties(Tokens $tokens, int $classIndex, ConstructorAnalysis $constructorAnalysis): void
    {
        $isDoctrineEntity = $this->isDoctrineEntity($tokens, $classIndex);
        $properties = $this->getClassProperties($tokens, $classIndex);

        $constructorPromotableParameters = $constructorAnalysis->getConstructorPromotableParameters();
        $constructorPromotableAssignments = $constructorAnalysis->getConstructorPromotableAssignments();

        foreach ($constructorPromotableParameters as $constructorParameterIndex => $constructorParameterName) {
            if (!\array_key_exists($constructorParameterName, $constructorPromotableAssignments)) {
                continue;
            }

            $propertyIndex = $this->getPropertyIndex($tokens, $properties, $constructorPromotableAssignments[$constructorParameterName]);

            if (!$this->isPropertyToPromote($tokens, $propertyIndex, $isDoctrineEntity)) {
                continue;
            }

            $propertyType = '';
            if ($propertyIndex !== null) {
                $propertyType = $this->getType($tokens, $propertyIndex);
            }

            $parameterTypeType = $this->getType($tokens, $constructorParameterIndex);

            if (!$this->typesAllowPromoting($propertyType, $parameterTypeType)) {
                continue;
            }

            $propertyVisibility = null;
            if ($propertyIndex !== null) {
                $propertyVisibility = $this->removePropertyAndReturnVisibility($tokens, $propertyIndex, $constructorParameterIndex);
            }

            $this->removeAssignment($tokens, $constructorPromotableAssignments[$constructorParameterName]);
            $this->updateParameterSignature(
                $tokens,
                $constructorParameterIndex,
                $propertyVisibility ?? new Token([\T_PUBLIC, 'public']),
                \substr($propertyType, 0, 1) === '?'
            );
        }
    }

    private function isDoctrineEntity(Tokens $tokens, int $index): bool
    {
        $phpDocIndex = $tokens->getPrevNonWhitespace($index);
        \assert(\is_int($phpDocIndex));

        if (!$tokens[$phpDocIndex]->isGivenKind(\T_DOC_COMMENT)) {
            return false;
        }

        $docBlock = new DocBlock($tokens[$phpDocIndex]->getContent());

        foreach ($docBlock->getAnnotations() as $annotation) {
            if (Preg::match('/\*\h+(@Document|@Entity|@Mapping\\\\Entity|@ODM\\\\Document|@ORM\\\\Entity|@ORM\\\\Mapping\\\\Entity)/', $annotation->getContent()) === 1) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array<int> $properties
     */
    private function getPropertyIndex(Tokens $tokens, array $properties, int $assignmentIndex): ?int
    {
        $propertyNameIndex = $tokens->getPrevTokenOfKind($assignmentIndex, [[\T_STRING]]);
        \assert(\is_int($propertyNameIndex));

        $propertyName = $tokens[$propertyNameIndex]->getContent();

        foreach ($properties as $name => $index) {
            if ($name !== $propertyName) {
                continue;
            }

            return $index;
        }

        return null;
    }

    private function isPropertyToPromote(Tokens $tokens, ?int $propertyIndex, bool $isDoctrineEntity): bool
    {
        if ($propertyIndex === null) {
            return !$this->promoteOnlyExistingProperties;
        }

        if (!$isDoctrineEntity) {
            return true;
        }

        $phpDocIndex = $tokens->getPrevTokenOfKind($propertyIndex, [[\T_DOC_COMMENT]]);
        \assert(\is_int($phpDocIndex));

        $variableIndex = $tokens->getNextTokenOfKind($phpDocIndex, ['{', [\T_VARIABLE]]);

        if ($variableIndex !== $propertyIndex) {
            return true;
        }

        $docBlock = new DocBlock($tokens[$phpDocIndex]->getContent());

        return \count($docBlock->getAnnotations()) === 0;
    }

    private function getType(Tokens $tokens, int $variableIndex): string
    {
        $type = '';

        $index = $tokens->getPrevTokenOfKind($variableIndex, ['(', ',', [\T_PRIVATE], [\T_PROTECTED], [\T_PUBLIC], [\T_VAR], [CT::T_ATTRIBUTE_CLOSE]]);
        \assert(\is_int($index));

        $index = $tokens->getNextMeaningfulToken($index);
        \assert(\is_int($index));

        while ($index < $variableIndex) {
            $type .= $tokens[$index]->getContent();

            $index = $tokens->getNextMeaningfulToken($index);
            \assert(\is_int($index));
        }

        return $type;
    }

    private function typesAllowPromoting(string $propertyType, string $parameterTypeType): bool
    {
        if ($propertyType === '') {
            return true;
        }

        if (\substr($propertyType, 0, 1) === '?') {
            $propertyType = \substr($propertyType, 1);
        }

        if (\substr($parameterTypeType, 0, 1) === '?') {
            $parameterTypeType = \substr($parameterTypeType, 1);
        }

        return \strtolower($propertyType) === \strtolower($parameterTypeType);
    }

    /**
     * @return array<string, int>
     */
    private function getClassProperties(Tokens $tokens, int $classIndex): array
    {
        $properties = [];
        $tokensAnalyzer = new TokensAnalyzer($tokens);

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

        $propertyStartIndex = $tokens->getNextMeaningfulToken($prevPropertyIndex);
        \assert(\is_int($propertyStartIndex));

        $propertyEndIndex = $this->getTokenOfKindSibling($tokens, 1, $propertyIndex, [';', ',']);

        $prevVisibilityIndex = $this->getTokenOfKindSibling($tokens, -1, $propertyIndex, ['{', '}', ';']);

        $visibilityIndex = $tokens->getNextMeaningfulToken($prevVisibilityIndex);
        \assert(\is_int($visibilityIndex));

        $visibilityToken = $tokens[$visibilityIndex];

        if ($tokens[$visibilityIndex]->isGivenKind(\T_VAR)) {
            $visibilityToken = null;
        }

        $prevPropertyStartIndex = $tokens->getPrevNonWhitespace($propertyStartIndex);
        \assert(\is_int($prevPropertyStartIndex));

        if ($tokens[$prevPropertyStartIndex]->isGivenKind(\T_DOC_COMMENT)) {
            $propertyStartIndex = $prevPropertyStartIndex;
        }

        $removeFrom = $propertyStartIndex;
        $removeTo = $propertyEndIndex;
        if ($tokens[$prevPropertyIndex]->equals(',')) {
            $removeFrom = $tokens->getPrevMeaningfulToken($propertyStartIndex);
            \assert(\is_int($removeFrom));
            $removeTo = $propertyEndIndex - 1;
        } elseif ($tokens[$propertyEndIndex]->equals(',')) {
            $removeFrom = $tokens->getNextMeaningfulToken($visibilityIndex);
            \assert(\is_int($removeFrom));
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

    private function removeAssignment(Tokens $tokens, int $variableAssignmentIndex): void
    {
        $thisIndex = $tokens->getPrevTokenOfKind($variableAssignmentIndex, [[\T_VARIABLE]]);
        \assert(\is_int($thisIndex));

        $propertyEndIndex = $tokens->getNextTokenOfKind($variableAssignmentIndex, [';']);
        \assert(\is_int($propertyEndIndex));

        $tokens->clearRange($thisIndex + 1, $propertyEndIndex);
        TokenRemover::removeWithLinesIfPossible($tokens, $thisIndex);
    }

    private function updateParameterSignature(Tokens $tokens, int $index, Token $visibilityToken, bool $makeTypeNullable): void
    {
        $prevElementIndex = $tokens->getPrevTokenOfKind($index, ['(', ',', [CT::T_ATTRIBUTE_CLOSE]]);
        \assert(\is_int($prevElementIndex));

        $propertyStartIndex = $tokens->getNextMeaningfulToken($prevElementIndex);
        \assert(\is_int($propertyStartIndex));

        $insertTokens = [];

        if ($visibilityToken->isGivenKind(\T_PRIVATE)) {
            $insertTokens[] = new Token([CT::T_CONSTRUCTOR_PROPERTY_PROMOTION_PRIVATE, $visibilityToken->getContent()]);
        } elseif ($visibilityToken->isGivenKind(\T_PROTECTED)) {
            $insertTokens[] = new Token([CT::T_CONSTRUCTOR_PROPERTY_PROMOTION_PROTECTED, $visibilityToken->getContent()]);
        } elseif ($visibilityToken->isGivenKind(\T_PUBLIC)) {
            $insertTokens[] = new Token([CT::T_CONSTRUCTOR_PROPERTY_PROMOTION_PUBLIC, $visibilityToken->getContent()]);
        }
        $insertTokens[] = new Token([\T_WHITESPACE, ' ']);

        if ($makeTypeNullable && !$tokens[$propertyStartIndex]->isGivenKind(CT::T_NULLABLE_TYPE)) {
            $insertTokens[] = new Token([CT::T_NULLABLE_TYPE, '?']);
        }

        $this->tokensToInsert[$propertyStartIndex] = $insertTokens;
    }
}
