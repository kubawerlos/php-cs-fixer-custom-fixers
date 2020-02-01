<?php

declare(strict_types = 1);

namespace Tests\Fixer;

/**
 * @internal
 *
 * @covers \PhpCsFixerCustomFixers\Fixer\NoSuperfluousConcatenationFixer
 */
final class NoSuperfluousConcatenationFixerTest extends AbstractFixerTestCase
{
    public function testIsRisky(): void
    {
        self::assertFalse($this->fixer->isRisky());
    }

    public function testConfiguration(): void
    {
        $options = $this->fixer->getConfigurationDefinition()->getOptions();
        self::assertArrayHasKey(0, $options);
        self::assertSame('allow_preventing_trailing_spaces', $options[0]->getName());
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
        yield ['<?php $foo. "bar";'];
        yield ['<?php "foo" .$bar;'];

        yield [
            '<?php "foobar";',
            '<?php "foo".\'bar\';',
        ];

        yield [
            '<?php "foobar";',
            '<?php "foo" . \'bar\';',
        ];

        yield [
            '<?php "foobar";',
            '<?php \'foo\' . "bar";',
        ];

        yield ['<?php "foo"
                      . "bar";'];

        yield ['<?php "foo" .
                      "bar";'];

        yield ['<?php "foo" // comment
                      . "bar";'];

        yield ['<?php "foo"/* comment
                      */. "bar";'];

        yield [
            '<?php "foo"/* comment */. "bar";',
        ];

        yield [
            '<?php "foobar";',
            '<?php "foo" . "bar";',
        ];

        yield [
            "<?php 'foobar';",
            "<?php 'foo' . 'bar';",
        ];

        yield [
            '<?php b"foobar";',
            '<?php b"foo" . "bar";',
        ];

        yield [
            '<?php B"foobar";',
            '<?php B"foo" . "bar";',
        ];

        yield [
            '<?php "foobar";',
            '<?php "foo" . B"bar";',
        ];

        yield [
            '<?php b"foobar";',
            '<?php b"foo" . b"bar";',
        ];

        yield [
            '<?php "foobarbazqux";',
            '<?php "foo" . "bar" . "baz" . "qux";',
        ];

        yield [
            '<?php b"你好世界";',
            '<?php b"你好" . b"世界";',
        ];

        yield [
            '<?php
                $a = "a"
                   . "a";
                $b = "b" .
                     "b";
                $c = $c . "c";
                $d = "d" . $d;
                $e = "ee";
                $f = "ff";
            ',
            '<?php
                $a = "a"
                   . "a";
                $b = "b" .
                     "b";
                $c = $c . "c";
                $d = "d" . $d;
                $e = \'e\' . "e";
                $f = "f" . "f";
            ',
        ];

        yield [
            '<?php
                "ab";
                $c . "d";
                "f"/* f */ . "g";
                "h" . $i;
                "j"./** k */"l";
            ',
            '<?php
                "a" . "b";
                $c . "d";
                "f"/* f */ . "g";
                "h" . $i;
                "j"./** k */"l";
            ',
        ];

        yield [
            '<?php echo "Foo  & Bar";',
            '<?php echo "Foo " . " & Bar";',
            ['allow_preventing_trailing_spaces' => true],
        ];

        yield [
            '<?php echo "Foo
                         & Bar";',
            '<?php echo "Foo" . "
                         & Bar";',
            ['allow_preventing_trailing_spaces' => true],
        ];

        yield [
            '<?php echo "Foo " . "
                         & Bar";',
            null,
            ['allow_preventing_trailing_spaces' => true],
        ];

        for ($bytevalue = 0; $bytevalue < 256; $bytevalue++) {
            $char = \chr($bytevalue);
            yield [
                \sprintf('<?php $bytevalue%d = "a_%sb_c";', $bytevalue, \addcslashes($char, '"$')),
                \sprintf('<?php $bytevalue%d = \'a_%sb\' . "_c";', $bytevalue, \addcslashes($char, "'")),
            ];
            yield [
                \sprintf('<?php $bytevalue%d = "a_%sb_c";', $bytevalue, \addcslashes($char, '"$')),
                \sprintf('<?php $bytevalue%d = "a_%sb" . \'_c\';', $bytevalue, \addcslashes($char, '"$')),
            ];
        }
    }
}
