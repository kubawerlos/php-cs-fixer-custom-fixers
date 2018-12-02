<?php

declare(strict_types = 1);

namespace Tests\Fixer;

use PhpCsFixer\Fixer\StringNotation\SingleQuoteFixer;

/**
 * @internal
 *
 * @covers \PhpCsFixerCustomFixers\Fixer\NoUnneededConcatenationFixer
 */
final class NoUnneededConcatenationFixerTest extends AbstractFixerTestCase
{
    public function testPriority(): void
    {
        static::assertLessThan((new SingleQuoteFixer())->getPriority(), $this->fixer->getPriority());
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
        yield ['<?php $foo. "bar";'];
        yield ['<?php "foo" .$bar;'];
        yield ['<?php "foo".\'bar\';'];
        yield ['<?php "foo" . \'bar\';'];
        yield ['<?php \'bar\' . "foo";'];
        yield ['<?php "foo"
                      . "bar";'];
        yield ['<?php "foo" .
                      "bar";'];
        yield ['<?php "foo" // comment
                      . "bar";'];

        yield [
            '<?php "foobar";',
            '<?php "foo" . "bar";',
        ];

        yield [
            "<?php 'foobar';",
            "<?php 'foo' . 'bar';",
        ];

        yield [
            '<?php "foobarbazqux";',
            '<?php "foo" . "bar" . "baz" . "qux";',
        ];
    }
}
