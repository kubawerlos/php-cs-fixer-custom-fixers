<?php

declare(strict_types = 1);

namespace PhpCsFixerCustomFixers\Fixer;

use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\FixerDefinition\FixerDefinitionInterface;
use PhpCsFixer\Preg;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;

final class CommentSurroundedBySpacesFixer extends AbstractFixer
{
    public function getDefinition(): FixerDefinitionInterface
    {
        return new FixerDefinition(
            'comment must be surrounded by spaces',
            [new CodeSample('<?php
/*foo*/
')]
        );
    }

    public function isCandidate(Tokens $tokens): bool
    {
        return $tokens->isAnyTokenKindsFound([T_COMMENT, T_DOC_COMMENT]);
    }

    public function isRisky(): bool
    {
        return false;
    }

    public function fix(\SplFileInfo $file, Tokens $tokens): void
    {
        foreach ($tokens as $index => $token) {
            if (!$token->isGivenKind([T_COMMENT, T_DOC_COMMENT])) {
                continue;
            }

            $newContent = Preg::replace('/^(\/\/|#|\/\*+)((?!(?:\/|\*|\h)).+)$/', '$1 $2', $token->getContent());
            $newContent = Preg::replace('/^(.+(?<!(?:\/|\*|\h)))(\*+\/)$/', '$1 $2', $newContent);

            if ($newContent === $token->getContent()) {
                continue;
            }

            $tokens[$index] = new Token([$token->getId(), $newContent]);
        }
    }

    public function getPriority(): int
    {
        // Must be run after MultilineCommentOpeningClosingFixer
        return -1;
    }
}
