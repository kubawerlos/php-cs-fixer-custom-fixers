<?php

declare(strict_types = 1);

namespace Tests\Fixer;

/**
 * @internal
 *
 * @covers \PhpCsFixerCustomFixers\Fixer\ImplodeCallFixer
 */
final class ImplodeCallFixerTest extends AbstractFixerTestCase
{
    public function testPriority() : void
    {
        static::assertSame(0, $this->fixer->getPriority());
    }

    /**
     * @param string      $expected
     * @param string|null $input
     *
     * @dataProvider provideFixCases
     */
    public function testFix(string $expected, string $input = null) : void
    {
        $this->doTest($expected, $input);
    }

    public function provideFixCases() : \Iterator
    {
        yield ["<?php implode('', [1,2,3]);"];
        yield ['<?php implode("", $foo);'];
        yield ['<?php implode($foo, $bar);'];
        yield ['<?php $arrayHelper->implode($foo);'];
        yield ['<?php ArrayHelper::implode($foo);'];
        yield ['<?php ArrayHelper\implode($foo);'];
        yield ['<?php define("implode", "foo"); implode; bar($baz);'];
        yield ['<?php function implode($foo) { return $foo; }'];

        yield [
            '<?php implode("", $foo);',
            '<?php implode($foo, "");',
        ];

        yield [
            '<?php \implode("", $foo);',
            '<?php \implode($foo, "");',
        ];

        yield [
            '<?php implode("Lorem ipsum dolor sit amet", $foo);',
            '<?php implode($foo, "Lorem ipsum dolor sit amet");',
        ];

        yield [
            '<?php implode(\'\', $foo);',
            '<?php implode($foo);',
        ];

        yield [
            '<?php IMPlode("", $foo);',
            '<?php IMPlode($foo, "");',
        ];

        yield [
            '<?php implode("", $weirdStuff[mt_rand($min, getMac()) + 200]);',
            '<?php implode($weirdStuff[mt_rand($min, getMac()) + 200], "");',
        ];

        yield [
            '<?php implode("",$foo);',
            '<?php implode($foo,"");',
        ];

        yield [
            '<?php
                implode(
                    "",
                    $foo
                );',
            '<?php
                implode(
                    $foo,
                    ""
                );',
        ];
    }
}
