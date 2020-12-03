<?php

/*
 * This file is part of PHP CS Fixer: custom fixers.
 *
 * (c) 2018-2020 Kuba Werłos
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

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
        yield [
            '<?php namespace Foo; $y = new \\Bar();',
        ];

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
            '<?php function f(Bar $bar, Baz $baz) {};',
            '<?php function f(\\Bar $bar, \\Baz $baz) {};',
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

        if (PHP_MAJOR_VERSION < 8) {
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
    }
}
