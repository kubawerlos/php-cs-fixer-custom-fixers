<?php

declare(strict_types = 1);

namespace PhpCsFixerCustomFixers\Fixer;

use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;

final class NoTwoConsecutiveEmptyCommentLinesFixer extends AbstractFixer
{
    public function getDefinition() : FixerDefinition
    {
        return new FixerDefinition(
            'There should be no two consecutive empty lines in comment or PHPDoc.',
            [new CodeSample('<?php
/**
 * Foo
 *
 *
 * Bar
 */
')]
        );
    }

    public function isCandidate(Tokens $tokens) : bool
    {
        return $tokens->isAnyTokenKindsFound([T_COMMENT, T_DOC_COMMENT]);
    }

    public function fix(\SplFileInfo $file, Tokens $tokens) : void
    {
        foreach ($tokens as $index => $token) {
            if (!$token->isGivenKind([T_COMMENT, T_DOC_COMMENT])) {
                continue;
            }

            $content = $token->getContent();

            $newContent = \preg_replace('/(\h+\*\h*\R\h*\*)(\h*\R\h*\*)*/', '$1', $content);

            if ($newContent !== $content) {
                $tokens[$index] = new Token([$token->getId(), $newContent]);
            }
        }
    }

    public function getPriority() : int
    {
        // must be run after NoTrailingWhitespaceInCommentFixer and PhpdocTrimFixer
        return -6;
    }
}
