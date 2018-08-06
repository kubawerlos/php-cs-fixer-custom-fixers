<?php

declare(strict_types = 1);

namespace PhpCsFixerCustomFixers\Fixer;

use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;

final class NoUselessConstructorCommentFixer extends AbstractFixer
{
    public function getDefinition(): FixerDefinition
    {
        return new FixerDefinition(
            'There must be no comment like: "Foo constructor".',
            [new CodeSample('<?php
class Foo {
    /**
     * Foo constructor
     */
    public function __construct() {}
}
')]
        );
    }

    public function isCandidate(Tokens $tokens): bool
    {
        return $tokens->isAnyTokenKindsFound([T_COMMENT, T_DOC_COMMENT]);
    }

    public function isRisky(): bool
    {
        return false;
    }

    public function fix(\SplFileInfo $file, Tokens $tokens): void
    {
        foreach ($tokens as $index => $token) {
            if (!$token->isGivenKind([T_COMMENT, T_DOC_COMMENT])) {
                continue;
            }

            if (\stripos($token->getContent(), 'constructor') === false) {
                continue;
            }

            $newContent = \preg_replace(
                '/(\*|\/\/)\h*(\h+[A-Za-z0-1\\\\_]+\h*)?\hconstructor\.?(\h*\R\h*\*|\h*$)/i',
                '$1',
                $token->getContent()
            );

            if ($newContent === $token->getContent()) {
                continue;
            }

            $tokens[$index] = new Token([$token->getId(), $newContent]);
        }
    }

    public function getPriority(): int
    {
        // must be run before NoEmptyPhpdocFixer, NoEmptyCommentFixer and PhpdocTrimFixer
        return 6;
    }
}
