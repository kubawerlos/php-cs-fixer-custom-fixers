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
 * @covers \PhpCsFixerCustomFixers\Fixer\NoNullableBooleanTypeFixer
 */
final class NoNullableBooleanTypeFixerTest extends AbstractFixerTestCase
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
     * @return iterable<array{0: string, 1?: string}>
     */
    public static function provideFixCases(): iterable
    {
        yield [
            '<?php function foo(bool $b) {}',
            '<?php function foo(?bool $b) {}',
        ];

        yield [
            '<?php function foo(boolean $b) {}',
            '<?php function foo(?boolean $b) {}',
        ];

        yield [
            '<?php function foo(Bool $x, Boolean $y) {}',
            '<?php function foo(?Bool $x, ?Boolean $y) {}',
        ];

        yield [
            '<?php
                function foo() : bool {};
                function bar() : bool {};
            ',
            '<?php
                function foo() : ?bool {};
                function bar() : ?bool {};
            ',
        ];

        yield [
            '<?php function foo(bool $a, bool $b, bool $c, bool $d) {}',
            '<?php function foo(?bool $a, ?bool $b, ?bool $c, ?bool $d) {}',
        ];
        yield [
            '<?php function foo(  bool $b ) {}',
            '<?php function foo( ? bool $b ) {}',
        ];

        yield [
            '<?php function foo(?int $b) : ?string {}',
        ];

        yield [
            '<?php FOO ? BOOL : BAR;',
        ];

        yield [
            '<?php FOO ?: bool;',
        ];

        yield [
            '<?php
                function foo() : bool {};
                function bar() : ?int {};
                $result = foo() ? bool : bar();
            ',
            '<?php
                function foo() : ?bool {};
                function bar() : ?int {};
                $result = foo() ? bool : bar();
            ',
        ];
    }
}
