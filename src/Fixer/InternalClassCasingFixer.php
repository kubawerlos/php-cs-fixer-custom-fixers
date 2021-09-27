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
use PhpCsFixer\Tokenizer\Analyzer\NamespacesAnalyzer;
use PhpCsFixer\Tokenizer\CT;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;

final class InternalClassCasingFixer extends AbstractFixer
{
    public function getDefinition(): FixerDefinitionInterface
    {
        return new FixerDefinition(
            'Classes defined internally by extension or core must be referenced with the correct case.',
            [new CodeSample("<?php\n\$foo = new STDClass();\n")]
        );
    }

    public function getPriority(): int
    {
        return 0;
    }

    public function isCandidate(Tokens $tokens): bool
    {
        return $tokens->isTokenKindFound(\T_STRING);
    }

    public function isRisky(): bool
    {
        return false;
    }

    public function fix(\SplFileInfo $file, Tokens $tokens): void
    {
        $namespaces = (new NamespacesAnalyzer())->getDeclarations($tokens);

        foreach ($namespaces as $namespace) {
            $this->fixCasing($tokens, $namespace->getScopeStartIndex(), $namespace->getScopeEndIndex(), $namespace->getFullName() === '');
        }
    }

    private function fixCasing(Tokens $tokens, int $startIndex, int $endIndex, bool $isInGlobalNamespace): void
    {
        for ($index = $startIndex; $index < $endIndex; $index++) {
            if (!$tokens[$index]->isGivenKind(\T_STRING)) {
                continue;
            }

            if (!$this->isGlobalClassUsage($tokens, $index, $isInGlobalNamespace)) {
                continue;
            }

            $correctCase = $this->getCorrectCase($tokens[$index]->getContent());

            if ($correctCase === $tokens[$index]->getContent()) {
                continue;
            }

            $tokens[$index] = new Token([\T_STRING, $correctCase]);
        }
    }

    private function isGlobalClassUsage(Tokens $tokens, int $index, bool $isInGlobalNamespace): bool
    {
        /** @var int $prevIndex */
        $prevIndex = $tokens->getPrevMeaningfulToken($index);

        if ($tokens[$prevIndex]->isGivenKind(\T_NS_SEPARATOR)) {
            /** @var int $prevIndex */
            $prevIndex = $tokens->getPrevMeaningfulToken($prevIndex);

            if ($tokens[$prevIndex]->isGivenKind(\T_STRING)) {
                return false;
            }
        } elseif (!$isInGlobalNamespace) {
            return false;
        }

        if ($tokens[$prevIndex]->isGivenKind([\T_AS, \T_CLASS, \T_CONST, \T_DOUBLE_COLON, \T_OBJECT_OPERATOR, CT::T_USE_TRAIT])) {
            return false;
        }

        /** @var int $nextIndex */
        $nextIndex = $tokens->getNextMeaningfulToken($index);

        if ($tokens[$nextIndex]->isGivenKind(\T_NS_SEPARATOR)) {
            return false;
        }

        return $tokens[$prevIndex]->isGivenKind([\T_NEW]) || !$tokens[$nextIndex]->equals('(');
    }

    private function getCorrectCase(string $className): string
    {
        /** @var null|array<string, string> $classes */
        static $classes;

        if ($classes === null) {
            $classes = [];
            foreach (\get_declared_classes() as $class) {
                if ((new \ReflectionClass($class))->isInternal()) {
                    $classes[\strtolower($class)] = $class;
                }
            }
        }

        $lowercaseClassName = \strtolower($className);

        if (!isset($classes[$lowercaseClassName])) {
            return $className;
        }

        return $classes[$lowercaseClassName];
    }
}
