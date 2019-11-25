<?php

declare(strict_types = 1);

namespace Tests\Analyzer\Analysis;

use PhpCsFixerCustomFixers\Analyzer\Analysis\ArrayArgumentAnalysis;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @covers \PhpCsFixerCustomFixers\Analyzer\Analysis\ArrayArgumentAnalysis
 */
final class ArrayArgumentAnalysisTest extends TestCase
{
    public function testGetKeyStartIndex(): void
    {
        $analysis = new ArrayArgumentAnalysis(1, 2, 3, 4);
        self::assertSame(1, $analysis->getKeyStartIndex());
    }

    public function testGetKeyEndIndex(): void
    {
        $analysis = new ArrayArgumentAnalysis(1, 2, 3, 4);
        self::assertSame(2, $analysis->getKeyEndIndex());
    }

    public function testGetArgumentStartIndex(): void
    {
        $analysis = new ArrayArgumentAnalysis(1, 2, 3, 4);
        self::assertSame(3, $analysis->getArgumentStartIndex());
    }

    public function testGetArgumentEndIndex(): void
    {
        $analysis = new ArrayArgumentAnalysis(1, 2, 3, 4);
        self::assertSame(4, $analysis->getArgumentEndIndex());
    }
}
