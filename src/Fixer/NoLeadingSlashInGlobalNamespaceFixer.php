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
use PhpCsFixer\Tokenizer\Token;
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
            /** @var Token $token */
            $token = $tokens[$index];

            if ($token->isGivenKind(T_NAMESPACE)) {
                return;
            }

            if (!$token->isGivenKind(T_NS_SEPARATOR)) {
                continue;
            }

            /** @var int $prevIndex */
            $prevIndex = $tokens->getPrevMeaningfulToken($index);

            /** @var Token $prevToken */
            $prevToken = $tokens[$prevIndex];

            if ($prevToken->isGivenKind(T_STRING)) {
                continue;
            }

            /** @var int $nextIndex */
            $nextIndex = $tokens->getTokenNotOfKindSibling($index, 1, [[T_COMMENT], [T_DOC_COMMENT], [T_NS_SEPARATOR], [T_STRING], [T_WHITESPACE]]);

            /** @var Token $nextToken */
            $nextToken = $tokens[$nextIndex];

            if ($prevToken->isGivenKind(T_NEW) || $nextToken->isGivenKind(T_DOUBLE_COLON)) {
                $tokens->clearTokenAndMergeSurroundingWhitespace($index);
            }
        }
    }
}
