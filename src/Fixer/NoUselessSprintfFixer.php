<?php

declare(strict_types=1);

/*
 * This file is part of PHP CS Fixer: custom fixers.
 *
 * (c) 2018-2020 Kuba Werłos
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace PhpCsFixerCustomFixers\Fixer;

use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\FixerDefinition\FixerDefinitionInterface;
use PhpCsFixer\Tokenizer\Analyzer\ArgumentsAnalyzer;
use PhpCsFixer\Tokenizer\Analyzer\FunctionsAnalyzer;
use PhpCsFixer\Tokenizer\Token;
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

    public function fix(\SplFileInfo $file, Tokens $tokens): void
    {
        $argumentsAnalyzer = new ArgumentsAnalyzer();
        $functionsAnalyzer = new FunctionsAnalyzer();

        for ($index = $tokens->count() - 1; $index > 0; $index--) {
            /** @var Token $token */
            $token = $tokens[$index];

            if (!$token->equals([T_STRING, 'sprintf'], false)) {
                continue;
            }

            if (!$functionsAnalyzer->isGlobalFunctionCall($tokens, $index)) {
                continue;
            }

            /** @var int $openParenthesisIndex */
            $openParenthesisIndex = $tokens->getNextTokenOfKind($index, ['(']);

            $closeParenthesisIndex = $tokens->findBlockEnd(Tokens::BLOCK_TYPE_PARENTHESIS_BRACE, $openParenthesisIndex);

            if ($argumentsAnalyzer->countArguments($tokens, $openParenthesisIndex, $closeParenthesisIndex) !== 1) {
                continue;
            }

            /** @var int $afterOpenParenthesisIndex */
            $afterOpenParenthesisIndex = $tokens->getNextMeaningfulToken($openParenthesisIndex);

            /** @var Token $afterOpenParenthesisToken */
            $afterOpenParenthesisToken = $tokens[$afterOpenParenthesisIndex];

            if ($afterOpenParenthesisToken->isGivenKind(T_ELLIPSIS)) {
                continue;
            }

            /** @var int $prevIndex */
            $prevIndex = $tokens->getPrevMeaningfulToken($index);

            /** @var Token $prevToken */
            $prevToken = $tokens[$prevIndex];

            if ($prevToken->isGivenKind(T_NS_SEPARATOR)) {
                $this->removeTokenAndSiblingWhitespace($tokens, $prevIndex, 1);
            }
            $this->removeTokenAndSiblingWhitespace($tokens, $index, 1);
            $this->removeTokenAndSiblingWhitespace($tokens, $openParenthesisIndex, 1);
            $this->removeTokenAndSiblingWhitespace($tokens, $closeParenthesisIndex, -1);
        }
    }

    private function removeTokenAndSiblingWhitespace(Tokens $tokens, int $index, int $direction): void
    {
        $tokens->clearAt($index);

        /** @var Token $siblingToken */
        $siblingToken = $tokens[$index + $direction];

        if ($siblingToken->isWhitespace()) {
            $tokens->clearAt($index + $direction);
        }
    }
}
