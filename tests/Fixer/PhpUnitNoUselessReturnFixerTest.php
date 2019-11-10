<?php

declare(strict_types = 1);

namespace Tests\Fixer;

/**
 * @internal
 *
 * @covers \PhpCsFixerCustomFixers\Fixer\PhpUnitNoUselessReturnFixer
 */
final class PhpUnitNoUselessReturnFixerTest extends AbstractFixerTestCase
{
    public function testIsRisky(): void
    {
        static::assertTrue($this->fixer->isRisky());
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

    private function getFixCases(): iterable
    {
        yield ['$this->markTestSkipped = true;'];

        yield ['
                $this->markAsRisky();
                return;
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

        yield [
            '   $this->markTestSkipped();
            ',
            '   $this->markTestSkipped();
                return;
            ',
        ];

        yield [
            '   $THIS->markTestSkipped();
            ',
            '   $THIS->markTestSkipped();
                return;
            ',
        ];

        yield [
            '   $this->markTestSkipped();
            ',
            '   $this->markTestSkipped();
                return 5;
            ',
        ];

        yield [
            '   $this->markTestSkipped();
            ',
            '   $this->markTestSkipped();
                return $this->getErrorCodeFactory()->createErrorCodeForSkippedTest()->getValue();
            ',
        ];

        yield [
            '   $this->markTestSkipped();
            ',
            '   $this->markTestSkipped();
                return $this->getErrorCodeFactory()->createErrorCodeForSkippedTest(function ($x) { return $x > 3; })->getValue();
            ',
        ];

        yield [
            '   $this->markTestSkipped(); // marking as skipped
            ',
            '   $this->markTestSkipped(); // marking as skipped
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
            '   $this->markTestSkipped(sprintf("skipped because of %s", "the reason"));
            ',
            '   $this->markTestSkipped(sprintf("skipped because of %s", "the reason"));
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
            '   SELF::markTestIncomplete();
            ',
            '   SELF::markTestIncomplete();
                return;
            ',
        ];

        yield [
            '   parent::markTestIncomplete();
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

        yield [
            '   static::markTestSkipped;
                parent::markTestSkipped();
                static::markTestSkipped()[0];
                static::markTestIncomplete();
                static::markTestIncomplete();
            ',
            '   static::markTestSkipped;
                parent::markTestSkipped();
                static::markTestSkipped()[0];
                static::markTestIncomplete();
                static::markTestIncomplete();
                return;
            ',
        ];
    }
}
