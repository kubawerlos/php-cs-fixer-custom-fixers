<?php declare(strict_types=1);

/*
 * This file is part of PHP CS Fixer: custom fixers.
 *
 * (c) 2018 Kuba Werłos
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Tests\Fixer;

use PhpCsFixer\Fixer\ConfigurableFixerInterface;
use PhpCsFixer\Fixer\DeprecatedFixerInterface;

/**
 * @internal
 *
 * @property ConfigurableFixerInterface&DeprecatedFixerInterface $fixer
 *
 * @covers \PhpCsFixerCustomFixers\Fixer\DataProviderStaticFixer
 */
final class DataProviderStaticFixerTest extends AbstractFixerTestCase
{
    public function testIsRisky(): void
    {
        self::assertTrue($this->fixer->isRisky());
    }

    public function testSuccessorName(): void
    {
        self::assertContains('php_unit_data_provider_static', $this->fixer->getSuccessorsNames());
    }

    public function testConfiguration(): void
    {
        $options = $this->fixer->getConfigurationDefinition()->getOptions();
        self::assertArrayHasKey(0, $options);
        self::assertSame('force', $options[0]->getName());
    }

    /**
     * @param null|array<string, bool> $configuration
     *
     * @dataProvider provideFixCases
     */
    public function testFix(string $expected, ?string $input = null, ?array $configuration = null): void
    {
        $this->doTest($expected, $input, $configuration);
    }

    /**
     * @return iterable<array{0: string, 1?: null|string, 2?: array<string, bool>}>
     */
    public static function provideFixCases(): iterable
    {
        yield 'do not fix when containing dynamic calls by default' => [
            '<?php
class FooTest extends TestCase {
    /**
     * @dataProvider provideFoo1Cases
     */
    public function testFoo1() {}
    public function provideFoo1Cases() { $this->init(); }
}',
        ];

        yield 'do not fix when containing dynamic calls and with `force` disabled' => [
            '<?php
class FooTest extends TestCase {
    /**
     * @dataProvider provideFoo1Cases
     */
    public function testFoo1() {}
    public function provideFoo1Cases() { $this->init(); }
}',
            null,
            ['force' => false],
        ];

        yield 'fix when containing dynamic calls and with `force` enabled' => [
            '<?php
class FooTest extends TestCase {
    /**
     * @dataProvider provideFoo1Cases
     */
    public function testFoo1() {}
    public static function provideFoo1Cases() { $this->init(); }
}',
            '<?php
class FooTest extends TestCase {
    /**
     * @dataProvider provideFoo1Cases
     */
    public function testFoo1() {}
    public function provideFoo1Cases() { $this->init(); }
}',
            ['force' => true],
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
