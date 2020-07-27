<?php

/*
 * This file is part of PHP CS Fixer: custom fixers.
 *
 * (c) 2018-2020 Kuba Werłos
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Tests\Fixer;

/**
 * @internal
 *
 * @covers \PhpCsFixerCustomFixers\Fixer\CommentSurroundedBySpacesFixer
 */
final class CommentSurroundedBySpacesFixerTest extends AbstractFixerTestCase
{
    public function testIsRisky(): void
    {
        self::assertFalse($this->fixer->isRisky());
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
        yield ['<?php $a; //'];
        yield ['<?php $a; ////'];
        yield ['<?php $a; /**/'];
        yield ['<?php $a; // foo'];
        yield ['<?php $a; # foo'];
        yield ['<?php $a; /* foo */'];
        yield ['<?php $a; /** foo */'];
        yield ['<?php $a; /**  foo  */'];
        yield ["<?php AA; /**\tfoo\t*/"];

        yield [
            '<?php
                /*
                 * foo
                 */',
        ];

        yield [
            '<?php $a; //  foo',
        ];

        yield [
            '<?php $a; // foo',
            '<?php $a; //foo',
        ];

        yield [
            '<?php $a; # foo',
            '<?php $a; #foo',
        ];

        yield [
            '<?php $a; /* foo */',
            '<?php $a; /*foo */',
        ];

        yield [
            '<?php $a; /* foo */',
            '<?php $a; /* foo*/',
        ];

        yield [
            '<?php $a; /* foo */',
            '<?php $a; /*foo*/',
        ];

        yield [
            '<?php $a; /* foo  */',
            '<?php $a; /*foo  */',
        ];

        yield [
            '<?php $a; /*  foo */',
            '<?php $a; /*  foo*/',
        ];

        yield [
            '<?php $a; /** foo */',
            '<?php $a; /**foo*/',
        ];

        yield [
            '<?php $a; /** foo */',
            '<?php $a; /** foo*/',
        ];

        yield [
            '<?php $a; /**** foo ****/',
            '<?php $a; /****foo****/',
        ];

        yield [
            '<?php $a; /* foo */// bar',
            '<?php $a; /*foo*///bar',
        ];

        yield [
            '<?php
                // foo
                //
                // bar
            ',
            '<?php
                //foo
                //
                //bar
            ',
        ];
    }
}
