<?php

declare(strict_types = 1);

namespace Tests\Fixer;

use PhpCsFixer\Fixer\DefinedFixerInterface;
use PhpCsFixer\Tokenizer\Tokens;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
abstract class AbstractFixerTestCase extends TestCase
{
    /**
     * @var DefinedFixerInterface
     */
    protected $fixer;

    final protected function setUp() : void
    {
        $reflectionClass = new \ReflectionClass(static::class);

        $className = 'PhpCsFixerCustomFixers\\Fixer\\' . \substr($reflectionClass->getShortName(), 0, -4);

        $this->fixer = new $className();
    }

    final public function testFixerDefinitionHasExactlyOneCodeSample() : void
    {
        $this->assertCount(1, $this->fixer->getDefinition()->getCodeSamples());
    }

    final public function testCodeSampleIsChangedDuringFixing() : void
    {
        $codeSample = $this->fixer->getDefinition()->getCodeSamples()[0];

        $tokens = Tokens::fromCode($codeSample->getCode());

        $this->fixer->fix($this->createMock(\SplFileInfo::class), $tokens);

        $this->assertNotSame($codeSample->getCode(), $tokens->generateCode());
    }

    final protected function doTest(string $expected, string $input = null) : void
    {
        if ($input === null) {
            $input = $expected;
        }

        $tokens = Tokens::fromCode($input);

        $this->assertTrue($this->fixer->isCandidate($tokens));

        $this->fixer->fix($this->createMock(\SplFileInfo::class), $tokens);

        $this->assertSame($expected, $tokens->generateCode());
    }
}
