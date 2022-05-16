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
            'A class that implements the `__toString ()` method must explicitly implement the `Stringable` interface.',
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
     * Must run before ClassDefinitionFixer, GlobalNamespaceImportFixer, OrderedInterfacesFixer.
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
        $namespaceStartIndex = null;

        for ($index = 1; $index < $tokens->count(); $index++) {
            if ($tokens[$index]->isGivenKind(\T_NAMESPACE)) {
                $namespaceStartIndex = $index;
                continue;
            }

            if (!$tokens[$index]->isGivenKind(\T_CLASS)) {
                continue;
            }

            $classStartIndex = $tokens->getNextTokenOfKind($index, ['{']);
            \assert(\is_int($classStartIndex));

            $classEndIndex = $tokens->findBlockEnd(Tokens::BLOCK_TYPE_CURLY_BRACE, $classStartIndex);

            if (!$this->doesHaveToStringMethod($tokens, $classStartIndex, $classEndIndex)) {
                continue;
            }

            $this->addStringableInterface($tokens, $index, $namespaceStartIndex);
        }
    }

    private function doesHaveToStringMethod(Tokens $tokens, int $classStartIndex, int $classEndIndex): bool
    {
        for ($index = $classStartIndex + 1; $index < $classEndIndex; $index++) {
            if (!$tokens[$index]->isGivenKind(\T_FUNCTION)) {
                continue;
            }

            $functionNameIndex = $tokens->getNextTokenOfKind($index, [[\T_STRING]]);

            if ($functionNameIndex === null || $functionNameIndex > $classEndIndex) {
                return false;
            }

            if ($tokens[$functionNameIndex]->equals([\T_STRING, '__toString'], false)) {
                return true;
            }
        }

        return false;
    }

    private function addStringableInterface(Tokens $tokens, int $classIndex, ?int $namespaceStartIndex): void
    {
        $implementsIndex = $tokens->getNextTokenOfKind($classIndex, ['{', [\T_IMPLEMENTS]]);
        \assert(\is_int($implementsIndex));

        if ($tokens[$implementsIndex]->equals('{')) {
            $prevIndex = $tokens->getPrevMeaningfulToken($implementsIndex);
            \assert(\is_int($prevIndex));

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

        $implementsEndIndex = $tokens->getNextTokenOfKind($implementsIndex, ['{']);
        \assert(\is_int($implementsEndIndex));
        if ($this->isStringableAlreadyUsed($tokens, $implementsIndex + 1, $implementsEndIndex - 1, $namespaceStartIndex)) {
            return;
        }

        $prevIndex = $tokens->getPrevMeaningfulToken($implementsEndIndex);
        \assert(\is_int($prevIndex));

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

    private function isStringableAlreadyUsed(Tokens $tokens, int $implementsStartIndex, int $implementsEndIndex, ?int $namespaceStartIndex): bool
    {
        for ($index = $implementsStartIndex; $index < $implementsEndIndex; $index++) {
            if (!$tokens[$index]->equals([\T_STRING, 'Stringable'], false)) {
                continue;
            }

            $namespaceSeparatorIndex = $tokens->getPrevMeaningfulToken($index);
            \assert(\is_int($namespaceSeparatorIndex));

            if ($tokens[$namespaceSeparatorIndex]->isGivenKind(\T_NS_SEPARATOR)) {
                $beforeNamespaceSeparatorIndex = $tokens->getPrevMeaningfulToken($namespaceSeparatorIndex);
                \assert(\is_int($beforeNamespaceSeparatorIndex));

                if (!$tokens[$beforeNamespaceSeparatorIndex]->isGivenKind(\T_STRING)) {
                    return true;
                }
            } else {
                if ($namespaceStartIndex === null) {
                    return true;
                }
                if ($this->isStringableImported($tokens, $namespaceStartIndex, $index)) {
                    return true;
                }
            }
        }

        return false;
    }

    private function isStringableImported(Tokens $tokens, int $startIndex, int $endIndex): bool
    {
        for ($index = $startIndex; $index < $endIndex; $index++) {
            if (!$tokens[$index]->equals([\T_STRING, 'Stringable'], false)) {
                continue;
            }

            $useIndex = $tokens->getPrevMeaningfulToken($index);
            \assert(\is_int($useIndex));

            if ($tokens[$useIndex]->isGivenKind(\T_NS_SEPARATOR)) {
                $useIndex = $tokens->getPrevMeaningfulToken($useIndex);
                \assert(\is_int($useIndex));
            }

            if ($tokens[$useIndex]->isGivenKind(\T_USE)) {
                return true;
            }
        }

        return false;
    }
}
