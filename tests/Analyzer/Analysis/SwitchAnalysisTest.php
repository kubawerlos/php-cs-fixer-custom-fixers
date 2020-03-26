<?php

declare(strict_types=1);

namespace Tests\Analyzer\Analysis;

use PhpCsFixerCustomFixers\Analyzer\Analysis\CaseAnalysis;
use PhpCsFixerCustomFixers\Analyzer\Analysis\SwitchAnalysis;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @covers \PhpCsFixerCustomFixers\Analyzer\Analysis\SwitchAnalysis
 */
final class SwitchAnalysisTest extends TestCase
{
    public function testCasesStart(): void
    {
        $analysis = new SwitchAnalysis(10, 20, []);
        self::assertSame(10, $analysis->getCasesStart());
    }

    public function testCasesEnd(): void
    {
        $analysis = new SwitchAnalysis(10, 20, []);
        self::assertSame(20, $analysis->getCasesEnd());
    }

    public function testCases(): void
    {
        $cases = [new CaseAnalysis(12), new CaseAnalysis(16)];

        $analysis = new SwitchAnalysis(10, 20, $cases);
        self::assertSame($cases, $analysis->getCases());
    }
}
