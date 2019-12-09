<?php

declare(strict_types = 1);

namespace Tests\Fixer;

/**
 * @internal
 *
 * @covers \PhpCsFixerCustomFixers\Fixer\PhpdocTypesTrimFixer
 */
final class PhpdocTypesTrimFixerTest extends AbstractFixerTestCase
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

    public static function provideFixCases(): iterable
    {
        yield [
            '<?php
                /**
                 * @customAnnotation Foo | Bar ($x) Bar | Foo
                 */
             ',
        ];

        yield [
            '<?php
                /**
                 * @param    Foo        $x
                 *
                 * @return        Bar
                 */
             ',
        ];

        yield [
            '<?php
                /**
                 * @param Foo|Bar $a
                 * @param Foo|Bar $b
                 * @param Foo|Bar $c
                 */
             ',
            '<?php
                /**
                 * @param Foo | Bar $a
                 * @param Foo |Bar $b
                 * @param Foo| Bar $c
                 */
             ',
        ];

        yield [
            '<?php
                /**
                 * @return Foo|Bar
                 */
             ',
            '<?php
                /**
                 * @return Foo | Bar
                 */
             ',
        ];

        yield [
            '<?php
                /**
                 * @return Foo&Bar
                 */
             ',
            '<?php
                /**
                 * @return Foo & Bar
                 */
             ',
        ];

        yield [
            '<?php
                /**
                 * @return Foo&Bar|Baz Weird type witt & and | together
                 */
             ',
            '<?php
                /**
                 * @return Foo & Bar | Baz Weird type witt & and | together
                 */
             ',
        ];

        yield [
            '<?php
                /**
                 * @param ClassA|ClassB|ClassC|ClassD|ClassE|ClassF|ClassG|ClassH $x should be 0 | 1
                 */
             ',
            '<?php
                /**
                 * @param ClassA | ClassB | ClassC | ClassD | ClassE | ClassF | ClassG | ClassH $x should be 0 | 1
                 */
             ',
        ];

        yield [
            '<?php
                /**
                 * @return Foo|Bar Description starts, do not trim | in the description
                 */
             ',
            '<?php
                /**
                 * @return Foo | Bar Description starts, do not trim | in the description
                 */
             ',
        ];

        yield [
            '<?php
                /**
                 * @return Foo|Bar Multiline
                 *                   description
                 */
             ',
            '<?php
                /**
                 * @return Foo | Bar Multiline
                 *                   description
                 */
             ',
        ];

        yield [
            '<?php
                /**
                 * @return Foo
                 *       | Bar
                 *       | Baz
                 */
             ',
        ];

        yield [
            '<?php
                /**
                 * @return Foo|
                 *         Bar |
                 *         Baz
                 */
             ',
            '<?php
                /**
                 * @return Foo |
                 *         Bar |
                 *         Baz
                 */
             ',
        ];

        yield [
            '<?php
                /**
                 * @param Foo|Bar &$a
                 * @param Foo&Bar &$b
                 * @param Foo|Bar & $c
                 * @param Foo&Bar & $d
                 */
             ',
            '<?php
                /**
                 * @param Foo | Bar &$a
                 * @param Foo & Bar &$b
                 * @param Foo | Bar & $c
                 * @param Foo & Bar & $d
                 */
             ',
        ];

        yield [
            '<?php
                /**
                 * @notParam Foo | Bar $x
                 * @param Foo|Bar $x
                 * @param Foo|Bar $y
                 *
                 * @notReturn Foo | Bar
                 * @return Foo|Bar
                 */
                 function fooBar($x, $y) {}
                 /**
                  * @return Baz
                  */
                 function baz() {}
             ',
            '<?php
                /**
                 * @notParam Foo | Bar $x
                 * @param Foo | Bar $x
                 * @param Foo|Bar $y
                 *
                 * @notReturn Foo | Bar
                 * @return Foo | Bar
                 */
                 function fooBar($x, $y) {}
                 /**
                  * @return Baz
                  */
                 function baz() {}
             ',
        ];
    }
}
