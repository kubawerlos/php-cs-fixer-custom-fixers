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
 * @covers \PhpCsFixerCustomFixers\Fixer\NoLeadingSlashInGlobalNamespaceFixer
 */
final class NoLeadingSlashInGlobalNamespaceFixerTest extends AbstractFixerTestCase
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
     * @return iterable<array{0: string, 1?: string}>
     */
    public static function provideFixCases(): iterable
    {
        yield ['<?php namespace Foo; $y = new \\Bar();'];
        yield ['<?php \\foo(\\bar(), \\baz());'];

        yield [
            '<?php $foo = new Bar();',
            '<?php $foo = new \\Bar();',
        ];

        yield [
            '<?php $foo = new Bar\\Baz();',
            '<?php $foo = new \\Bar\\Baz();',
        ];

        yield [
            '<?php $foo = Bar::NAME;',
            '<?php $foo = \\Bar::NAME;',
        ];

        yield [
            '<?php $foo = Bar\\Baz::NAME;',
            '<?php $foo = \\Bar\\Baz::NAME;',
        ];

        yield [
            '<?php function f(Bar $bar, Baz $baz, ?Qux $qux) {};',
            '<?php function f(\\Bar $bar, \\Baz $baz, ?\\Qux $qux) {};',
        ];

        yield [
            '<?php function f(): Bar {};',
            '<?php function f(): \\Bar {};',
        ];

        yield [
            '<?php
                namespace { $x = new Foo(); }
                namespace Bar { $y = new \\Baz(); }
                namespace { $x = new Foo2(); }
                namespace Bar2 { $y = new \\Baz2(); }
            ',
            '<?php
                namespace { $x = new \\Foo(); }
                namespace Bar { $y = new \\Baz(); }
                namespace { $x = new \\Foo2(); }
                namespace Bar2 { $y = new \\Baz2(); }
            ',
        ];

        yield [
            '<?php $x = \\getcwd();',
        ];

        yield [
            '<?php
                $a = new Foo\\Bar();
                $b = new Baz();
            ',
            '<?php
                $a = new \\Foo\\Bar();
                $b = new \\Baz();
            ',
        ];
    }

    /**
     * @requires PHP <8.0
     *
     * @dataProvider provideFixPre80Cases
     */
    public function testFixPre80(string $expected, ?string $input = null): void
    {
        $this->doTest($expected, $input);
    }

    /**
     * @return iterable<array{string, string}>
     */
    public static function provideFixPre80Cases(): iterable
    {
        yield [
            '<?php $foo =  Bar::value();',
            '<?php $foo = \\ Bar::value();',
        ];

        yield [
            '<?php $foo = /* comment */Bar::value();',
            '<?php $foo = \\/* comment */Bar::value();',
        ];

        yield [
            '<?php $foo = /** comment */Bar::value();',
            '<?php $foo = \\/** comment */Bar::value();',
        ];
    }

    /**
     * @requires PHP 8.0
     */
    public function testFix80(): void
    {
        $this->doTest(
            '<?php function f(Bar | Baz | Qux $x) {};',
            '<?php function f(\\Bar | \\Baz | \\Qux $x) {};',
        );
    }
}
