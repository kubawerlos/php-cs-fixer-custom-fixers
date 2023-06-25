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
 * @covers \PhpCsFixerCustomFixers\Fixer\AbstractTypesFixer
 * @covers \PhpCsFixerCustomFixers\Fixer\PhpdocTypesTrimFixer
 */
final class PhpdocTypesTrimFixerTest extends AbstractFixerTestCase
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
        yield ['<?php
                /**
                 * @return
                 */
             '];

        yield ['<?php
                /**
                 * @customAnnotation Foo | Bar ($x) Bar | Foo
                 */
             '];

        yield ['<?php
                /**
                 * @param    Foo        $x
                 *
                 * @return        Bar
                 */
             '];

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
                 * @param &$e
                 * @param & $f
                 * @param Foo|Bar & $g
                 */
             ',
            '<?php
                /**
                 * @param Foo | Bar &$a
                 * @param Foo & Bar &$b
                 * @param Foo | Bar & $c
                 * @param Foo & Bar & $d
                 * @param &$e
                 * @param & $f
                 * @param Foo | Bar & $g
                 */
             ',
        ];

        yield [
            '<?php
                /**
                 * @param |Foo $foo
                 * @param &Foo $bar
                 */
             ',
        ];

        yield [
            '<?php
                /**
                 * @param Foo|Bar $x
                 * @param Foo|Bar $y
                 * @param Foo|Bar $z
                 */
             ',
            '<?php
                /**
                 * @param Foo |  Bar $x
                 * @param Foo  | Bar $y
                 * @param Foo  |  Bar $z
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
