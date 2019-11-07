<?php

declare(strict_types = 1);

namespace Tests\Fixer;

/**
 * @internal
 *
 * @covers \PhpCsFixerCustomFixers\Fixer\NoUselessSprintfFixer
 */
final class NoUselessSprintfFixerTest extends AbstractFixerTestCase
{
    public function testPriority(): void
    {
        static::assertSame(0, $this->fixer->getPriority());
    }

    public function testIsRisky(): void
    {
        static::assertTrue($this->fixer->isRisky());
    }

    /**
     * @dataProvider provideFixCases
     */
    public function testFix(string $expected, ?string $input = null): void
    {
        $this->doTest($expected, $input);
    }

    public function provideFixCases(): iterable
    {
        yield ['<?php $foo = sprintf($format, $value);'];
        yield ['<?php $foo = sprintf("My name is %s.", "Earl");'];
        yield ['<?php $foo = sprintf("Sum of %d and %d is %d.", 2, 2, 4);'];
        yield ['<?php $foo = sprintf();'];
        yield ['<?php $foo = sprintf[0]("Bar");'];
        yield ['<?php $foo = $printingHelper->sprintf($bar);'];
        yield ['<?php $foo = PrintingHelper::sprintf($bar);'];
        yield ['<?php $foo = PrintingHelper\sprintf($bar);'];
        yield ['<?php define("sprintf", "foo"); sprintf; bar($baz);'];
        yield ['<?php namespace Foo; function sprintf($bar) { return $baz; }'];

        yield [
            '<?php $foo;',
            '<?php sprintf($foo);',
        ];

        yield [
            '<?php $foo;',
            '<?php \sprintf($foo);',
        ];

        yield [
            '<?php $foo ;',
            '<?php \ sprintf ( $foo ) ;',
        ];

        yield [
            '<?php $foo;',
            '<?php SPRINTF($foo);',
        ];

        yield [
            '<?php $foo;',
            '<?php sprintf(sprintf(sprintf($foo)));',
        ];

        yield [
            '<?php sprintf($foo, 7);',
            '<?php sprintf(sprintf(sprintf($foo), 7));',
        ];

        yield [
            '<?php
                $foo = "Foo";',
            '<?php
                $foo = sprintf(
                    "Foo"
                );',
        ];

        yield [
            '<?php
                PrintingHelper::sprintf("Message");
                $foo = sprintf("Hello, %s!", "Foo");
                $bar = "Bar";
                $baz = "Baz";
            ',
            '<?php
                PrintingHelper::sprintf("Message");
                $foo = sprintf("Hello, %s!", "Foo");
                $bar = sprintf("Bar");
                $baz = sprintf("Baz");
            ',
        ];
    }
}
