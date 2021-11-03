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

use PhpCsFixerCustomFixers\Fixer\PhpUnitDedicatedAssertFixer;

/**
 * @internal
 *
 * @covers \PhpCsFixerCustomFixers\Fixer\PhpUnitDedicatedAssertFixer
 */
final class PhpUnitDedicatedAssertFixerTest extends AbstractFixerTestCase
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
        foreach (self::getFixCases() as $name => $fixCase) {
            yield $name => \array_map(
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
        yield 'ignore class on not $this' => ['$notThis->assertSame(3, count($array));'];
        yield 'ignore property' => ['self::assertSame;'];
        yield 'ignore assertion with no arguments' => ['self::assertSame();'];
        yield 'ignore assertion with single argument' => ['self::assertSame(count($array));'];
        yield 'ignore other assertions' => ['self::assertGreaterThan(2, count($array));'];
        yield 'ignore other functions' => ['self::assertSame(2, countIncorrectly($array));'];
        yield 'ignore function in first argument' => ['self::assertSame(count($array), 2);'];
        yield 'ignore function from namespace' => ['self::assertSame(2, count\better_count($array));'];
        yield 'ignore function used with 2 arguments' => ['self::assertSame(3, count($array, COUNT_RECURSIVE));'];
        yield 'ignore assertion with code after function' => ['self::assertSame(3, count($array) + 1);'];

        yield 'fix count' => [
            '$this->assertCount(3, $array);',
            '$this->assertSame(3, count($array));',
        ];

        yield 'fix sizeof' => [
            'static::assertCount(3, $array);',
            'static::assertSame(3, sizeof($array));',
        ];

        yield 'fix instanceof' => [
            'self::assertInstanceOf("stdClass", $object);',
            'self::assertSame("stdClass", get_class($object));',
        ];

        yield 'fix not instanceof' => [
            'self::assertNotInstanceOf("Closure", $object);',
            'self::assertNotSame("Closure", get_class($object));',
        ];

        yield 'fix different casing' => [
            'self::assertCount(3, $array);',
            'self::assertSame(3, COUNT($array));',
        ];

        yield 'fix expected being variable' => [
            'self::assertCount($arrayCount, $array);',
            'self::assertSame($arrayCount, count($array));',
        ];

        yield 'fix with leading slash' => [
            'self::assertCount(3, $array);',
            'self::assertSame(3, \count($array));',
        ];

        yield 'fix with many spaces' => [
            '$this->assertCount ( 3 ,  $array  ) ;',
            '$this->assertSame ( 3 , \count ( $array ) ) ;',
        ];

        $reflection = new \ReflectionClass(PhpUnitDedicatedAssertFixer::class);

        /** @var array<string> $assertions */
        $assertions = $reflection->getConstant('ASSERTIONS');

        foreach ($assertions as $assertion) {
            $expected = 'self::assertCount(3, $array);';
            $input = \sprintf('self::%s(3, count($array));', $assertion);

            if (\stripos($assertion, 'Not', 6) !== false) {
                $expected = \str_replace('assert', 'assertNot', $expected);
                $expected = \str_replace('3', '4', $expected);
                $input = \str_replace('3', '4', $input);
            }

            yield \sprintf('Test assertion "%s"', $assertion) => [$expected, $input];
        }

        yield 'fix multiple assertions' => [
            '
                if (false) self::assertSame(1);
                self::assertSame(3, $arrayCount);
                self::assertCount(3, $array);
                if (false) self::assertSame(4);
            ',
            '
                if (false) self::assertSame(1);
                self::assertSame(3, $arrayCount);
                self::assertSame(3, count($array));
                if (false) self::assertSame(4);
            ',
        ];
    }
}
