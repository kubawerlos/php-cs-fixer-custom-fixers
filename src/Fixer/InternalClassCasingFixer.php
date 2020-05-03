<?php

declare(strict_types=1);

/*
 * This file is part of PHP CS Fixer: custom fixers.
 *
 * (c) 2018-2020 Kuba Werłos
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

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
            'Class defined internally by an extension, or the core should be called using the correct casing.',
            [new CodeSample("<?php\n\$foo = new STDClass();\n")]
        );
    }

    public function getPriority(): int
    {
        return 0;
    }

    public function isCandidate(Tokens $tokens): bool
    {
        return $tokens->isTokenKindFound(T_STRING);
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
            /** @var Token $token */
            $token = $tokens[$index];

            if (!$token->isGivenKind(T_STRING)) {
                continue;
            }

            if (!$this->isGlobalClassUsage($tokens, $index, $isInGlobalNamespace)) {
                continue;
            }

            $correctCase = $this->getCorrectCase($token->getContent());

            if ($correctCase === $token->getContent()) {
                continue;
            }

            $tokens[$index] = new Token([T_STRING, $correctCase]);
        }
    }

    private function isGlobalClassUsage(Tokens $tokens, int $index, bool $isInGlobalNamespace): bool
    {
        /** @var int $prevIndex */
        $prevIndex = $tokens->getPrevMeaningfulToken($index);

        /** @var Token $prevToken */
        $prevToken = $tokens[$prevIndex];

        if ($prevToken->isGivenKind(T_NS_SEPARATOR)) {
            /** @var int $prevIndex */
            $prevIndex = $tokens->getPrevMeaningfulToken($prevIndex);

            /** @var Token $prevToken */
            $prevToken = $tokens[$prevIndex];

            if ($prevToken->isGivenKind(T_STRING)) {
                return false;
            }
        } elseif (!$isInGlobalNamespace) {
            return false;
        }

        if ($prevToken->isGivenKind([T_AS, T_CLASS, T_CONST, T_DOUBLE_COLON, T_OBJECT_OPERATOR, CT::T_USE_TRAIT])) {
            return false;
        }

        /** @var int $nextIndex */
        $nextIndex = $tokens->getNextMeaningfulToken($index);

        /** @var Token $nextToken */
        $nextToken = $tokens[$nextIndex];

        if ($nextToken->isGivenKind(T_NS_SEPARATOR)) {
            return false;
        }

        return $prevToken->isGivenKind([T_NEW]) || !$nextToken->equals('(');
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
