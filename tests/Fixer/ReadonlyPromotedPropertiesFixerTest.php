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
 * @covers \PhpCsFixerCustomFixers\Fixer\ReadonlyPromotedPropertiesFixer
 *
 * @requires PHP >= 8.1
 */
final class ReadonlyPromotedPropertiesFixerTest extends AbstractFixerTestCase
{
    public function testIsRisky(): void
    {
        self::assertRiskiness(true);
    }

    /**
     * @dataProvider provideFixCases
     */
    public function testFix(string $expected, ?string $input = null): void
    {
        $this->doTest($expected, $input);
    }

    /**
     * @return iterable<array{string, 1?: string}>
     */
    public static function provideFixCases(): iterable
    {
        yield 'not promoted property' => [
            '<?php class Foo {
                public function __construct(
                    int $x
                ) {}
            }',
        ];

        yield 'multiple promoted properties' => [
            '<?php class Foo {
                public function __construct(
                    public readonly int $a,
                    protected readonly int $b,
                    private readonly int $c,
                ) {}
            }',
            '<?php class Foo {
                public function __construct(
                    public int $a,
                    protected int $b,
                    private int $c,
                ) {}
            }',
        ];

        yield 'already readonly properties' => [
            '<?php class Foo {
                public function __construct(
                    public readonly int $a,
                    readonly public int $b,
                    public readonly int $c,
                    readonly public int $d,
                    public readonly int $e,
                    readonly public int $f,
                    public readonly int $g,
                ) {}
            }',
            '<?php class Foo {
                public function __construct(
                    public readonly int $a,
                    readonly public int $b,
                    public int $c,
                    readonly public int $d,
                    public int $e,
                    readonly public int $f,
                    public readonly int $g,
                ) {}
            }',
        ];

        yield 'not in constructor' => [
            '<?php
                class Foo { public function __construct(public readonly int $x) {} }
                class Bar { public function notConstruct(int $x) {} }
                class Baz { public function __construct(public readonly int $x) {} }
            ',
            '<?php
                class Foo { public function __construct(public int $x) {} }
                class Bar { public function notConstruct(int $x) {} }
                class Baz { public function __construct(public int $x) {} }
            ',
        ];

        yield 'property used in assignment' => [
            <<<'PHP'
                <?php class Foo {
                    public function __construct(
                        public int $p1,
                        public int $p2,
                        public int $p3,
                        public int $p4,
                        public int $p5,
                        public int $p6,
                        public int $p7,
                        public int $p8,
                        public int $p9,
                        public bool $p10,
                        public bool $p11,
                        public bool $p12,
                        public int $p13,
                        public int $p14,
                        public int $p15,
                        public string $p16,
                        public array $p17,
                    ) {}
                    public function f() {
                        $this->p1 = 1;
                        $this->p2++;
                        $this->p3--;
                        $this->p4 += 4;
                        $this->p5 -= 5;
                        $this->p6 *= 6;
                        $this->p7 /= 7;
                        $this->p8 %= 8;
                        $this->p9 **= 9;
                        $this->p10 &= true;
                        $this->p11 |= true;
                        $this->p12 ^= true;
                        $this->p13 <<= 1;
                        $this->p14 >>= 1;
                        $this->p15 ??= 15;
                        $this->p16 .= '16';
                        $this->p17[0][0][0][0][0][0][0][0] = 17;
                    }
                }
                PHP,
        ];

        yield 'property of other object used in assignment' => [
            '<?php class Foo {
                public function __construct(public readonly int $x) {}
                public function doSomething() { $object->x = 42; }
            }',
            '<?php class Foo {
                public function __construct(public int $x) {}
                public function doSomething() { $object->x = 42; }
            }',
        ];

        yield 'multiple properties with assignments' => [
            '<?php class Foo {
                public function __construct(public int $x, public readonly int $y, public int $z) {}
                public function doSomething() { $this->x = 42;  $this->z = 10; }
            }',
            '<?php class Foo {
                public function __construct(public int $x, public int $y, public int $z) {}
                public function doSomething() { $this->x = 42;  $this->z = 10; }
            }',
        ];

        yield 'property used in return statement' => [
            <<<'PHP'
                <?php class Foo {
                    public function __construct(public readonly object $p) {}
                    public function f() {
                        return $this->p;
                    }
                }
                PHP,
            <<<'PHP'
                <?php class Foo {
                    public function __construct(public object $p) {}
                    public function f() {
                        return $this->p;
                    }
                }
                PHP,
        ];

        yield 'property used as argument' => [
            <<<'PHP'
                <?php class Foo {
                    public function __construct(
                        public readonly array $array,
                        public readonly object $object,
                    ) {}
                    public function f() {
                        bar($this->array[4], $this->object);
                        baz(1, $this->array, 2);
                        baz(3, $this->object, 4);
                    }
                }
                PHP,
            <<<'PHP'
                <?php class Foo {
                    public function __construct(
                        public array $array,
                        public object $object,
                    ) {}
                    public function f() {
                        bar($this->array[4], $this->object);
                        baz(1, $this->array, 2);
                        baz(3, $this->object, 4);
                    }
                }
                PHP,
        ];

        yield 'property used in method call' => [
            <<<'PHP'
                <?php class Foo {
                    public function __construct(public readonly object $p) {}
                    public function f() {
                        $this->p->method();
                    }
                }
                PHP,
            <<<'PHP'
                <?php class Foo {
                    public function __construct(public object $p) {}
                    public function f() {
                        $this->p->method();
                    }
                }
                PHP,
        ];

        yield 'property used in null-safe method call' => [
            <<<'PHP'
                <?php class Foo {
                    public function __construct(public readonly object $p) {}
                    public function f() {
                        $this->p?->method();
                    }
                }
                PHP,
            <<<'PHP'
                <?php class Foo {
                    public function __construct(public object $p) {}
                    public function f() {
                        $this->p?->method();
                    }
                }
                PHP,
        ];

        yield 'property of property' => [
            <<<'PHP'
                <?php class Foo {
                    public function __construct(public readonly object $object) {}
                    public function f() {
                        $this->object->property = 3;
                    }
                }
                PHP,
            <<<'PHP'
                <?php class Foo {
                    public function __construct(public object $object) {}
                    public function f() {
                        $this->object->property = 3;
                    }
                }
                PHP,
        ];

        yield 'property used multiple times' => [
            <<<'PHP'
                <?php class Foo {
                    public function __construct(public readonly object $p) {}
                    public function f() {
                        $this->value1 = $this->p->method1();
                        bar($this->p, $this->p->property1);
                        $this->value2 = $this->p->property2;
                        $this->p->method2();
                    }
                }
                PHP,
            <<<'PHP'
                <?php class Foo {
                    public function __construct(public object $p) {}
                    public function f() {
                        $this->value1 = $this->p->method1();
                        bar($this->p, $this->p->property1);
                        $this->value2 = $this->p->property2;
                        $this->p->method2();
                    }
                }
                PHP,
        ];

        yield 'property used multiple times, including assignment' => [
            <<<'PHP'
                <?php class Foo {
                    public function __construct(public object $p) {}
                    public function f() {
                        $this->p->method1();
                        $this->p = new Bar();
                        $this->p->method2();
                    }
                }
                PHP,
        ];
    }

    /**
     * @dataProvider provideFix82Cases
     *
     * @requires PHP >= 8.2
     */
    public function testFix82(string $expected, ?string $input = null): void
    {
        $this->doTest($expected, $input);
    }

    /**
     * @return iterable<list<string>>
     */
    public static function provideFix82Cases(): iterable
    {
        $template = '<?php %s class C1 { public function __construct(public int $x) {} }';

        foreach (
            [
                'readonly',
                'abstract readonly',
                'final readonly',
                'readonly final',
                'readonly abstract',
            ] as $classModifiers
        ) {
            yield [\sprintf($template, $classModifiers)];
        }

        yield [
            '<?php
                class Foo { public function __construct(public readonly int $x) {} }
                readonly class Bar { public function __construct(int $x) {} }
                class Baz { public function __construct(public readonly int $x) {} }
            ',
            '<?php
                class Foo { public function __construct(public int $x) {} }
                readonly class Bar { public function __construct(int $x) {} }
                class Baz { public function __construct(public int $x) {} }
            ',
        ];
    }

    /**
     * @dataProvider provideFix84Cases
     *
     * @requires PHP >= 8.4
     */
    public function testFix84(string $expected, ?string $input = null): void
    {
        $this->doTest($expected, $input);
    }

    /**
     * @return iterable<string, array{0: string, 1?: string}>
     */
    public static function provideFix84Cases(): iterable
    {
        yield 'asymmetric visibility with both visibilities' => [
            <<<'PHP'
                <?php
                final class Foo {
                    public function __construct(
                        public public(set) readonly int $a,
                        public protected(set) readonly int $b,
                        public private(set) readonly int $c,
                        protected protected(set) readonly int $d,
                        protected private(set) readonly int $e,
                        private private(set) readonly int $f,
                    ) {}
                }
                PHP,
            <<<'PHP'
                <?php
                final class Foo {
                    public function __construct(
                        public public(set) int $a,
                        public protected(set) int $b,
                        public private(set) int $c,
                        protected protected(set) int $d,
                        protected private(set) int $e,
                        private private(set) int $f,
                    ) {}
                }
                PHP,
        ];

        yield 'asymmetric visibility with only write visibility' => [
            <<<'PHP'
                <?php
                final class Foo {
                    public function __construct(
                        public(set) readonly int $a,
                        protected(set) readonly int $b,
                        private(set) readonly int $c,
                    ) {}
                }
                PHP,
            <<<'PHP'
                <?php
                final class Foo {
                    public function __construct(
                        public(set) int $a,
                        protected(set) int $b,
                        private(set) int $c,
                    ) {}
                }
                PHP,
        ];

        yield 'readonly asymmetric visibility' => [
            <<<'PHP'
                <?php
                final class Foo {
                    public function __construct(
                        readonly public public(set) int $a,
                        public readonly protected(set) int $b,
                        public private(set) readonly int $c,
                        readonly private(set) int $e,
                        private(set) readonly int $f,
                    ) {}
                }
                PHP,
        ];
    }
}
