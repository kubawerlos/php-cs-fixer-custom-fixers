<?php

declare(strict_types = 1);

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
                if (isset($used[$useDeclaration->getFullName()])) {
                    $this->removeUseDeclaration($tokens, $useDeclaration);
                }
                $used[$useDeclaration->getFullName()] = true;
            }
        }
    }

    private function removeUseDeclaration(Tokens $tokens, NamespaceUseAnalysis $useDeclaration): void
    {
        static $noUnusedImportsFixer, $reflectionMethod;

        if ($reflectionMethod === null) {
            $noUnusedImportsFixer = new NoUnusedImportsFixer();

            $reflectionMethod = new \ReflectionMethod($noUnusedImportsFixer, 'removeUseDeclaration');
            $reflectionMethod->setAccessible(true);
        }

        $reflectionMethod->invoke($noUnusedImportsFixer, $tokens, $useDeclaration);
    }
}
