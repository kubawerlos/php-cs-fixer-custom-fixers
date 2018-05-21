<?php

declare(strict_types = 1);

namespace PhpCsFixerCustomFixers\Fixer;

use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;

final class NoTwoConsecutiveEmptyLinesFixer extends AbstractFixer
{
    public function getDefinition() : FixerDefinition
    {
        return new FixerDefinition(
            'There should be no two consecutive empty lines in code.',
            [new CodeSample('<?php
namespace Foo;


class Foo {};
')]
        );
    }

    public function isCandidate(Tokens $tokens) : bool
    {
        return true;
    }

    public function fix(\SplFileInfo $file, Tokens $tokens) : void
    {
        foreach ($tokens as $index => $token) {
            if (!$token->isGivenKind(T_WHITESPACE)) {
                continue;
            }

            $prevToken = $tokens[$index - 1];
            if ($prevToken->isGivenKind(T_OPEN_TAG)) {
                $this->removeConsecutiveNewLines($tokens, $index, 1);
                continue;
            }

            $this->removeConsecutiveNewLines($tokens, $index, 2);
        }
    }

    public function getPriority() : int
    {
        // must be run after NoTrailingWhitespaceFixer and NoWhitespaceInBlankLineFixer
        return -20;
    }

    private function removeConsecutiveNewLines(Tokens $tokens, int $index, int $numberOfLinesToRemove) : void
    {
        $content = $tokens[$index]->getContent();

        $newContent = \preg_replace(\sprintf('/(\R{%d})\R+/', $numberOfLinesToRemove), '$1', $content);

        if ($newContent !== $content) {
            $tokens[$index] = new Token([T_WHITESPACE, $newContent]);
        }
    }
}
