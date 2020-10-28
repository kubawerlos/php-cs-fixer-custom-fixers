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
 * @covers \PhpCsFixerCustomFixers\Fixer\DataProviderStaticFixer
 */
final class DataProviderStaticFixerTest extends AbstractFixerTestCase
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
        yield 'do not fix when containing dynamic calls' => [
            '<?php
class FooTest extends TestCase {
    /**
     * @dataProvider provideFoo1Cases
     */
    public function testFoo1() {}
    public function provideFoo1Cases() { $this->init(); }
}',
        ];

        yield 'fix single' => [
            '<?php
class FooTest extends TestCase {
    /**
     * @dataProvider provideFooCases
     */
    public function testFoo() {}
    public static function provideFooCases() { $x->getData(); }
}',
            '<?php
class FooTest extends TestCase {
    /**
     * @dataProvider provideFooCases
     */
    public function testFoo() {}
    public function provideFooCases() { $x->getData(); }
}',
        ];

        yield 'fix multiple' => [
            '<?php
class FooTest extends TestCase {
    /** @dataProvider provider1 */
    public function testFoo1() {}
    /** @dataProvider provider2 */
    public function testFoo2() {}
    /** @dataProvider provider3 */
    public function testFoo13() {}
    public static function provider1() {}
    public function provider2() { $this->init(); }
    public static function provider3() {}
}',
            '<?php
class FooTest extends TestCase {
    /** @dataProvider provider1 */
    public function testFoo1() {}
    /** @dataProvider provider2 */
    public function testFoo2() {}
    /** @dataProvider provider3 */
    public function testFoo13() {}
    public function provider1() {}
    public function provider2() { $this->init(); }
    public static function provider3() {}
}',
        ];

        yield 'fix with multilines' => [
            '<?php
class FooTest extends TestCase {
    /**
     * @dataProvider provideFooCases
     */
    public function testFoo() {}
    public
        static function
            provideFooCases() { $x->getData(); }
}',
            '<?php
class FooTest extends TestCase {
    /**
     * @dataProvider provideFooCases
     */
    public function testFoo() {}
    public
        function
            provideFooCases() { $x->getData(); }
}',
        ];

        yield 'fix when data provider is abstract' => [
            '<?php
abstract class FooTest extends TestCase {
    /**
     * @dataProvider provideFooCases
     */
    public function testFoo() {}
    abstract public static function provideFooCases();
}',
            '<?php
abstract class FooTest extends TestCase {
    /**
     * @dataProvider provideFooCases
     */
    public function testFoo() {}
    abstract public function provideFooCases();
}',
        ];
    }
}
