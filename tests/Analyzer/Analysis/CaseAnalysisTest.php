<?php

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
