<?php

declare(strict_types = 1);

namespace PhpCsFixerCustomFixers\Fixer;

use PhpCsFixer\DocBlock\DocBlock;
use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\FixerDefinition\FixerDefinitionInterface;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;
use PhpCsFixerCustomFixers\Adapter\NamespacesAnalyzerAdapter;
use PhpCsFixerCustomFixers\Adapter\PregAdapter;
use PhpCsFixerCustomFixers\Adapter\TokensAnalyzerAdapter;

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
        foreach (NamespacesAnalyzerAdapter::getDeclarations($tokens) as $namespace) {
            $this->fixPhpdocSelfAccessor($tokens, $namespace->getScopeStartIndex(), $namespace->getScopeEndIndex(), $namespace->getFullName());
        }
    }

    private function fixPhpdocSelfAccessor(Tokens $tokens, int $namespaceStartIndex, int $namespaceEndIndex, string $fullName): void
    {
        $tokensAnalyzer = new TokensAnalyzerAdapter($tokens);

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

            $classyName = $tokens[$nameIndex]->getContent();

            $this->replaceNameOccurrences($tokens, $fullName, $classyName, $startIndex, $endIndex);

            $index = $endIndex;
        }
    }

    private function replaceNameOccurrences(Tokens $tokens, string $namespace, string $classyName, int $startIndex, int $endIndex): void
    {
        for ($index = $startIndex; $index < $endIndex; $index++) {
            if (!$tokens[$index]->isGivenKind(T_DOC_COMMENT)) {
                continue;
            }

            $newContent = $this->getNewContent($tokens[$index]->getContent(), $namespace, $classyName);

            if ($newContent === $tokens[$index]->getContent()) {
                continue;
            }
            $tokens[$index] = new Token([T_DOC_COMMENT, $newContent]);
        }
    }

    private function getNewContent(string $content, string $namespace, string $classyName): string
    {
        $docBlock = new DocBlock($content);

        $fqcn = ($namespace !== '' ? '\\' . $namespace : '') . '\\' . $classyName;

        foreach ($docBlock->getAnnotations() as $annotation) {
            if (!$annotation->supportTypes()) {
                continue;
            }

            $types = [];
            foreach ($annotation->getTypes() as $type) {
                /** @var string $type */
                $type = PregAdapter::replace(
                    \sprintf('/(?<![a-zA-Z0-9_\x7f-\xff\\\\])(%s|%s)\b(?!\\\\)/', $classyName, \preg_quote($fqcn, '/')),
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
