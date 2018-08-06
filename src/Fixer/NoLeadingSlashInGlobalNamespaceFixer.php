<?php

declare(strict_types = 1);

namespace PhpCsFixerCustomFixers\Fixer;

use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\Tokenizer\Tokens;

final class NoLeadingSlashInGlobalNamespaceFixer extends AbstractFixer
{
    public function getDefinition(): FixerDefinition
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

    public function isCandidate(Tokens $tokens): bool
    {
        return $tokens->isTokenKindFound(T_NS_SEPARATOR);
    }

    public function isRisky(): bool
    {
        return false;
    }

    public function fix(\SplFileInfo $file, Tokens $tokens): void
    {
        foreach ($tokens as $index => $token) {
            if ($token->isGivenKind(T_NAMESPACE)) {
                return;
            }

            if (!$token->isGivenKind(T_NS_SEPARATOR)) {
                continue;
            }

            $prevIndex = $tokens->getPrevMeaningfulToken($index);
            if ($tokens[$prevIndex]->isGivenKind(T_STRING)) {
                continue;
            }

            $tokens->clearTokenAndMergeSurroundingWhitespace($index);
        }
    }

    public function getPriority(): int
    {
        // must be run before PhpdocToCommentFixer
        return 26;
    }
}
