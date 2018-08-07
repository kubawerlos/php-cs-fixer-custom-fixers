<?php

declare(strict_types = 1);

namespace PhpCsFixerCustomFixers\Fixer;

use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;
use PhpCsFixer\Tokenizer\TokensAnalyzer;

final class PhpdocSelfAccessorFixer extends AbstractFixer
{
    public function getDefinition(): FixerDefinition
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
        $tokensAnalyzer = new TokensAnalyzer($tokens);

        for ($index = 0, $c = $tokens->count(); $index < $c; $index++) {
            if (!$tokens[$index]->isGivenKind([T_CLASS, T_INTERFACE]) || $tokensAnalyzer->isAnonymousClass($index)) {
                continue;
            }

            $nameIndex  = $tokens->getNextTokenOfKind($index, [[T_STRING]]);
            $startIndex = $tokens->getNextTokenOfKind($nameIndex, ['{']);
            $endIndex   = $tokens->findBlockEnd(Tokens::BLOCK_TYPE_CURLY_BRACE, $startIndex);

            $name = $tokens[$nameIndex]->getContent();

            $this->replaceNameOccurrences($tokens, $name, $startIndex, $endIndex);

            $index = $endIndex;
        }
    }

    public function getPriority(): int
    {
        return 0;
    }

    private function replaceNameOccurrences(Tokens $tokens, string $name, int $startIndex, int $endIndex): void
    {
        for ($index = $startIndex; $index < $endIndex; $index++) {
            if (!$tokens[$index]->isGivenKind(T_DOC_COMMENT)) {
                continue;
            }

            $newContent = \preg_replace(
                \sprintf('/(@[a-zA-Z]+) %s\b(?!\\\\)/i', $name),
                '$1 self',
                $tokens[$index]->getContent()
            );

            if ($newContent === $tokens[$index]->getContent()) {
                continue;
            }

            $tokens[$index] = new Token([T_DOC_COMMENT, $newContent]);
        }
    }
}
