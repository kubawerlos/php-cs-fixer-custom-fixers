<?php

declare(strict_types = 1);

namespace Tests\Fixer;

use PhpCsFixer\Fixer\Phpdoc\NoEmptyPhpdocFixer;

/**
 * @internal
 *
 * @covers \PhpCsFixerCustomFixers\Fixer\PhpdocOnlyAllowedAnnotationsFixer
 */
final class PhpdocOnlyAllowedAnnotationsFixerTest extends AbstractFixerTestCase
{
    public function testPriority(): void
    {
        static::assertGreaterThan((new NoEmptyPhpdocFixer())->getPriority(), $this->fixer->getPriority());
    }

    public function testConfiguration(): void
    {
        $options = $this->fixer->getConfigurationDefinition()->getOptions();
        static::assertArrayHasKey(0, $options);
        static::assertSame('elements', $options[0]->getName());
    }

    public function testIsRisky(): void
    {
        static::assertFalse($this->fixer->isRisky());
    }

    /**
     * @param string      $expected
     * @param null|string $input
     * @param null|array  $configuration
     *
     * @dataProvider provideFixCases
     */
    public function testFix(string $expected, ?string $input = null, ?array $configuration = null): void
    {
        $this->fixer->configure($configuration);

        $this->doTest($expected, $input);
    }

    public function provideFixCases(): iterable
    {
        yield [
            '<?php

',
            '<?php
/** @var string */
',
        ];

        yield [
            '<?php
/**
 */
',
            '<?php
/**
 * @param Foo $foo
 * @return Bar
 */
',
        ];

        yield [
            '<?php
/**
 * @param Foo $foo
 */
',
            '<?php
/**
 * @param Foo $foo
 * @return Bar
 */
',
            ['elements' => ['param']],
        ];

        yield [
            '<?php
/**
 * @ORM\Id
 * @ORM\Column(type="integer")
 */
',
            '<?php
/**
 * @ORM\Id
 * @ORM\GeneratedValue
 * @ORM\Column(type="integer")
 */
',
            ['elements' => ['ORM\Id', 'ORM\Column']],
        ];

        yield [
            '<?php
/**
 * @foo
 * @bar
 */
/**
 * @foo
 */
',
            '<?php
/**
 * @foo
 * @bar
 * @baz
 */
/**
 * @foo
 * @foobar
 */
',
            ['elements' => ['foo', 'bar']],
        ];
    }
}
