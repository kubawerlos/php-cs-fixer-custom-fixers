<?php

/*
 * This file is part of PHP CS Fixer: custom fixers.
 *
 * (c) 2018 Kuba Werłos
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Tests;

use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;

trait AssertSameTokensTrait
{
    private static function assertSameTokens(Tokens $expectedTokens, Tokens $inputTokens): void
    {
        self::assertSame($expectedTokens->count(), $inputTokens->count(), 'Both collections must have the same size.');

        foreach ($expectedTokens as $index => $expectedToken) {
            /** @var Token $inputToken */
            $inputToken = $inputTokens[$index];

            self::assertTrue(
                $expectedToken->equals($inputToken),
                \sprintf("Token at index %d must be:\n%s,\ngot:\n%s.", $index, $expectedToken->toJson(), $inputToken->toJson())
            );
        }
    }
}
