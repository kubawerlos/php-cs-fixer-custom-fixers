<?php

declare(strict_types = 1);

namespace PhpCsFixerCustomFixers\Fixer;

use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\FixerDefinition\FixerDefinitionInterface;
use PhpCsFixer\Preg;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;

final class NoUselessCommentFixer extends AbstractFixer
{
    /**
     * {@inheritdoc}
     */
    public function getDefinition(): FixerDefinitionInterface
    {
        return new FixerDefinition(
            'There must be no comment like "Class Foo".',
            [
                new CodeSample('<?php
/**
 * Class Foo
 * Class to do something
 */
class Foo {
    /**
     * Get bar
     */
    function getBar() {}
}
'),
            ]
        );
    }

    public function getPriority(): int
    {
        // must be run before NoEmptyCommentFixer, NoEmptyPhpdocFixer, PhpdocTrimConsecutiveBlankLineSeparationFixer and PhpdocTrimFixer
        return 6;
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
        for ($index = $tokens->count() - 1; $index > 0; $index--) {
            $token = $tokens[$index];

            if (!$token->isGivenKind([T_COMMENT, T_DOC_COMMENT])) {
                continue;
            }

            $nextIndex = $tokens->getTokenNotOfKindSibling(
                $index,
                1,
                [[T_WHITESPACE], [T_COMMENT], [T_ABSTRACT], [T_FINAL], [T_PUBLIC], [T_PROTECTED], [T_PRIVATE], [T_STATIC]]
            );
            if ($nextIndex === null) {
                return;
            }

            if ($tokens[$nextIndex]->isGivenKind([T_CLASS, T_INTERFACE, T_TRAIT])) {
                $newContent = Preg::replace(
                    '/\R?(?<=\n|\r|\r\n|^#|^\/\/|^\/\*|^\/\*\*)\h+\**\h*(class|interface|trait)\h+[A-Za-z0-9\\\\_]+.?(?=\R|$)/i',
                    '',
                    $token->getContent()
                );
            } elseif ($tokens[$nextIndex]->isGivenKind(T_FUNCTION)) {
                $newContent = Preg::replace(
                    '/\R?(?<=\n|\r|\r\n|^#|^\/\/|^\/\*|^\/\*\*)\h+\**\h*((adds?|gets?|removes?|sets?)\h+[A-Za-z0-9\\\\_]+|([A-Za-z0-9\\\\_]+\h+)?constructor).?(?=\R|$)/i',
                    '',
                    $token->getContent()
                );
            } else {
                continue;
            }

            if ($newContent === $token->getContent()) {
                continue;
            }

            $tokens[$index] = new Token([$token->getId(), $newContent]);
        }
    }
}
