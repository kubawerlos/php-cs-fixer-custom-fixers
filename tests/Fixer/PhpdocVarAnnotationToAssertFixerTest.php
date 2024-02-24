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
 * @covers \PhpCsFixerCustomFixers\Fixer\PhpdocVarAnnotationToAssertFixer
 */
final class PhpdocVarAnnotationToAssertFixerTest extends AbstractFixerTestCase
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
     * @return iterable<array{0: string, 1?: string, 2?: array<string, list<string>>}>
     */
    public static function provideFixCases(): iterable
    {
        yield 'missing @var annotation' => [
            '<?php
                /** @see www.example.com */
                $x = 42;
            ',
        ];

        yield 'multiple annotations' => [
            '<?php
                /**
                 * @see www.example.com
                 * @var int $x
                 */
                $x = 42;
            ',
        ];

        yield 'missing type' => [
            '<?php
                /** @var $x */
                $x = 42;
                /** @var  $y */
                $y = 42;
            ',
        ];

        yield 'invalid type' => [
            '<?php
                /** @var Foo\\Bar\\ $x */
                $x = getValue();
                /** @var Foo\\\\Bar $y */
                $y = getValue();
            ',
        ];

        yield 'different variable name' => [
            '<?php
                /** @var int $x */
                $y = 42;
            ',
        ];

        yield 'PHPDoc not followed by variable' => [
            '<?php
                /** @var int $x */
                foo();
                $x = 42;
            ',
        ];

        yield 'PHPDoc not followed by assignment' => [
            '<?php
                /** @var int $x */
                $x->getValue();
            ',
        ];

        yield '@var inside of property declaration' => [
            '<?php class Foo {
                private
                    /** @var int $x */
                    $x = 1,
                    /** @var int $y */
                    $y = 2;
            }',
        ];

        yield 'already having assert' => [
            '<?php
                $a = 42;
                assert(is_int($a));
                foo($a);

                /** @var int $b */
                $b = 42;
                assert(is_int($b));

                /** @var int $c */
                $c = 42;
                ASSERT(IS_INT($c));

                /** @var int $d */
                $d = 42;
                \\assert(\\is_int($d));

                $e = 42;
                assert(is_int($e));
                foo($e);
            ',
            '<?php
                /** @var int $a */
                $a = 42;
                foo($a);

                /** @var int $b */
                $b = 42;
                assert(is_int($b));

                /** @var int $c */
                $c = 42;
                ASSERT(IS_INT($c));

                /** @var int $d */
                $d = 42;
                \\assert(\\is_int($d));

                /** @var int $e */
                $e = 42;
                foo($e);
            ',
        ];

        yield 'single type' => [
            '<?php
                $x = 42;
                assert(is_int($x));
            ',
            '<?php
                /** @var int $x */
                $x = 42;
            ',
        ];

        yield 'nullable type' => [
            '<?php
                $x = 42;
                assert(is_null($x) || is_int($x));
            ',
            '<?php
                /** @var ?int $x */
                $x = 42;
            ',
        ];

        yield 'single type uppercase' => [
            '<?php
                /** @var int $x */
                $y = 42;
                assert(is_int($y));
                /** @var int $z */
            ',
            '<?php
                /** @var int $x */
                /** @var INT $y */
                $y = 42;
                /** @var int $z */
            ',
        ];

        yield 'multiple simple types' => [
            '<?php
                $x = getValue();
                assert(is_bool($x) || is_int($x) || is_string($x));
            ',
            '<?php
                /** @var bool|int|string $x */
                $x = getValue();
            ',
        ];

        yield 'multiple class types' => [
            '<?php
                $x = getValue();
                assert($x instanceof Foo || $x instanceof Bar || $x instanceof \\Baz\\Qux);
            ',
            '<?php
                /** @var Foo|Bar|\\Baz\\Qux $x */
                $x = getValue();
            ',
        ];

        yield 'mixed types' => [
            '<?php
                $x = getValue();
                assert(is_bool($x) || $x instanceof Foo || is_string($x) || $x instanceof Bar);
            ',
            '<?php
                /** @var bool|Foo|string|Bar $x */
                $x = getValue();
            ',
        ];

        yield 'avoid duplicates' => [
            '<?php
                $x = getValue();
                assert(is_bool($x) || is_null($x) || is_int($x) || is_string($x));
            ',
            '<?php
                /** @var bool|null|int|null|string $x */
                $x = getValue();
            ',
        ];

        yield 'arrays' => [
            '<?php
                $x = [1];
                assert(is_array($x));

                /** @var array<int> $y */
                $y = [1, 2];

                $z = [1, 2, 3];
                assert(is_array($z));
            ',
            '<?php
                /** @var array $x */
                $x = [1];

                /** @var array<int> $y */
                $y = [1, 2];

                /** @var array $z */
                $z = [1, 2, 3];
            ',
        ];

        yield 'boolean' => [
            '<?php
                $x = true;
                assert(is_bool($x));

                $y = false;
                assert(is_bool($y));
            ',
            '<?php
                /** @var bool $x */
                $x = true;

                /** @var boolean $y */
                $y = false;
            ',
        ];

        yield 'callable' => [
            '<?php
                $x = function () {};
                assert(is_callable($x));
            ',
            '<?php
                /** @var callable $x */
                $x = function () {};
            ',
        ];

        yield 'float' => [
            '<?php
                $x = 0.5;
                assert(is_float($x));

                $y = 1.5;
                assert(is_float($y));
            ',
            '<?php
                /** @var double $x */
                $x = 0.5;

                /** @var float $y */
                $y = 1.5;
            ',
        ];

        yield 'int' => [
            '<?php
                $x = 1;
                assert(is_int($x));

                $y = 2;
                assert(is_int($y));
            ',
            '<?php
                /** @var int $x */
                $x = 1;

                /** @var integer $y */
                $y = 2;
            ',
        ];

        yield 'iterable' => [
            '<?php
                $x = [1, 2, 3];
                assert(is_iterable($x));
            ',
            '<?php
                /** @var iterable $x */
                $x = [1, 2, 3];
            ',
        ];

        yield 'null' => [
            '<?php
                $x = null;
                assert(is_null($x));
            ',
            '<?php
                /** @var null $x */
                $x = null;
            ',
        ];

        yield 'object' => [
            '<?php
                $x = DateTime::createFromFormat("U");
                assert(is_object($x));
            ',
            '<?php
                /** @var object $x */
                $x = DateTime::createFromFormat("U");
            ',
        ];

        yield 'resource' => [
            '<?php
                $x = tmpfile();
                assert(is_resource($x));
            ',
            '<?php
                /** @var resource $x */
                $x = tmpfile();
            ',
        ];

        yield 'string' => [
            '<?php
                $x = "foo";
                assert(is_string($x));
            ',
            '<?php
                /** @var string $x */
                $x = "foo";
            ',
        ];

        yield 'complex assignment' => [
            '<?php
                $x = array_sump(array_map(
                    function ($x) { $x++; return $x + 6; },
                    [1, 2, getThirdElement()]
                ));
                assert(is_int($x));

                if ($condition) {
                    $y = 3;
                    assert(is_int($y));
                }
                $z = 4;
                assert(is_int($z));
            ',
            '<?php
                /** @var int $x */
                $x = array_sump(array_map(
                    function ($x) { $x++; return $x + 6; },
                    [1, 2, getThirdElement()]
                ));

                if ($condition) {
                    /** @var int $y */
                    $y = 3;
                }
                /** @var int $z */
                $z = 4;
            ',
        ];
    }
}
