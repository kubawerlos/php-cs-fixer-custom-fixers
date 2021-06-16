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
 * @covers \PhpCsFixerCustomFixers\Fixer\StringableInterfaceFixer
 *
 * @requires PHP 8.0
 */
final class StringableInterfaceFixerTest extends AbstractFixerTestCase
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
        yield ['<?php class Foo {}'];
        yield ['<?php class Foo { public function toString() { return "Foo"; } }'];
        yield ['<?php class Foo implements STRINGABLE  { public function __toString() { return "Foo"; } }'];
        yield ['<?php class Foo implements Stringable  { public function __toString() { return "Foo"; } }'];
        yield ['<?php class Foo implements \Stringable { public function __toString() { return "Foo"; } }'];

        yield ['<?php class Foo {
                    public function toString() {
                    function () { return 0; };
                        return "Foo";
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
            class Foo implements Stringable, \Stringable
            {
                public function __toString() { return "Foo"; }
            }
            ',
            '<?php namespace FooNamespace;
            class Foo implements Stringable
            {
                public function __toString() { return "Foo"; }
            }
            ',
        ];

        yield [
            '<?php namespace FooNamespace;
            class Foo implements Bar\Stringable, \Stringable
            {
                public function __toString() { return "Foo"; }
            }
            ',
            '<?php namespace FooNamespace;
            class Foo implements Bar\Stringable
            {
                public function __toString() { return "Foo"; }
            }
            ',
        ];

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
    }
}
