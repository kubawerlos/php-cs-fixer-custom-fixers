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
 * @covers \PhpCsFixerCustomFixers\Fixer\PhpdocArrayStyleFixer
 */
final class PhpdocArrayStyleFixerTest extends AbstractFixerTestCase
{
    public function testIsRisky(): void
    {
        self::assertRiskiness(false);
    }

    public function testSuccessorName(): void
    {
        self::assertSuccessorName('phpdoc_array_type');
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
        yield ['<?php /** @tagNotSupportingTypes string[] */'];

        yield [
            '<?php /** @var array<int> */',
            '<?php /** @var int[] */',
        ];

        yield [
            '<?php /** @var array<array<array<array<int>>>> */',
            '<?php /** @var int[][][][] */',
        ];

        yield [
            '<?php /** @var iterable<array<int>> */',
            '<?php /** @var iterable<int[]> */',
        ];

        yield [
            '<?php /** @var array<Foo\Bar> */',
            '<?php /** @var Foo\Bar[] */',
        ];

        yield [
            '<?php /** @var array<bool>|array<float>|array<int>|array<string> */',
            '<?php /** @var array<bool>|float[]|array<int>|string[] */',
        ];

        yield [
            '<?php
            /** @return array<int> */
            /* @return array<int> */
            ',
            '<?php
            /** @return int[] */
            /* @return array<int> */
            ',
        ];

        yield [
            '<?php
                /**
                 * @foo array<int>
                 * @param array<int>
                 */
              ',
            '<?php
                /**
                 * @foo array<int>
                 * @param int[]
                 */
              ',
        ];

        yield [
            '<?php
                /** @var array<int> */
                /** @var array<int> */
                /** @foo int[] */
                /** @var array<int> */
              ',
            '<?php
                /** @var int[] */
                /** @var int[] */
                /** @foo int[] */
                /** @var int[] */
              ',
        ];

        yield [
            '<?php /** @var array<Hello_Legacy_Naming> */',
            '<?php /** @var Hello_Legacy_Naming[] */',
        ];

        $expected = $input = 'string';
        for ($i = 0; $i < 128; $i++) {
            $expected = 'array<' . $expected . '>';
            $input .= '[]';
        }

        yield [
            \sprintf('<?php /** @var %s */', $expected),
            \sprintf('<?php /** @var %s */', $input),
        ];
    }
}
