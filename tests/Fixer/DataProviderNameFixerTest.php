<?php

declare(strict_types=1);

namespace Tests\Fixer;

use PhpCsFixer\FixerConfiguration\FixerOptionInterface;

/**
 * @internal
 *
 * @covers \PhpCsFixerCustomFixers\Fixer\DataProviderNameFixer
 */
final class DataProviderNameFixerTest extends AbstractFixerTestCase
{
    public function testConfiguration(): void
    {
        /** @var FixerOptionInterface[] $options */
        $options = $this->fixer->getConfigurationDefinition()->getOptions();
        self::assertArrayHasKey(0, $options);
        self::assertSame('prefix', $options[0]->getName());
        self::assertSame('provide', $options[0]->getDefault());
        self::assertArrayHasKey(1, $options);
        self::assertSame('suffix', $options[1]->getName());
        self::assertSame('Cases', $options[1]->getDefault());
    }

    public function testIsRisky(): void
    {
        self::assertTrue($this->fixer->isRisky());
    }

    /**
     * @dataProvider provideFixCases
     */
    public function testFix(string $expected, ?string $input = null, ?array $configuration = null): void
    {
        $this->doTest($expected, $input, $configuration);
    }

    public static function provideFixCases(): iterable
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
     * @dataProvider notExistingFunction
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

        yield 'data provider target name already used' => [
            '<?php
class FooTest extends TestCase {
    /**
     * @dataProvider dataProvider
     */
    public function testFoo() {}
    public function dataProvider() {}
    public function provideFooCases() {}
}',
        ];

        yield 'data provider defined for anonymous function' => [
            '<?php
class FooTest extends TestCase {
    public function testFoo()
    {
        /**
         * @dataProvider notDataProvider
         */
        function () { return true; };
    }
    public function notDataProvider() {}
}',
        ];

        yield 'multiple data providers for test' => [
            '<?php
class FooTest extends TestCase {
    /**
     * @dataProvider provideFooCases
     * @dataProvider foo2DataProvider
     */
    public function testFoo() {}
    public function provideFooCases() {}
    public function foo2DataProvider() {}
}',
            '<?php
class FooTest extends TestCase {
    /**
     * @dataProvider foo1DataProvider
     * @dataProvider foo2DataProvider
     */
    public function testFoo() {}
    public function foo1DataProvider() {}
    public function foo2DataProvider() {}
}',
        ];

        yield 'data providers with new name as part of namespace' => [
            '<?php
class FooTest extends TestCase {
    /**
     * @dataProvider provideFooCases
     */
    public function testFoo() {
        $x = Foo\ProvideFooCases::X_DEFAULT;
    }
    public function provideFooCases() {}
}',
            '<?php
class FooTest extends TestCase {
    /**
     * @dataProvider foo
     */
    public function testFoo() {
        $x = Foo\ProvideFooCases::X_DEFAULT;
    }
    public function foo() {}
}',
        ];

        yield 'complex example' => [
            '<?php
class FooTest extends TestCase {
    /** @dataProvider notExistingFunction */
    public function testClosure()
    {
        /** Preparing data */
        $x = 0;
        /** @dataProvider notDataProvider */
        function () { return true; };
    }

    /**
     * @dataProvider reusedDataProvider
     * @dataProvider provideFooCases
     */
    public function testFoo() {}

    /**
     * @dataProvider reusedDataProvider
     * @dataProvider provideBarCases
     */
    public function testBar() {}

    public function reusedDataProvider() {}

    /** @dataProvider provideBazCases */
    public function testBaz() {}
    public function provideBazCases() {}

    /** @dataProvider provideSomethingCases */
    public function testSomething() {}
    public function provideSomethingCases() {}
    public function provideFooCases() {}
    public function provideBarCases() {}
}',
            '<?php
class FooTest extends TestCase {
    /** @dataProvider notExistingFunction */
    public function testClosure()
    {
        /** Preparing data */
        $x = 0;
        /** @dataProvider notDataProvider */
        function () { return true; };
    }

    /**
     * @dataProvider reusedDataProvider
     * @dataProvider testFooProvider
     */
    public function testFoo() {}

    /**
     * @dataProvider reusedDataProvider
     * @dataProvider testBarProvider
     */
    public function testBar() {}

    public function reusedDataProvider() {}

    /** @dataProvider provideBazCases */
    public function testBaz() {}
    public function provideBazCases() {}

    /** @dataProvider someDataProvider */
    public function testSomething() {}
    public function someDataProvider() {}
    public function testFooProvider() {}
    public function testBarProvider() {}
}',
        ];

        foreach (['abstract', 'final', 'private', 'protected', 'static', '/* private */'] as $modifier) {
            yield \sprintf('test function with %s modifier', $modifier) => [
                \sprintf('<?php
                class FooTest extends TestCase {
                    /**
                     * @dataProvider provideFooCases
                     */
                    %s function testFoo() {}
                    public function provideFooCases() {}
                }', $modifier),
                \sprintf('<?php
                class FooTest extends TestCase {
                    /**
                     * @dataProvider fooDataProvider
                     */
                    %s function testFoo() {}
                    public function fooDataProvider() {}
                }', $modifier),
            ];
        }

        yield 'custom prefix' => [
            '<?php
class FooTest extends TestCase {
    /**
     * @dataProvider theBestPrefixFooCases
     */
    public function testFoo() {}
    public function theBestPrefixFooCases() {}
}',
            '<?php
class FooTest extends TestCase {
    /**
     * @dataProvider dp
     */
    public function testFoo() {}
    public function dp() {}
}',
            ['prefix' => 'theBestPrefix'],
        ];

        yield 'custom suffix' => [
            '<?php
class FooTest extends TestCase {
    /**
     * @dataProvider provideFooTheBestSuffix
     */
    public function testFoo() {}
    public function provideFooTheBestSuffix() {}
}',
            '<?php
class FooTest extends TestCase {
    /**
     * @dataProvider dp
     */
    public function testFoo() {}
    public function dp() {}
}',
            ['suffix' => 'TheBestSuffix'],
        ];

        yield 'custom prefix and suffix' => [
            '<?php
class FooTest extends TestCase {
    /**
     * @dataProvider theBestPrefixFooTheBestSuffix
     */
    public function testFoo() {}
    public function theBestPrefixFooTheBestSuffix() {}
}',
            '<?php
class FooTest extends TestCase {
    /**
     * @dataProvider dp
     */
    public function testFoo() {}
    public function dp() {}
}',

            [
                'prefix' => 'theBestPrefix',
                'suffix' => 'TheBestSuffix',
            ],
        ];

        yield 'empty suffix' => [
            '<?php
class FooTest extends TestCase {
    /**
     * @dataProvider dataProviderForFoo
     */
    public function testFoo() {}
    public function dataProviderForFoo() {}
}',
            '<?php
class FooTest extends TestCase {
    /**
     * @dataProvider dp
     */
    public function testFoo() {}
    public function dp() {}
}',

            [
                'prefix' => 'dataProviderFor',
                'suffix' => '',
            ],
        ];
    }
}
