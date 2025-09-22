<?php declare(strict_types=1);

/*
 * This file is part of PHP CS Fixer: custom fixers.
 *
 * (c) 2018 Kuba Werłos
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Tests\Fixer;

use PhpCsFixerCustomFixers\Fixer\PhpdocPropertySortedFixer;

/**
 * @internal
 *
 * @covers \PhpCsFixerCustomFixers\Fixer\PhpDocPropertySorterFixer
 */
final class PhpDocPropertySorterFixerTest extends AbstractFixerTestCase
{
    public function testSuccessorName(): void
    {
        self::assertSuccessorName(PhpdocPropertySortedFixer::name());
    }

    public function testIsRisky(): void
    {
        self::assertRiskiness(false);
    }

    /**
     * @dataProvider provideFixCases
     */
    public function testFix(string $expected, ?string $input = null): void
    {
        $this->doTest($expected, $input);
    }

    /**
     * @return iterable<array{0: string, 1?: string}>
     */
    public static function provideFixCases(): iterable
    {
        yield from PhpdocPropertySortedFixerTest::provideFixCases();
    }
}
