<?php

declare(strict_types = 1);

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
    public function testCurlyBracesStart(): void
    {
        $analysis = new SwitchAnalysis(10, 20, []);
        static::assertSame(10, $analysis->getCurlyBracesStart());
    }

    public function testCurlyBracesEnd(): void
    {
        $analysis = new SwitchAnalysis(10, 20, []);
        static::assertSame(20, $analysis->getCurlyBracesEnd());
    }

    public function testCases(): void
    {
        $cases = [new CaseAnalysis(12), new CaseAnalysis(16)];

        $analysis = new SwitchAnalysis(10, 20, $cases);
        static::assertSame($cases, $analysis->getCases());
    }
}
