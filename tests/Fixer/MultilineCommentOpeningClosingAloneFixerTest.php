<?php

declare(strict_types = 1);

namespace Tests\Fixer;

/**
 * @internal
 *
 * @covers \PhpCsFixerCustomFixers\Fixer\MultilineCommentOpeningClosingAloneFixer
 */
final class MultilineCommentOpeningClosingAloneFixerTest extends AbstractFixerTestCase
{
    public function testIsRisky(): void
    {
        static::assertFalse($this->fixer->isRisky());
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
        yield ['<?php /* Foo */'];
        yield ['<?php /** Foo */'];

        yield ['<?php /**
                    * Foo
                    */'];

        yield [
            '<?php
                /*
                 * Foo
                 */',
            '<?php
                /* Foo
                 */',
        ];

        yield [
            '<?php
                /*
                 * Foo
                 */',
            '<?php
                /*Foo
                 */',
        ];

        yield [
            '<?php
                /*
                 *    Foo
                 */',
            '<?php
                /*    Foo
                 */',
        ];

        yield [
            '<?php
                /**
                 * Foo
                 */',
            '<?php
                /** Foo
                 */',
        ];

        yield [
            '<?php
                /**
                 * Foo
                 */',
            '<?php
                /**Foo
                 */',
        ];

        yield [
            '<?php
                /****
                 * Foo
                 ****/',
            '<?php
                /**** Foo
                 ****/',
        ];

        yield [
            '<?php
                /*
                 * Foo
                 */',
            '<?php
                /*
                 * Foo */',
        ];

        yield [
            '<?php
                /**
                 * Foo
                 */',
            '<?php
                /**
                 * Foo */',
        ];

        yield [
            '<?php
                /**
                 * Foo
                 */',
            '<?php
                /**
                 * Foo*/',
        ];

        yield [
            \str_replace("\n", "\r", '<?php
                /**
                 * Foo
                 */'),
            \str_replace("\n", "\r", '<?php
                /**
                 * Foo */'),
        ];

        yield [
            '<?php
                /**
                 * Foo
                 * Bar
                 * Baz
                 */',
            '<?php
                /** Foo
                 * Bar
                 * Baz */',
        ];

        yield [
            \str_replace("\n", "\r", '<?php
                /**
                 * Foo
                 * Bar
                 * Baz
                 */'),
            \str_replace("\n", "\r", '<?php
                /** Foo
                 * Bar
                 * Baz */'),
        ];

        yield [
            '<?php
                /*
                 * //Foo
                 */',
            '<?php
                /*//Foo
                 */',
        ];

        yield ['<?php // with invisible character at the end' . \chr(226) . \chr(128) . \chr(168)];

        yield [
            '<?php
                /*
                 * Foo
                 */
                /* Bar */
                /*
                 * Baz
                 */
             ',
            '<?php
                /* Foo
                 */
                /* Bar */
                /*
                 * Baz
                 */
             ',
        ];
    }
}
