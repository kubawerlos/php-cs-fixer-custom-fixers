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

/**
 * @internal
 *
 * @covers \PhpCsFixerCustomFixers\Fixer\NoDuplicatedImportsFixer
 */
final class NoDuplicatedImportsFixerTest extends AbstractFixerTestCase
{
    public function testIsRisky(): void
    {
        self::assertRiskiness(false);
    }

    /**
     * @dataProvider provideFixCases
     */
    public function testFix(string $expected, ?string $input = null): void
    {
        if (\getenv('FAST_LINT_TEST_CASES') !== '1') {
            self::markTestSkipped('Duplicated imports can be tested only if FAST_LINT_TEST_CASES is enabled.');
        }
        $this->doTest($expected, $input);
    }

    /**
     * @return iterable<array{0: string, 1?: string}>
     */
    public static function provideFixCases(): iterable
    {
        yield [
            '<?php
                namespace FooBar;
                use Foo;
                use Bar;
            ',
            '<?php
                namespace FooBar;
                use Foo;
                use Foo;
                use Bar;
            ',
        ];

        yield [
            '<?php
                namespace FooBar;
                use Foo;
                use Bar;
            ',
            '<?php
                namespace FooBar;
                use Foo;
                use Bar;
                use Foo;
            ',
        ];

        yield [
            '<?php
                namespace FooBar;
                use Vendor\\Project\\Foo;
                use Bar;
                use Baz;
            ',
            '<?php
                namespace FooBar;
                use Vendor\\Project\\Foo;
                use Bar;
                use Vendor\\Project\\Foo;
                use Baz;
            ',
        ];

        yield [
            '<?php
                namespace FooBar;
                use Vendor\\Project\\Duplicated\\Foo;
                use Vendor\\Project\\Duplicated\\Bar;
            ',
            '<?php
                namespace FooBar;
                use Vendor\\Project\\Duplicated\\Foo;
                use Vendor\\Foo;
                use Vendor\\Project\\Duplicated\\Bar;
                use Vendor\\Project\\Duplicated\\Foo;
                use Vendor\\Project\\Duplicated\\Bar;
                use Vendor\\Bar;
                use Vendor\\Project\\Duplicated\\Foo;
                use Vendor\\Project\\Duplicated\\Foo;
            ',
        ];

        yield [
            '<?php
                namespace Foo;
                use Vendor\\Class1;
                use Vendor\\Class2;
                namespace Bar;
                use Vendor\\Class1;
                use Vendor\\Class2;
            ',
            '<?php
                namespace Foo;
                use Vendor\\Class1;
                use Vendor\\Class1;
                use Vendor\\Class2;
                namespace Bar;
                use Vendor\\Class1;
                use Vendor\\Class2;
                use Vendor\\Class2;
            ',
        ];

        yield [
            '<?php
                namespace N;
                use Foo\\Bar;
                use Foo\\Bar as Baz;
            ',
        ];

        yield [
            '<?php
                namespace N;
                use Foo;
                use function Foo;
                use const Foo;
            ',
        ];

        yield [
            '<?php
                namespace N;
                use Foo\\Bar;
            ',
            '<?php
                namespace N;
                use Foo\\Bar;
                use Foo\\Baz as Bar;
            ',
        ];

        yield [
            '<?php
                namespace N;
                use Foo;
                use Bar;
                use const Foo;
                use const Bar;
                use function Foo;
                use function Bar;
            ',
            '<?php
                namespace N;
                use Foo;
                use Foo;
                use Bar;
                use Bar;
                use Bar;
                use const Foo;
                use const Foo;
                use const Bar;
                use const Bar;
                use const Bar;
                use function Foo;
                use function Foo;
                use function Bar;
                use function Bar;
                use function Bar;
            ',
        ];
    }
}
