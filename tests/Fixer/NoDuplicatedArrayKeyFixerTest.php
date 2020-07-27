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
 * @covers \PhpCsFixerCustomFixers\Fixer\NoDuplicatedArrayKeyFixer
 */
final class NoDuplicatedArrayKeyFixerTest extends AbstractFixerTestCase
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
            '<?php $x = [1, 1, 2, 2];',
        ];

        foreach (['1', '1.0', '"foo"', "'foo'", 'KEY_123', 'Constants::CONFIG_KEY', 'Library\\Constants::CONFIG_KEY'] as $duplicatedKey) {
            yield [
                \sprintf('<?php
                $x = [
                    "not_duplicated_key" => $v,
                    %s => $v,
                ];
            ', $duplicatedKey),
                \sprintf('<?php
                $x = [
                    %s => $v,
                    "not_duplicated_key" => $v,
                    %s => $v,
                ];
            ', $duplicatedKey, $duplicatedKey),
            ];
        }

        yield [
            '<?php $x = [2 => $e, 1 => $e];',
            '<?php $x = [1 => $e, 2 => $e, 1 => $e];',
        ];

        yield ['<?php
                $x = [
                    $i++ => $i,
                    $i++ => $i,
                    $i++ => $i,
                ];
        '];

        yield ['<?php
                $x = [
                    random_key() => true,
                    random_key() => true,
                    random_key() => true,
                ];
        '];

        yield ['<?php
                $x = [
                    Randomizer::key() => true,
                    Randomizer::key() => true,
                    Randomizer::key() => true,
                ];
        '];

        yield [
            '<?php
                $x = array(
                    2 => $e,
                    1 => $e,
                );
            ',
            '<?php
                $x = array(
                    1 => $e,
                    2 => $e,
                    1 => $e,
                );
            ',
        ];

        yield [
            '<?php
                $x = [
                    "bar" => 2,
                    "foo" => 3,
                ];
            ',
            '<?php
                $x = [
                    "foo" => 1,
                    "bar" => 2,
                    "foo" => 3,
                ];
            ',
        ];

        yield [
            '<?php
                $x = [
                             0,
                             2,
                    "bar" => 3,
                             4,
                    "foo" => 5,
                             6,
                ];
            ',
            '<?php
                $x = [
                             0,
                    "foo" => 1,
                             2,
                    "bar" => 3,
                             4,
                    "foo" => 5,
                             6,
                ];
            ',
        ];

        yield [
            '<?php
                $x = [
                    // comment 1
                    "foo" => 1,
                    // comment 2
                    // comment 3
                    "bar" => 3,
                    // comment 4
                ];
            ',
            '<?php
                $x = [
                    // comment 1
                    "foo" => 1,
                    // comment 2
                    "bar" => 2,
                    // comment 3
                    "bar" => 3,
                    // comment 4
                ];
            ',
        ];

        yield [
            '<?php
                $x = [
                    1 + 1 => 3,
                    1 + 2 => 4,
                    6 + 1 => 5,
                ];
            ',
            '<?php
                $x = [
                    1 + 1 => 1,
                    1 + 2 => 2,
                    1 + 1 => 3,
                    1 + 2 => 4,
                    6 + 1 => 5,
                ];
            ',
        ];

        yield [
            '<?php $x = [
                [
                    "foo" => "bar",
                ],
                [
                    "foo" => "bar",
                ],
            ];',
        ];
    }
}
