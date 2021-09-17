<?php

/*
 * This file is part of PHP CS Fixer: custom fixers.
 *
 * (c) 2018 Kuba Werłos
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

namespace PhpCsFixerCustomFixers\Fixer;

use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\FixerDefinition\FixerDefinitionInterface;
use PhpCsFixer\Tokenizer\Analyzer\BlocksAnalyzer;
use PhpCsFixer\Tokenizer\CT;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;

final class NoUselessParenthesisFixer extends AbstractFixer
{
    public function getDefinition(): FixerDefinitionInterface
    {
        return new FixerDefinition(
            'There must be no useless parentheses.',
            [
                new CodeSample('<?php
foo(($bar));
'),
            ]
        );
    }

    public function getPriority(): int
    {
        return 0;
    }

    public function isCandidate(Tokens $tokens): bool
    {
        return $tokens->isAnyTokenKindsFound(['(', CT::T_BRACE_CLASS_INSTANTIATION_OPEN]);
    }

    public function isRisky(): bool
    {
        return false;
    }

    public function fix(\SplFileInfo $file, Tokens $tokens): void
    {
        for ($index = 0; $index < $tokens->count(); $index++) {
            /** @var Token $token */
            $token = $tokens[$index];

            if (!$token->equalsAny(['(', [CT::T_BRACE_CLASS_INSTANTIATION_OPEN]])) {
                continue;
            }

            /** @var array{isStart: bool, type: int} $blockType */
            $blockType = Tokens::detectBlockType($token);
            $blockEndIndex = $tokens->findBlockEnd($blockType['type'], $index);

            if (!$this->isBlockToRemove($tokens, $index, $blockEndIndex)) {
                continue;
            }

            $this->clearWhitespace($tokens, $index + 1);
            $this->clearWhitespace($tokens, $blockEndIndex - 1);
            $tokens->clearTokenAndMergeSurroundingWhitespace($index);
            $tokens->clearTokenAndMergeSurroundingWhitespace($blockEndIndex);

            /** @var int $prevIndex */
            $prevIndex = $tokens->getPrevMeaningfulToken($index);

            /** @var Token $prevToken */
            $prevToken = $tokens[$prevIndex];

            if ($prevToken->isGivenKind([\T_RETURN, \T_THROW])) {
                $tokens->ensureWhitespaceAtIndex($prevIndex + 1, 0, ' ');
            }
        }
    }

    private function isBlockToRemove(Tokens $tokens, int $startIndex, int $endIndex): bool
    {
        if ($this->isParenthesisBlockInside($tokens, $startIndex, $endIndex)) {
            return true;
        }

        /** @var int $prevStartIndex */
        $prevStartIndex = $tokens->getPrevMeaningfulToken($startIndex);
        /** @var int $nextEndIndex */
        $nextEndIndex = $tokens->getNextMeaningfulToken($endIndex);

        if ((new BlocksAnalyzer())->isBlock($tokens, $prevStartIndex, $nextEndIndex)) {
            return true;
        }

        /** @var Token $prevStartToken */
        $prevStartToken = $tokens[$prevStartIndex];
        /** @var Token $nextEndToken */
        $nextEndToken = $tokens[$nextEndIndex];

        if ($nextEndToken->equals('(')) {
            return false;
        }

        if ($this->isForbiddenBeforeOpenParenthesis($tokens, $prevStartIndex)) {
            return false;
        }

        if ($this->isExpressionInside($tokens, $startIndex, $endIndex)) {
            return true;
        }

        return $prevStartToken->equalsAny(['=', [\T_RETURN], [\T_THROW]]) && $nextEndToken->equals(';');
    }

    private function isForbiddenBeforeOpenParenthesis(Tokens $tokens, int $index): bool
    {
        /** @var Token $token */
        $token = $tokens[$index];

        if (
            $token->isGivenKind([
                \T_ARRAY,
                \T_CATCH,
                \T_CLASS,
                \T_ELSEIF,
                \T_EMPTY,
                \T_EVAL,
                \T_EXIT,
                \T_FUNCTION,
                \T_IF,
                \T_ISSET,
                \T_LIST,
                \T_STATIC,
                \T_STRING,
                \T_SWITCH,
                \T_UNSET,
                \T_VARIABLE,
                \T_WHILE,
                CT::T_CLASS_CONSTANT,
                CT::T_USE_LAMBDA,
            ])
            || \defined('T_FN') && $token->isGivenKind(\T_FN)
            || \defined('T_MATCH') && $token->isGivenKind(\T_MATCH)
        ) {
            return true;
        }

        /** @var null|array{isStart: bool, type: int} $blockType */
        $blockType = Tokens::detectBlockType($token);

        return $blockType !== null && !$blockType['isStart'];
    }

    private function isParenthesisBlockInside(Tokens $tokens, int $startIndex, int $endIndex): bool
    {
        /** @var int $nextStartIndex */
        $nextStartIndex = $tokens->getNextMeaningfulToken($startIndex);
        /** @var Token $nextStartToken */
        $nextStartToken = $tokens[$nextStartIndex];

        return $nextStartToken->equalsAny(['(', [CT::T_BRACE_CLASS_INSTANTIATION_OPEN]])
            && (new BlocksAnalyzer())->isBlock($tokens, $nextStartIndex, $tokens->getPrevMeaningfulToken($endIndex));
    }

    private function isExpressionInside(Tokens $tokens, int $startIndex, int $endIndex): bool
    {
        $expression = false;

        /** @var int $index */
        $index = $tokens->getNextMeaningfulToken($startIndex);

        while ($index < $endIndex) {
            $expression = true;

            /** @var Token $token */
            $token = $tokens[$index];

            if (
                !$token->isGivenKind([
                    \T_CONSTANT_ENCAPSED_STRING,
                    \T_DNUMBER,
                    \T_DOUBLE_COLON,
                    \T_LNUMBER,
                    \T_OBJECT_OPERATOR,
                    \T_STRING,
                    \T_VARIABLE,
                ]) && !$token->isMagicConstant()
            ) {
                return false;
            }

            /** @var int $index */
            $index = $tokens->getNextMeaningfulToken($index);
        }

        return $expression;
    }

    private function clearWhitespace(Tokens $tokens, int $index): void
    {
        /** @var Token $token */
        $token = $tokens[$index];

        if (!$token->isWhitespace()) {
            return;
        }

        /** @var int $prevIndex */
        $prevIndex = $tokens->getNonEmptySibling($index, -1);

        /** @var Token $prevToken */
        $prevToken = $tokens[$prevIndex];

        if ($prevToken->isComment()) {
            $tokens->ensureWhitespaceAtIndex($index, 0, \rtrim($token->getContent(), " \t"));

            return;
        }

        $tokens->clearAt($index);
    }
}
