<?php

declare(strict_types = 1);

namespace Tests\Adapter;

use PhpCsFixerCustomFixers\Adapter\UtilsAdapter;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @covers \PhpCsFixerCustomFixers\Adapter\UtilsAdapter
 */
final class UtilsAdapterTest extends TestCase
{
    public function testNaturalLanguageJoinWithBackticks(): void
    {
        static::assertSame(
            '`foo`, `bar` and `baz`',
            UtilsAdapter::naturalLanguageJoinWithBackticks(['foo', 'bar', 'baz'])
        );
    }
}
