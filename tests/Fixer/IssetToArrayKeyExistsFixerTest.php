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
 * @covers \PhpCsFixerCustomFixers\Fixer\IssetToArrayKeyExistsFixer
 */
final class IssetToArrayKeyExistsFixerTest extends AbstractFixerTestCase
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
        yield ['<?php isset($x);'];
        yield ['<?php $object->isset($x);'];
        yield ['<?php isset($array[$key1], $array[$key2]);'];

        yield [
            '<?php array_key_exists($key, $array);',
            '<?php isset($array[$key]);',
        ];

        yield [
            '<?php array_key_exists($key, FooClass::FOO_CONST);',
            '<?php isset(FooClass::FOO_CONST[$key]);',
        ];

        yield [
            '<?php array_key_exists($key3, $array[$key1][$key2]);',
            '<?php isset($array[$key1][$key2][$key3]);',
        ];

        yield [
            '<?php array_key_exists(array_key_exists($key, $array2) ? $x : $y, $array1);',
            '<?php isset($array1[isset($array2[$key]) ? $x : $y]);',
        ];

        yield [
            '<?php
                isset($x);
                array_key_exists($key, $array1);
                isset($array2[$key], $array3[$key]);
                array_key_exists($key, $array4);
                isset($y);
            ',
            '<?php
                isset($x);
                isset($array1[$key]);
                isset($array2[$key], $array3[$key]);
                isset($array4[$key]);
                isset($y);
            ',
        ];
    }
}
