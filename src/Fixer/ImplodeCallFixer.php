<?php

declare(strict_types = 1);

namespace PhpCsFixerCustomFixers\Fixer;

use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\Tokenizer\Analyzer\FunctionsAnalyzer;
use PhpCsFixer\Tokenizer\CT;
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
                $firstArgumentIndex = \reset($argumentsIndices);
                $tokens->insertAt($firstArgumentIndex, new Token([T_WHITESPACE, ' ']));
                $tokens->insertAt($firstArgumentIndex, new Token(','));
                $tokens->insertAt($firstArgumentIndex, new Token([T_CONSTANT_ENCAPSED_STRING, "''"]));
                continue;
            }

            if (\count($argumentsIndices) === 2) {
                list($firstArgumentIndex, $secondArgumentIndex) = $argumentsIndices;
                if ($tokens[$firstArgumentIndex]->isGivenKind(T_CONSTANT_ENCAPSED_STRING)) {
                    continue;
                }
                if (!$tokens[$secondArgumentIndex]->isGivenKind(T_CONSTANT_ENCAPSED_STRING)) {
                    continue;
                }

                $insideCommaIndex = $tokens->getPrevTokenOfKind($secondArgumentIndex, [',']);

                $indicesToMove = [$secondArgumentIndex, $insideCommaIndex];

                $insideWhitespaceIndex = $tokens->getPrevTokenOfKind($secondArgumentIndex, [[T_WHITESPACE]]);
                if ($insideWhitespaceIndex > $insideCommaIndex) {
                    $indicesToMove[] = $insideWhitespaceIndex;
                }

                $tokensToInsert = [];
                foreach ($indicesToMove as $indexToRemove) {
                    $tokensToInsert[] = clone $tokens[$indexToRemove];
                    $tokens->clearAt($indexToRemove);
                }

                foreach (\array_reverse($tokensToInsert) as $tokenToInsert) {
                    $tokens->insertAt($firstArgumentIndex, $tokenToInsert);
                }
            }
        }
    }

    public function getPriority() : int
    {
        return 0;
    }

    private function getArgumentIndices(Tokens $tokens, int $functionNameIndex) : array
    {
        $startBracketIndex = $tokens->getNextTokenOfKind($functionNameIndex, ['(']);
        $endBracketIndex   = $tokens->findBlockEnd(Tokens::BLOCK_TYPE_PARENTHESIS_BRACE, $startBracketIndex);
        $argumentsIndices  = [];
        $parameterRecorded = false;

        $index = $startBracketIndex;
        while ($index < $endBracketIndex) {
            $index++;
            $token = $tokens[$index];

            if (!$parameterRecorded && !$token->isWhitespace() && !$token->isComment()) {
                $argumentsIndices[] = $index;
                $parameterRecorded  = true;
            }

            if ($token->equals('(')) {
                $index = $tokens->findBlockEnd(Tokens::BLOCK_TYPE_PARENTHESIS_BRACE, $index);

                continue;
            }

            if ($token->isGivenKind(CT::T_ARRAY_SQUARE_BRACE_OPEN)) {
                $index = $tokens->findBlockEnd(Tokens::BLOCK_TYPE_ARRAY_SQUARE_BRACE, $index);

                continue;
            }

            if ($token->equals(',')) {
                $parameterRecorded = false;
            }
        }

        return $argumentsIndices;
    }
}
