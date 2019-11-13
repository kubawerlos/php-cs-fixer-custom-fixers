<?php

declare(strict_types = 1);

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
        static::assertFalse($this->fixer->isRisky());
    }

    /**
     * @dataProvider provideFixCases
     */
    public function testFix(string $expected, ?string $input = null): void
    {
        $this->doTest($expected, $input);
    }

    public function provideFixCases(): iterable
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
                  }
              ',
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
                  }
              ',
        ];
    }
}
