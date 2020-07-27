<?php

/*
 * This file is part of PHP CS Fixer: custom fixers.
 *
 * (c) 2018-2020 Kuba Werłos
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Tests\Analyzer\Analysis;

use PhpCsFixerCustomFixers\Analyzer\Analysis\CaseAnalysis;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @covers \PhpCsFixerCustomFixers\Analyzer\Analysis\CaseAnalysis
 */
final class CaseAnalysisTest extends TestCase
{
    public function testColonIndex(): void
    {
        $analysis = new CaseAnalysis(20);
        self::assertSame(20, $analysis->getColonIndex());
    }
}
