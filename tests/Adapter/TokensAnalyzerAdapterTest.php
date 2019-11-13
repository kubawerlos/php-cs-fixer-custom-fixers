<?php

declare(strict_types = 1);

namespace Tests\Adapter;

use PhpCsFixer\Tokenizer\Tokens;
use PhpCsFixerCustomFixers\Adapter\TokensAnalyzerAdapter;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @covers \PhpCsFixerCustomFixers\Adapter\TokensAnalyzerAdapter
 */
final class TokensAnalyzerAdapterTest extends TestCase
{
    public function testIsAnonymousClass(): void
    {
        $tokens = Tokens::fromCode('<?php new class() {};');

        $adapter = new TokensAnalyzerAdapter($tokens);

        static::assertTrue($adapter->isAnonymousClass(3));
    }
}
