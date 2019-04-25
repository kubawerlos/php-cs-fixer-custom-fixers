<?php

declare(strict_types = 1);

namespace Tests\Fixer;

/**
 * @internal
 *
 * @covers \PhpCsFixerCustomFixers\Fixer\PhpdocVarAnnotationToAssertFixer
 */
final class PhpdocVarAnnotationToAssertFixerTest extends AbstractFixerTestCase
{
    public function testPriority(): void
    {
        static::assertSame(5, $this->fixer->getPriority());
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
    public function testFix(string $expected, ?string $input = null): void
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
            '<?php assert($foo instanceof Foo); // This is foo',
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
/**
 * @var Foo
 */
',
            '<?php
/**
 * @var Foo
 */
',
        ];

        yield [
            '<?php
assert($foo instanceof Foo || $foo instanceof Bar || $foo instanceof Baz || is_null($foo) || is_string($foo) || is_int($foo) || $foo instanceof stdClass);
',
            '<?php
/**
 * @var Foo|Bar|Baz|null|string|int|stdClass $foo
 */
',
        ];

        yield [
            '<?php
assert($foo instanceof Foo && $foo instanceof Bar);
',
            '<?php
/**
 * @var Foo&Bar $foo
 */
',
        ];

        yield [
            '<?php
assert($foo instanceof Foo);
',
            '<?php
/**
 * @var Foo $foo
 */
',
        ];

        yield [
            '<?php
// TODO -> move assert after "$foo"?
',
            '<?php
/** @var int $foo */
$foo = 1;
',
        ];

        yield [
            '<?php
assert(false === $foo || true === $foo || is_bool($foo) || is_float($foo) || is_array($foo));
',
            '<?php
/**
 * @var false|true|bool|float|array $foo
 */
',
        ];

        yield [
            '<?php
assert($foo instanceof Foo); // This is foo
',
            '<?php
/**
 * @var Foo $foo This is foo
 */
',
        ];

        yield [
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
assert($foo instanceof Foo);
',
            '<?php
/** @var Foo $foo
 */
',
        ];

        yield [
            '<?php
assert($foo instanceof Foo);
',
            '<?php
/**
 * @var Foo $foo */
',
        ];

        yield [
            '<?php
/**    @var Foo        */
',
            '<?php
/**    @var Foo        */
',
        ];
    }
}
