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
 * @covers \PhpCsFixerCustomFixers\Fixer\NoTrailingCommaInSinglelineFixer
 */
final class NoTrailingCommaInSinglelineFixerTest extends AbstractFixerTestCase
{
    public function testIsRisky(): void
    {
        self::assertFalse($this->fixer->isRisky());
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
            '<?php $x = [
                1,
                2,
            ];',
        ];

        yield [
            '<?php $x = [1, 2,// foo
];',
        ];

        yield [
            '<?php $array = [1, 2];',
            '<?php $array = [1, 2,];',
        ];

        yield [
            '<?php $array = [1, 2];',
            '<?php $array = [1, 2, ];',
        ];

        yield [
            '<?php $array = [1, 2,
                             3, 4];',
            '<?php $array = [1, 2,
                             3, 4, ];',
        ];

        yield [
            '<?php $array = [1, 2] + [3, 4] + [5, 6];',
            '<?php $array = [1, 2, ] + [3, 4, ] + [5, 6, ];',
        ];

        yield [
            '<?php $array = [1, 2/* foo */];',
            '<?php $array = [1, 2, /* foo */];',
        ];

        yield [
            '<?php $array = array(1, 2);',
            '<?php $array = array(1, 2, );',
        ];

        yield [
            '<?php list($x, $y) = $list;',
            '<?php list($x, $y,) = $list;',
        ];

        yield [
            '<?php [$x, $y] = $list;',
            '<?php [$x, $y,] = $list;',
        ];

        yield [
            '<?php list($x, $y) = $list;',
            '<?php list($x, $y, , ,) = $list;',
        ];

        yield [
            '<?php list($x, $y) = $list;',
            '<?php list($x, $y , , , ) = $list;',
        ];

        yield [
            '<?php foo($x, $y);',
            '<?php foo($x, $y, );',
        ];

        yield [
            '<?php
                $array = [1, 2] + [3, 4] + [5, 6];
                foo(
                    1,
                    2,
                );
                list($x, $y) = $list;
                ',
            '<?php
                $array = [1, 2, ] + [3, 4] + [5, 6, ];
                foo(
                    1,
                    2,
                );
                list($x, $y,) = $list;
                ',
        ];
    }

    /**
     * @requires PHP ^8.0
     * @dataProvider provideFix80Cases
     */
    public function testFix80(string $expected, ?string $input = null): void
    {
        $this->doTest($expected, $input);
    }

    /**
     * @return iterable<array{0: string, 1?: string}>
     */
    public static function provideFix80Cases(): iterable
    {
        yield [
            '<?php function foo($x, $y) {}',
            '<?php function foo($x, $y, ) {}',
        ];

        yield [
            '<?php $f = function ($x, $y) {};',
            '<?php $f = function ($x, $y, ) {};',
        ];
    }
}
