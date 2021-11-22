<?php declare(strict_types=1);

/*
 * This file is part of PHP CS Fixer: custom fixers.
 *
 * (c) 2018 Kuba Werłos
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

final class NoUselessStrlenFixer extends AbstractFixer
{
    public function getDefinition(): FixerDefinitionInterface
    {
        return new FixerDefinition(
            'The `strlen` or` mb_strlen` functions should not be compared against 0.',
            [
                new CodeSample(
                    '<?php
$isEmpty = strlen($string) === 0;
$isNotEmpty = strlen($string) > 0;
'
                ),
            ],
            null,
            'when the function `strlen` is overridden'
        );
    }

    public function getPriority(): int
    {
        return 0;
    }

    public function isCandidate(Tokens $tokens): bool
    {
        return $tokens->isTokenKindFound(\T_LNUMBER) && $tokens->isAnyTokenKindsFound(['>', '<', \T_IS_IDENTICAL, \T_IS_NOT_IDENTICAL, \T_IS_EQUAL, \T_IS_NOT_EQUAL]);
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
            if (!$tokens[$index]->equalsAny([[\T_STRING, 'strlen'], [\T_STRING, 'mb_strlen']], false)) {
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

            $tokensToRemove = [
                $index => 1,
                $openParenthesisIndex => 1,
                $closeParenthesisIndex => -1,
            ];

            /** @var int $prevIndex */
            $prevIndex = $tokens->getPrevMeaningfulToken($index);

            $startIndex = $index;
            if ($tokens[$prevIndex]->isGivenKind(\T_NS_SEPARATOR)) {
                $startIndex = $prevIndex;
                $tokensToRemove[$prevIndex] = 1;
            }

            if (!$this->transformCondition($tokens, $startIndex, $closeParenthesisIndex)) {
                continue;
            }

            $this->removeTokenAndSiblingWhitespace($tokens, $tokensToRemove);
        }
    }

    private function transformCondition(Tokens $tokens, int $startIndex, int $endIndex): bool
    {
        if ($this->transformConditionLeft($tokens, $startIndex)) {
            return true;
        }

        return $this->transformConditionRight($tokens, $endIndex);
    }

    private function transformConditionLeft(Tokens $tokens, int $index): bool
    {
        /** @var int $prevIndex */
        $prevIndex = $tokens->getPrevMeaningfulToken($index);

        $changeCondition = false;
        if ($tokens[$prevIndex]->equals('<')) {
            $changeCondition = true;
        } elseif (!$tokens[$prevIndex]->isGivenKind([\T_IS_IDENTICAL, \T_IS_NOT_IDENTICAL, \T_IS_EQUAL, \T_IS_NOT_EQUAL])) {
            return false;
        }

        /** @var int $prevPrevIndex */
        $prevPrevIndex = $tokens->getPrevMeaningfulToken($prevIndex);

        if (!$tokens[$prevPrevIndex]->equals([\T_LNUMBER, '0'])) {
            return false;
        }

        if ($changeCondition) {
            $tokens[$prevIndex] = new Token([\T_IS_NOT_IDENTICAL, '!==']);
        }

        $tokens[$prevPrevIndex] = new Token([\T_CONSTANT_ENCAPSED_STRING, '\'\'']);

        return true;
    }

    private function transformConditionRight(Tokens $tokens, int $index): bool
    {
        /** @var int $nextIndex */
        $nextIndex = $tokens->getNextMeaningfulToken($index);

        $changeCondition = false;
        if ($tokens[$nextIndex]->equals('>')) {
            $changeCondition = true;
        } elseif (!$tokens[$nextIndex]->isGivenKind([\T_IS_IDENTICAL, \T_IS_NOT_IDENTICAL, \T_IS_EQUAL, \T_IS_NOT_EQUAL])) {
            return false;
        }

        /** @var int $nextNextIndex */
        $nextNextIndex = $tokens->getNextMeaningfulToken($nextIndex);

        if (!$tokens[$nextNextIndex]->equals([\T_LNUMBER, '0'])) {
            return false;
        }

        if ($changeCondition) {
            $tokens[$nextIndex] = new Token([\T_IS_NOT_IDENTICAL, '!==']);
        }

        $tokens[$nextNextIndex] = new Token([\T_CONSTANT_ENCAPSED_STRING, '\'\'']);

        return true;
    }

    /**
     * @param array<int, int> $tokensToRemove
     */
    private function removeTokenAndSiblingWhitespace(Tokens $tokens, array $tokensToRemove): void
    {
        foreach ($tokensToRemove as $index => $direction) {
            $tokens->clearAt($index);

            if ($tokens[$index + $direction]->isWhitespace()) {
                $tokens->clearAt($index + $direction);
            }
        }
    }
}
