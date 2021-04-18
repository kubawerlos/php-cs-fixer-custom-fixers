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
use PhpCsFixer\Tokenizer\CT;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;

final class NoLeadingSlashInGlobalNamespaceFixer extends AbstractFixer
{
    public function getDefinition(): FixerDefinitionInterface
    {
        return new FixerDefinition(
            'When in global namespace there must be no leading slash for class.',
            [new CodeSample('<?php
$x = new \Foo();
namespace Bar;
$y = new \Baz();
')]
        );
    }

    public function getPriority(): int
    {
        return 0;
    }

    public function isCandidate(Tokens $tokens): bool
    {
        return $tokens->isTokenKindFound(\T_NS_SEPARATOR);
    }

    public function isRisky(): bool
    {
        return false;
    }

    public function fix(\SplFileInfo $file, Tokens $tokens): void
    {
        $index = 0;
        while (++$index < $tokens->count()) {
            $index = $this->skipNamespacedCode($tokens, $index);

            if (!$this->isToRemove($tokens, $index)) {
                continue;
            }

            $tokens->clearTokenAndMergeSurroundingWhitespace($index);
        }
    }

    private function isToRemove(Tokens $tokens, int $index): bool
    {
        /** @var Token $token */
        $token = $tokens[$index];

        if (!$token->isGivenKind(\T_NS_SEPARATOR)) {
            return false;
        }

        /** @var int $prevIndex */
        $prevIndex = $tokens->getPrevMeaningfulToken($index);

        /** @var Token $prevToken */
        $prevToken = $tokens[$prevIndex];

        if ($prevToken->isGivenKind(\T_STRING)) {
            return false;
        }
        if ($prevToken->isGivenKind([\T_NEW, CT::T_NULLABLE_TYPE, CT::T_TYPE_COLON])) {
            return true;
        }

        /** @var int $nextIndex */
        $nextIndex = $tokens->getTokenNotOfKindSibling($index, 1, [[\T_COMMENT], [\T_DOC_COMMENT], [\T_NS_SEPARATOR], [\T_STRING], [\T_WHITESPACE]]);

        /** @var Token $nextToken */
        $nextToken = $tokens[$nextIndex];

        if ($nextToken->isGivenKind(\T_DOUBLE_COLON)) {
            return true;
        }

        return $prevToken->equalsAny(['(', ',', [CT::T_TYPE_ALTERNATION]]) && $nextToken->isGivenKind([\T_VARIABLE, CT::T_TYPE_ALTERNATION]);
    }

    private function skipNamespacedCode(Tokens $tokens, int $index): int
    {
        /** @var Token $token */
        $token = $tokens[$index];

        if (!$token->isGivenKind(\T_NAMESPACE)) {
            return $index;
        }

        /** @var int $nextIndex */
        $nextIndex = $tokens->getNextMeaningfulToken($index);

        /** @var Token $nextToken */
        $nextToken = $tokens[$nextIndex];

        if ($nextToken->equals('{')) {
            return $nextIndex;
        }

        /** @var int $nextIndex */
        $nextIndex = $tokens->getNextTokenOfKind($index, ['{', ';']);

        /** @var Token $nextToken */
        $nextToken = $tokens[$nextIndex];

        if ($nextToken->equals(';')) {
            return $tokens->count() - 1;
        }

        return $tokens->findBlockEnd(Tokens::BLOCK_TYPE_CURLY_BRACE, $nextIndex);
    }
}
