<?php

declare(strict_types = 1);

namespace Tests\Fixer;

/**
 * @internal
 *
 * @covers \PhpCsFixerCustomFixers\Fixer\PhpdocSingleLineVarFixer
 */
final class PhpdocSingleLineVarFixerTest extends AbstractFixerTestCase
{
    public function testPriority(): void
    {
        static::assertSame(0, $this->fixer->getPriority());
    }

    public function testIsRisky(): void
    {
        static::assertFalse($this->fixer->isRisky());
    }

    /**
     * @param string      $expected
     * @param null|string $input
     *
     * @dataProvider provideFixCases
     */
    public function testFix(string $expected, string $input = null): void
    {
        $this->doTest($expected, $input);
    }

    public function provideFixCases(): \Generator
    {
        yield [ // Wrong annotation
            '<?php
/**
 * @throws Foo
 */
',
        ];

        yield [
            '<?php /** @var Foo */',
        ];

        yield [
            '<?php /** @var Foo $foo This is foo */',
        ];

        yield [ // Only single @var can be fixed
            '<?php
/**
 * @var Foo $foo
 * @var Bar $bar
 */
',
        ];

        yield [ // Do not fix if other annotation exists
            '<?php
/**
 * @var Foo $foo
 * @param Bar $bar
 */
',
        ];

        yield [ // Do not fix if description exists
            '<?php
/**
 * Foo
 *
 * @var Bar
 */
',
        ];

        yield [ // Do not fix if inheritdoc exists
            '<?php
/**
 * @var Foo
 *
 * {@inheritdoc}
 */
',
        ];

        yield [
            '<?php
/** @var Foo */
',
            '<?php
/**
 * @var Foo
 */
',
        ];

        yield [
            '<?php
/** @var Foo|Bar|Baz|null|string|int|stdClass */
',
            '<?php
/**
 * @var Foo|Bar|Baz|null|string|int|stdClass
 */
',
        ];

        yield [
            '<?php
/** @var Foo $foo */
',
            '<?php
/**
 * @var Foo $foo
 */
',
        ];

        yield [
            '<?php
/** @var Foo $foo This is foo */
',
            '<?php
/**
 * @var Foo $foo This is foo
 */
',
        ];

        yield [
            '<?php
/** @var Foo */
',
            '<?php
/**
 *
 *
 * @var Foo
 *
 *
 *
 *
 */
',
        ];

        yield [
            '<?php
/** @var Foo $foo */
',
            '<?php
/** @var Foo $foo
 */
',
        ];

        yield [
            '<?php
/** @var Foo $foo */
',
            '<?php
/**
 * @var Foo $foo */
',
        ];

        yield [
            '<?php
/** @var Foo */
',
            '<?php
/**    @var Foo        */
',
        ];
    }
}
