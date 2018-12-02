<?php

declare(strict_types = 1);

namespace Tests\Fixer;

use PhpCsFixer\Fixer\Phpdoc\PhpdocToCommentFixer;

/**
 * @internal
 *
 * @covers \PhpCsFixerCustomFixers\Fixer\NoLeadingSlashInGlobalNamespaceFixer
 */
final class NoLeadingSlashInGlobalNamespaceFixerTest extends AbstractFixerTestCase
{
    public function testPriority(): void
    {
        static::assertGreaterThan((new PhpdocToCommentFixer())->getPriority(), $this->fixer->getPriority());
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
        yield [
            '<?php $foo = new Bar();',
            '<?php $foo = new \Bar();',
        ];

        yield [
            '<?php $foo = new Bar\Baz();',
            '<?php $foo = new \Bar\Baz();',
        ];

        yield [
            '<?php $foo = Bar::NAME;',
            '<?php $foo = \Bar::NAME;',
        ];

        yield [
            '<?php $x = new Foo(); namespace Bar; $y = new \Baz();',
            '<?php $x = new \Foo(); namespace Bar; $y = new \Baz();',
        ];
    }
}
