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
     * @return iterable<array{0: string, 1?: string, 2?: array<string, array<string>>}>
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

        yield 'single type' => [
            '<?php
                /** @var int $x */
                $y = 42;
                assert(is_int($y));
                /** @var int $z */
            ',
            '<?php
                /** @var int $x */
                /** @var int $y */
                $y = 42;
                /** @var int $z */
            ',
        ];

        yield 'multiple types' => [
            '<?php
                $x = getValue();
                assert(is_bool($x) || is_int($x) || is_string($x));
            ',
            '<?php
                /** @var bool|int|string $x */
                $x = getValue();
            ',
        ];

        yield 'multiple variables' => [
            '<?php
                $x1 = true;
                assert(is_bool($x1));
                $x2 = false;
                assert(is_bool($x2));
                $x3 = function () {};
                assert(is_callable($x3));
                $x4 = 0.5;
                assert(is_float($x4));
                $x5 = 1.5;
                assert(is_float($x5));
                $x6 = 2;
                assert(is_int($x6));
                $x7 = null;
                assert(is_null($x7));
                $x8 = "foo";
                assert(is_string($x8));
            ',
            '<?php
                /** @var bool $x1 */
                $x1 = true;
                /** @var boolean $x2 */
                $x2 = false;
                /** @var callable $x3 */
                $x3 = function () {};
                /** @var double $x4 */
                $x4 = 0.5;
                /** @var float $x5 */
                $x5 = 1.5;
                /** @var int $x6 */
                $x6 = 2;
                /** @var null $x7 */
                $x7 = null;
                /** @var string $x8 */
                $x8 = "foo";
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
    }
}
