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

use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\FixerDefinition\FixerDefinitionInterface;
use PhpCsFixer\FixerDefinition\VersionSpecification;
use PhpCsFixer\FixerDefinition\VersionSpecificCodeSample;
use PhpCsFixer\Tokenizer\CT;
use PhpCsFixer\Tokenizer\FCT;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;
use PhpCsFixerCustomFixers\Analyzer\ConstructorAnalyzer;

/**
 * @no-named-arguments
 */
final class ReadonlyPromotedPropertiesFixer extends AbstractFixer
{
    private const ASSIGNMENT_KINDS = [
        '=',
        [\T_INC, '++'],
        [\T_DEC, '--'],
        [\T_PLUS_EQUAL, '+='],
        [\T_MINUS_EQUAL, '-='],
        [\T_MUL_EQUAL, '*='],
        [\T_DIV_EQUAL, '/='],
        [\T_MOD_EQUAL, '%='],
        [\T_POW_EQUAL, '**='],
        [\T_AND_EQUAL, '&='],
        [\T_OR_EQUAL, '|='],
        [\T_XOR_EQUAL, '^='],
        [\T_SL_EQUAL, '<<='],
        [\T_SR_EQUAL, '>>='],
        [\T_COALESCE_EQUAL, '??='],
        [\T_CONCAT_EQUAL, '.='],
    ];
    private const PROMOTED_PROPERTY_VISIBILITY_KINDS = [
        CT::T_CONSTRUCTOR_PROPERTY_PROMOTION_PRIVATE,
        CT::T_CONSTRUCTOR_PROPERTY_PROMOTION_PROTECTED,
        CT::T_CONSTRUCTOR_PROPERTY_PROMOTION_PUBLIC,
        FCT::T_PUBLIC_SET,
        FCT::T_PROTECTED_SET,
        FCT::T_PRIVATE_SET,
    ];

