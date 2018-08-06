<?php

declare(strict_types = 1);

namespace PhpCsFixerCustomFixers\Fixer;

use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\Tokenizer\Analyzer\ArgumentsAnalyzer;
use PhpCsFixer\Tokenizer\Tokens;

final class NoReferenceInFunctionDefinitionFixer extends AbstractFixer
{
    public function getDefinition() : FixerDefinition
    {
        return new FixerDefinition(
            'There must be no reference in function definition.',
            [new CodeSample('<?php
function foo(&$x) {}
')],
            null,
            'When rely on reference.'
        );
    }

    public function isCandidate(Tokens $tokens) : bool
    {
        return $tokens->isAnyTokenKindsFound([T_FUNCTION]);
    }

    public function isRisky() : bool
    {
        return true;
    }

    public function fix(\SplFileInfo $file, Tokens $tokens) : void
    {
        foreach ($tokens as $index => $token) {
            if (!$token->isGivenKind(T_FUNCTION)) {
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

    public function getPriority() : int
    {
        return 0;
    }

    /**
     * @return int[]
     */
    private function getArgumentStartIndices(Tokens $tokens, int $functionNameIndex) : array
    {
        $argumentsAnalyzer = new ArgumentsAnalyzer();

        $openParenthesis  = $tokens->getNextTokenOfKind($functionNameIndex, ['(']);
        $closeParenthesis = $tokens->findBlockEnd(Tokens::BLOCK_TYPE_PARENTHESIS_BRACE, $openParenthesis);

        $indices = [];

        foreach (\array_keys($argumentsAnalyzer->getArguments($tokens, $openParenthesis, $closeParenthesis)) as $startIndexCandidate) {
            $indices[] = $tokens->getNextMeaningfulToken($startIndexCandidate - 1);
        }

        return $indices;
    }
}
