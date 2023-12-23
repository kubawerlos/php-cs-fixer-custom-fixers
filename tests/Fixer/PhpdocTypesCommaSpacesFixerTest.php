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
 * @covers \PhpCsFixerCustomFixers\Fixer\PhpdocTypesCommaSpacesFixer
 */
final class PhpdocTypesCommaSpacesFixerTest extends AbstractFixerTestCase
{
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
        yield ['<?php /** @var int $commaCount Number of "," in text. */'];

        yield [
            '<?php /** @var array<int, string> */',
            '<?php /** @var array<int,string> */',
        ];

        yield [
            '<?php /** @var array<int, string> */',
            '<?php /** @var array<int    ,    string> */',
        ];

        yield [
            '<?php
                /** @var array<int, string> $a */
                /** @var array<int, string> $b */
                /** @var array<int, string> $c */
                /** @var array<int, string> $d */
            ',
            '<?php
                /** @var array<int    ,string> $a */
                /** @var array<int,    string> $b */
                /** @var array<int, string> $c */
                /** @var array<int    ,    string> $d */
            ',
        ];

        yield [
            '<?php /**
                    * @param array<int, string> $a
                    * @param array<int, string> $b
                    * @param array<int, string> $c
                    * @param array<int, array<int, array<int, string>>> $d
                    */',
            '<?php /**
                    * @param array<int,string> $a
                    * @param array<int ,string> $b
                    * @param array<int , string> $c
                    * @param array<int    ,    array<int    ,    array<int    ,    string>>> $d
                    */',
        ];

        yield [
            '<?php /**
                    * @return array<    Foo, Bar, Baz    >
                    */',
            '<?php /**
                    * @return array<    Foo    ,    Bar    ,    Baz    >
                    */',
        ];

        yield [
            '<?php /**
                    * The "," in here should not be touched
                    *
                    * @param array<int, int> $x Comma in type should be fixed, but this one: "," should not
                    * @param array<int, int> $y Comma in type should be fixed, but this one: "," and "," should not
                    *
                    * @return array<string, Foo> Description having "," should not be touched
                    */',
            '<?php /**
                    * The "," in here should not be touched
                    *
                    * @param array<int,int> $x Comma in type should be fixed, but this one: "," should not
                    * @param array<int , int> $y Comma in type should be fixed, but this one: "," and "," should not
                    *
                    * @return array<string,Foo> Description having "," should not be touched
                    */',
        ];
    }
}
