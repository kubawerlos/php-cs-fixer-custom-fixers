<?php

declare(strict_types = 1);

namespace PhpCsFixerCustomFixers\Fixer;

use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\FixerDefinition\FixerDefinitionInterface;
use PhpCsFixer\Preg;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;

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
        // must be run after CommentToPhpdocFixer and PhpdocAddMissingParamAnnotationFixer
        // must be run before PhpdocAlignFixer
        return -2;
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
