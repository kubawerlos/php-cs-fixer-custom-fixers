<?php

declare(strict_types = 1);

namespace PhpCsFixerCustomFixers\Fixer;

use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\FixerDefinition\FixerDefinitionInterface;
use PhpCsFixer\Tokenizer\Analyzer\ArgumentsAnalyzer;
use PhpCsFixer\Tokenizer\Tokens;

final class NoReferenceInFunctionDefinitionFixer extends AbstractFixer
{
    public function getDefinition(): FixerDefinitionInterface
    {
        return new FixerDefinition(
            'There must be no reference in function definition.',
            [new CodeSample('<?php
function foo(&$x) {}
')],
            null,
            'when rely on reference'
        );
    }

    public function getPriority(): int
    {
        return 0;
    }

    public function isCandidate(Tokens $tokens): bool
    {
        return $tokens->isAnyTokenKindsFound([T_FUNCTION]);
    }

    public function isRisky(): bool
    {
        return true;
    }

    public function fix(\SplFileInfo $file, Tokens $tokens): void
    {
        for ($index = $tokens->count() - 1; $index > 0; $index--) {
            if (!$tokens[$index]->isGivenKind(T_FUNCTION)) {
                continue;
            }

            $indices = $this->getArgumentStartIndices($tokens, $index);

            foreach ($indices as $i) {
                if ($tokens[$i]->getContent() === '&') {
                    $tokens->clearTokenAndMergeSurroundingWhitespace($i);
                }
            }
        }
    }

    /**
     * @return int[]
     */
    private function getArgumentStartIndices(Tokens $tokens, int $functionNameIndex): array
    {
        $argumentsAnalyzer = new ArgumentsAnalyzer();

        /** @var int $openParenthesis */
        $openParenthesis = $tokens->getNextTokenOfKind($functionNameIndex, ['(']);

        $closeParenthesis = $tokens->findBlockEnd(Tokens::BLOCK_TYPE_PARENTHESIS_BRACE, $openParenthesis);

        $indices = [];

        foreach (\array_keys($argumentsAnalyzer->getArguments($tokens, $openParenthesis, $closeParenthesis)) as $startIndexCandidate) {
            /** @var int $index */
            $index = $tokens->getNextMeaningfulToken($startIndexCandidate - 1);

            $indices[] = $index;
        }

        return $indices;
    }
}
