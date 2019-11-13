<?php

declare(strict_types = 1);

namespace Tests\Adapter;

use PhpCsFixer\Tokenizer\Analyzer\Analysis\TypeAnalysis;
use PhpCsFixer\Tokenizer\Tokens;
use PhpCsFixerCustomFixers\Adapter\FunctionsAnalyzerAdapter;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @covers \PhpCsFixerCustomFixers\Adapter\FunctionsAnalyzerAdapter
 */
final class FunctionsAnalyzerAdapterTest extends TestCase
{
    public function testIsGlobalFunctionCall(): void
    {
        $tokens = Tokens::fromCode('<?php foo(); ');
        $adapter = new FunctionsAnalyzerAdapter();

        static::assertTrue($adapter->isGlobalFunctionCall($tokens, 1));
    }

    public function testGetFunctionReturnType(): void
    {
        $tokens = Tokens::fromCode('<?php function foo(): string {} ');
        $adapter = new FunctionsAnalyzerAdapter();

        static::assertSame(
            \serialize(new TypeAnalysis('string', 8, 8)),
            \serialize($adapter->getFunctionReturnType($tokens, 3))
        );
    }
}
