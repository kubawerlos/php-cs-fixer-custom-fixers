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

use PhpCsFixer\Fixer\ConfigurableFixerInterface;

/**
 * @internal
 *
 * @property ConfigurableFixerInterface $fixer
 *
 * @covers \PhpCsFixerCustomFixers\Fixer\NoSuperfluousConcatenationFixer
 */
final class NoSuperfluousConcatenationFixerTest extends AbstractFixerTestCase
{
    public function testIsRisky(): void
    {
        self::assertRiskiness(false);
    }

    public function testConfiguration(): void
    {
        $options = $this->fixer->getConfigurationDefinition()->getOptions();
        self::assertArrayHasKey(0, $options);
        self::assertSame('allow_preventing_trailing_spaces', $options[0]->getName());
    }

    /**
     * @param null|array<string, bool> $configuration
     *
     * @dataProvider provideFixCases
     */
    public function testStringIsTheSame(string $expected, ?string $input = null, ?array $configuration = null): void
    {
        if ($input === null) {
            $this->expectNotToPerformAssertions();
        } else {
            self::assertSame(
                eval('return ' . $expected . ';'),
                eval('return ' . $input . ';'),
            );
        }
    }

    /**
     * @param null|array<string, bool> $configuration
     *
     * @dataProvider provideFixCases
     */
    public function testFix(string $expected, ?string $input = null, ?array $configuration = null): void
    {
        $this->doTest(
            '<?php ' . $expected . ';',
            $input === null ? null : ('<?php ' . $input . ';'),
            $configuration,
        );
    }

