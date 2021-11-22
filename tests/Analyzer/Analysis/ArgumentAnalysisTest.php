<?php declare(strict_types=1);

/*
 * This file is part of PHP CS Fixer: custom fixers.
 *
 * (c) 2018 Kuba Werłos
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Tests\Analyzer\Analysis;

use PhpCsFixerCustomFixers\Analyzer\Analysis\ArgumentAnalysis;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @covers \PhpCsFixerCustomFixers\Analyzer\Analysis\ArgumentAnalysis
 */
final class ArgumentAnalysisTest extends TestCase
{
    public function testColonIndex(): void
    {
        $analysis = new ArgumentAnalysis(1, 2, true);

        self::assertSame(1, $analysis->getStartIndex());
        self::assertSame(2, $analysis->getEndIndex());
        self::assertTrue($analysis->isConstant());
    }
}
