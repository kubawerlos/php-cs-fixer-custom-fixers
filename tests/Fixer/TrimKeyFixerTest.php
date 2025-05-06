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
 * @covers \PhpCsFixerCustomFixers\Fixer\TrimKeyFixer
 */
final class TrimKeyFixerTest extends AbstractFixerTestCase
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
        yield 'do not trim when with concatenation' => [
            <<<'PHP'
                <?php return [
                    'foo '. $a => 'v',
                    $b . ' foo' => 'v',
                    $c . ' foo ' . $d => 'v',
                ];
                PHP,
        ];

        yield 'trim array' => [
            <<<'PHP'
                <?php return [
                    'k1' => 'v ',
                    "k2" => "v",
                    'k3' => ' v',
                    "k4" => "v",
                    'k5' => 'v',
                ];
                PHP,
            <<<'PHP'
                <?php return [
                    'k1 ' => 'v ',
                    "k2    " => "v",
                    ' k3' => ' v',
                    "    k4" => "v",
                    '    k5    ' => 'v',
                ];
                PHP,
        ];

        yield 'trim generator' => [
            <<<'PHP'
                <?php function g() {
                    yield 'k1' => 'v';
                    yield 'k2' => 'v';
                    yield 'k3' => ' v ';
                    yield 'k4' => 'v';
                    yield 'k5' => 'v';
                    yield 'k6' => 'v';
                }
                PHP,
            <<<'PHP'
                <?php function g() {
                    yield 'k1 ' => 'v';
                    yield 'k2    ' => 'v';
                    yield 'k3' => ' v ';
                    yield ' k4' => 'v';
                    yield '    k5' => 'v';
                    yield '    k6    ' => 'v';
                }
                PHP,
        ];
        yield 'trim strings having non-whitespace characters' => [
            <<<'PHP'
                <?php return [
                    'k1' => true,
                    0 => 0,
                    ' ' => 1,
                    '  ' => 2,
                    'k2' => false,
                ];
                PHP,
            <<<'PHP'
                <?php return [
                    ' k1 ' => true,
                    0 => 0,
                    ' ' => 1,
                    '  ' => 2,
                    ' k2 ' => false,
                ];
                PHP,
        ];
    }
}
