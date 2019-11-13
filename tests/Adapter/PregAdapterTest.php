<?php

declare(strict_types = 1);

namespace Tests\Adapter;

use PhpCsFixerCustomFixers\Adapter\PregAdapter;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @covers \PhpCsFixerCustomFixers\Adapter\PregAdapter
 */
final class PregAdapterTest extends TestCase
{
    public function testMatch(): void
    {
        static::assertSame(1, PregAdapter::match('/A/', 'AA', $matches));
        static::assertSame(['A'], $matches);
    }

    public function testMatchAll(): void
    {
        static::assertSame(2, PregAdapter::matchAll('/A/', 'AA', $matches));
        static::assertSame([['A', 'A']], $matches);
    }

    public function testReplace(): void
    {
        static::assertSame('BB', PregAdapter::replace('/A/', 'B', 'AA'));
    }
}
