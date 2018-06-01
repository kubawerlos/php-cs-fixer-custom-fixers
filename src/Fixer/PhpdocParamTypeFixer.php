<?php

declare(strict_types = 1);

namespace PhpCsFixerCustomFixers\Fixer;

use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;

final class PhpdocParamTypeFixer extends AbstractFixer
{
    public function getDefinition() : FixerDefinition
    {
        return new FixerDefinition(
            'Adds missing type for `@param` annotation.',
            [new CodeSample('<?php
/**
 * @param string $foo
 * @param        $bar
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
            if (!$token->isGivenKind([T_DOC_COMMENT])) {
                continue;
            }

            if (\stripos($token->getContent(), '@param') === false) {
                continue;
            }

            $newContent = \preg_replace(
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

    public function getPriority() : int
    {
        // must be run after CommentToPhpdocFixer and PhpdocAddMissingParamAnnotationFixer
        // must be run before PhpdocAlignFixer
        return -2;
    }
}
