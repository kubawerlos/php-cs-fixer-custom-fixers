<?php

declare(strict_types = 1);

namespace Tests\Fixer;

use PhpCsFixer\Fixer\DefinedFixerInterface;
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
        $summary = $this->fixer->getDefinition()->getSummary();

        if (\preg_match('/^[A-Z]$/', $summary[1]) === 1) {
            static::assertRegExp('/^[A-Z]$/', $summary[0]);
        } else {
            static::assertRegExp('/^[a-z`]$/', $summary[0]);
        }
    }

    final public function testFixerDefinitionSummaryDoesNotEndWithDot(): void
    {
        $summary = $this->fixer->getDefinition()->getSummary();

        static::assertStringEndsNotWith('.', $summary);
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

    final public function testCodeSampleIsChangedDuringFixing(): void
    {
        $codeSample = $this->fixer->getDefinition()->getCodeSamples()[0];

        $tokens = Tokens::fromCode($codeSample->getCode());

        $this->fixer->fix($this->createMock(\SplFileInfo::class), $tokens);

        static::assertNotSame($codeSample->getCode(), $tokens->generateCode());
    }

    final protected function doTest(string $expected, ?string $input = null): void
    {
        if ($input === null) {
            $input = $expected;
        }

        $tokens = Tokens::fromCode($input);

        static::assertTrue($this->fixer->isCandidate($tokens));

        $this->fixer->fix($this->createMock(\SplFileInfo::class), $tokens);

        $tokens->clearEmptyTokens();

        static::assertTokens(Tokens::fromCode($expected), $tokens);
    }
}
