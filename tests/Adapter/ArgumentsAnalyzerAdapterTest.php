<?php

declare(strict_types = 1);

namespace Tests\Adapter;

use PhpCsFixer\Tokenizer\Tokens;
use PhpCsFixerCustomFixers\Adapter\ArgumentsAnalyzerAdapter;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @covers \PhpCsFixerCustomFixers\Adapter\ArgumentsAnalyzerAdapter
 */
final class ArgumentsAnalyzerAdapterTest extends TestCase
{
    public function testCountArguments(): void
    {
        $tokens = Tokens::fromCode('<?php function foo($a, $b, $c, $d) {} ');
        $adapter = new ArgumentsAnalyzerAdapter();

        static::assertSame(4, $adapter->countArguments($tokens, 4, 15));
    }

    public function testGetArguments(): void
    {
        $tokens = Tokens::fromCode('<?php function foo($a, $b, $c, $d) {} ');
        $adapter = new ArgumentsAnalyzerAdapter();

        static::assertSame(
            [5 => 5, 7 => 8, 10 => 11, 13 => 14],
            $adapter->getArguments($tokens, 4, 15)
        );
    }
}
