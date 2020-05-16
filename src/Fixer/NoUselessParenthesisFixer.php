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
use PhpCsFixer\Tokenizer\Analyzer\BlocksAnalyzer;
use PhpCsFixer\Tokenizer\CT;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;

final class NoUselessParenthesisFixer extends AbstractFixer
{
    private const PARENTHESIS_TOKENS = ['(',  CT::T_BRACE_CLASS_INSTANTIATION_OPEN];
    private const BLOCK_START_TOKENS = ['{', '(', '[', [CT::T_ARRAY_INDEX_CURLY_BRACE_OPEN], [CT::T_ARRAY_SQUARE_BRACE_OPEN], [CT::T_BRACE_CLASS_INSTANTIATION_OPEN]];

    /**
     * {@inheritdoc}
     */
    public function getDefinition(): FixerDefinitionInterface
    {
        return new FixerDefinition(
            'There must be no useless parenthesis.',
            [
                new CodeSample('<?php
foo (($bar));
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
        return $tokens->isAnyTokenKindsFound(['(',  CT::T_BRACE_CLASS_INSTANTIATION_OPEN]);
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

            /** @var int $prevIndex */
            $prevIndex = $tokens->getPrevMeaningfulToken($index);

            /** @var Token $prevToken */
            $prevToken = $tokens[$prevIndex];

            if ($prevToken->isGivenKind([T_ARRAY, T_CLASS, T_STATIC, T_STRING, T_VARIABLE])) {
                continue;
            }

            /** @var array<string, int> $blockType */
            $blockType = Tokens::detectBlockType($token);
            $blockEndIndex = $tokens->findBlockEnd($blockType['type'], $index);

            if (!$this->isBlockToRemove($tokens, $index, $blockEndIndex)) {
                continue;
            }

            $tokens->clearAt($index);
            $tokens->clearAt($blockEndIndex);
            $this->clearWhitespace($tokens, $index + 1);
            $this->clearWhitespace($tokens, $blockEndIndex - 1);

            if ($prevToken->isGivenKind(T_RETURN)) {
                $tokens->ensureWhitespaceAtIndex($prevIndex + 1, 0, ' ');
            }
        }
    }

    private function isBlockToRemove(Tokens $tokens, int $startIndex, int $endIndex): bool
    {
        $blocksAnalyzer = new BlocksAnalyzer();

        /** @var int $prevStartIndex */
        $prevStartIndex = $tokens->getPrevMeaningfulToken($startIndex);

        /** @var Token $prevStartToken */
        $prevStartToken = $tokens[$prevStartIndex];

        /** @var int $nextEndIndex */
        $nextEndIndex = $tokens->getNextMeaningfulToken($endIndex);

        /** @var Token $nextEndToken */
        $nextEndToken = $tokens[$nextEndIndex];

        if ($blocksAnalyzer->isBlock($tokens, $prevStartIndex, $nextEndIndex)) {
            return true;
        }

        if ($prevStartToken->equalsAny(['=', [T_RETURN]]) && $nextEndToken->equals(';')) {
            return true;
        }

        /** @var int $nextStartIndex */
        $nextStartIndex = $tokens->getNextMeaningfulToken($startIndex);

        /** @var int $prevEndIndex */
        $prevEndIndex = $tokens->getPrevMeaningfulToken($endIndex);

        return $blocksAnalyzer->isBlock($tokens, $nextStartIndex, $prevEndIndex);
    }

    private function clearWhitespace(Tokens $tokens, int $index): void
    {
        /** @var Token $token */
        $token = $tokens[$index];

        if (!$token->isWhitespace()) {
            return;
        }

        $tokens->ensureWhitespaceAtIndex($index, 0, \rtrim($token->getContent(), " \t"));
    }
}
