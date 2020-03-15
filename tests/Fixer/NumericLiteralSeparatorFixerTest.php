<?php

declare(strict_types = 1);

namespace Tests\Fixer;

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
        yield 'null ignores fixing' => [
            '<?php
                echo 1234567890;
                echo 1_234_567_890;
            ',
            null,
            ['decimal' => null],
        ];
        yield 'default is to remove separator for binaries' => [
            '<?php echo 0b01010100011010000110010101101111;',
            '<?php echo 0b01010100_01101000_01100101_01101111;',
        ];
        yield 'removing separator for binaries' => [
            '<?php echo 0b01010100011010000110010101101111;',
            '<?php echo 0b01010100_01101000_01100101_01101111;',
            ['binary' => false],
        ];
        yield 'adding separator for binaries' => [
            '<?php echo 0b01010100_01101000_01100101_01101111;',
            '<?php echo 0b01010100011010000110010101101111;',
            ['binary' => true],
        ];
        yield 'default is to remove separator for decimals' => [
            '<?php echo 1234567890;',
            '<?php echo 1_234_567_890;',
        ];
        yield 'removing separator for decimals' => [
            '<?php echo 1234567890;',
            '<?php echo 1_2_3_4_567890;',
            ['decimal' => false],
        ];
        yield 'adding separator for decimals' => [
            '<?php
                echo 123_456;
                echo 1_234_567_890;
                echo -123;
                echo -123_456;
                echo 1_234_567_890;
                echo -0;
            ',
            '<?php
                echo 123456;
                echo 1234567890;
                echo -123;
                echo -123456;
                echo 1_2_3_4_5_6_7_8_9_0;
                echo -0;
            ',
            ['decimal' => true],
        ];
        yield 'default is to remove separator for floats' => [
            '<?php echo 1234567890.12;',
            '<?php echo 1_234_567_890.12;',
        ];
        yield 'removing separator for floats' => [
            '<?php echo 1234567890.12;',
            '<?php echo 1_234_567_890.12;',
            ['float' => false],
        ];
        yield 'adding separator for floats' => [
            '<?php
                echo 12_345.6;
                echo 12_345.678_89;
                echo 1_234_567_890.12;
                echo 1_000e10;
                echo 2_000e10;
                echo 3_000e-8;
                echo -100_000e-8;
                echo 1.234_567_89e100;
                echo 123_456_789.123_456_78e123_456;
            ',
            '<?php
                echo 12345.6;
                echo 12345.67889;
                echo 1234567890.12;
                echo 1000e10;
                echo 2_0_0_0e10;
                echo 3000e-8;
                echo -100000e-8;
                echo 1.23456789e100;
                echo 123456789.12345678e123456;
            ',
            ['float' => true],
        ];
        yield 'default is to remove separator for hexadecimals' => [
            '<?php echo 0x42726F776E;',
            '<?php echo 0x42_72_6F_77_6E;',
        ];
        yield 'removing separator for hexadecimals' => [
            '<?php echo 0x42726F776E;',
            '<?php echo 0x42_72_6F_77_6E;',
            ['hexadecimal' => false],
        ];
        yield 'adding separator for hexadecimals' => [
            '<?php
                echo 0x42_72_6F_77_6E;
                echo 0x42_72_6F_77_6E;
            ',
            '<?php
                echo 0x42726F776E;
                echo 0x42_726F_776E;
            ',
            ['hexadecimal' => true],
        ];
        yield 'default is to remove separator for octals' => [
            '<?php echo 01234567;',
            '<?php echo 0123_4567;',
        ];
        yield 'removing separator for octals' => [
            '<?php echo 01234567;',
            '<?php echo 0123_4567;',
            ['octal' => false],
        ];
        yield 'adding separator for octals' => [
            '<?php echo 0123_4567;',
            '<?php echo 01234567;',
            ['octal' => true],
        ];
        yield 'uppercase binary' => [
            '<?php echo 0b01010100_01101000_01100101_01101111;',
            '<?php echo 0b01010100011010000110010101101111;',
            ['binary' => true],
        ];
        yield 'uppercase float' => [
            '<?php
                echo 1_234E78;
                echo 1_234.56E78;
            ',
            '<?php
                echo 1234E78;
                echo 1234.56E78;
            ',
            ['float' => true],
        ];
        yield 'uppercase hexadecimal' => [
            '<?php echo 0X42_72_6F_77_6E;',
            '<?php echo 0X42726F776E;',
            ['hexadecimal' => true],
        ];
        yield 'add separators for all numbers' => [
            '<?php
                echo 1_000_000;
                echo 1e10;
                echo 1E10;
                echo -1e-5;
                echo 0X42_72_6F_77_6E;
                echo 0x12_3e_45;
                echo 012_345.123_45e12_345;
            ',
            '<?php
                echo 1000000;
                echo 1e10;
                echo 1E10;
                echo -1e-5;
                echo 0X42726F776E;
                echo 0x123e45;
                echo 012345.12345e12345;
            ',
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
