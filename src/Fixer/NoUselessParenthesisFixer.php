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
            'There can be no useless parentheses.',
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

            if ($prevToken->isGivenKind(\T_RETURN)) {
                $tokens->ensureWhitespaceAtIndex($prevIndex + 1, 0, ' ');
            }
        }
    }

    private function isBlockToRemove(Tokens $tokens, int $startIndex, int $endIndex): bool
    {
        $blocksAnalyzer = new BlocksAnalyzer();

        // is there a block of parenthesis inside?
        /** @var int $nextStartIndex */
        $nextStartIndex = $tokens->getNextMeaningfulToken($startIndex);
        /** @var Token $nextStartToken */
        $nextStartToken = $tokens[$nextStartIndex];
        if ($nextStartToken->equalsAny(['(', [CT::T_BRACE_CLASS_INSTANTIATION_OPEN]])) {
            /** @var int $prevEndIndex */
            $prevEndIndex = $tokens->getPrevMeaningfulToken($endIndex);
            if ($blocksAnalyzer->isBlock($tokens, $nextStartIndex, $prevEndIndex)) {
                return true;
            }
        }

        // is there a block of parenthesis outside?
        /** @var int $prevStartIndex */
        $prevStartIndex = $tokens->getPrevMeaningfulToken($startIndex);
        /** @var int $nextEndIndex */
        $nextEndIndex = $tokens->getNextMeaningfulToken($endIndex);
        if ($blocksAnalyzer->isBlock($tokens, $prevStartIndex, $nextEndIndex)) {
            return true;
        }

        // is there assignment, return or throw before?
        /** @var Token $prevStartToken */
        $prevStartToken = $tokens[$prevStartIndex];
        /** @var Token $nextEndToken */
        $nextEndToken = $tokens[$nextEndIndex];

        return $prevStartToken->equalsAny(['=', [\T_RETURN], [\T_THROW]]) && $nextEndToken->equals(';');
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
