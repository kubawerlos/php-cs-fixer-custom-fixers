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
            /** @var Token $token */
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

            /** @var Token $nextToken */
            $nextToken = $tokens[$nextIndex];

            if ($nextToken->isGivenKind([T_CLASS, T_INTERFACE, T_TRAIT])) {
                $newContent = Preg::replace(
                    '/\R?(?<=\n|\r|\r\n|^#|^\/\/|^\/\*|^\/\*\*)\h+\**\h*(class|interface|trait)\h+[A-Za-z0-9\\\\_]+.?(?=\R|$)/i',
                    '',
                    $token->getContent()
                );
            } elseif ($nextToken->isGivenKind(T_FUNCTION)) {
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
