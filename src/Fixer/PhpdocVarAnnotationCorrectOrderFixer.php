<?php

declare(strict_types = 1);

namespace PhpCsFixerCustomFixers\Fixer;

use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;

final class PhpdocVarAnnotationCorrectOrderFixer extends AbstractFixer
{
    public function getDefinition() : FixerDefinition
    {
        return new FixerDefinition(
            'In `@var` type and variable must be in correct order.',
            [new CodeSample('<?php
/** @var $foo int */
$foo = 2 + 2;
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

            if (\stripos($token->getContent(), '@var') === false) {
                continue;
            }

            $newContent = \preg_replace(
                '/(@var\s*)(\$\S+)(\s+)(\S+)\b/i',
                '$1$4$3$2',
                $token->getContent()
            );

            if ($newContent === $token->getContent()) {
                continue;
            }

            $tokens[$index] = new Token([$token->getId(), $newContent]);
        }
    }

    public function getPriority() : int
    {
        // must be before PhpdocNoIncorrectVarAnnotationFixer
        return 7;
    }
}
