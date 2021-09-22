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
            '<?php class Foo {
                private int $x;
                public function bar(int $x) {}
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

        yield 'interface constructors are not supported' => [
            '<?php
            interface Foo {
                public function __construct(int $x);
            }
            ',
        ];

        yield 'do not promote when not simple assignment' => [
            '<?php class Foo {
                private int $x;
                private int $y;
                public function __construct(int $x, int $y) {
                    $this->x = $x + 1;
                    $this->y = 1 + $y;
                }
            }
            ',
        ];

        yield 'do not promote when extra assignment' => [
            '<?php class Foo {
                private string $x;
                private string $y;
                public function __construct(string $y) {
                    $this->x = $this->y = $y;
                }
            }
            ',
        ];

        yield 'do not promote when constructor is not present' => [
            '<?php class Foo {
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

        yield 'do not promote when multiple assignments to the same property' => [
            '<?php class Foo {
                private string $a;
                private string $b;
                public function __construct(string $x, string $y)
                {
                    $this->a = $x;
                    $this->a = $y;
                }
            }',
        ];

        yield 'var keywords are not promoted' => [
            '<?php class Foo {
                public function __construct(public int $x) {
                }
            }
            ',
            '<?php class Foo {
                var int $x;
                public function __construct(int $x) {
                    $this->x = $x;
                }
            }
            ',
        ];

        yield 'do not promote when no type in constructor' => [
            '<?php class Foo {
                private int $b;
                public function __construct(private int $a, $b, private int $c) {
                    $this->b = $b;
                }
            }
            ',
            '<?php class Foo {
                private int $a;
                private int $b;
                private int $c;
                public function __construct(int $a, $b, int $c) {
                    $this->a = $a;
                    $this->b = $b;
                    $this->c = $c;
                }
            }
            ',
        ];

        yield 'do not promote when property used not on $this' => [
            '<?php class Foo {
                private int $y;
                public function __construct(private int $x, int $y, private int $z) {
                    $notThis->y = $y;
                }
            }
            ',
            '<?php class Foo {
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
            '<?php class Foo {
                public function __construct(private string $bar) {
                }
            }
            ',
            '<?php class Foo {
                private string $bar;
                public function __construct(string $bar) {
                    $this->bar = $bar;
                }
            }
            ',
        ];

        yield 'promote single property when not defined' => [
            '<?php class Foo {
                public function __construct(public string $bar) {
                }
            }
            ',
            '<?php class Foo {
                public function __construct(string $bar) {
                    $this->bar = $bar;
                }
            }
            ',
        ];

        yield 'promote multiple properties' => [
            '<?php class Point {
                public function __construct(
                    public float $x = 0.0,
                    public float $y = 0.0,
                    public float $z = 0.0,
                ) {
                }
            }',
            '<?php class Point {
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

        yield 'promote with default values' => [
            '<?php class Foo {
                public function __construct(
                    public array $a1 = [1, 2],
                    protected array $a2 = [OTHER_CONST],
                    private array $a3 = [],
                    public bool $b = true,
                    public int $i = -100,
                    public string $s = "Ipsum Lorem"
                ) {
                }
            }',
            '<?php class Foo {
                public array $a1 = [1, 2, 3, 4];
                protected array $a2 = ["Lorem Ipsum", SOME_CONST];
                private array $a3 = [-1, 0, "One", 2, "Two and a half"];
                public bool $b = true;
                public int $i = 42;
                public string $s = "Lorem Ipsum";
                public function __construct(
                    array $a1 = [1, 2],
                    array $a2 = [OTHER_CONST],
                    array $a3 = [],
                    bool $b = true,
                    int $i = -100,
                    string $s = "Ipsum Lorem"
                ) {
                    $this->a1 = $a1;
                    $this->a2 = $a2;
                    $this->a3 = $a3;
                    $this->b = $b;
                    $this->i = $i;
                    $this->s = $s;
                }
            }',
        ];

        yield 'promote multiple properties when written with comma' => [
            '<?php class Foo {
                public function __construct(
                    protected string $a,
                    protected string $b,
                    protected string $c,
                ) {
                }
            }',
            '<?php class Foo {
                protected $a, $b, $c;
                public function __construct(
                    string $a,
                    string $b,
                    string $c,
                ) {
                    $this->a = $a;
                    $this->b = $b;
                    $this->c = $c;
                }
            }',
        ];

        yield 'promote multiple properties when written with comma including first one' => [
            '<?php class Foo {
                protected $b, $d;
                public function __construct(
                    protected string $a,
                    string $b,
                    protected string $c,
                    string $d,
                ) {
                }
            }',
            '<?php class Foo {
                protected $a, $b, $c, $d;
                public function __construct(
                    string $a,
                    string $b,
                    string $c,
                    string $d,
                ) {
                    $this->a = $a;
                    $this->c = $c;
                }
            }',
        ];

        yield 'promote multiple properties when written with comma including last one' => [
            '<?php class Foo {
                protected $a, $e;
                public function __construct(
                    string $a,
                    protected string $b,
                    protected string $c,
                    protected string $d,
                    string $e,
                    protected string $f,
                ) {
                }
            }',
            '<?php class Foo {
                protected $a, $b, $c, $d, $e, $f;
                public function __construct(
                    string $a,
                    string $b,
                    string $c,
                    string $d,
                    string $e,
                    string $f,
                ) {
                    $this->b = $b;
                    $this->c = $c;
                    $this->d = $d;
                    $this->f = $f;
                }
            }',
        ];

        yield 'promote when types are missing in properties definitions' => [
            '<?php class Foo {
                public function __construct(
                    public bool $b,
                    protected int $i,
                    private string $s
                ) {
                }
            }',
            '<?php class Foo {
                public $b;
                protected $i;
                private $s;
                public function __construct(
                    bool $b,
                    int $i,
                    string $s
                ) {
                    $this->b = $b;
                    $this->i = $i;
                    $this->s = $s;
                }
            }',
        ];

        yield 'promote when properties are defined below constructor' => [
            '<?php class Foo {
                public function __construct(
                    public array $a,
                    public bool $b,
                    public int $i,
                    public string $s
                ) {
                }
            }',
            '<?php class Foo {
                public array $a;
                public bool $b;
                public function __construct(
                    array $a,
                    bool $b,
                    int $i,
                    string $s
                ) {
                    $this->a = $a;
                    $this->b = $b;
                    $this->i = $i;
                    $this->s = $s;
                }
                public int $i;
                public string $s;
            }',
        ];

        foreach (['array', 'bool', 'int', 'float', 'string', 'Bar', 'Bar\\Baz', '?string', 'self'] as $type) {
            yield \sprintf('promote when type is "%s"', $type) => [
                \sprintf('<?php class Foo {
                    public function __construct(
                        private %s $x,
                        private int $y,
                    ) {
                    }
                }', $type),
                \sprintf('<?php class Foo {
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
            '<?php class Point {
                public function __construct(
                    public float $x = 0.0,
                    public float $y = 0.0,
                ) {
                }
            }',
            '<?php class Point {
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

        yield 'promote only when used on $this and other object' => [
            '<?php class Foo {
                public function __construct(private int $x) {
                    $notThis1->x = $x;
                    $notThis2->x = $x;
                }
            }
            ',
            '<?php class Foo {
                private int $x;
                public function __construct(int $x) {
                    $notThis1->x = $x;
                    $this->x = $x;
                    $notThis2->x = $x;
                }
            }
            ',
        ];

        yield 'promote only when there is an assignment' => [
            '<?php class Foo {
                protected string $y;
                public function __construct(
                    protected string $x,
                    string $y,
                    private string $z
                ) {
                }
            }
            ',
            '<?php class Foo {
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
            '<?php class Foo {
                public function __construct(private string $x) {
                }
            }
            ',
            '<?php class Foo {
                private string $x;
                public function __construct(string $y) {
                    $this->x = $y;
                }
            }
            ',
        ];

        yield 'promote in multiple classes' => [
            '<?php
            abstract class Foo { // promote here
                public function __construct(private string $x) {
                }
            }
            abstract class Bar { // do not promote here
                private string $x;
                abstract public function __construct(string $x);
            }
            abstract class Baz { // do not promote here
                private string $x;
                public function not_construct(string $x) {
                    $this->x = $x;
                }
            }
            abstract class Qux { // promote here
                public function __construct(private string $x) {
                }
            }
            ',
            '<?php
            abstract class Foo { // promote here
                private string $x;
                public function __construct(string $x) {
                    $this->x = $x;
                }
            }
            abstract class Bar { // do not promote here
                private string $x;
                abstract public function __construct(string $x);
            }
            abstract class Baz { // do not promote here
                private string $x;
                public function not_construct(string $x) {
                    $this->x = $x;
                }
            }
            abstract class Qux { // promote here
                private string $x;
                public function __construct(string $x) {
                    $this->x = $x;
                }
            }
            ',
        ];

        yield 'handle messy class' => [
            '<?php class Foo {
                private function f1() {}
                private function f2() {}
                private string $variableNotTyped;
                public function __construct(
                    private string $x,
                    public string $var,
                    private string $y,
                    string $variableNotAssigned,
                    $variableNotTyped,
                    private string $z,
                ) {
                }
            }
            ',
            '<?php class Foo {
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
    }
}
