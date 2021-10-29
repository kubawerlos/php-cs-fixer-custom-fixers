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
 * @covers \PhpCsFixerCustomFixers\Fixer\PhpUnitAssertArgumentsOrderFixer
 */
final class PhpUnitAssertArgumentsOrderFixerTest extends AbstractFixerTestCase
{
    public function testIsRisky(): void
    {
        self::assertTrue($this->fixer->isRisky());
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
        foreach (self::getFixCases() as $fixCase) {
            yield \array_map(
                static function (string $case): string {
                    return \sprintf('<?php
class FooTest extends TestCase {
    public function testFoo() {
        %s
    }
}', $case);
                },
                $fixCase
            );
        }
    }

    /**
     * @return iterable<array{0: string, 1?: string}>
     */
    private static function getFixCases(): iterable
    {
        yield ['$notThis->assertSame($x, 1);'];
        yield ['self::assertSame;'];
        yield ['self::assertSame();'];
        yield ['self::assertSame(1);'];
        yield ['self::assertSame(1, 2);'];

        yield [
            '$this->assertSame(1, $x);',
            '$this->assertSame($x, 1);',
        ];

        yield [
            'static::assertSame(true, $x);',
            'static::assertSame($x, true);',
        ];

        yield [
            'self::assertSame([1, 2, 3], $x);',
            'self::assertSame($x, [1, 2, 3]);',
        ];

        yield [
            'self::assertSame(array(1, 2, 3), $x);',
            'self::assertSame($x, array(1, 2, 3));',
        ];

        yield [
            'self::assertSame(
                false,
                $x
            );',
            'self::assertSame(
                $x,
                false
            );',
        ];

        yield [
            'self::assertSame(6, foo(1, 2, 3));',
            'self::assertSame(foo(1, 2, 3), 6);',
        ];

        yield [
            'self::assertSame(1, $x, "Message");',
            'self::assertSame($x, 1, "Message");',
        ];

        yield [
            '$this->ASSERTSAME(1, $x);',
            '$this->ASSERTSAME($x, 1);',
        ];

        yield [
            '
                self::assertSame(1, $a);
                self::assertSame(2, $b);
                self::assertSame(3, $c);
            ',
            '
                self::assertSame($a, 1);
                self::assertSame(2, $b);
                self::assertSame($c, 3);
            ',
        ];
    }
}
