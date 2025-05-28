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
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;

final class TypedClassConstantFixer extends AbstractFixer
{
    private const TOKEN_TO_TYPE_MAP = [
        \T_DNUMBER => 'float',
        \T_LNUMBER => 'int',
        \T_CONSTANT_ENCAPSED_STRING => 'string',
        CT::T_ARRAY_SQUARE_BRACE_CLOSE => 'array',
    ];

    public function getDefinition(): FixerDefinitionInterface
    {
        return new FixerDefinition(
            'Class constants must have a type.',
            [
                new VersionSpecificCodeSample(
                    <<<'PHP'
                        <?php
                        class Foo {
                            public const MAX_VALUE_OF_SOMETHING = 42;
                            public const THE_NAME_OF_SOMEONE = 'John Doe';
                        }

                        PHP,
                    new VersionSpecification(80300),
                ),
            ],
            '',
            'when constant can be of different types',
        );
    }

    public function getPriority(): int
    {
        return 0;
    }

    public function isCandidate(Tokens $tokens): bool
    {
        return $tokens->isAllTokenKindsFound([\T_CLASS, \T_CONST]);
    }

    public function isRisky(): bool
    {
        return true;
    }

    public function fix(\SplFileInfo $file, Tokens $tokens): void
    {
        for ($index = $tokens->count() - 1; $index > 0; $index--) {
            if (!$tokens[$index]->isGivenKind(\T_CLASS)) {
                continue;
            }

            $openParenthesisIndex = $tokens->getNextTokenOfKind($index, ['{']);
            \assert(\is_int($openParenthesisIndex));

            $closeParenthesisIndex = $tokens->findBlockEnd(Tokens::BLOCK_TYPE_CURLY_BRACE, $openParenthesisIndex);

            self::fixClass($tokens, $openParenthesisIndex, $closeParenthesisIndex);
        }
    }

    private static function fixClass(Tokens $tokens, int $openParenthesisIndex, int $closeParenthesisIndex): void
    {
        for ($index = $closeParenthesisIndex; $index > $openParenthesisIndex; $index--) {
            if (!$tokens[$index]->isGivenKind(\T_CONST)) {
                continue;
            }

            $constantNameIndex = $tokens->getNextMeaningfulToken($index);
            \assert(\is_int($constantNameIndex));

            $assignmentIndex = $tokens->getNextMeaningfulToken($constantNameIndex);
            \assert(\is_int($assignmentIndex));

            if (!$tokens[$assignmentIndex]->equals('=')) {
                continue;
            }

            $type = self::getTypeOfExpression($tokens, $assignmentIndex);

            $tokens->insertAt(
                $constantNameIndex,
                [
                    new Token([$type === 'array' ? CT::T_ARRAY_TYPEHINT : \T_STRING, $type]),
                    new Token([\T_WHITESPACE, ' ']),
                ],
            );
        }
    }

    private static function getTypeOfExpression(Tokens $tokens, int $assignmentIndex): string
    {
        $semicolonIndex = $tokens->getNextTokenOfKind($assignmentIndex, [';']);
        \assert(\is_int($semicolonIndex));

        $beforeSemicolonIndex = $tokens->getPrevMeaningfulToken($semicolonIndex);
        \assert(\is_int($beforeSemicolonIndex));

        $tokenId = $tokens[$beforeSemicolonIndex]->getId();

        if (isset(self::TOKEN_TO_TYPE_MAP[$tokenId])) {
            return self::TOKEN_TO_TYPE_MAP[$tokenId];
        }

        if ($tokens[$beforeSemicolonIndex]->isGivenKind(\T_STRING)) {
            $lowercasedContent = \strtolower($tokens[$beforeSemicolonIndex]->getContent());
            if (\in_array($lowercasedContent, ['false', 'true', 'null'], true)) {
                return $lowercasedContent;
            }
        }

        if ($tokens[$beforeSemicolonIndex]->equals(')')) {
            $openParenthesisIndex = $tokens->findBlockStart(Tokens::BLOCK_TYPE_PARENTHESIS_BRACE, $beforeSemicolonIndex);

            $arrayIndex = $tokens->getPrevMeaningfulToken($openParenthesisIndex);
            \assert(\is_int($arrayIndex));

            if ($tokens[$arrayIndex]->isGivenKind(\T_ARRAY)) {
                return 'array';
            }
        }

        return 'mixed';
    }
}
