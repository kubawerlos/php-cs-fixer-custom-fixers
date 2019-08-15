<?php

declare(strict_types = 1);

namespace PhpCsFixerCustomFixers\Fixer;

use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\FixerDefinition\FixerDefinitionInterface;
use PhpCsFixer\Tokenizer\Analyzer\ArgumentsAnalyzer;
use PhpCsFixer\Tokenizer\Analyzer\FunctionsAnalyzer;
use PhpCsFixer\Tokenizer\Tokens;

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
        $argumentsAnalyzer = new ArgumentsAnalyzer();
        $functionsAnalyzer = new FunctionsAnalyzer();

        foreach ($tokens as $index => $token) {
            if (!$token->equals([T_STRING, 'sprintf'], false)) {
                continue;
            }

            if (!$functionsAnalyzer->isGlobalFunctionCall($tokens, $index)) {
                continue;
            }

            /** @var int $openParenthesis */
            $openParenthesis = $tokens->getNextTokenOfKind($index, ['(']);

            $closeParenthesis = $tokens->findBlockEnd(Tokens::BLOCK_TYPE_PARENTHESIS_BRACE, $openParenthesis);

            if ($argumentsAnalyzer->countArguments($tokens, $openParenthesis, $closeParenthesis) !== 1) {
                continue;
            }

            $prevIndex = $tokens->getPrevMeaningfulToken($index);
            if ($tokens[$prevIndex]->isGivenKind(T_NS_SEPARATOR)) {
                $tokens->clearAt($prevIndex);
            }
            $tokens->clearAt($index);
            $tokens->clearAt($openParenthesis);
            if ($tokens[$openParenthesis + 1]->isWhitespace()) {
                $tokens->clearAt($openParenthesis + 1);
            }
            if ($tokens[$closeParenthesis - 1]->isWhitespace()) {
                $tokens->clearAt($closeParenthesis - 1);
            }
            $tokens->clearAt($closeParenthesis);
        }
    }

    public function getPriority(): int
    {
        return 0;
    }
}
