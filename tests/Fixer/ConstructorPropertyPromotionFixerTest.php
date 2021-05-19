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
 * @covers \PhpCsFixerCustomFixers\Fixer\ConstructorPropertyPromotionFixer
 *
 * @requires PHP 8.0
 */
final class ConstructorPropertyPromotionFixerTest extends AbstractFixerTestCase
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
        yield 'non-constructors are not supported' => [
            '<?php
            abstract class Foo {
                private int $x;
                abstract public function bar(int $x);
            }
            ',
        ];

        yield 'abstract constructors are not supported' => [
            '<?php
            abstract class Foo {
                private int $x;
                abstract public function __construct(int $x);
            }
            ',
        ];

        yield 'var keywords are not supported' => [
            '<?php
            class Foo {
                var int $x;
                public function __construct(int $x) {
                    $this->x = $x;
                }
            }
            ',
        ];

        yield 'do not promote when not type in constructor' => [
            '<?php
            class Foo {
                private $x;
                public function __construct($x, private int $y) {
                    $this->x = $x;
                }
            }
            ',
            '<?php
            class Foo {
                private $x;
                private $y;
                public function __construct($x, int $y) {
                    $this->x = $x;
                    $this->y = $y;
                }
            }
            ',
        ];

        yield 'do not promote when not simple assignment' => [
            '<?php
            class Foo {
                private int $x;
                private int $y;
                public function __construct(int $x, int $y) {
                    $this->x = $x + 1;
                    $this->y = 1 + $y;
                }
            }
            ',
        ];

        yield 'do not promote when constructor is not present' => [
            '<?php
            class Foo {
                private int $x;
                public function bar() {
                    $class = new class() {
                        private int $y;
                        public function __construct() {
                        }
                    };
                }
            }
            ',
        ];

        yield 'do not promote when property used not on $this' => [
            '<?php
            class Foo {
                private int $y;
                public function __construct(private int $x, int $y, private int $z) {
                    $notThis->y = $y;
                }
            }
            ',
            '<?php
            class Foo {
                private int $x;
                private int $y;
                private int $z;
                public function __construct(int $x, int $y, int $z) {
                    $this->x = $x;
                    $notThis->y = $y;
                    $this->z = $z;
                }
            }
            ',
        ];

        yield 'promote single property' => [
            '<?php
            class Foo {
                public function __construct(private string $bar) {
                }
            }
            ',
            '<?php
            class Foo {
                private string $bar;
                public function __construct(string $bar) {
                    $this->bar = $bar;
                }
            }
            ',
        ];

        yield 'promote multiple properties' => [
            '<?php
            class Point {
                public function __construct(
                    public float $x = 0.0,
                    public float $y = 0.0,
                    public float $z = 0.0,
                ) {
                }
            }',
            '<?php
            class Point {
                public float $x;
                public float $y;
                public float $z;
                public function __construct(
                    float $x = 0.0,
                    float $y = 0.0,
                    float $z = 0.0,
                ) {
                    $this->x = $x;
                    $this->y = $y;
                    $this->z = $z;
                }
            }',
        ];

        foreach (['array', 'bool', 'int', 'float', 'string', 'Bar', 'Bar\\Baz', '?string', 'self'] as $type) {
            yield \sprintf('promote when type is "%s"', $type) => [
                \sprintf('<?php
                class Foo {
                    public function __construct(
                        private %s $x,
                        private int $y,
                    ) {
                    }
                }', $type),
                \sprintf('<?php
                class Foo {
                    private $x;
                    private $y;
                    public function __construct(
                        %s $x,
                        int $y,
                    ) {
                        $this->x = $x;
                        $this->y = $y;
                    }
                }', $type),
            ];
        }

        yield 'remove property PHPDoc when promoting' => [
            '<?php
            class Point {
                public function __construct(
                    public float $x = 0.0,
                    public float $y = 0.0,
                ) {
                }
            }',
            '<?php
            class Point {
                /** @var float */
                public float $x;
                /** @var float */
                public float $y;
                public function __construct(
                    float $x = 0.0,
                    float $y = 0.0,
                ) {
                    $this->x = $x;
                    $this->y = $y;
                }
            }',
        ];

        yield 'promote property used on $this and other object' => [
            '<?php
            class Foo {
                public function __construct(private int $x) {
                    $notThis1->x = $x;
                    $notThis2->x = $x;
                }
            }
            ',
            '<?php
            class Foo {
                private int $x;
                public function __construct(int $x) {
                    $notThis1->x = $x;
                    $this->x = $x;
                    $notThis2->x = $x;
                }
            }
            ',
        ];

        yield 'promote not all properties' => [
            '<?php
            class Foo {
                protected string $y;
                public function __construct(
                    protected string $x,
                    string $y,
                    private string $z
                ) {
                }
            }
            ',
            '<?php
            class Foo {
                protected string $x;
                protected string $y;
                private string $z;
                public function __construct(
                    string $x,
                    string $y,
                    string $z
                ) {
                    $this->x = $x;
                    $this->z = $z;
                }
            }
            ',
        ];

        yield 'promote with different name' => [
            '<?php
            class Foo {
                public function __construct(private string $x) {
                }
            }
            ',
            '<?php
            class Foo {
                private string $x;
                public function __construct(string $y) {
                    $this->x = $y;
                }
            }
            ',
        ];

        yield 'do not promote when extra assignment' => [
            '<?php
            class Foo {
                private string $x;
                private string $y;
                public function __construct(string $y) {
                    $this->x = $this->y = $y;
                }
            }
            ',
        ];

        yield 'messy class' => [
            '<?php
            class Foo {
                private function f1() {}
                var $var;
                private function f2() {}
                private string $variableNotTyped;
                public function __construct(
                    private string $x,
                    string $var,
                    private string $y,
                    string $variableNotAssigned,
                    $variableNotTyped,
                    private string $z,
                ) {
                    $this->var = $var;
                }
            }
            ',
            '<?php
            class Foo {
                private string $x;
                private function f1() {}
                var $var;
                private string $y;
                private function f2() {}
                private string $z;
                private string $variableNotTyped;
                public function __construct(
                    string $x,
                    string $var,
                    string $y,
                    string $variableNotAssigned,
                    $variableNotTyped,
                    string $z,
                ) {
                    $this->x = $x;
                    $this->var = $var;
                    $this->y = $y;
                    $this->z = $z;
                }
            }
            ',
        ];

        yield 'promote in multiple classes' => [
            '<?php
            abstract class Foo {
                public function __construct(private string $x) {
                }
            }
            abstract class Bar {
                private string $x;
                abstract public function __construct(string $x);
            }
            abstract class Baz {
                private string $x;
                public function not_construct(string $x) {
                    $this->x = $x;
                }
            }
            abstract class Qux {
                public function __construct(private string $x) {
                }
            }
            ',
            '<?php
            abstract class Foo {
                private string $x;
                public function __construct(string $x) {
                    $this->x = $x;
                }
            }
            abstract class Bar {
                private string $x;
                abstract public function __construct(string $x);
            }
            abstract class Baz {
                private string $x;
                public function not_construct(string $x) {
                    $this->x = $x;
                }
            }
            abstract class Qux {
                private string $x;
                public function __construct(string $x) {
                    $this->x = $x;
                }
            }
            ',
        ];
    }
}
