<?php

declare(strict_types=1);

namespace PhpCsFixerCustomFixers\Fixer;

use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\FixerDefinition\FixerDefinitionInterface;
use PhpCsFixer\Preg;
use PhpCsFixer\Tokenizer\CT;
use PhpCsFixer\Tokenizer\Tokens;
use PhpCsFixerCustomFixers\Analyzer\Analysis\ArrayElementAnalysis;
use PhpCsFixerCustomFixers\Analyzer\ArrayAnalyzer;
use PhpCsFixerCustomFixers\TokenRemover;

final class NoDuplicatedArrayKeyFixer extends AbstractFixer
{
    public function getDefinition(): FixerDefinitionInterface
    {
        return new FixerDefinition(
            'Duplicated array keys must be removed.',
            [new CodeSample('<?php
$x = [
    "foo" => 1,
    "bar" => 2,
    "foo" => 3,
];
')]
        );
    }

    public function getPriority(): int
    {
        return 0;
    }

    public function isCandidate(Tokens $tokens): bool
    {
        return $tokens->isAnyTokenKindsFound([T_ARRAY, CT::T_ARRAY_SQUARE_BRACE_OPEN]);
    }

    public function isRisky(): bool
    {
        return false;
    }

    public function fix(\SplFileInfo $file, Tokens $tokens): void
    {
        for ($index = $tokens->count() - 1; $index > 0; $index--) {
            if (!$tokens[$index]->isGivenKind([T_ARRAY, CT::T_ARRAY_SQUARE_BRACE_OPEN])) {
                continue;
            }

            $this->fixArray($tokens, $index);
        }
    }

    private function fixArray(Tokens $tokens, int $index): void
    {
        $arrayAnalyzer = new ArrayAnalyzer();

        $keys = [];
        foreach (\array_reverse($arrayAnalyzer->getElements($tokens, $index)) as $arrayElementAnalysis) {
            $key = $this->getKeyContentIfPossible($tokens, $arrayElementAnalysis);
            if ($key === null) {
                continue;
            }
            if (isset($keys[$key])) {
                /** @var int $startIndex */
                $startIndex = $arrayElementAnalysis->getKeyStartIndex();

                /** @var int $endIndex */
                $endIndex = $tokens->getNextMeaningfulToken($arrayElementAnalysis->getValueEndIndex());
                if ($tokens[$endIndex + 1]->isWhitespace() && Preg::match('/^\h+$/', $tokens[$endIndex + 1]->getContent()) === 1) {
                    $endIndex++;
                }

                $tokens->clearRange($startIndex + 1, $endIndex);
                TokenRemover::removeWithLinesIfPossible($tokens, $startIndex);
            }
            $keys[$key] = true;
        }
    }

    private function getKeyContentIfPossible(Tokens $tokens, ArrayElementAnalysis $arrayElementAnalysis): ?string
    {
        if ($arrayElementAnalysis->getKeyStartIndex() === null || $arrayElementAnalysis->getKeyEndIndex() === null) {
            return null;
        }

        $content = '';
        for ($index = $arrayElementAnalysis->getKeyEndIndex(); $index >= $arrayElementAnalysis->getKeyStartIndex(); $index--) {
            if ($tokens[$index]->isWhitespace() || $tokens[$index]->isComment()) {
                continue;
            }
            if ($tokens[$index]->equalsAny([[T_VARIABLE], '('])) {
                return null;
            }
            $content .= $tokens[$index]->getContent();
        }

        return $content;
    }
}
