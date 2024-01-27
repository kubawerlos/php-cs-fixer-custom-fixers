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

/**
 * @internal
 *
 * @covers \PhpCsFixerCustomFixers\Fixer\PhpdocTypeListFixer
 */
final class PhpdocTypeListFixerTest extends AbstractFixerTestCase
{
    public function testIsRisky(): void
    {
        self::assertRiskiness(false);
    }

    public function testSuccessorName(): void
    {
        self::assertSuccessorName('phpdoc_array_style');
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
        yield [
            '<?php
                /** @var array<int, string> */
                /** @var array<int, array<string, bool>> */
                /** @var array<int, array{string, string, string}> */
            ',
        ];

        yield [
            '<?php /** @var list<string> */',
            '<?php /** @var array<string> */',
        ];

        yield [
            '<?php /** @var list<list<list<string>>> */',
            '<?php /** @var array<array<array<string>>> */',
        ];

        yield [
            '<?php /** @var list<array<int, list<bool>>> */',
            '<?php /** @var array<array<int, array<bool>>> */',
        ];

        yield [
            '<?php /** @var array<int, list<array<string, bool>>> */',
            '<?php /** @var array<int, array<array<string, bool>>> */',
        ];

        yield [
            '<?php /** @var list<callable(int, int): string> */',
            '<?php /** @var array<callable(int, int): string> */',
        ];

        yield [
            '<?php /** @var non-empty-list<Type> */',
            '<?php /** @var non-empty-array<Type> */',
        ];
    }
}
