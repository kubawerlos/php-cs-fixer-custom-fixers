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
 * @covers \PhpCsFixerCustomFixers\Fixer\ForeachUseValueFixer
 */
final class ForeachUseValueFixerTest extends AbstractFixerTestCase
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
     * @return iterable<string, array{0: string, 1?: string}>
     */
    public static function provideFixCases(): iterable
    {
        yield 'do not touch without key' => ['<?php foreach ($elements as $value) {}'];
        yield 'do not touch with unwrap' => ['<?php foreach ($elements as $key => [$value1, $value2]) {}'];
        yield 'do not touch without key and with unwrap' => ['<?php foreach ($elements as [$value1, $value2]) {}'];
        yield 'do not touch elements from call' => ['<?php foreach (self::getElements() as $key => $value) {}'];

        yield 'do not change assignment' => [
            <<<'PHP'
                <?php
                foreach ($elements as $key => $value) {
                    $elements[$key]++;
                    $elements[$key]--;
                    $elements[$key] = foo();
                    $elements[$key] += 2;
                    $elements[$key] -= 2;
                    $elements[$key] *= 2;
                    $elements[$key] /= 2;
                    $elements[$key] %= 2;
                    $elements[$key] **= 2;
                    $elements[$key] &= 2;
                    $elements[$key] |= 2;
                    $elements[$key] ^= 2;
                    $elements[$key] <<= 2;
                    $elements[$key] >>= 2;
                    $elements[$key] .= '2';
                    $elements[$key] ??= 0;
                }
                PHP,
        ];

        yield 'do not change unset' => [
            <<<'PHP'
                <?php
                foreach ($elements as $key => $value) {
                    unset($elements[$key]);
                    notUnset($value);
                    unset($elements[$key]);
                }
                PHP,
            <<<'PHP'
                <?php
                foreach ($elements as $key => $value) {
                    unset($elements[$key]);
                    notUnset($elements[$key]);
                    unset($elements[$key]);
                }
                PHP,
        ];

        yield 'do not change 2-dimension assignment' => [
            <<<'PHP'
                <?php
                foreach ($elements as $k1 => $element) {
                    foreach ($element as $k2 => $value) {
                        $elements[$k1][$k2] = 42;
                    }
                }
                PHP,
        ];

        yield 'elements in variable' => [
            <<<'PHP'
                <?php
                foreach ($elements as $key => $value) {
                    $product *= $value;
                    $elements[$key] = $value + 1;
                }
                PHP,
            <<<'PHP'
                <?php
                foreach ($elements as $key => $value) {
                    $product *= $elements[$key];
                    $elements[$key] = $elements[$key] + 1;
                }
                PHP,
        ];

        yield 'elements in variable with alternative syntax' => [
            <<<'PHP'
                <?php
                foreach ($elements as $key => $value):
                    $product *= $value;
                endforeach;
                PHP,
            <<<'PHP'
                <?php
                foreach ($elements as $key => $value):
                    $product *= $elements[$key];
                endforeach;
                PHP,
        ];

        yield 'elements in constant' => [
            <<<'PHP'
                <?php
                foreach (ELEMENTS as $key => $value) {
                    $product *= $value;
                }
                PHP,
            <<<'PHP'
                <?php
                foreach (ELEMENTS as $key => $value) {
                    $product *= ELEMENTS[$key];
                }
                PHP,
        ];

        yield 'fix only proper calls' => [
            <<<'PHP'
                <?php
                foreach ($elements as $key => $value) {
                    $result += $value;
                    $result += $elements[$notKey];
                    $result += $notElements[$notKey];
                    $result += $value;
                }
                PHP,
            <<<'PHP'
                <?php
                foreach ($elements as $key => $value) {
                    $result += $elements[$key];
                    $result += $elements[$notKey];
                    $result += $notElements[$notKey];
                    $result += $elements[$key];
                }
                PHP,
        ];

        yield 'nested loops' => [
            <<<'PHP'
                <?php
                foreach ($elements1 as $key1 => $value1) {
                    foreach ($elements2 as $key2 => $value2) {
                        foo($value1);
                        foo($elements1[$key2]);
                        foo($elements2[$key1]);
                        foo($value2);
                    }
                }
                PHP,
            <<<'PHP'
                <?php
                foreach ($elements1 as $key1 => $value1) {
                    foreach ($elements2 as $key2 => $value2) {
                        foo($elements1[$key1]);
                        foo($elements1[$key2]);
                        foo($elements2[$key1]);
                        foo($elements2[$key2]);
                    }
                }
                PHP,
        ];

        yield 'multiple loops' => [
            <<<'PHP'
                <?php
                foreach ($elements1 as $key1 => $value1) {
                    foo($value1);
                    foo($elements2[$key2]);
                    foo($elements3[$key3]);
                }
                foreach ($e as $v) {}
                foreach ($e as $k => $v);
                foreach ($elements2 as $key2 => $value2) {
                    foo($elements1[$key1]);
                    foo($value2);
                    foo($elements3[$key3]);
                }
                foreach ($elements3 as $key3 => $value3) {
                    foo($elements1[$key1]);
                    foo($elements2[$key2]);
                    foo($value3);
                }
                PHP,
            <<<'PHP'
                <?php
                foreach ($elements1 as $key1 => $value1) {
                    foo($elements1[$key1]);
                    foo($elements2[$key2]);
                    foo($elements3[$key3]);
                }
                foreach ($e as $v) {}
                foreach ($e as $k => $v);
                foreach ($elements2 as $key2 => $value2) {
                    foo($elements1[$key1]);
                    foo($elements2[$key2]);
                    foo($elements3[$key3]);
                }
                foreach ($elements3 as $key3 => $value3) {
                    foo($elements1[$key1]);
                    foo($elements2[$key2]);
                    foo($elements3[$key3]);
                }
                PHP,
        ];
    }
}
