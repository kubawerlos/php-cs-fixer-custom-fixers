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
 * @covers \PhpCsFixerCustomFixers\Fixer\ClassConstantUsageFixer
 */
final class ClassConstantUsageFixerTest extends AbstractFixerTestCase
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
        yield 'non-string constants are ignored' => [
            <<<'PHP'
                <?php
                class Foo
                {
                    public const B = true;
                    public const I = 10;
                    public function f()
                    {
                        return 10 * f1(true, false, true);
                    }
                }
                PHP,
        ];
        yield 'multiple constants with the same value' => [
            <<<'PHP'
                <?php
                class Foo
                {
                    public const BAR = 'a';
                    public const BAZ = 'a';
                    public function f()
                    {
                        return 'a';
                    }
                }
                PHP,
        ];

        yield 'constants all over the class' => [
            <<<'PHP'
                <?php
                class Foo
                {
                    public const BAR = 'bar';
                    public function f()
                    {
                        return self::BAR . self::BAZ;
                    }
                    public const BAZ = 'baz';
                }
                PHP,
            <<<'PHP'
                <?php
                class Foo
                {
                    public const BAR = 'bar';
                    public function f()
                    {
                        return 'bar' . 'baz';
                    }
                    public const BAZ = 'baz';
                }
                PHP,
        ];
    }
}
