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
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;

final class NoNullableBooleanTypeFixer extends AbstractFixer
{
    public function getDefinition(): FixerDefinitionInterface
    {
        return new FixerDefinition(
            'There must be no nullable boolean type.',
            [new CodeSample('<?php
function foo(?bool $bar) : ?bool
{
     return $bar;
 }
')],
            null,
            'when the null is used'
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
        return true;
    }

    public function fix(\SplFileInfo $file, Tokens $tokens): void
    {
        for ($index = $tokens->count() - 1; $index > 0; $index--) {
            /** @var Token $token */
            $token = $tokens[$index];

            if ($token->getContent() !== '?') {
                continue;
            }

            /** @var int $nextIndex */
            $nextIndex = $tokens->getNextMeaningfulToken($index);

            /** @var Token $nextToken */
            $nextToken = $tokens[$nextIndex];

            if (!$nextToken->equals([T_STRING, 'bool'], false) && !$nextToken->equals([T_STRING, 'boolean'], false)) {
                continue;
            }

            /** @var int $nextNextIndex */
            $nextNextIndex = $tokens->getNextMeaningfulToken($nextIndex);

            /** @var Token $nextNextToken */
            $nextNextToken = $tokens[$nextNextIndex];

            if (!$nextNextToken->isGivenKind(T_VARIABLE) && $nextNextToken->getContent() !== '{') {
                continue;
            }

            $tokens->clearTokenAndMergeSurroundingWhitespace($index);
        }
    }
}
