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

    /**
     * @return iterable<array{0: string, 1?: string}>
     */
    public static function provideFixCases(): iterable
    {
        yield ['<?php foo([1, 2]);'];
        yield ['<?php $foo([1, 2]);'];
        yield ['<?php foo(($a || $b) && ($c || $d));'];
        yield ['<?php $a = array([]);'];
        yield ['<?php $f($x);'];
        yield ['<?php if ($x) {} elseif ($y) {}'];
        yield ['<?php array($x);'];
        yield ['<?php empty($x);'];
        yield ['<?php isset($x);'];
        yield ['<?php unset($x);'];
        yield ['<?php exit(2);'];
        yield ['<?php eval("<?php echo 3;");'];
        yield ['<?php list($x) = [1];'];
        yield ['<?php switch ($x) { default: return true; }'];
        yield ['<?php try {} catch (Exception $x) {}'];
        yield ['<?php $c = new class([]) {};'];
        yield ['<?php $f = function ($x) { return $x + 2; };'];
        yield ['<?php $f = function ($x): int { return $x + 2; };'];
        yield ['<?php $f = function ($x) use ($y) { return $x + $y; };'];
        yield ['<?php $f = function ($x) use ($y): int { return $x + $y; };'];
        yield ['<?php $f = function &($x) { return $x + 2; };'];
        yield ['<?php $f = function &($x): int { return $x + 2; };'];
        yield ['<?php $arrayOfCallbacks["foo"]($bar);'];
        yield ['<?php do {} while($x);'];
        yield ['<?php Obj::class($x);'];
        yield ['<?php class Foo { public static function create($x) { return new static($x); } }'];
        yield ['<?php class Foo { public static function create($x) { return $this->{$prop}; } }'];
        yield ['<?php $c = new class($x) {};'];
        yield ['<?php $c = new class($x) implements FooInterface {};'];
        yield ['<?php $c = new class($x) extends FooClass {};'];
        yield ['<?php $object->{"set_value"}($x);'];
        yield ['<?php $object->{"set_{$name}"}($x);'];
        if (\defined('T_FN')) {
            yield ['<?php $f = fn ($x) => $x + 2;'];
            yield ['<?php $f = fn &($x) => $x + 2;'];
            yield ['<?php $f = fn &($x): int => $x + 2;'];
        }
        if (\defined('T_MATCH')) {
            yield ['<?php return match ($x) { default => 0 };'];
        }
        yield ['<?php class Foo {
                    public function createSelf() {
                        return new self([1, 2]);
                    }
                    public function createStatic() {
                        return new static([1, 2]);
                    }
                }'];
        yield ['<?php return ($function)(42);'];
        yield ['<?php return ($object->function)(42);'];
        yield ['<?php return ($object->function)(new stdClass());'];
        yield ['<?php return ($object->getFunction())(42);'];
        yield ['<?php return ($foo)($bar)($baz);'];
        yield ['<?php return (1 + 2) * 3;'];

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
            '<?php throw new Exception("message");',
            '<?php throw(new Exception("message"));',
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
            '<?php foo( $bar );',
            '<?php foo(( $bar ));',
        ];

        yield [
            '<?php foo($bar);',
            '<?php foo( ($bar) );',
        ];

        yield [
            '<?php foo( $bar );',
            '<?php foo( ( ( ( $bar ) ) ) );',
        ];

        yield [
            '<?php foo/* one */ /* two */ ( /* three */ $bar /* four */ ) /* five */ /* six */;',
            '<?php foo( /* one */ ( /* two */ ( /* three */ $bar /* four */ ) /* five */ ) /* six */ );',
        ];

        yield [
            '<?php echo 1 + 2 + 3;',
            '<?php echo 1 + (2) + 3;',
        ];

        yield [
            '<?php echo 1.5 + 2.5 + 3.5;',
            '<?php echo 1.5 + (2.5) + 3.5;',
        ];

        yield [
            '<?php $s = "a" . "b" . "c";',
            '<?php $s = "a" . ("b") . "c";',
        ];

        yield [
            '<?php echo 1 + $obj->value + 3;',
            '<?php echo 1 + ($obj->value) + 3;',
        ];

        yield [
            '<?php echo 1 + Obj::VALUE + 3;',
            '<?php echo 1 + (Obj::VALUE) + 3;',
        ];

        yield [
            '<?php $s = "a" . "b" . "c";',
            '<?php $s = "a" . ( "b" ) . "c";',
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
                    // comment 2
                        ( // comment 3
                            true // comment 4
                        ) // comment 5
 // comment 6
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
                    # comment 2
                        ( # comment 3
                            true # comment 4
                        ) # comment 5
 # comment 6
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
                    /* comment 2 */
                        ( /* comment 3 */
                            true /* comment 4 */
                        ) /* comment 5 */
 /* comment 6 */
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