    public function getDefinition(): FixerDefinitionInterface
    {
        return new FixerDefinition(
            'Promoted properties must be declared as read-only.',
            [
                new VersionSpecificCodeSample(
                    '<?php class Foo {
    public function __construct(
        public array $a,
        public bool $b,
    ) {}
}
',
                    new VersionSpecification(80100),
                ),
            ],
            '',
            'when property is written',
        );
    }

    /**
     * Must run after PromotedConstructorPropertyFixer.
     */
    public function getPriority(): int
    {
        return 0;
    }

    public function isCandidate(Tokens $tokens): bool
    {
        return $tokens->isAnyTokenKindsFound(self::PROMOTED_PROPERTY_VISIBILITY_KINDS);
    }

    public function isRisky(): bool
    {
        return true;
    }

    public function fix(\SplFileInfo $file, Tokens $tokens): void
    {
        $constructorAnalyzer = new ConstructorAnalyzer();

        for ($index = $tokens->count() - 1; $index > 0; $index--) {
            if (!$tokens[$index]->isGivenKind(\T_CLASS)) {
                continue;
            }

            if (self::isClassReadonly($tokens, $index)) {
                continue;
            }

            $constructorAnalysis = $constructorAnalyzer->findNonAbstractConstructor($tokens, $index);
            if ($constructorAnalysis === null) {
                continue;
            }

            $classOpenBraceIndex = $tokens->getNextTokenOfKind($index, ['{']);
            \assert(\is_int($classOpenBraceIndex));
            $classCloseBraceIndex = $tokens->findBlockEnd(Tokens::BLOCK_TYPE_CURLY_BRACE, $classOpenBraceIndex);

            $constructorOpenParenthesisIndex = $tokens->getNextTokenOfKind($constructorAnalysis->getConstructorIndex(), ['(']);
            \assert(\is_int($constructorOpenParenthesisIndex));
            $constructorCloseParenthesisIndex = $tokens->findBlockEnd(Tokens::BLOCK_TYPE_PARENTHESIS_BRACE, $constructorOpenParenthesisIndex);

            self::fixParameters(
                $tokens,
                $classOpenBraceIndex,
                $classCloseBraceIndex,
                $constructorOpenParenthesisIndex,
                $constructorCloseParenthesisIndex,
            );
        }
    }

    private static function isClassReadonly(Tokens $tokens, int $index): bool
    {
        do {
            $index = $tokens->getPrevMeaningfulToken($index);
            \assert(\is_int($index));
        } while ($tokens[$index]->isGivenKind([\T_ABSTRACT, \T_FINAL]));

        return $tokens[$index]->isGivenKind(\T_READONLY);
    }

    private static function fixParameters(
        Tokens $tokens,
        int $classOpenBraceIndex,
        int $classCloseBraceIndex,
        int $constructorOpenParenthesisIndex,
        int $constructorCloseParenthesisIndex
    ): void {
        for ($index = $constructorCloseParenthesisIndex; $index > $constructorOpenParenthesisIndex; $index--) {
            if (!$tokens[$index]->isGivenKind(\T_VARIABLE)) {
                continue;
            }

            $insertIndex = self::getInsertIndex($tokens, $index);
            if ($insertIndex === null) {
                continue;
            }

            if (!self::isUsedAsReadonly($tokens, \substr($tokens[$index]->getContent(), 1), $classOpenBraceIndex, $classCloseBraceIndex)) {
                continue;
            }

            $tokens->insertAt(
                $insertIndex + 1,
                [
                    new Token([\T_WHITESPACE, ' ']),
                    new Token([\T_READONLY, 'readonly']),
                ],
            );
        }
    }

    private static function getInsertIndex(Tokens $tokens, int $index): ?int
    {
        $insertIndex = null;

        $index = $tokens->getPrevMeaningfulToken($index);
        \assert(\is_int($index));
        while (!$tokens[$index]->equalsAny([',', '('])) {
            $index = $tokens->getPrevMeaningfulToken($index);
            \assert(\is_int($index));
            if ($tokens[$index]->isGivenKind(\T_READONLY)) {
                return null;
            }
            if ($insertIndex === null && $tokens[$index]->isGivenKind(self::PROMOTED_PROPERTY_VISIBILITY_KINDS)) {
                $insertIndex = $index;
            }
        }

        return $insertIndex;
    }

    private static function isUsedAsReadonly(Tokens $tokens, string $name, int $index, int $endIndex): bool
    {
        $sequence = [
            [\T_VARIABLE, '$this'],
            [\T_OBJECT_OPERATOR],
            [\T_STRING, $name],
        ];

        while ($index < $endIndex) {
            $propertyAssignment = $tokens->findSequence($sequence, $index, $endIndex);
            if ($propertyAssignment === null) {
                break;
            }

            $index = \array_key_last($propertyAssignment);

            if (!self::isReadonlyUsage($tokens, $index)) {
                return false;
            }
        }

        return true;
    }

    private static function isReadonlyUsage(Tokens $tokens, int $index): bool
    {
        $index = $tokens->getPrevMeaningfulToken($index);
        \assert(\is_int($index));

        while (!$tokens[$index]->equalsAny(self::ASSIGNMENT_KINDS)) {
            if ($tokens[$index]->isObjectOperator()) {
                $methodOrPropertyIndex = $tokens->getNextMeaningfulToken($index);
                \assert(\is_int($methodOrPropertyIndex));

                $afterMethodOrPropertyIndex = $tokens->getNextMeaningfulToken($methodOrPropertyIndex);
                \assert(\is_int($afterMethodOrPropertyIndex));

                if ($tokens[$afterMethodOrPropertyIndex]->equals('(') || $tokens[$afterMethodOrPropertyIndex]->isObjectOperator()) {
                    return true;
                }

                $index = $afterMethodOrPropertyIndex;
                continue;
            }

            $blockType = Tokens::detectBlockType($tokens[$index]);
            if ($blockType !== null && $blockType['isStart']) {
                $index = $tokens->findBlockEnd($blockType['type'], $index);

                $index = $tokens->getNextMeaningfulToken($index);
                \assert(\is_int($index));
                continue;
            }

            return true;
        }

        return false;
    }
}
