<?php

declare(strict_types = 1);

namespace Tests\Fixer;

/**
 * @internal
 *
 * @covers \PhpCsFixerCustomFixers\Fixer\NoUnneededConcatenationFixer
 */
final class NoUnneededConcatenationFixerTest extends AbstractFixerTestCase
{
    public function testIsRisky(): void
    {
        static::assertFalse($this->fixer->isRisky());
    }

    public function testSuccessorName(): void
    {
        static::assertContains('NoSuperfluousConcatenationFixer', $this->fixer->getSuccessorsNames());
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
    }
}
