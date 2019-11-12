<?php

declare(strict_types = 1);

namespace PhpCsFixerCustomFixers\Fixer;

use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\FixerDefinition\FixerDefinitionInterface;
use PhpCsFixer\Tokenizer\Analyzer\ArgumentsAnalyzer;
use PhpCsFixer\Tokenizer\Analyzer\FunctionsAnalyzer;
use PhpCsFixer\Tokenizer\Tokens;
use PhpCsFixerCustomFixers\Adapter\TokensAdapter;

final class NoUselessSprintfFixer extends AbstractFixer
{
    public function getDefinition(): FixerDefinitionInterface
    {
        return new FixerDefinition(
            'Function `sprintf` without parameters should not be used.',
            [new CodeSample("<?php\n\$foo = sprintf('Foo');\n")],
            null,
            'when the function `sprintf` is overridden'
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

    protected function applyFix(\SplFileInfo $file, TokensAdapter $tokens): void
    {
        $argumentsAnalyzer = new ArgumentsAnalyzer();
        $functionsAnalyzer = new FunctionsAnalyzer();

        foreach ($tokens->toArray() as $index => $token) {
            if (!$token->equals([T_STRING, 'sprintf'], false)) {
                continue;
            }

            if (!$functionsAnalyzer->isGlobalFunctionCall($tokens->tokens(), $index)) {
                continue;
            }

            $openParenthesis = $tokens->getNextTokenOfKind($index, ['(']);

            $closeParenthesis = $tokens->findBlockEnd(Tokens::BLOCK_TYPE_PARENTHESIS_BRACE, $openParenthesis);

            if ($argumentsAnalyzer->countArguments($tokens->tokens(), $openParenthesis, $closeParenthesis) !== 1) {
                continue;
            }

            $prevIndex = $tokens->getPrevMeaningfulToken($index);
            if ($tokens[$prevIndex]->isGivenKind(T_NS_SEPARATOR)) {
                $this->removeTokenAndSiblingWhitespace($tokens, $prevIndex, 1);
            }
            $this->removeTokenAndSiblingWhitespace($tokens, $index, 1);
            $this->removeTokenAndSiblingWhitespace($tokens, $openParenthesis, 1);
            $this->removeTokenAndSiblingWhitespace($tokens, $closeParenthesis, -1);
        }
    }

    private function removeTokenAndSiblingWhitespace(TokensAdapter $tokens, int $index, int $direction): void
    {
        $tokens->clearAt($index);
        if ($tokens[$index + $direction]->isWhitespace()) {
            $tokens->clearAt($index + $direction);
        }
    }
}
