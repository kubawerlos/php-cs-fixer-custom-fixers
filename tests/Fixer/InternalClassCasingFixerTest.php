<?php

declare(strict_types = 1);

namespace Tests\Fixer;

/**
 * @internal
 *
 * @covers \PhpCsFixerCustomFixers\Fixer\InternalClassCasingFixer
 */
final class InternalClassCasingFixerTest extends AbstractFixerTestCase
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
     * @param string|null $input
     *
     * @dataProvider provideFixCases
     */
    public function testFix(string $expected, string $input = null): void
    {
        $this->doTest($expected, $input);
    }

    public function provideFixCases(): \Generator
    {
        yield ['<?php class STDCLASS {};'];
        yield ['<?php class STDCLASS { use EXCEPTION; };'];
        yield ['<?php use TheVendor\TheException as EXCEPTION;'];
        yield ['<?php new Foo\STDClass();'];
        yield ['<?php new STDClass\Foo();'];
        yield ['<?php namespace Foo; new STDClass();'];
        yield ['<?php namespace STDClass; new Foo();'];
        yield ['<?php $foo->STDCLASS();'];
        yield ['<?php Foo::STDCLASS();'];
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
    }
}
