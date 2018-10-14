<?php

declare(strict_types = 1);

namespace PhpCsFixerCustomFixers\Fixer;

use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\Preg;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;

final class PhpdocVarAnnotationCorrectOrderFixer extends AbstractFixer implements DeprecatingFixerInterface
{
    public function getDefinition(): FixerDefinition
    {
        return new FixerDefinition(
            '`@var` annotation must have type and name in the correct order.',
            [new CodeSample('<?php
/** @var $foo int */
$foo = 2 + 2;
')]
        );
    }

    public function isCandidate(Tokens $tokens): bool
    {
        return $tokens->isTokenKindFound(T_DOC_COMMENT);
    }

    public function isRisky(): bool
    {
        return false;
    }

    public function fix(\SplFileInfo $file, Tokens $tokens): void
    {
        foreach ($tokens as $index => $token) {
            if (!$token->isGivenKind(T_DOC_COMMENT)) {
                continue;
            }

            if (\stripos($token->getContent(), '@var') === false) {
                continue;
            }

            $newContent = Preg::replace(
                '/(@var\s*)(\$[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)(\s+)([^\$](?:[^<\s]|<[^>]*>)*)(\s|\*)/i',
                '$1$4$3$2$5',
                $token->getContent()
            );

            if ($newContent === $token->getContent()) {
                continue;
            }

            $tokens[$index] = new Token([$token->getId(), $newContent]);
        }
    }

    public function getPriority(): int
    {
        // must be before PhpdocNoIncorrectVarAnnotationFixer
        return 7;
    }

    public function getPullRequestId(): int
    {
        return 3881;
    }
}
