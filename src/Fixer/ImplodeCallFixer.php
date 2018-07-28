<?php

declare(strict_types = 1);

namespace PhpCsFixerCustomFixers\Fixer;

use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\Tokenizer\Analyzer\ArgumentsAnalyzer;
use PhpCsFixer\Tokenizer\Analyzer\FunctionsAnalyzer;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;

final class ImplodeCallFixer extends AbstractFixer
{
    public function getDefinition() : FixerDefinition
    {
        return new FixerDefinition(
            'Function `implode` must be called with 2 arguments in the documented order.',
            [new CodeSample('<?php
implode($foo, "") . implode($bar);
')]
        );
    }

    public function isCandidate(Tokens $tokens) : bool
    {
        return $tokens->isTokenKindFound(T_STRING);
    }

    public function fix(\SplFileInfo $file, Tokens $tokens) : void
    {
        $functionsAnalyzer = new FunctionsAnalyzer();

        foreach ($tokens as $index => $token) {
            if (!$functionsAnalyzer->isGlobalFunctionIndex($tokens, $index)) {
                continue;
            }

            // Temporary: fix until https://github.com/FriendsOfPHP/PHP-CS-Fixer/pull/3895 is done
            $prevIndex = $tokens->getPrevMeaningfulToken($index);
            $nextIndex = $tokens->getNextMeaningfulToken($index);
            if ($tokens[$prevIndex]->isGivenKind(T_FUNCTION) || !$tokens[$nextIndex]->equals('(')) {
                continue;
            }

            $lowercaseContent = \strtolower($token->getContent());
            if ($lowercaseContent !== 'implode') {
                continue;
            }

            $argumentsIndices = $this->getArgumentIndices($tokens, $index);

            if (\count($argumentsIndices) === 1) {
                $firstArgumentIndex = \key($argumentsIndices);
                $tokens->insertAt($firstArgumentIndex, [
                    new Token([T_CONSTANT_ENCAPSED_STRING, "''"]),
                    new Token(','),
                    new Token([T_WHITESPACE, ' ']),
                ]);

                continue;
            }

            if (\count($argumentsIndices) === 2) {
                list($firstArgumentIndex, $secondArgumentIndex) = \array_keys($argumentsIndices);

                // If the first argument is string we have nothing to do
                if ($tokens[$firstArgumentIndex]->isGivenKind(T_CONSTANT_ENCAPSED_STRING)) {
                    continue;
                }
                // If the second argument is not string we cannot make a swap
                if (!$tokens[$secondArgumentIndex]->isGivenKind(T_CONSTANT_ENCAPSED_STRING)) {
                    continue;
                }

                // collect tokens from first argument
                $firstArgumenteEndIndex  = $argumentsIndices[\key($argumentsIndices)];
                $newSecondArgumentTokens = [];
                for ($i = \key($argumentsIndices); $i <= $firstArgumenteEndIndex; $i++) {
                    $newSecondArgumentTokens[] = clone $tokens[$i];
                    $tokens->clearAt($i);
                }

                $tokens->insertAt($firstArgumentIndex, clone $tokens[$secondArgumentIndex]);

                // insert above increased the second argument index
                $secondArgumentIndex++;
                $tokens->clearAt($secondArgumentIndex);
                $tokens->insertAt($secondArgumentIndex, $newSecondArgumentTokens);
            }
        }
    }

    public function getPriority() : int
    {
        return 0;
    }

    /**
     * @return array<int, int> In the format: startIndex => endIndex
     */
    private function getArgumentIndices(Tokens $tokens, int $functionNameIndex) : array
    {
        $argumentsAnalyzer = new ArgumentsAnalyzer();

        $openParenthesis  = $tokens->getNextTokenOfKind($functionNameIndex, ['(']);
        $closeParenthesis = $tokens->findBlockEnd(Tokens::BLOCK_TYPE_PARENTHESIS_BRACE, $openParenthesis);

        $indices = [];

        foreach ($argumentsAnalyzer->getArguments($tokens, $openParenthesis, $closeParenthesis) as $startIndexCandidate => $endIndex) {
            $indices[$tokens->getNextMeaningfulToken($startIndexCandidate - 1)] = $tokens->getPrevMeaningfulToken($endIndex + 1);
        }

        return $indices;
    }
}
