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
 * @covers \PhpCsFixerCustomFixers\Fixer\SingleSpaceBeforeStatementFixer
 */
final class SingleSpaceBeforeStatementFixerTest extends AbstractFixerTestCase
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
        yield ['<?php $isNotFoo = !require "foo.php";'];
        yield ['<?php foo(new stdClass());'];
        yield ['<?php $content = @include "foo.php";'];
        yield ['<?php $items = [new Item(), new Item()];'];
        yield ['<?php class Foo {public function bar() {}}'];
        yield ['<?php foo(
                          new Item()
                      );'];
        yield ['<?php
                      new Foo();'];

        yield [
            '<?php new Foo();',
            '<?php  new Foo();',
        ];

        yield [
            '<?php class Foo { public function bar() {} }',
            '<?php class Foo { public    function bar() {} }',
        ];

        yield [
            '<?php $items = [new Item(), new Item()];',
            '<?php $items = [new Item(),new Item()];',
        ];

        yield [
            '<?php
                $x = new stdClass();
                $bar. require("bar_description.txt");
                $baz =
                       new Baz();
            ',
            '<?php
                $x =   new stdClass();
                $bar.require("bar_description.txt");
                $baz =
                       new Baz();
            ',
        ];

        yield [
            '<?php $items = [new Item(), new Item()]; ?> and <?php class Foo { public function bar() {} } ?>',
            '<?php $items = [new Item(),new Item()]; ?> and <?php class Foo { public    function bar() {} } ?>',
        ];

        yield [
            '<?php $items = [new Item(), new Item()]; ?> and <?php /* class: */class Foo { public function bar() {} } ?>',
            '<?php $items = [new Item(),new Item()]; ?> and <?php /* class: */class Foo { public    function bar() {} } ?>',
        ];
    }
}