    /**
     * @return iterable<array{0: string, 1?: null|string, 2?: array<string, bool>}>
     */
    public static function provideFixCases(): iterable
    {
        yield 'non-string on left side' => ['$foo. "bar"'];
        yield 'non-string on right side' => ['"foo" .$bar'];
        yield 'linebreak before concatenation' => ["'foo'\n.'bar'"];
        yield 'linebreak after concatenation' => ["'foo'.\n'bar'"];
        yield 'double slashes comment between strings' => ["'foo' // comment\n    . 'bar'"];
        yield 'PHPDoc comment between strings' => ['"foo"/** comment */. "bar"'];
        yield 'multiline comment between strings' => ["'foo'/* comment\n    */. 'bar'"];

        yield 'two single quoted strings' => [
            "'foobar'",
            "'foo' . 'bar'",
        ];

        yield 'two double quoted strings' => [
            '"foobar"',
            '"foo" . "bar"',
        ];

        yield 'single and double quoted strings with concatenation with spaces' => [
            '"foobar"',
            '\'foo\' . "bar"',
        ];

        yield 'double and single quoted strings with concatenation without spaces' => [
            '"foobar"',
            '"foo".\'bar\'',
        ];

        yield 'double and single quoted strings with concatenation with spaces' => [
            '"foobar"',
            '"foo" . \'bar\'',
        ];

        yield 'two binary strings' => [
            'b"foobar"',
            'b"foo" . b"bar"',
        ];

        yield 'binary string and non-binary string' => [
            'b"foobar"',
            'b"foo" . "bar"',
        ];

        yield 'non-binary string and binary string' => [
            '"foobar"',
            '"foo" . B"bar"',
        ];

        yield 'binary string with uppercase B and non-binary string' => [
            'B"foobar"',
            'B"foo" . "bar"',
        ];

        yield 'multiple concatenations' => [
            '"foobarbazqux"',
            '"foo" . "bar" . "baz" . "qux"',
        ];

        yield 'strings with non-ascii characters' => [
            'b"你好世界"',
            'b"你好" . b"世界"',
        ];

        yield 'multiple expressions' => [
            '"ab";
             $c . "d";
             "f"/* f */ . "g";
             "h" . $i;
             "j"./** k */"l";
            ',
            '"a" . "b";
             $c . "d";
             "f"/* f */ . "g";
             "h" . $i;
             "j"./** k */"l";
            ',
        ];

        yield 'multiple assignment expressions' => [
            '$a = "a"
                . "a";
             $b = "b" .
                  "b";
             $c = $c . "c";
             $d = "d" . $d;
             $e = "ee";
             $f = "ff";
            ',
            '$a = "a"
                . "a";
             $b = "b" .
                  "b";
             $c = $c . "c";
             $d = "d" . $d;
             $e = \'e\' . "e";
             $f = "f" . "f";
            ',
        ];

        yield 'option to prevent trailing spaces' => [
            '"Foo " . "
                         & Bar"',
            null,
            ['allow_preventing_trailing_spaces' => true],
        ];

        yield 'option to prevent trailing spaces without trailing spaces' => [
            '"Foo
                         & Bar"',
            '"Foo" . "
                         & Bar"',
            ['allow_preventing_trailing_spaces' => true],
        ];

        yield 'option to prevent trailing spaces with single line concatenation' => [
            '"Foo  & Bar"',
            '"Foo " . " & Bar"',
            ['allow_preventing_trailing_spaces' => true],
        ];

        yield 'dollar as last character in double quotes merged with double quotes' => [
            '"My name is \$foo"',
            '"My name is $" . "foo"',
        ];

        yield 'dollar as last character in double quotes merged with single quotes' => [
            '"My name is \$foo"',
            '"My name is $" . \'foo\'',
        ];

        yield 'dollar as last character in single quotes merged with double quotes' => [
            '"My name is \$foo"',
            '\'My name is $\' . "foo"',
        ];

        yield 'dollar as last character in single quotes merged with single quotes' => [
            '\'My name is $foo\'',
            '\'My name is $\' . \'foo\'',
        ];

        yield 'multiple dollars as last characters' => [
            '"one \$two \$three $"',
            '"one $" . "two $" . "three $"',
        ];

        yield 'escaped double quotes in single quote' => [
            <<<'CONTENT'
"\\\"Foo\\\"\n"
CONTENT
            ,
            <<<'CONTENT'
'\"Foo\"' . "\n"
CONTENT
        ];

        yield 'escaped double quotes with slash before in single quote' => [
            <<<'CONTENT'
"\\\"\Foo\\\\\"\n"
CONTENT
            ,
            <<<'CONTENT'
'\"\Foo\\\"' . "\n"
CONTENT
        ];

        yield 'double quotes in single quote with multiple slashes before' => [
            <<<'CONTENT'
"2 slashes: \\\\\", 3 slashes: \\\\\\\", 4 slashes: \\\\\\\\\";\n"
CONTENT
            ,
            <<<'CONTENT'
'2 slashes: \\\\", 3 slashes: \\\\\\", 4 slashes: \\\\\\\\";' . "\n"
CONTENT
        ];

        yield 'double quotes in single quote with multiple slashes before when last slash is not escaped' => [
            <<<'CONTENT'
"2 slashes: \\\\\", 3 slashes: \\\\\\\", 4 slashes: \\\\\\\\\";\n"
CONTENT
            ,
            <<<'CONTENT'
'2 slashes: \\\", 3 slashes: \\\\\", 4 slashes: \\\\\\\";' . "\n"
CONTENT
        ];

        yield 'empty single quoted string on left side' => [
            '\'foo\'',
            '\'\' . \'foo\'',
        ];

        yield 'empty single quoted string on right side' => [
            '\'foo\'',
            '\'foo\' . \'\'',
        ];

        yield 'empty double quoted string on left side' => [
            '"foo"',
            '"" . \'foo\'',
        ];

        yield 'empty double quoted string on right side' => [
            '"foo"',
            '\'foo\' . ""',
        ];

        yield 'two empty strings' => [
            '\'\'',
            '\'\' . \'\'',
        ];

        for ($bytevalue = 0; $bytevalue < 256; $bytevalue++) {
            $char = \chr($bytevalue);
            yield \sprintf('single quoted string with character with codepoint %d', $bytevalue) => [
                \sprintf('$bytevalue%d = "a_%sb_c"', $bytevalue, \addcslashes($char, '"$')),
                \sprintf('$bytevalue%d = \'a_%sb\' . "_c"', $bytevalue, \addcslashes($char, "'")),
            ];
            yield \sprintf('double quoted string with character with codepoint %d', $bytevalue) => [
                \sprintf('$bytevalue%d = "a_%sb_c"', $bytevalue, \addcslashes($char, '"$')),
                \sprintf('$bytevalue%d = "a_%sb" . \'_c\'', $bytevalue, \addcslashes($char, '"$')),
            ];
        }
    }
}
