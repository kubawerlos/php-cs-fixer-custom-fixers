<?php

declare(strict_types = 1);

namespace PhpCsFixerCustomFixers\Fixer;

use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\FixerDefinition\FixerDefinitionInterface;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;
use PhpCsFixerCustomFixers\Adapter\PregAdapter;

final class NoUnneededConcatenationFixer extends AbstractFixer
{
    public function getDefinition(): FixerDefinitionInterface
    {
        return new FixerDefinition(
            'There should not be inline concatenation of strings.',
            [new CodeSample("<?php\necho 'foo' . 'bar';\n")]
        );
    }

    public function getPriority(): int
    {
        // must be run after SingleLineThrowFixer and SingleQuoteFixer
        return -1;
    }

    public function isCandidate(Tokens $tokens): bool
    {
        return $tokens->isAllTokenKindsFound(['.', T_CONSTANT_ENCAPSED_STRING]);
    }

    public function isRisky(): bool
    {
        return false;
    }

    public function fix(\SplFileInfo $file, Tokens $tokens): void
    {
        for ($index = $tokens->count() - 1; $index > 0; $index--) {
            if (!$tokens[$index]->equals('.')) {
                continue;
            }

            /** @var int $prevIndex */
            $prevIndex = $tokens->getPrevMeaningfulToken($index);
            if (!$tokens[$prevIndex]->isGivenKind(T_CONSTANT_ENCAPSED_STRING)) {
                continue;
            }
            if (!$this->areOnlyHorizontalWhitespacesBetween($tokens, $prevIndex, $index)) {
                continue;
            }

            /** @var int $nextIndex */
            $nextIndex = $tokens->getNextMeaningfulToken($index);
            if (!$tokens[$nextIndex]->isGivenKind(T_CONSTANT_ENCAPSED_STRING)) {
                continue;
            }
            if (!$this->areOnlyHorizontalWhitespacesBetween($tokens, $index, $nextIndex)) {
                continue;
            }

            $this->fixConcat($tokens, $prevIndex, $nextIndex);
        }
    }

    private function areOnlyHorizontalWhitespacesBetween(Tokens $tokens, int $indexStart, int $indexEnd): bool
    {
        for ($index = $indexStart + 1; $index < $indexEnd; $index++) {
            if (!$tokens[$index]->isGivenKind(T_WHITESPACE)) {
                return false;
            }
            if (PregAdapter::match('/\R/', $tokens[$index]->getContent()) === 1) {
                return false;
            }
        }

        return true;
    }

    private function fixConcat(Tokens $tokens, int $prevIndex, int $nextIndex): void
    {
        if ($tokens[$prevIndex]->getContent()[0] !== $tokens[$nextIndex]->getContent()[0]) {
            return;
        }

        $tokens->overrideRange(
            $prevIndex,
            $nextIndex,
            [new Token([T_CONSTANT_ENCAPSED_STRING, \substr($tokens[$prevIndex]->getContent(), 0, -1) . \substr($tokens[$nextIndex]->getContent(), 1)])]
        );
    }
}
