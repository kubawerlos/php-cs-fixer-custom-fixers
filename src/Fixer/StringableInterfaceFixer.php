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
use PhpCsFixer\Tokenizer\Analyzer\Analysis\NamespaceUseAnalysis;
use PhpCsFixer\Tokenizer\Analyzer\NamespaceUsesAnalyzer;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;

/**
 * @no-named-arguments
 */
final class StringableInterfaceFixer extends AbstractFixer
{
    public function getDefinition(): FixerDefinitionInterface
    {
        return new FixerDefinition(
            'A class that implements the `__toString()` method must explicitly implement the `Stringable` interface.',
            [new CodeSample('<?php
class Foo
{
   public function __toString()
   {
        return "Foo";
   }
}
')],
            '',
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
        // @phpstan-ignore greaterOrEqual.alwaysTrue
        return \PHP_VERSION_ID >= 80000 && $tokens->isAllTokenKindsFound([\T_CLASS, \T_STRING]);
    }

    public function isRisky(): bool
    {
        return false;
    }

    public function fix(\SplFileInfo $file, Tokens $tokens): void
    {
        $useDeclarations = (new NamespaceUsesAnalyzer())->getDeclarationsFromTokens($tokens);

        $stringableInterfaces = ['stringable'];

        for ($index = 1; $index < $tokens->count(); $index++) {
            if ($tokens[$index]->isGivenKind(\T_NAMESPACE)) {
                $stringableInterfaces = [];
                continue;
            }

            if ($tokens[$index]->isGivenKind(\T_USE)) {
                $name = self::getNameFromUse($index, $useDeclarations);
                if ($name !== null) {
                    $stringableInterfaces[] = $name;
                }
                continue;
            }

            if (!$tokens[$index]->isGivenKind(\T_CLASS)) {
                continue;
            }

            $classStartIndex = $tokens->getNextTokenOfKind($index, ['{']);
            \assert(\is_int($classStartIndex));

            $classEndIndex = $tokens->findBlockEnd(Tokens::BLOCK_TYPE_CURLY_BRACE, $classStartIndex);

            if (!self::doesHaveToStringMethod($tokens, $classStartIndex, $classEndIndex)) {
                continue;
            }

            if (self::doesImplementStringable($tokens, $index, $classStartIndex, $stringableInterfaces)) {
                continue;
            }

            self::addStringableInterface($tokens, $index);
        }
    }

    /**
     * @param list<NamespaceUseAnalysis> $useDeclarations
     */
    private static function getNameFromUse(int $index, array $useDeclarations): ?string
    {
        $uses = \array_filter(
            $useDeclarations,
            static fn (NamespaceUseAnalysis $namespaceUseAnalysis): bool => $namespaceUseAnalysis->getStartIndex() === $index,
        );

        \assert(\count($uses) === 1);

        $useDeclaration = \reset($uses);

        $lowercasedFullName = \strtolower($useDeclaration->getFullName());
        if ($lowercasedFullName !== 'stringable' && $lowercasedFullName !== '\\stringable') {
            return null;
        }

        return \strtolower($useDeclaration->getShortName());
    }

    private static function doesHaveToStringMethod(Tokens $tokens, int $classStartIndex, int $classEndIndex): bool
    {
        $index = $classStartIndex;

        while ($index < $classEndIndex) {
            $index++;

            if ($tokens[$index]->equals('{')) {
                $index = $tokens->findBlockEnd(Tokens::BLOCK_TYPE_CURLY_BRACE, $index);
                continue;
            }

            if (!$tokens[$index]->isGivenKind(\T_FUNCTION)) {
                continue;
            }

            $functionNameIndex = $tokens->getNextMeaningfulToken($index);

            if ($tokens[$functionNameIndex]->equals([\T_STRING, '__toString'], false)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param list<string> $stringableInterfaces
     */
    private static function doesImplementStringable(
        Tokens $tokens,
        int $classKeywordIndex,
        int $classOpenBraceIndex,
        array $stringableInterfaces
    ): bool {
        $implementedInterfaces = self::getInterfaces($tokens, $classKeywordIndex, $classOpenBraceIndex);
        if ($implementedInterfaces === []) {
            return false;
        }
        if (\in_array('\\stringable', $implementedInterfaces, true)) {
            return true;
        }

        foreach ($stringableInterfaces as $stringableInterface) {
            if (\in_array($stringableInterface, $implementedInterfaces, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return list<string>
     */
    private static function getInterfaces(Tokens $tokens, int $classKeywordIndex, int $classOpenBraceIndex): array
    {
        $implementsIndex = $tokens->getNextTokenOfKind($classKeywordIndex, ['{', [\T_IMPLEMENTS]]);
        \assert(\is_int($implementsIndex));

        $interfaces = [];
        $interface = '';
        for (
            $index = $tokens->getNextMeaningfulToken($implementsIndex);
            // @phpstan-ignore-next-line
            $index < $classOpenBraceIndex;
            $index = $tokens->getNextMeaningfulToken($index)
        ) {
            \assert(\is_int($index));
            if ($tokens[$index]->equals(',')) {
                $interfaces[] = \strtolower($interface);
                $interface = '';
                continue;
            }
            $interface .= $tokens[$index]->getContent();
        }
        if ($interface !== '') {
            $interfaces[] = \strtolower($interface);
        }

        return $interfaces;
    }

    private static function addStringableInterface(Tokens $tokens, int $classIndex): void
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
                    new Token([\T_STRING, \Stringable::class]),
                ],
            );

            return;
        }

        $afterImplementsIndex = $tokens->getNextMeaningfulToken($implementsIndex);
        \assert(\is_int($afterImplementsIndex));

        $tokens->insertAt(
            $afterImplementsIndex,
            [
                new Token([\T_NS_SEPARATOR, '\\']),
                new Token([\T_STRING, \Stringable::class]),
                new Token(','),
                new Token([\T_WHITESPACE, ' ']),
            ],
        );
    }
}
