<?php

declare(strict_types = 1);

namespace Tests\Fixer;

/**
 * @internal
 *
 * @covers \PhpCsFixerCustomFixers\Fixer\DataProviderNameFixer
 */
final class DataProviderNameFixerTest extends AbstractFixerTestCase
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
        yield 'data provider correctly named' => [
            '<?php
class FooTest extends TestCase {
    /**
     * @dataProvider provideFooCases
     */
    public function testFoo() {}
    public function provideFooCases() {}
}',
        ];

        yield 'fixing simple scenario with test class prefixed' => [
            '<?php
class FooTest extends TestCase {
    /**
     * @dataProvider provideFooCases
     */
    public function testFoo() {}
    public function provideFooCases() {}
}',
            '<?php
class FooTest extends TestCase {
    /**
     * @dataProvider fooDataProvider
     */
    public function testFoo() {}
    public function fooDataProvider() {}
}',
        ];

        yield 'fixing simple scenario with test class annotated' => [
            '<?php
class FooTest extends TestCase {
    /**
     * @test
     * @dataProvider provideFooCases
     */
    public function foo() {}
    public function provideFooCases() {}
}',
            '<?php
class FooTest extends TestCase {
    /**
     * @test
     * @dataProvider fooDataProvider
     */
    public function foo() {}
    public function fooDataProvider() {}
}',
        ];

        yield 'data provider not found' => [
            '<?php
class FooTest extends TestCase {
    /**
     * @dataProvider fooDataProvider
     */
    public function testFoo() {}
}',
        ];

        yield 'data provider used multiple times' => [
            '<?php
class FooTest extends TestCase {
    /**
     * @dataProvider reusedDataProvider
     */
    public function testFoo() {}
    /**
     * @dataProvider reusedDataProvider
     */
    public function testBar() {}
    public function reusedDataProvider() {}
}',
        ];

        yield 'data provider call without function' => [
            '<?php
class FooTest extends TestCase {
    /**
     * @dataProvider fooDataProvider
     */
    private $prop;
}',
        ];
    }
}