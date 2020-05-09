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

namespace Tests\Fixer;

/**
 * @internal
 *
 * @covers \PhpCsFixerCustomFixers\Fixer\TypedPropertiesSpaceFixer
 *
 * @requires PHP 7.4
 */
final class TypedPropertiesSpaceFixerTest extends AbstractFixerTestCase
{
    /**
     * @dataProvider provideFixCases
     */
    public function testFix(string $expected, ?string $input = null): void
    {
        $this->doTest($expected, $input);
    }

    public static function provideFixCases(): iterable
    {
        yield [
            '<?php class Foo {
                public            $a;
                protected         $b;
                private           $c;
                static            $d;
                var               $e;
            }',
        ];

        yield [
            '<?php class Foo {
                public int $bar;
                public function baz (    $x    ,    $y    ) {
                               return    $x    +    $y;     }
            }',
            '<?php class Foo {
                public int    $bar;
                public function baz (    $x    ,    $y    ) {
                               return    $x    +    $y;     }
            }',
        ];

        yield [
            '<?php new class () {
                public int $bar;
            };',
            '<?php new class () {
                public int    $bar;
            };',
        ];

        yield [
            '<?php trait Foo {
                public int $bar;
            }',
            '<?php trait Foo {
                public int    $bar;
            }',
        ];

        yield [
            '<?php class Foo {
                public int $bar;
            }',
            '<?php class Foo {
                public int$bar;
            }',
        ];

        yield [
            '<?php class Foo {
                public int $a;
                protected int $b;
                private int $c;
                static int $d;
                var int $e;
            }',
            '<?php class Foo {
                public int        $a;
                protected int     $b;
                private int       $c;
                static int        $d;
                var int           $e;
            }',
        ];

        yield [
            '<?php class Foo {
                public            $a;
                public int $b;
                public            $c;
                public int $d;
            }',
            '<?php class Foo {
                public            $a;
                public int        $b;
                public            $c;
                public int$d;
            }',
        ];
    }
}
