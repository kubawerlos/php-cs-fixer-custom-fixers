<?php

declare(strict_types=1);

/*
 * This file is part of PHP CS Fixer: custom fixers.
 *
 * (c) Kuba Werłos <werlos@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Tests\Fixer;

/**
 * @internal
 *
 * @covers \PhpCsFixerCustomFixers\Fixer\InternalClassCasingFixer
 */
final class InternalClassCasingFixerTest extends AbstractFixerTestCase
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
        yield ['<?php class STDCLASS {};'];
        yield ['<?php class STDCLASS { use EXCEPTION; };'];
        yield ['<?php use TheVendor\TheException as EXCEPTION;'];
        yield ['<?php new Foo\STDClass();'];
        yield ['<?php new STDClass\Foo();'];
        yield ['<?php namespace Foo; new STDClass();'];
        yield ['<?php namespace STDClass; new Foo();'];
        yield ['<?php $foo->STDCLASS();'];
        yield ['<?php $foo->STDCLASS;'];
        yield ['<?php Foo::STDCLASS();'];
        yield ['<?php Foo::STDCLASS;'];
        yield ['<?php function STDCLASS() { return 42; }; '];
        yield ['<?php STDCLASS();'];
        yield ['<?php \STDCLASS();'];
        yield ['<?php const STDCLASS = 42;'];

        yield [
            '<?php new stdClass();',
            '<?php new STDClass();',
        ];

        yield [
            '<?php new \stdClass();',
            '<?php new \STDClass();',
        ];

        yield [
            '<?php namespace Foo; new \stdClass();',
            '<?php namespace Foo; new \STDClass();',
        ];

        yield [
            '<?php function foo(stdClass $c): stdClass {}',
            '<?php function foo(STDCLASS $c): stdclass {}',
        ];

        yield [
            '<?php class Foo extends Exception {}',
            '<?php class Foo extends EXCEPTION {}',
        ];

        yield [
            '<?php
                $a = STDCLASS();
                $b = new Foo\STDCLASS();
                $c = new \stdClass();
                $d = new \stdClass();
            ',
            '<?php
                $a = STDCLASS();
                $b = new Foo\STDCLASS();
                $c = new \stdClass();
                $d = new \STDCLASS();
            ',
        ];
    }
}
