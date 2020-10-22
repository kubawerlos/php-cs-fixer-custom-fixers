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
 * @covers \PhpCsFixerCustomFixers\Fixer\PhpdocArrayStyleFixer
 */
final class PhpdocArrayStyleFixerTest extends AbstractFixerTestCase
{
    public function testConfiguration(): void
    {
        $options = $this->fixer->getConfigurationDefinition()->getOptions();
        self::assertArrayHasKey(0, $options);
        self::assertSame('style', $options[0]->getName());
    }

    public function testIsRisky(): void
    {
        self::assertFalse($this->fixer->isRisky());
    }

    /**
     * @dataProvider provideFixCases
     */
    public function testFix(string $expected, ?string $input = null, ?array $configuration = null): void
    {
        $this->doTest($expected, $input, $configuration);
    }

    public static function provideFixCases(): iterable
    {
        yield ['<?php /** @tagNotSupportingTypes array<int, string> */'];
        yield ['<?php /** @var array<int, string> */'];
        yield ['<?php /** @var non-empty-array<int> */'];
        yield ['<?php /** @var non_empty_array<int> */'];
        yield ['<?php /** @var arrayarray<int> */'];

        $pairs = [
            [
                'int[]',
                'array<int>',
            ],
            [
                'int[][][][]',
                'array<array<array<array<int>>>>',
            ],
            [
                'Foo\Bar[]',
                'array<Foo\Bar>',
            ],
        ];

        foreach ($pairs as $pair) {
            yield [
                \sprintf('<?php /** @var %s */', $pair[0]),
                \sprintf('<?php /** @var %s */', $pair[1]),
            ];

            yield [
                \sprintf('<?php /** @var %s */', $pair[1]),
                \sprintf('<?php /** @var %s */', $pair[0]),
                ['style' => 'generic'],
            ];
        }

        yield [
            '<?php /** @var bool[]|float[]|int[]|string[] */',
            '<?php /** @var array<bool>|float[]|array<int>|string[] */',
        ];

        yield [
            '<?php /** @var array<bool>|array<float>|array<int>|array<string> */',
            '<?php /** @var array<bool>|float[]|array<int>|string[] */',
            ['style' => 'generic'],
        ];

        yield [
            '<?php
            /** @return int[] */
            /* @return array<int> */',
            '<?php
            /** @return array<int> */
            /* @return array<int> */',
        ];

        yield [
            '<?php
                /** @var int[] */
                /** @var int[] */
                /** @foo array<int> */
                /** @var int[] */
              ',
            '<?php
                /** @var array<int> */
                /** @var int[] */
                /** @foo array<int> */
                /** @var array<int> */
              ',
        ];
    }
}
