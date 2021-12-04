<?php declare(strict_types=1);

/*
 * This file is part of PHP CS Fixer: custom fixers.
 *
 * (c) 2018 Kuba Werłos
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Tests;

use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;

trait AssertSameTokensTrait
{
    private static function assertSameTokens(Tokens $expectedTokens, Tokens $inputTokens): void
    {
        self::assertCount($expectedTokens->count(), $inputTokens, 'Both collections must have the same size.');

        /** @var Token $expectedToken */
        foreach ($expectedTokens as $index => $expectedToken) {
            $inputToken = $inputTokens[$index];
            \assert($inputToken instanceof Token);

            self::assertTrue(
                $expectedToken->equals($inputToken),
                \sprintf("Token at index %d must be:\n%s,\ngot:\n%s.", $index, $expectedToken->toJson(), $inputToken->toJson())
            );
        }
    }
}
