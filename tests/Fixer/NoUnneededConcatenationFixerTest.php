<?php

declare(strict_types = 1);

namespace Tests\Fixer;

use PhpCsFixer\Fixer\StringNotation\SingleQuoteFixer;

/**
 * @internal
 *
 * @covers \PhpCsFixerCustomFixers\Fixer\NoUnneededConcatenationFixer
 */
final class NoUnneededConcatenationFixerTest extends AbstractFixerTestCase
{
    public function testPriority(): void
    {
        static::assertLessThan((new SingleQuoteFixer())->getPriority(), $this->fixer->getPriority());
    }

    public function testIsRisky(): void
    {
        static::assertFalse($this->fixer->isRisky());
    }

    /**
     * @param string      $expected
     * @param null|string $input
     *
     * @dataProvider provideFixCases
     */
    public function testFix(string $expected, ?string $input = null): void
    {
        $this->doTest($expected, $input);
    }

    public function provideFixCases(): iterable
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
    }
}
