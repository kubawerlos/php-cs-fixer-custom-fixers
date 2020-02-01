<?php

declare(strict_types = 1);

namespace Tests\Fixer;

/**
 * @internal
 *
 * @covers \PhpCsFixerCustomFixers\Fixer\NullableParamStyleFixer
 */
final class NullableParamStyleFixerTest extends AbstractFixerTestCase
{
    public function testConfiguration(): void
    {
        $options = $this->fixer->getConfigurationDefinition()->getOptions();
        self::assertArrayHasKey(0, $options);
        self::assertSame('style', $options[0]->getName());
    }

    public function testIsRisky(): void
    {
        self::assertFalse($this->fixer->isRisky());
    }

    public function testSuccessorName(): void
    {
        self::assertContains('nullable_type_declaration_for_default_null_value', $this->fixer->getSuccessorsNames());
    }

    /**
     * @dataProvider provideFixCases
     */
    public function testFix(string $expected, ?string $input = null, ?array $configuration = null): void
    {
        $this->doTest($expected, $input, $configuration);
    }

    public static function provideFixCases(): iterable
    {
        yield ['<?php function foo($x = null) {}'];
        yield ['<?php function foo(int $x, ?int $y) {}'];

        yield [
            '<?php function foo(?int $i = null, ?array $a = NULL, ?callable $c = Null) {}',
            '<?php function foo(int $i = null, array $a = NULL, callable $c = Null) {}',
        ];

        yield [
            '<?php function foo(?\A $x = null, ?B\C\D\E $y = null) {}',
            '<?php function foo(\A $x = null, B\C\D\E $y = null) {}',
        ];

        yield [
            '<?php function foo(/* c1 */?\/* c2 */A $x = null) {}',
            '<?php function foo(/* c1 */\/* c2 */A $x = null) {}',
        ];

        yield [
            '<?php function foo(?A \ B \ C $x = null) {}',
            '<?php function foo(A \ B \ C $x = null) {}',
        ];

        yield [
            '<?php $foo = function (?int $x = null) {};',
            '<?php $foo = function (int $x = null) {};',
        ];

        yield [
            '<?php function foo(?int $x = null) {}',
        ];

        yield [
            '<?php function foo(int $x = null, string $y = NULL) {}',
            '<?php function foo(?int $x = null, ?string $y = NULL) {}',
            ['style' => 'without_question_mark'],
        ];

        yield [
            '<?php function foo(
                int $x = null,
                int $y = null,
                string $z = NULL
            ) {}',
            '<?php function foo(
                ?int $x = null,
                int $y = null,
                ?string $z = NULL
            ) {}',
            ['style' => 'without_question_mark'],
        ];

        yield [
            '<?php function foo(int $x = null) {}',
            null,
            ['style' => 'without_question_mark'],
        ];

        yield [
            '<?php $foo = function (?int $x = null, $y = null) {};',
            '<?php $foo = function (int $x = null, $y = null) {};',
        ];

        yield [
            '<?php $foo = function (?int &$x = null) {};',
            '<?php $foo = function (int &$x = null) {};',
        ];

        yield [
            '<?php $foo = function (int &$x = null) {};',
            '<?php $foo = function (?int &$x = null) {};',
            ['style' => 'without_question_mark'],
        ];
    }
}
