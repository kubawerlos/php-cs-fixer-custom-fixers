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

    public function provideFixCases(): \Iterator
    {
        yield ['<?php throw new Exception; foo(
                    "Boo"
                );'];

        yield ['<?php throw new $exceptionName; foo(
                    "Boo"
                );'];

        yield ['<?php throw $exception; foo(
                    "Boo"
                );'];

        yield ['<?php throw new Exception("Boo",0);'];

        yield [
            '<?php throw new Exception("Boo", 0);',
            '<?php throw new Exception(
                "Boo",
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
            '<?php throw new Vendor\\Exception("Boo");',
            '<?php throw new Vendor\\Exception(
                "Boo"
            );',
        ];

        yield [
            '<?php throw new \\Vendor\\Exception("Boo");',
            '<?php throw new \\Vendor\\Exception(
                "Boo"
            );',
        ];

        yield [
            '<?php throw new $exceptionName("Boo");',
            '<?php throw new $exceptionName(
                "Boo"
            );',
        ];

        yield [
            '<?php throw new WeirdException("Boo", -20, "An elephant", 1, 2, 3, 4, 5, 6, 7, 8);',
            '<?php throw new WeirdException("Boo", -20, "An elephant",

                1,
        2,
                                    3, 4, 5, 6, 7, 8
            );',
        ];
    }
}
