<?php

declare(strict_types = 1);

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
                use Vendor\Project\Foo;
                use Bar;
                use Baz;
            ',
            '<?php
                namespace FooBar;
                use Vendor\Project\Foo;
                use Bar;
                use Vendor\Project\Foo;
                use Baz;
            ',
        ];

        yield [
            '<?php
                namespace FooBar;
                use Vendor\Project\Duplicated\Foo;
                use Vendor\Foo;
                use Vendor\Project\Duplicated\Bar;
                use Vendor\Bar;
            ',
            '<?php
                namespace FooBar;
                use Vendor\Project\Duplicated\Foo;
                use Vendor\Foo;
                use Vendor\Project\Duplicated\Bar;
                use Vendor\Project\Duplicated\Foo;
                use Vendor\Project\Duplicated\Bar;
                use Vendor\Bar;
                use Vendor\Project\Duplicated\Foo;
                use Vendor\Project\Duplicated\Foo;
            ',
        ];

        yield [
            '<?php
                namespace Foo;
                use Vendor\Class1;
                use Vendor\Class2;
                namespace Bar;
                use Vendor\Class1;
                use Vendor\Class2;
            ',
            '<?php
                namespace Foo;
                use Vendor\Class1;
                use Vendor\Class1;
                use Vendor\Class2;
                namespace Bar;
                use Vendor\Class1;
                use Vendor\Class2;
                use Vendor\Class2;
            ',
        ];
    }
}
