<?php

declare(strict_types=1);

/*
 * This file is part of PHP CS Fixer: custom fixers.
 *
 * (c) 2018-2020 Kuba Werłos
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Tests\Fixer;

use PhpCsFixer\Tokenizer\Tokens;

/**
 * @internal
 *
 * @covers \PhpCsFixerCustomFixers\Fixer\NumericLiteralSeparatorFixer
 *
 * @requires PHP 7.4
 */
final class NumericLiteralSeparatorFixerTest extends AbstractFixerTestCase
{
    public function testConfiguration(): void
    {
        $options = $this->fixer->getConfigurationDefinition()->getOptions();
        self::assertArrayHasKey(0, $options);
        self::assertSame('binary', $options[0]->getName());
        self::assertArrayHasKey(1, $options);
        self::assertSame('decimal', $options[1]->getName());
        self::assertArrayHasKey(2, $options);
        self::assertSame('float', $options[2]->getName());
        self::assertArrayHasKey(3, $options);
        self::assertSame('hexadecimal', $options[3]->getName());
        self::assertArrayHasKey(4, $options);
        self::assertSame('octal', $options[4]->getName());
    }

    public function testReversingCodeSample(): void
    {
        $codeSample = $this->fixer->getDefinition()->getCodeSamples()[0];

        Tokens::clearCache();
        $tokens = Tokens::fromCode($codeSample->getCode());

        $this->fixer->fix($this->createMock(\SplFileInfo::class), $tokens);

        $this->doTest(
            $codeSample->getCode(),
            $tokens->generateCode(),
            [
                'binary' => true,
                'decimal' => true,
                'float' => true,
                'hexadecimal' => true,
                'octal' => true,
            ]
        );
    }

    /**
     * @dataProvider provideFixCases
     */
    public function testFix(string $expected, ?string $input = null, ?array $configuration = null): void
    {
        $this->doTest(
            $expected,
            $input,
            $configuration
        );
    }

    public static function provideFixCases(): iterable
    {
        yield [
            '<?php
                echo 1234567890;
                echo 1_234_567_890;
            ',
            null,
            ['decimal' => null],
        ];

        yield [
            '<?php echo 1_234_567_890;',
            '<?php echo 1_2_3_4_5_6_7_8_9_0;',
            ['decimal' => true],
        ];

        $cases = [
            'binary' => [
                '0b0101010001101000' => '0b01010100_01101000',
                '0B0101010001101000' => '0B01010100_01101000',
                '0b01010100011010000110010101101111' => '0b01010100_01101000_01100101_01101111',
                '0b110001000' => '0b1_10001000',
                '0b100100010001000' => '0b1001000_10001000',
            ],
            'decimal' => [
                '1234' => '1_234',
                '-1234' => '-1_234',
                '12345' => '12_345',
                '123456' => '123_456',
            ],
            'float' => [
                '1234.5' => '1_234.5',
                '1.2345' => '1.234_5',
                '1234e5' => '1_234e5',
                '1234E5' => '1_234E5',
                '1e2345' => '1e2_345',
                '1234.5678e1234' => '1_234.567_8e1_234',
                '1.1e-1234' => '1.1e-1_234',
                '1.1e-12345' => '1.1e-12_345',
                '1.1e-123456' => '1.1e-123_456',
                '01234.5' => '01_234.5',
                '01234e5' => '01_234e5',
            ],
            'hexadecimal' => [
                '0x42726F776E' => '0x42_72_6F_77_6E',
                '0X42726F776E' => '0X42_72_6F_77_6E',
                '0x2726F776E' => '0x2_72_6F_77_6E',
                '0x1234567890abcdef' => '0x12_34_56_78_90_ab_cd_ef',
                '0X1234567890ABCDEF' => '0X12_34_56_78_90_AB_CD_EF',
                '0x1234e5' => '0x12_34_e5',
            ],
            'octal' => [
                '012345' => '01_2345',
                '0123456' => '012_3456',
                '01234567' => '0123_4567',
                '012345670' => '01234_5670',
            ],
        ];

        foreach ($cases as $option => $pairs) {
            foreach ($pairs as $withoutSeparator => $withSeparator) {
                yield [
                    \sprintf('<?php echo %s;', $withoutSeparator),
                    \sprintf('<?php echo %s;', $withSeparator),
                    [$option => false],
                ];
                yield [
                    \sprintf('<?php echo %s;', $withSeparator),
                    \sprintf('<?php echo %s;', $withoutSeparator),
                    [$option => true],
                ];
                yield [
                    \sprintf('<?php echo %s;', $withSeparator),
                    \sprintf('<?php echo %s;', $withoutSeparator),
                    [
                        'binary' => true,
                        'decimal' => true,
                        'float' => true,
                        'hexadecimal' => true,
                        'octal' => true,
                    ],
                ];
            }
        }

        yield [
            '<?php
                echo 0b10101010_10101010;
                echo 1234567;
                echo 1_234e1234;
                echo 0x12_34_56_78_90;
                echo 01234;
            ',
            '<?php
                echo 0b1010101010101010;
                echo 1_234_567;
                echo 1_234e1234;
                echo 0x1_234_567_890;
                echo 01_234;
            ',
            [
                'binary' => true,
                'decimal' => false,
                'float' => null,
                'hexadecimal' => true,
                'octal' => false,
            ],
        ];
    }
}
