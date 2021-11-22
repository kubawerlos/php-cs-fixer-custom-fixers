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
 * @covers \PhpCsFixerCustomFixers\Fixer\NoCommentedOutCodeFixer
 */
final class NoCommentedOutCodeFixerTest extends AbstractFixerTestCase
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
        yield ['<?php // do not remove me'];
        yield ['<?php # do not remove me'];
        yield ['<?php /* do not remove me */'];
        yield ['<?php /** do not remove me */'];
        yield ['<?php
                    /**
                     * do not remove me
                     */',
        ];

        yield [
            '<?php
                // To foo
                //
                // or not to foo?
            ',
        ];

        yield ['<?php // var_dump("no semicolon after")'];

        yield [
            "<?php\n",
            "<?php\n// var_dump('x');",
        ];

        yield [
            "<?php\n",
            "<?php\n/// var_dump('x');",
        ];

        yield [
            "<?php\n",
            "<?php\n# var_dump('x');",
        ];

        yield [
            "<?php\n",
            "<?php\n/* var_dump('x'); */",
        ];

        yield [
            "<?php\n",
            "<?php\n/** var_dump('x'); */",
        ];

        yield [
            '<?php
              ',
            '<?php
                  // if (true) {
                  //     return 42;
                  // }
              ',
        ];

        yield [
            '<?php
              ',
            '<?php
                  // // if (true) {
                  // //     return 42;
                  // // }
              ',
        ];

        yield [
            '<?php
              ',
            '<?php
                  /* if (true) {
                   *     return 42;
                   * }
                   */
              ',
        ];

        yield [
            '<?php
                  // This line is a comment
              ',
            '<?php
                  // if (true) {
                  //     return 42;
                  // }
                  // This line is a comment
                  // if (false) {
                  //     return 100;
                  // }
              ',
        ];

        yield [
            '<?php
              ',
            '<?php
                  // /**
                  //  * remove me
                  //  */
              ',
        ];

        yield [
            '<?php
              ',
            '<?php
                  // $foo;
                  //
                  // $bar;
              ',
        ];

        yield [
            '<?php
            ',
            '<?php
                //
                // $foo;
            ',
        ];

        yield [
            '<?php
                // Keep this
            ',
            '<?php
                // // Foo
                // Keep this
                // // Bar
            ',
        ];

        yield [
            '<?php
                class Foo { // comment 1
                }
            ',
            '<?php
                class Foo { // comment 1
                    // public function bar() // comment 2
                    // { // comment 3
                    // } // comment 4
                }
            ',
        ];

        yield [
            '<?php
                 /* // Hello
                  * @see www.example.com
                  */
            ',
        ];

        yield [
            '<?php
            class Foo {
                public function f1()
                {
                    return 1;
                }
            }',
            '<?php
            class Foo {
                public function f1()
                {
                    return 1;
                }
                //public function f2()
                //{
                //    return 2;
                //}
            }',
        ];

        yield [
            '<?php
                // www.example.com
                // https://www.example.com
                // https://www.example.com/hello-world
            ',
        ];

        yield [
            '<?php
                /*
                 * https://www.example.com
                 */
            ',
        ];

        yield [
            '<?php
                /*
                 * https://www.example1.com
                 * https://www.example2.com
                 */
            ',
        ];

        yield [
            '<?php
                // See: https://www.example.com
            ',
        ];

        yield [
            '<?php
                // See:
                // https://www.example.com
            ',
        ];
    }
}
