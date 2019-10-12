<?php

declare(strict_types = 1);

namespace Tests\Fixer;

use PhpCsFixer\Fixer\ConfigurableFixerInterface;
use PhpCsFixer\Fixer\DefinedFixerInterface;
use PhpCsFixer\Linter\TokenizerLinter;
use PhpCsFixer\Tests\Test\Assert\AssertTokensTrait;
use PhpCsFixer\Tokenizer\Tokens;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
abstract class AbstractFixerTestCase extends TestCase
{
    use AssertTokensTrait;

    /** @var DefinedFixerInterface */
    protected $fixer;

    final protected function setUp(): void
    {
        $reflectionClass = new \ReflectionClass(static::class);

        $className = 'PhpCsFixerCustomFixers\\Fixer\\' . \substr($reflectionClass->getShortName(), 0, -4);

        $this->fixer = new $className();
    }

    final public function testFixerDefinitionSummaryStartWithCorrectCase(): void
    {
        static::assertRegExp('/^[A-Z`].*\.$/', $this->fixer->getDefinition()->getSummary());
    }

    final public function testFixerDefinitionRiskyDescriptionStartWithLowercase(): void
    {
        if (!$this->fixer->isRisky()) {
            $this->addToAssertionCount(1);

            return;
        }

        static::assertRegExp('/^[a-z]/', $this->fixer->getDefinition()->getRiskyDescription());
    }

    final public function testFixerDefinitionRiskyDescriptionDoesNotEndWithDot(): void
    {
        if (!$this->fixer->isRisky()) {
            $this->addToAssertionCount(1);

            return;
        }

        static::assertStringEndsNotWith('.', $this->fixer->getDefinition()->getRiskyDescription());
    }

    final public function testFixerDefinitionHasExactlyOneCodeSample(): void
    {
        static::assertCount(1, $this->fixer->getDefinition()->getCodeSamples());
    }

    final public function testCodeSampleEndsWithNewLine(): void
    {
        $codeSample = $this->fixer->getDefinition()->getCodeSamples()[0];

        static::assertRegExp('/\n$/', $codeSample->getCode());
    }

    final public function testCodeSampleIsChangedDuringFixing(): void
    {
        $codeSample = $this->fixer->getDefinition()->getCodeSamples()[0];
        if ($this->fixer instanceof ConfigurableFixerInterface) {
            $this->fixer->configure($codeSample->getConfiguration());
        }

        Tokens::clearCache();
        $tokens = Tokens::fromCode($codeSample->getCode());

        $this->fixer->fix($this->createMock(\SplFileInfo::class), $tokens);

        static::assertNotSame($codeSample->getCode(), $tokens->generateCode());
    }

    final protected function doTest(string $expected, ?string $input = null): void
    {
        if ($expected === $input) {
            throw new \InvalidArgumentException('Expected must be different to input.');
        }

        $linter = new TokenizerLinter();

        static::assertNull($linter->lintSource($expected)->check());

        if ($input !== null) {
            static::assertNull($linter->lintSource($input)->check());

            Tokens::clearCache();
            $tokens = Tokens::fromCode($input);

            static::assertTrue($this->fixer->isCandidate($tokens));

            $this->fixer->fix($this->createMock(\SplFileInfo::class), $tokens);

            $tokens->clearEmptyTokens();

            static::assertSame($expected, $tokens->generateCode());

            Tokens::clearCache();
            static::assertTokens(Tokens::fromCode($expected), $tokens);
        }

        Tokens::clearCache();
        $tokens = Tokens::fromCode($expected);

        $this->fixer->fix($this->createMock(\SplFileInfo::class), $tokens);

        static::assertSame($expected, $tokens->generateCode());

        static::assertFalse($tokens->isChanged());
    }
}
