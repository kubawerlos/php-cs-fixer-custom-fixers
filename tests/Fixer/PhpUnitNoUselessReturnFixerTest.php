<?php

declare(strict_types = 1);

namespace Tests\Fixer;

use PhpCsFixer\Fixer\Whitespace\NoExtraBlankLinesFixer;

/**
 * @internal
 *
 * @covers \PhpCsFixerCustomFixers\Fixer\PhpUnitNoUselessReturnFixer
 */
final class PhpUnitNoUselessReturnFixerTest extends AbstractFixerTestCase
{
    public function testPriority(): void
    {
        static::assertLessThan((new NoExtraBlankLinesFixer())->getPriority(), $this->fixer->getPriority());
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
        return \array_map(
            static function (array $args): array {
                return \array_map(
                    static function (string $case): string {
                        return \sprintf('<?php
class FooTest extends TestCase {
    public function testFoo() {
        %s
    }
}', $case);
                    },
                    $args
                );
            },
            \iterator_to_array($this->getFixCases())
        );
    }

    private function getFixCases(): \Generator
    {
        yield ['$this->markTestSkipped = true;'];

        yield ['
                $this->markAsRisky();
                return;
                $this->assertTrue($x);
            '];

        yield ['
                $this->markTestSkipped()[0];
                return;
            '];

        yield ['
                $this->markTestSkipped();
                throw new CustomExceptionForSkippedTest();
            '];

        yield ['
                $this->getSomeObject()->markTestSkipped();
                return;
            '];

        yield ['
                $mock->markTestSkipped();
                return;
            '];

        yield ['
            $this->markTestSkipped();
            return 5;
        '];

        yield [
            '   $this->markTestSkipped();
            ',
            '   $this->markTestSkipped();
                return;
            ',
        ];

        yield [
            '   $this->markTestSkipped("message");
            ',
            '   $this->markTestSkipped("message");
                return;
            ',
        ];

        yield [
            '   if ($x > 42) {
                    $this->markTestSkipped();
                }
                self:assertSame(-2, $x);
            ',
            '   if ($x > 42) {
                    $this->markTestSkipped();
                    return;
                }
                self:assertSame(-2, $x);
            ',
        ];

        yield [
            '   $this->markTestSkipped();
            ',
            '   $this->markTestSkipped();return;
            ',
        ];

        yield [
            '   self::markTestIncomplete();
            ',
            '   self::markTestIncomplete();
                return;
            ',
        ];

        yield [
            '   static::FAIL();
            ',
            '   static::FAIL();
                return;
            ',
        ];

        yield [
            '   self::fail();
            ',
            '   self::fail();
                return
                ;
            ',
        ];

        yield [
            '   static::markTestIncomplete();
                self::markTestSkipped();
                $this->fail();
            ',
            '   static::markTestIncomplete();
                return;
                self::markTestSkipped();
                return;
                $this->fail();
                return;
            ',
        ];
    }
}
