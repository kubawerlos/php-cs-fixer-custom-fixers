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
        static::assertFalse($this->fixer->isRisky());
    }

    public function testConfiguration(): void
    {
        $options = $this->fixer->getConfigurationDefinition()->getOptions();
        static::assertArrayHasKey(0, $options);
        static::assertSame('allow_preventing_trailing_spaces', $options[0]->getName());
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
        yield ['<?php "foo".\'bar\';'];
        yield ['<?php "foo" . \'bar\';'];
        yield ['<?php \'foo\' . "bar";'];

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
            '<?php
                $a = "a"
                   . "a";
                $b = "b" .
                     "b";
                $c = $c . "c";
                $d = "d" . $d;
                $e = \'e\' . "e";
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
    }
}
