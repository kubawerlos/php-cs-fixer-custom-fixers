<?php

declare(strict_types = 1);

namespace PhpCsFixerCustomFixers\Fixer;

use PhpCsFixer\DocBlock\DocBlock;
use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\FixerDefinition\FixerDefinitionInterface;
use PhpCsFixer\Preg;
use PhpCsFixer\Tokenizer\Analyzer\NamespacesAnalyzer;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;
use PhpCsFixer\Tokenizer\TokensAnalyzer;

final class PhpdocSelfAccessorFixer extends AbstractFixer
{
    public function getDefinition(): FixerDefinitionInterface
    {
        return new FixerDefinition(
            'In PHPDoc inside class or interface element `self` should be preferred over the class name itself.',
            [new CodeSample('<?php
class Foo {
    /**
     * @var Foo
     */
     private $instance;
}
')]
        );
    }

    public function getPriority(): int
    {
        return 0;
    }

    public function isCandidate(Tokens $tokens): bool
    {
        return $tokens->isAnyTokenKindsFound([T_CLASS, T_INTERFACE]) && $tokens->isTokenKindFound(T_DOC_COMMENT);
    }

    public function isRisky(): bool
    {
        return false;
    }

    public function fix(\SplFileInfo $file, Tokens $tokens): void
    {
        $namespaces = (new NamespacesAnalyzer())->getDeclarations($tokens);

        foreach ($namespaces as $namespace) {
            $this->fixPhpdocSelfAccessor($tokens, $namespace->getScopeStartIndex(), $namespace->getScopeEndIndex(), $namespace->getFullName());
        }
    }

    private function fixPhpdocSelfAccessor(Tokens $tokens, int $namespaceStartIndex, int $namespaceEndIndex, string $fullName): void
    {
        $tokensAnalyzer = new TokensAnalyzer($tokens);

        $index = $namespaceStartIndex;
        while ($index < $namespaceEndIndex) {
            $index++;

            if (!$tokens[$index]->isGivenKind([T_CLASS, T_INTERFACE]) || $tokensAnalyzer->isAnonymousClass($index)) {
                continue;
            }

            /** @var int $nameIndex */
            $nameIndex = $tokens->getNextTokenOfKind($index, [[T_STRING]]);

            /** @var int $startIndex */
            $startIndex = $tokens->getNextTokenOfKind($nameIndex, ['{']);

            $endIndex = $tokens->findBlockEnd(Tokens::BLOCK_TYPE_CURLY_BRACE, $startIndex);

            $name = $tokens[$nameIndex]->getContent();

            $this->replaceNameOccurrences($tokens, $fullName, $name, $startIndex, $endIndex);

            $index = $endIndex;
        }
    }

    private function replaceNameOccurrences(Tokens $tokens, string $namespace, string $name, int $startIndex, int $endIndex): void
    {
        for ($index = $startIndex; $index < $endIndex; $index++) {
            if (!$tokens[$index]->isGivenKind(T_DOC_COMMENT)) {
                continue;
            }

            $newContent = $this->getNewContent($tokens[$index]->getContent(), $namespace, $name);

            if ($newContent === $tokens[$index]->getContent()) {
                continue;
            }
            $tokens[$index] = new Token([T_DOC_COMMENT, $newContent]);
        }
    }

    private function getNewContent(string $content, string $namespace, string $name): string
    {
        $docBlock = new DocBlock($content);

        $fqcn = ($namespace !== '' ? '\\' . $namespace : '') . '\\' . $name;

        foreach ($docBlock->getAnnotations() as $annotation) {
            if (!$annotation->supportTypes()) {
                continue;
            }

            $types = [];
            foreach ($annotation->getTypes() as $type) {
                /** @var string $type */
                $type = Preg::replace(
                    \sprintf('/(?<![a-zA-Z0-9_\x7f-\xff\\\\])(%s|\Q%s\E)\b(?!\\\\)/', $name, $fqcn),
                    'self',
                    $type
                );

                $types[] = $type;
            }

            $annotation->setTypes($types);
        }

        return $docBlock->getContent();
    }
}
