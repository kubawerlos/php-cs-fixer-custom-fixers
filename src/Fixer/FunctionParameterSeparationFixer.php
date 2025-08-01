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
use PhpCsFixer\Preg;
use PhpCsFixer\Tokenizer\CT;
use PhpCsFixer\Tokenizer\FCT;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;

/**
 * @no-named-arguments
 */
final class FunctionParameterSeparationFixer extends AbstractFixer
{
    public function getDefinition(): FixerDefinitionInterface
    {
        return new FixerDefinition(
            'Function parameters, if any is having attribute or hook, must be separated by a blank line.',
            [
                new VersionSpecificCodeSample(
                    <<<'PHP'
                        <?php
                        class Foo {
                            public function __construct(
                                #[Attibute1]
                                private string $x,
                                #[Attibute2]
                                private string $y,
                                #[Attibute3]
                                private string $z,
                            ) {}
                        }

                        PHP,
                    new VersionSpecification(80000),
                ),
            ],
            '',
        );
    }

    /**
     * Must run after MethodArgumentSpaceFixer.
     */
    public function getPriority(): int
    {
        return 0;
    }

    public function isCandidate(Tokens $tokens): bool
    {
        return $tokens->isTokenKindFound(\T_FUNCTION);
    }

    public function isRisky(): bool
    {
        return false;
    }

    public function fix(\SplFileInfo $file, Tokens $tokens): void
    {
        for ($index = $tokens->count() - 1; $index > 0; $index--) {
            if (!$tokens[$index]->isGivenKind([\T_FUNCTION])) {
                continue;
            }

            $openParenthesisIndex = $tokens->getNextTokenOfKind($index, ['(']);
            \assert(\is_int($openParenthesisIndex));

            $closeParenthesisIndex = $tokens->findBlockEnd(Tokens::BLOCK_TYPE_PARENTHESIS_BRACE, $openParenthesisIndex);

            if (!self::shouldBeFixed($tokens, $openParenthesisIndex, $closeParenthesisIndex)) {
                continue;
            }

            self::fixFunction($tokens, $openParenthesisIndex, $closeParenthesisIndex);
        }
    }

    private static function shouldBeFixed(Tokens $tokens, int $openParenthesisIndex, int $closeParenthesisIndex): bool
    {
        for ($index = $openParenthesisIndex; $index < $closeParenthesisIndex; $index++) {
            if ($tokens[$index]->isGivenKind([FCT::T_ATTRIBUTE, CT::T_PROPERTY_HOOK_BRACE_OPEN])) {
                return true;
            }
        }

        return false;
    }

    private static function fixFunction(Tokens $tokens, int $openParenthesisIndex, int $closeParenthesisIndex): void
    {
        $prevCloseParenthesisIndex = $tokens->getPrevMeaningfulToken($closeParenthesisIndex);
        \assert(\is_int($prevCloseParenthesisIndex));

        for ($index = $openParenthesisIndex; $index < $prevCloseParenthesisIndex; $index++) {
            if ($tokens[$index]->isGivenKind(FCT::T_ATTRIBUTE)) {
                $index = $tokens->findBlockEnd(Tokens::BLOCK_TYPE_ATTRIBUTE, $index);
                continue;
            }

            if ($tokens[$index]->isGivenKind(CT::T_PROPERTY_HOOK_BRACE_OPEN)) {
                $index = $tokens->findBlockEnd(Tokens::BLOCK_TYPE_PROPERTY_HOOK, $index);
                continue;
            }

            if (!$tokens[$index]->equals(',')) {
                continue;
            }

            if (!$tokens[$index + 1]->isGivenKind(\T_WHITESPACE) || \substr_count($tokens[$index + 1]->getContent(), "\n") !== 1) {
                continue;
            }

            $tokens[$index + 1] = new Token([
                \T_WHITESPACE,
                Preg::replace('/(\\r\\n|\\n)(\\h+)$/', '$1$1$2', $tokens[$index + 1]->getContent(), 1),
            ]);
        }
    }
}
