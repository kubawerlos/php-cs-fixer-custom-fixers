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

use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\FixerDefinition\FixerDefinitionInterface;
use PhpCsFixer\FixerDefinition\VersionSpecification;
use PhpCsFixer\FixerDefinition\VersionSpecificCodeSample;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;

final class TypedPropertiesSpaceFixer extends AbstractFixer
{
    private const CANDIDATE_TOKENS = [T_CLASS, T_TRAIT];

    public function getDefinition(): FixerDefinitionInterface
    {
        return new FixerDefinition(
            'Typed properties must have single space between type and variable.',
            [new VersionSpecificCodeSample(
                '<?php
class Foo {
    private int    $bar;
    private int$baz;
}
',
                new VersionSpecification(70400)
            )]
        );
    }

    public function getPriority(): int
    {
        return 0;
    }

    public function isCandidate(Tokens $tokens): bool
    {
        return $tokens->isAnyTokenKindsFound(self::CANDIDATE_TOKENS);
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

            if (!$token->isGivenKind(self::CANDIDATE_TOKENS)) {
                continue;
            }

            /** @var int $curlyBraceStartIndex */
            $curlyBraceStartIndex = $tokens->getNextTokenOfKind($index, ['{']);

            $curlyBraceEndIndex = $tokens->findBlockEnd(Tokens::BLOCK_TYPE_CURLY_BRACE, $curlyBraceStartIndex);

            $this->fixClassyElement($tokens, $curlyBraceStartIndex + 1, $curlyBraceEndIndex);
        }
    }

    private function fixClassyElement(Tokens $tokens, int $curlyBraceStartIndex, int $curlyBraceEndIndex): void
    {
        $index = $curlyBraceEndIndex;
        while ($index > $curlyBraceStartIndex) {
            $index--;

            /** @var Token $token */
            $token = $tokens[$index];

            if ($token->equals('}')) {
                $index = $tokens->findBlockStart(Tokens::BLOCK_TYPE_CURLY_BRACE, $index);
                continue;
            }

            if ($token->equals(')')) {
                $index = $tokens->findBlockStart(Tokens::BLOCK_TYPE_PARENTHESIS_BRACE, $index);
                continue;
            }

            if (!$token->isGivenKind(T_VARIABLE)) {
                continue;
            }

            /** @var int $prevMeaningfulIndex */
            $prevMeaningfulIndex = $tokens->getPrevMeaningfulToken($index);

            /** @var Token $prevMeaningfulToken */
            $prevMeaningfulToken = $tokens[$prevMeaningfulIndex];
            if ($prevMeaningfulToken->isGivenKind([T_PRIVATE, T_PROTECTED, T_PUBLIC, T_STATIC, T_VAR])) {
                continue;
            }

            $tokens->ensureWhitespaceAtIndex($index - 1, 1, ' ');
        }
    }
}
