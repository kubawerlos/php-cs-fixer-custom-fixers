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
use PhpCsFixer\Tokenizer\Transformer\BraceClassInstantiationTransformer;

final class NoUselessParenthesisFixer extends AbstractFixer
{
    public function getDefinition(): FixerDefinitionInterface
    {
        return new FixerDefinition(
            'There must be no useless parenthesis.',
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

            /** @var array<string, int> $blockType */
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

            if ($prevToken->isGivenKind(T_RETURN)) {
                $tokens->ensureWhitespaceAtIndex($prevIndex + 1, 0, ' ');
            }

            $transformer = new BraceClassInstantiationTransformer();

            /** @var Token $t */
            foreach ($tokens as $i => $t) {
                $transformer->process($tokens, $t, $i);
            }
        }
    }

    private function isBlockToRemove(Tokens $tokens, int $startIndex, int $endIndex): bool
    {
        $blocksAnalyzer = new BlocksAnalyzer();

        /** @var int $prevIndex */
        $prevIndex = $tokens->getPrevMeaningfulToken($startIndex);

        /** @var Token $prevToken */
        $prevToken = $tokens[$prevIndex];

        /** @var int $nextIndex */
        $nextIndex = $tokens->getNextMeaningfulToken($endIndex);

        /** @var Token $nextToken */
        $nextToken = $tokens[$nextIndex];

        return $blocksAnalyzer->isBlock($tokens, $prevIndex, $nextIndex)
            || $prevToken->equalsAny(['=', [T_RETURN], [T_THROW]]) && $nextToken->equals(';');
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
