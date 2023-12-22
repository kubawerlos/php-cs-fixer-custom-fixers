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
 * @covers \PhpCsFixerCustomFixers\Fixer\StringableInterfaceFixer
 */
final class StringableInterfaceFixerTest extends AbstractFixerTestCase
{
    public function testIsRisky(): void
    {
        self::assertFalse($this->fixer->isRisky());
    }

    /**
     * @dataProvider provideFixCases
     *
     * @requires PHP 8.0
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
        yield ['<?php class Foo {}'];
        yield ['<?php class Foo { public function toString() { return "Foo"; } }'];
        yield ['<?php class Foo implements STRINGABLE  { public function __toString() { return "Foo"; } }'];
        yield ['<?php class Foo implements Stringable  { public function __toString() { return "Foo"; } }'];
        yield ['<?php class Foo implements \Stringable { public function __toString() { return "Foo"; } }'];

        yield ['<?php
            namespace Foo;
            use Stringable;
            class Bar implements Stringable {
                public function __toString() { return ""; }
            }
        '];

        yield ['<?php
            use Stringable as Stringy;
            class Bar implements Stringy {
                public function __toString() { return ""; }
            }
        '];

        yield ['<?php
            namespace Foo;
            use Stringable as Stringy;
            class Bar implements Stringy {
                public function __toString() { return ""; }
            }
        '];

        yield ['<?php
            namespace Foo;
            use \Stringable;
            class Bar implements Stringable {
                public function __toString() { return ""; }
            }
        '];

        yield ['<?php
            namespace Foo;
            use Bar;
            use STRINGABLE;
            use Baz;
            class Qux implements Stringable {
                public function __toString() { return ""; }
            }
        '];

        yield ['<?php class Foo {
                    public function toString() {
                    function () { return 0; };
                        return "Foo";
                    }
                }'];

        yield ['<?php class Foo
            {
                public function bar() {
                    $ohject->__toString();
                }
            }'];

        yield [
            '<?php class Foo implements \Stringable
            {
                public function __toString() { return "Foo"; }
            }
            ',
            '<?php class Foo
            {
                public function __toString() { return "Foo"; }
            }
            ',
        ];

        yield [
            '<?php namespace FooNamespace;
            class Foo implements \Stringable
            {
                public function __toString() { return "Foo"; }
            }
            ',
            '<?php namespace FooNamespace;
            class Foo
            {
                public function __toString() { return "Foo"; }
            }
            ',
        ];

        yield [
            '<?php namespace FooNamespace;
            use Bar\Stringable;
            class Foo implements Stringable, \Stringable
            {
                public function __toString() { return "Foo"; }
            }
            ',
            '<?php namespace FooNamespace;
            use Bar\Stringable;
            class Foo implements Stringable
            {
                public function __toString() { return "Foo"; }
            }
            ',
        ];

        $template = '<?php
            namespace FooNamespace;
            class Test implements %s
            {
                public function __toString() { return "Foo"; }
            }
        ';

        $implementedInterfacesCases = [
            'Stringable',
            'Foo\Stringable',
            '\Foo\Stringable',
            'Foo\Stringable\Bar',
            '\Foo\Stringable\Bar',
            'Foo\Stringable, Bar\Stringable',
            'Stringable\Foo, Stringable\Bar',
            '\Stringable\Foo, Stringable\Bar',
            'Foo\Stringable\Bar',
            '\Foo\Stringable\Bar',
        ];

        foreach ($implementedInterfacesCases as $implementedInterface) {
            yield [
                \sprintf($template, $implementedInterface . ', \Stringable'),
                \sprintf($template, $implementedInterface),
            ];
        }

        yield [
            '<?php class Foo implements FooInterface, \Stringable
            {
                public function __toString() { return "Foo"; }
            }
            ',
            '<?php class Foo implements FooInterface
            {
                public function __toString() { return "Foo"; }
            }
            ',
        ];

        yield [
            '<?php class Foo extends Bar implements \Stringable
            {
                public function __toString() { return "Foo"; }
            }
            ',
            '<?php class Foo extends Bar
            {
                public function __toString() { return "Foo"; }
            }
            ',
        ];

        yield [
            '<?php class Foo implements \Stringable
            {
                public function __TOSTRING() { return "Foo"; }
            }
            ',
            '<?php class Foo
            {
                public function __TOSTRING() { return "Foo"; }
            }
            ',
        ];

        yield [
            '<?php
                namespace Foo;
                use Bar;
                class Baz implements Stringable, \Stringable {
                    public function __toString() { return ""; }
                }
            ',
            '<?php
                namespace Foo;
                use Bar;
                class Baz implements Stringable {
                    public function __toString() { return ""; }
                }
            ',
        ];

        yield [
            '<?php new class implements \Stringable {
                public function __construct() {}
                public function __toString() {}
            };
            ',
            '<?php new class {
                public function __construct() {}
                public function __toString() {}
            };
            ',
        ];

        yield [
            '<?php new class() implements \Stringable {
                public function __construct() {}
                public function __toString() {}
            };
            ',
            '<?php new class() {
                public function __construct() {}
                public function __toString() {}
            };
            ',
        ];

        yield [
            '<?php
            class Foo1 implements \Stringable { public function __toString() { return "1"; } }
            class Foo2 { public function __noString() { return "2"; } }
            class Foo3 implements \Stringable { public function __toString() { return "3"; } }
            class Foo4 { public function __noString() { return "4"; } }
            class Foo5 { public function __noString() { return "5"; } }
            ',
            '<?php
            class Foo1 { public function __toString() { return "1"; } }
            class Foo2 { public function __noString() { return "2"; } }
            class Foo3 { public function __toString() { return "3"; } }
            class Foo4 { public function __noString() { return "4"; } }
            class Foo5 { public function __noString() { return "5"; } }
            ',
        ];

        yield [
            '<?php
                namespace Foo { class C implements I, \Stringable { public function __toString() { return ""; } }}
                namespace Bar { class C implements \Stringable, I { public function __toString() { return ""; } }}
                namespace Baz { class C implements I, \Stringable { public function __toString() { return ""; } }}
            ;
            ',
            '<?php
                namespace Foo { class C implements I { public function __toString() { return ""; } }}
                namespace Bar { class C implements \Stringable, I { public function __toString() { return ""; } }}
                namespace Baz { class C implements I { public function __toString() { return ""; } }}
            ;
            ',
        ];

        yield [
            '<?php
                namespace Foo { use Stringable as Stringy; class C {} }
                namespace Bar { class C implements Stringy, \Stringable { public function __toString() { return ""; } }}
            ;
            ',
            '<?php
                namespace Foo { use Stringable as Stringy; class C {} }
                namespace Bar { class C implements Stringy { public function __toString() { return ""; } }}
            ;
            ',
        ];

        yield ['<?php
            namespace Foo;
            use Stringable;
            class Bar {
                public function foo() {
                    new class () implements Stringable {
                        public function __toString() { return ""; }
                    };
                }
            }
        '];
    }
}
