<?php

declare(strict_types = 1);

namespace PhpCsFixerCustomFixers\Fixer;

use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\FixerDefinition\FixerDefinitionInterface;
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
            if ($tokens[$index]->getContent() !== '?') {
                continue;
            }

            /** @var int $nextIndex */
            $nextIndex = $tokens->getNextMeaningfulToken($index);

            if (!$tokens[$nextIndex]->equals([T_STRING, 'bool'], false) && !$tokens[$nextIndex]->equals([T_STRING, 'boolean'], false)) {
                continue;
            }

            $nextNextIndex = $tokens->getNextMeaningfulToken($nextIndex);
            if (!$tokens[$nextNextIndex]->isGivenKind(T_VARIABLE) && $tokens[$nextNextIndex]->getContent() !== '{') {
                continue;
            }

            $tokens->clearTokenAndMergeSurroundingWhitespace($index);
        }
    }
}
