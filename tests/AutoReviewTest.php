<?php

declare(strict_types = 1);

namespace Tests;

use PhpCsFixer\Fixer\FixerInterface;
use PhpCsFixer\FixerNameValidator;
use PhpCsFixerCustomFixers\Fixer\AbstractFixer;
use PhpCsFixerCustomFixers\Fixers;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @covers \PhpCsFixerCustomFixers\Fixer\AbstractFixer
 */
final class AutoReviewTest extends TestCase
{
    /**
     * @dataProvider provideFixerCases
     */
    public function testFixerExtendsAbstractFixer(FixerInterface $fixer) : void
    {
        static::assertInstanceOf(AbstractFixer::class, $fixer);
    }

    /**
     * @dataProvider provideFixerCases
     */
    public function testFixerHasValidName(FixerInterface $fixer) : void
    {
        $validator = new FixerNameValidator();

        static::assertTrue(
            $validator->isValid($fixer->getName(), true),
            \sprintf('Fixer name "%s" is incorrect', $fixer->getName())
        );
    }

    /**
     * @dataProvider provideFixerCases
     */
    public function testFixerIsFinal(FixerInterface $fixer) : void
    {
        static::assertTrue((new \ReflectionClass($fixer))->isFinal());
    }

    public function provideFixerCases() : array
    {
        return \array_map(
            static function (FixerInterface $fixer) : array {
                return [new $fixer()];
            },
            \iterator_to_array(new Fixers())
        );
    }

    public function testFixerSupportsAllFilesByDefault() : void
    {
        $fixer = $this->getMockForAbstractClass(AbstractFixer::class);

        static::assertTrue($fixer->supports($this->createMock(\SplFileInfo::class)));
    }
}
