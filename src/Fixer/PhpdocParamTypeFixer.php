<?php

declare(strict_types=1);

/*
 * This file is part of PHP CS Fixer: custom fixers.
 *
 * (c) 2018-2020 Kuba Werłos
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

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

    public function fix(\SplFileInfo $file, Tokens $tokens): void
    {
        for ($index = $tokens->count() - 1; $index > 0; $index--) {
            /** @var Token $token */
            $token = $tokens[$index];

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
