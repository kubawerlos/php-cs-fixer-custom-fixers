<?php

declare(strict_types = 1);

namespace PhpCsFixerCustomFixers\Fixer;

use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\FixerDefinition\FixerDefinitionInterface;
use PhpCsFixer\Preg;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;

final class NoSuperfluousConcatenationFixer extends AbstractFixer
{
    public function getDefinition(): FixerDefinitionInterface
    {
        return new FixerDefinition(
            'There should not be superfluous inline concatenation of strings.',
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
            if (Preg::match('/\R/', $tokens[$index]->getContent()) === 1) {
                return false;
            }
        }

        return true;
    }

    private function fixConcat(Tokens $tokens, int $firstIndex, int $secondIndex): void
    {
        $firstStringContent = $tokens[$firstIndex]->getContent();
        $secondStringContent = $tokens[$secondIndex]->getContent();

        if ($firstStringContent[\strlen($firstStringContent) - 1] !== $secondStringContent[\strlen($secondStringContent) - 1]) {
            return;
        }

        $tokens->overrideRange(
            $firstIndex,
            $secondIndex,
            [new Token([
                T_CONSTANT_ENCAPSED_STRING,
                \substr($firstStringContent, 0, -1) . $this->getStringContent($secondStringContent) . $firstStringContent[\strlen($firstStringContent) - 1],
            ])]
        );
    }

    private function getStringContent(string $string): string
    {
        $offset = \strtolower($string[0]) === 'b' ? 2 : 1;

        return \substr($string, $offset, -1);
    }
}
