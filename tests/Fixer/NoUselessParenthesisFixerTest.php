<?php

/*
 * This file is part of PHP CS Fixer: custom fixers.
 *
 * (c) 2018 Kuba Werłos
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Tests\Fixer;

/**
 * @internal
 *
 * @covers \PhpCsFixerCustomFixers\Fixer\NoUselessParenthesisFixer
 */
final class NoUselessParenthesisFixerTest extends AbstractFixerTestCase
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

    public static function provideFixCases(): iterable
    {
        yield ['<?php foo([1, 2]);'];
        yield ['<?php $foo([1, 2]);'];
        yield ['<?php foo(($a || $b) && ($c || $d));'];
        yield ['<?php $a = array([]);'];
        yield ['<?php $c = new class([]) {};'];
        yield ['<?php class Foo {
                    public function createSelf() {
                        return new self([1, 2]);
                    }
                    public function createStatic() {
                        return new static([1, 2]);
                    }
                }'];

        yield [
            '<?php return $bar;',
            '<?php return ($bar);',
        ];

        yield [
            '<?php throw $exception;',
            '<?php throw ($exception);',
        ];

        yield [
            '<?php throw new Exception("message");',
            '<?php throw (new Exception("message"));',
        ];

        yield [
            '<?php return array();',
            '<?php return(array());',
        ];

        yield [
            '<?php return array();',
            '<?php return((((array()))));',
        ];

        yield [
            '<?php foo($bar);',
            '<?php foo(($bar));',
        ];

        yield [
            '<?php $foo = $bar;',
            '<?php $foo = (((($bar))));',
        ];

        yield [
            '<?php $foo = [1, 2];',
            '<?php $foo = ([1, 2]);',
        ];

        yield [
            '<?php $foo = [1];',
            '<?php $foo = [(1)];',
        ];

        yield [
            '<?php $foo = [new stdClass()];',
            '<?php $foo = [(new stdClass())];',
        ];

        yield [
            '<?php $foo = $bar[1];',
            '<?php $foo = $bar[(1)];',
        ];

        yield [
            '<?php $foo = $bar{1};',
            '<?php $foo = $bar{(1)};',
        ];

        yield [
            '<?php echo (new stdClass())->foo;',
            '<?php echo ((new stdClass()))->foo;',
        ];

        yield [
            '<?php foo($bar);',
            '<?php foo(((($bar))));',
        ];

        yield [
            '<?php foo( $bar );',
            '<?php foo( ( $bar ) );',
        ];

        yield [
            '<?php foo($bar);',
            '<?php foo(( $bar ));',
        ];

        yield [
            '<?php foo( $bar );',
            '<?php foo( ($bar) );',
        ];

        yield [
            '<?php foo( $bar );',
            '<?php foo( ( ( ( $bar ) ) ) );',
        ];

        yield [
            '<?php foo( /* one */ /* two */ /* three */ $bar /* four */ /* five */ /* six */ );',
            '<?php foo( /* one */ ( /* two */ ( /* three */ $bar /* four */ ) /* five */ ) /* six */ );',
        ];

        yield [
            '<?php return // foo
                        1 // bar
;',
            '<?php return ( // foo
                        1 // bar
                    );',
        ];

        yield [
            '<?php return // foo
                    // bar
                        1 // baz
 // qux
                    ;',
            '<?php return // foo
                    ( // bar
                        1 // baz
                    ) // qux
                    ;',
        ];

        yield [
            '<?php
                if (
                    $foo
                ) {
                    return true;
                }
            ',
            '<?php
                if (
                    (
                        $foo
                    )
                ) {
                    return true;
                }
            ',
        ];

        yield [
            '<?php
                if // comment 1
                    ( // comment 2
                        // comment 3
                            true // comment 4
 // comment 5
                    ) // comment 6
                    { // comment 7
                        return true;
                    }
            ',
            '<?php
                if // comment 1
                    ( // comment 2
                        ( // comment 3
                            true // comment 4
                        ) // comment 5
                    ) // comment 6
                    { // comment 7
                        return true;
                    }
            ',
        ];

        yield [
            '<?php
                if # comment 1
                    ( # comment 2
                        # comment 3
                            true # comment 4
 # comment 5
                    ) # comment 6
                    { # comment 7
                        return true;
                    }
            ',
            '<?php
                if # comment 1
                    ( # comment 2
                        ( # comment 3
                            true # comment 4
                        ) # comment 5
                    ) # comment 6
                    { # comment 7
                        return true;
                    }
            ',
        ];

        yield [
            '<?php
                if /* comment 1 */
                    ( /* comment 2 */
                        /* comment 3 */
                            true /* comment 4 */
 /* comment 5 */
                    ) /* comment 6 */
                    { /* comment 7 */
                        return true;
                    }
            ',
            '<?php
                if /* comment 1 */
                    ( /* comment 2 */
                        ( /* comment 3 */
                            true /* comment 4 */
                        ) /* comment 5 */
                    ) /* comment 6 */
                    { /* comment 7 */
                        return true;
                    }
            ',
        ];

        yield [
            '<?php
                foo(1);
                foo((2 + 3) * (4 + 5));
                foo(6);
                foo(7 + 8);
            ',
            '<?php
                foo((1));
                foo((2 + 3) * (4 + 5));
                foo((((6))));
                foo((7 + 8));
            ',
        ];

        yield [
            '<?php
                "String with {$curly} braces";
                return 1;
            ',
            '<?php
                "String with {$curly} braces";
                return (1);
            ',
        ];
    }
}
