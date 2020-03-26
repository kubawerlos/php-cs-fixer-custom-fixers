<?php

declare(strict_types=1);

namespace Tests\Analyzer\Analysis;

use PhpCsFixerCustomFixers\Analyzer\Analysis\DataProviderAnalysis;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @covers \PhpCsFixerCustomFixers\Analyzer\Analysis\DataProviderAnalysis
 */
final class DataProviderAnalysisTest extends TestCase
{
    public function testGetName(): void
    {
        $analysis = new DataProviderAnalysis('Foo', 1, [2, 3]);
        self::assertSame('Foo', $analysis->getName());
    }

    public function testGetNameIndex(): void
    {
        $analysis = new DataProviderAnalysis('Foo', 1, [2, 3]);
        self::assertSame(1, $analysis->getNameIndex());
    }

    public function testGetUsageIndices(): void
    {
        $analysis = new DataProviderAnalysis('Foo', 1, [2, 3]);
        self::assertSame([2, 3], $analysis->getUsageIndices());
    }
}
