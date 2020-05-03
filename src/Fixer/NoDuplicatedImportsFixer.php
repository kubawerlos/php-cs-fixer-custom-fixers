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

use PhpCsFixer\Fixer\Import\NoUnusedImportsFixer;
use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\FixerDefinition\FixerDefinitionInterface;
use PhpCsFixer\Tokenizer\Analyzer\Analysis\NamespaceUseAnalysis;
use PhpCsFixer\Tokenizer\Analyzer\NamespacesAnalyzer;
use PhpCsFixer\Tokenizer\Analyzer\NamespaceUsesAnalyzer;
use PhpCsFixer\Tokenizer\Tokens;

final class NoDuplicatedImportsFixer extends AbstractFixer
{
    public function getDefinition(): FixerDefinitionInterface
    {
        return new FixerDefinition(
            'Duplicated `use` statements must be removed.',
            [new CodeSample('<?php
namespace FooBar;
use Foo;
use Foo;
use Bar;
')]
        );
    }

    public function getPriority(): int
    {
        return 0;
    }

    public function isCandidate(Tokens $tokens): bool
    {
        return $tokens->isTokenKindFound(T_USE);
    }

    public function isRisky(): bool
    {
        return false;
    }

    public function fix(\SplFileInfo $file, Tokens $tokens): void
    {
        $useDeclarations = (new NamespaceUsesAnalyzer())->getDeclarationsFromTokens($tokens);

        foreach ((new NamespacesAnalyzer())->getDeclarations($tokens) as $namespace) {
            $currentNamespaceUseDeclarations = \array_filter(
                $useDeclarations,
                static function (NamespaceUseAnalysis $useDeclaration) use ($namespace): bool {
                    return $useDeclaration->getStartIndex() >= $namespace->getScopeStartIndex()
                        && $useDeclaration->getEndIndex() <= $namespace->getScopeEndIndex();
                }
            );

            $used = [];

            foreach ($currentNamespaceUseDeclarations as $useDeclaration) {
                $key = $this->getUniqueKey($useDeclaration);
                if (isset($used[$key])) {
                    $this->removeUseDeclaration($tokens, $useDeclaration);
                }
                $used[$key] = true;
            }
        }
    }

    private function getUniqueKey(NamespaceUseAnalysis $useDeclaration): string
    {
        if ($useDeclaration->isClass()) {
            return 'class_' . $useDeclaration->getShortName();
        }
        if ($useDeclaration->isFunction()) {
            return 'function_' . $useDeclaration->getShortName();
        }

        return 'constant_' . $useDeclaration->getShortName();
    }

    private function removeUseDeclaration(Tokens $tokens, NamespaceUseAnalysis $useDeclaration): void
    {
        $noUnusedImportsFixer = new NoUnusedImportsFixer();

        $reflectionMethod = new \ReflectionMethod($noUnusedImportsFixer, 'removeUseDeclaration');
        $reflectionMethod->setAccessible(true);

        $reflectionMethod->invoke($noUnusedImportsFixer, $tokens, $useDeclaration);
    }
}
