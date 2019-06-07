<?php

declare(strict_types = 1);

namespace Tests\Fixer;

use PhpCsFixer\Fixer\FunctionNotation\MethodArgumentSpaceFixer;
use PhpCsFixer\Fixer\Operator\ConcatSpaceFixer;

/**
 * @internal
 *
 * @covers \PhpCsFixerCustomFixers\Fixer\SingleLineThrowFixer
 */
final class SingleLineThrowFixerTest extends AbstractFixerTestCase
{
    public function testPriority(): void
    {
        static::assertGreaterThan((new ConcatSpaceFixer())->getPriority(), $this->fixer->getPriority());
        static::assertGreaterThan((new MethodArgumentSpaceFixer())->getPriority(), $this->fixer->getPriority());
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

    public function provideFixCases(): iterable
    {
        yield ['<?php throw new Exception; foo(
                    "Foo"
                );'];

        yield ['<?php throw new $exceptionName; foo(
                    "Foo"
                );'];

        yield ['<?php throw $exception; foo(
                    "Foo"
                );'];

        yield ['<?php throw new Exception("Foo", 0);'];

        yield [
            '<?php throw new Exception("Foo", 0);',
            '<?php throw new Exception(
                "Foo",
                0
            );',
        ];
        yield [
            '<?php throw new Exception(new ExceptionReport("Foo"), 0);',
            '<?php throw new Exception(
                new
                    ExceptionReport("Foo"),
                0
            );',
        ];

        yield [
            '<?php throw new Exception(sprintf("Error with number %s", 42));',
            '<?php throw new Exception(sprintf(
                "Error with number %s",
                42
            ));',
        ];

        yield [
            '<?php throw new Vendor\\Exception("Foo");',
            '<?php throw new Vendor\\Exception(
                "Foo"
            );',
        ];

        yield [
            '<?php throw new \Vendor\\Exception("Foo");',
            '<?php throw new \Vendor\\Exception(
                "Foo"
            );',
        ];

        yield [
            '<?php throw $this->exceptionFactory->createAnException("Foo");',
            '<?php throw $this
                ->exceptionFactory
                ->createAnException(
                    "Foo"
                );',
        ];

        yield [
            '<?php throw ExceptionFactory::createAnException("Foo");',
            '<?php throw ExceptionFactory
                    ::
                    createAnException(
                        "Foo"
                    );',
        ];

        yield [
            '<?php throw new Exception("Foo", 0);',
            '<?php throw
                new
                    Exception
                        (
                            "Foo"
                                ,
                            0
                        );',
        ];

        yield [
            '<?php throw new $exceptionName("Foo");',
            '<?php throw new $exceptionName(
                "Foo"
            );',
        ];

        yield [
            '<?php throw clone $exceptionName("Foo");',
            '<?php throw clone $exceptionName(
                "Foo"
            );',
        ];

        yield [
            '<?php throw new WeirdException("Foo", -20, "An elephant", 1, 2, 3, 4, 5, 6, 7, 8);',
            '<?php throw new WeirdException("Foo", -20, "An elephant",

                1,
        2,
                                    3, 4, 5, 6, 7, 8
            );',
        ];

        yield [
            '<?php
                if ($foo) {
                    throw new Exception("It is foo", 1);
                } else {
                    throw new \Exception("It is not foo", 0);
                }
            ',
            '<?php
                if ($foo) {
                    throw new Exception(
                        "It is foo",
                        1
                    );
                } else {
                    throw new \Exception(
                        "It is not foo", 0
                    );
                }
            ',
        ];
    }
}
