<?php

declare(strict_types = 1);

namespace PhpCsFixerCustomFixers\Fixer;

use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;

final class NoUnneededConcatenationFixer extends AbstractFixer
{
    public function getDefinition(): FixerDefinition
    {
        return new FixerDefinition(
            'There should not be inline concatenation of strings.',
            [new CodeSample("<?php 'foo' . 'bar';\n")]
        );
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
        for ($index = 0; $index < $tokens->count(); $index++) {
            if ($tokens[$index]->getContent() !== '.') {
                continue;
            }

            if ($tokens[$index - 1]->isGivenKind(T_WHITESPACE) && \preg_match('/\R/u', $tokens[$index - 1]->getContent()) === 1) {
                continue;
            }

            if ($tokens[$index + 1]->isGivenKind(T_WHITESPACE) && \preg_match('/\R/u', $tokens[$index + 1]->getContent()) === 1) {
                continue;
            }

            $prevIndex = $tokens->getPrevMeaningfulToken($index);
            if (!$tokens[$prevIndex]->isGivenKind(T_CONSTANT_ENCAPSED_STRING)) {
                continue;
            }
            $stringBorder = $tokens[$prevIndex]->getContent()[0];

            $nextIndex = $tokens->getNextMeaningfulToken($index);
            if (!$tokens[$nextIndex]->isGivenKind(T_CONSTANT_ENCAPSED_STRING)) {
                continue;
            }

            if ($stringBorder !== $tokens[$nextIndex]->getContent()[0]) {
                continue;
            }

            $tokens[$prevIndex] = new Token([T_CONSTANT_ENCAPSED_STRING, \substr($tokens[$prevIndex]->getContent(), 0, -1) . \substr($tokens[$nextIndex]->getContent(), 1)]);
            for ($i = $prevIndex + 1; $i <= $nextIndex; $i++) {
                $tokens->clearAt($i);
            }
        }
    }

    public function getPriority(): int
    {
        return -1;
    }
}
