<?php

declare(strict_types = 1);

namespace PhpCsFixerCustomFixers\Fixer;

use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\FixerDefinition\FixerDefinitionInterface;
use PhpCsFixer\Preg;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;
use PhpCsFixerCustomFixers\Adapter\TokensAdapter;

final class PhpdocParamTypeFixer extends AbstractFixer
{
    public function getDefinition(): FixerDefinitionInterface
    {
        return new FixerDefinition(
            '`@param` must have type.',
            [new CodeSample('<?php
/**
 * @param string $foo
 * @param        $bar
 */
function a($foo, $bar) {}
')]
        );
    }

    public function getPriority(): int
    {
        // must be run after CommentToPhpdocFixer
        // must be run before PhpdocAlignFixer
        return 0;
    }

    public function isCandidate(Tokens $tokens): bool
    {
        return $tokens->isAnyTokenKindsFound([T_COMMENT, T_DOC_COMMENT]);
    }

    public function isRisky(): bool
    {
        return false;
    }

    protected function applyFix(\SplFileInfo $file, TokensAdapter $tokens): void
    {
        foreach ($tokens->toArray() as $index => $token) {
            if (!$token->isGivenKind([T_DOC_COMMENT])) {
                continue;
            }

            if (\stripos($token->getContent(), '@param') === false) {
                continue;
            }

            $newContent = Preg::replace(
                '/(@param) {0,7}( *\$)/i',
                '$1 mixed $2',
                $token->getContent()
            );

            if ($newContent === $token->getContent()) {
                continue;
            }

            $tokens[$index] = new Token([T_DOC_COMMENT, $newContent]);
        }
    }
}
