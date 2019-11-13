<?php

declare(strict_types = 1);

namespace PhpCsFixerCustomFixers\Fixer;

use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\FixerDefinition\FixerDefinitionInterface;
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
        return $tokens->isTokenKindFound(T_NS_SEPARATOR);
    }

    public function isRisky(): bool
    {
        return false;
    }

    public function fix(\SplFileInfo $file, Tokens $tokens): void
    {
        for ($index = 0; $index < $tokens->count(); $index++) {
            $token = $tokens[$index];

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

            $nextIndex = $tokens->getTokenNotOfKindSibling($index, 1, [[T_COMMENT], [T_DOC_COMMENT], [T_NS_SEPARATOR], [T_STRING], [T_WHITESPACE]]);
            if ($tokens[$prevIndex]->isGivenKind(T_NEW) || $tokens[$nextIndex]->isGivenKind(T_DOUBLE_COLON)) {
                $tokens->clearTokenAndMergeSurroundingWhitespace($index);
            }
        }
    }
}
