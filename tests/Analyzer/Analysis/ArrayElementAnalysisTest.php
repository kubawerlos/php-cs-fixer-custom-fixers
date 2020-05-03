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

namespace Tests\Analyzer\Analysis;

use PhpCsFixerCustomFixers\Analyzer\Analysis\ArrayElementAnalysis;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @covers \PhpCsFixerCustomFixers\Analyzer\Analysis\ArrayElementAnalysis
 */
final class ArrayElementAnalysisTest extends TestCase
{
    public function testGetKeyStartIndex(): void
    {
        $analysis = new ArrayElementAnalysis(1, 2, 3, 4);
        self::assertSame(1, $analysis->getKeyStartIndex());
    }

    public function testGetKeyEndIndex(): void
    {
        $analysis = new ArrayElementAnalysis(1, 2, 3, 4);
        self::assertSame(2, $analysis->getKeyEndIndex());
    }

    public function testGetValueStartIndex(): void
    {
        $analysis = new ArrayElementAnalysis(1, 2, 3, 4);
        self::assertSame(3, $analysis->getValueStartIndex());
    }

    public function testGetValueEndIndex(): void
    {
        $analysis = new ArrayElementAnalysis(1, 2, 3, 4);
        self::assertSame(4, $analysis->getValueEndIndex());
    }
}
