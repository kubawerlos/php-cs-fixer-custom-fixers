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

use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\FixerDefinition\FixerDefinitionInterface;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;

final class StringableInterfaceFixer extends AbstractFixer
{
    public function getDefinition(): FixerDefinitionInterface
    {
        return new FixerDefinition(
            'Class that implements the `__toString()` method must implement the `Stringable` interface.',
            [new CodeSample('<?php
class Foo
{
   public function __toString()
   {
        return "Foo";
   }
}
')]
        );
    }

    /**
     * Must run before ClassDefinitionFixer.
     */
    public function getPriority(): int
    {
        return 37;
    }

    public function isCandidate(Tokens $tokens): bool
    {
        return \PHP_VERSION_ID >= 80000 && $tokens->isAllTokenKindsFound([\T_CLASS, \T_STRING]);
    }

    public function isRisky(): bool
    {
        return false;
    }

    public function fix(\SplFileInfo $file, Tokens $tokens): void
    {
        $isNamespaced = false;

        for ($index = 1; $index < $tokens->count(); $index++) {
            /** @var Token $token */
            $token = $tokens[$index];

            if ($token->isGivenKind(\T_NAMESPACE)) {
                $isNamespaced = true;
                continue;
            }

            if (!$token->isGivenKind(\T_CLASS)) {
                continue;
            }

            /** @var int $classStartIndex */
            $classStartIndex = $tokens->getNextTokenOfKind($index, ['{']);

            $classEndIndex = $tokens->findBlockEnd(Tokens::BLOCK_TYPE_CURLY_BRACE, $classStartIndex);

            if (!$this->doesHaveToStringMethod($tokens, $classStartIndex, $classEndIndex)) {
                continue;
            }

            $this->addStringableInterface($tokens, $index, $isNamespaced);
        }
    }

    private function doesHaveToStringMethod(Tokens $tokens, int $classStartIndex, int $classEndIndex): bool
    {
        for ($index = $classStartIndex + 1; $index < $classEndIndex; $index++) {
            /** @var Token $token */
            $token = $tokens[$index];

            if (!$token->isGivenKind(\T_FUNCTION)) {
                continue;
            }

            $functionNameIndex = $tokens->getNextTokenOfKind($index, [[\T_STRING]]);

            if ($functionNameIndex === null || $functionNameIndex > $classEndIndex) {
                return false;
            }

            /** @var Token $functionNameToken */
            $functionNameToken = $tokens[$functionNameIndex];

            if ($functionNameToken->equals([\T_STRING, '__toString'], false)) {
                return true;
            }
        }

        return false;
    }

    private function addStringableInterface(Tokens $tokens, int $classIndex, bool $isNamespaced): void
    {
        /** @var int $classNameIndex */
        $classNameIndex = $tokens->getNextTokenOfKind($classIndex, [[\T_STRING]]);

        /** @var int $implementsIndex */
        $implementsIndex = $tokens->getNextTokenOfKind($classNameIndex, ['{', [\T_IMPLEMENTS]]);

        /** @var Token $implementsToken */
        $implementsToken = $tokens[$implementsIndex];

        if ($implementsToken->equals('{')) {
            /** @var int $prevIndex */
            $prevIndex = $tokens->getPrevMeaningfulToken($implementsIndex);

            $tokens->insertAt(
                $prevIndex + 1,
                [
                    new Token([\T_WHITESPACE, ' ']),
                    new Token([\T_IMPLEMENTS, 'implements']),
                    new Token([\T_WHITESPACE, ' ']),
                    new Token([\T_NS_SEPARATOR, '\\']),
                    new Token([\T_STRING, 'Stringable']),
                ]
            );

            return;
        }

        /** @var int $implementsEndIndex */
        $implementsEndIndex = $tokens->getNextTokenOfKind($classNameIndex, ['{']);
        if ($this->isStringableAlreadyUsed($tokens, $implementsIndex + 1, $implementsEndIndex - 1, $isNamespaced)) {
            return;
        }

        /** @var int $prevIndex */
        $prevIndex = $tokens->getPrevMeaningfulToken($implementsEndIndex);

        $tokens->insertAt(
            $prevIndex + 1,
            [
                new Token(','),
                new Token([\T_WHITESPACE, ' ']),
                new Token([\T_NS_SEPARATOR, '\\']),
                new Token([\T_STRING, 'Stringable']),
            ]
        );
    }

    private function isStringableAlreadyUsed(Tokens $tokens, int $implementsStartIndex, int $implementsEndIndex, bool $isNamespaced): bool
    {
        for ($index = $implementsStartIndex; $index < $implementsEndIndex; $index++) {
            /** @var Token $token */
            $token = $tokens[$index];

            if (!$token->equals([\T_STRING, 'Stringable'], false)) {
                continue;
            }

            /** @var int $namespaceSeparatorIndex */
            $namespaceSeparatorIndex = $tokens->getPrevMeaningfulToken($index);

            /** @var Token $namespaceSeparatorToken */
            $namespaceSeparatorToken = $tokens[$namespaceSeparatorIndex];

            if ($namespaceSeparatorToken->isGivenKind(\T_NS_SEPARATOR)) {
                /** @var int $beforeNamespaceSeparatorIndex */
                $beforeNamespaceSeparatorIndex = $tokens->getPrevMeaningfulToken($namespaceSeparatorIndex);

                /** @var Token $beforeNamespaceSeparatorToken */
                $beforeNamespaceSeparatorToken = $tokens[$beforeNamespaceSeparatorIndex];

                if (!$beforeNamespaceSeparatorToken->isGivenKind(\T_STRING)) {
                    return true;
                }
            } else {
                if (!$isNamespaced) {
                    return true;
                }
            }
        }

        return false;
    }
}
