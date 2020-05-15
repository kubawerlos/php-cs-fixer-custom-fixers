<?php

declare(strict_types=1);

/*
 * This file is part of PHP CS Fixer: custom fixers.
 *
 * (c) 2018-2020 Kuba Werłos
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Tests\Fixer;

use PhpCsFixer\Fixer\ConfigurableFixerInterface;
use PhpCsFixer\Fixer\DefinedFixerInterface;
use PhpCsFixer\Linter\TokenizerLinter;
use PhpCsFixer\Tests\Test\Assert\AssertTokensTrait;
use PhpCsFixer\Tokenizer\Tokens;
use PHPUnit\Framework\TestCase;
use Tests\AssertRegExpTrait;

/**
 * @internal
 */
abstract class AbstractFixerTestCase extends TestCase
{
    use AssertRegExpTrait;
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
        self::assertRegExp('/^[A-Z`].*\.$/', $this->fixer->getDefinition()->getSummary());
    }

    final public function testFixerDefinitionRiskyDescriptionStartWithLowercase(): void
    {
        if (!$this->fixer->isRisky()) {
            $this->addToAssertionCount(1);

            return;
        }

        self::assertRegExp('/^[a-z]/', $this->fixer->getDefinition()->getRiskyDescription());
    }

    final public function testFixerDefinitionRiskyDescriptionDoesNotEndWithDot(): void
    {
        if (!$this->fixer->isRisky()) {
            $this->addToAssertionCount(1);

            return;
        }

        self::assertStringEndsNotWith('.', $this->fixer->getDefinition()->getRiskyDescription());
    }

    final public function testFixerDefinitionHasExactlyOneCodeSample(): void
    {
        self::assertCount(1, $this->fixer->getDefinition()->getCodeSamples());
    }

    final public function testCodeSampleEndsWithNewLine(): void
    {
        $codeSample = $this->fixer->getDefinition()->getCodeSamples()[0];

        self::assertRegExp('/\n$/', $codeSample->getCode());
    }

    /**
     * @coversNothing
     */
    final public function testCodeSampleIsChangedDuringFixing(): void
    {
        $codeSample = $this->fixer->getDefinition()->getCodeSamples()[0];
        if ($this->fixer instanceof ConfigurableFixerInterface) {
            $this->fixer->configure($codeSample->getConfiguration());
        }

        Tokens::clearCache();
        $tokens = Tokens::fromCode($codeSample->getCode());

        $this->fixer->fix($this->createMock(\SplFileInfo::class), $tokens);

        self::assertNotSame($codeSample->getCode(), $tokens->generateCode());
    }

    final public function testPriority(): void
    {
        self::assertIsInt($this->fixer->getPriority());
    }

    final protected function doTest(string $expected, ?string $input = null, ?array $configuration = null): void
    {
        if ($this->fixer instanceof ConfigurableFixerInterface) {
            $this->fixer->configure($configuration);
        }

        if ($expected === $input) {
            throw new \InvalidArgumentException('Expected must be different to input.');
        }

        $linter = new TokenizerLinter();

        self::assertNull($linter->lintSource($expected)->check());

        if ($input !== null) {
            self::assertNull($linter->lintSource($input)->check());

            Tokens::clearCache();
            $tokens = Tokens::fromCode($input);

            self::assertTrue($this->fixer->isCandidate($tokens));

            $this->fixer->fix($this->createMock(\SplFileInfo::class), $tokens);

            $tokens->clearEmptyTokens();

            self::assertSame($expected, $tokens->generateCode());

            Tokens::clearCache();
            self::assertTokens(Tokens::fromCode($expected), $tokens);
        }

        Tokens::clearCache();
        $tokens = Tokens::fromCode($expected);

        $this->fixer->fix($this->createMock(\SplFileInfo::class), $tokens);

        self::assertSame($expected, $tokens->generateCode());

        self::assertFalse($tokens->isChanged());
    }
}
