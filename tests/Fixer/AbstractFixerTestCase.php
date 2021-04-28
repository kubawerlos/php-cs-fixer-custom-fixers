<?php

/*
 * This file is part of PHP CS Fixer: custom fixers.
 *
 * (c) 2018 Kuba Werłos
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Tests\Fixer;

use PhpCsFixer\Fixer\ConfigurableFixerInterface;
use PhpCsFixer\Fixer\DefinedFixerInterface;
use PhpCsFixer\Fixer\DeprecatedFixerInterface;
use PhpCsFixer\Linter\Linter;
use PhpCsFixer\Tokenizer\Tokens;
use PHPUnit\Framework\TestCase;
use Tests\AssertRegExpTrait;
use Tests\AssertSameTokensTrait;

/**
 * @internal
 */
abstract class AbstractFixerTestCase extends TestCase
{
    use AssertRegExpTrait;
    use AssertSameTokensTrait;

    /** @var DefinedFixerInterface */
    protected $fixer;

    final protected function setUp(): void
    {
        $reflectionClass = new \ReflectionClass(static::class);

        $className = 'PhpCsFixerCustomFixers\\Fixer\\' . \substr($reflectionClass->getShortName(), 0, -4);

        /** @var DefinedFixerInterface $fixer */
        $fixer = new $className();

        $this->fixer = $fixer;
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

        /** @var string $riskyDescription */
        $riskyDescription = $this->fixer->getDefinition()->getRiskyDescription();

        self::assertRegExp('/^[a-z]/', $riskyDescription);
    }

    final public function testFixerDefinitionRiskyDescriptionDoesNotEndWithDot(): void
    {
        if (!$this->fixer->isRisky()) {
            $this->addToAssertionCount(1);

            return;
        }

        /** @var string $riskyDescription */
        $riskyDescription = $this->fixer->getDefinition()->getRiskyDescription();

        self::assertStringEndsNotWith('.', $riskyDescription);
    }

    final public function testFixerDefinitionHasExactlyOneCodeSample(): void
    {
        if ($this->fixer instanceof DeprecatedFixerInterface) {
            self::assertGreaterThanOrEqual(1, \count($this->fixer->getDefinition()->getCodeSamples()));
        } else {
            self::assertCount(1, $this->fixer->getDefinition()->getCodeSamples());
        }
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
            $this->fixer->configure($codeSample->getConfiguration() ?? []);
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

    /**
     * @param null|array<string, mixed> $configuration
     */
    final protected function doTest(string $expected, ?string $input = null, ?array $configuration = null): void
    {
        if ($this->fixer instanceof ConfigurableFixerInterface) {
            $this->fixer->configure($configuration ?? []);
        }

        if ($expected === $input) {
            throw new \InvalidArgumentException('Expected must be different to input.');
        }

        self::assertNull($this->lintSource($expected));

        Tokens::clearCache();
        $expectedTokens = Tokens::fromCode($expected);

        if ($input !== null) {
            Tokens::clearCache();
            $inputTokens = Tokens::fromCode($input);

            self::assertTrue($this->fixer->isCandidate($inputTokens));

            $this->fixer->fix($this->createMock(\SplFileInfo::class), $inputTokens);
            $inputTokens->clearEmptyTokens();

            self::assertSame($expected, $inputTokens->generateCode());

            self::assertSameTokens($expectedTokens, $inputTokens);
        }

        $this->fixer->fix($this->createMock(\SplFileInfo::class), $expectedTokens);

        self::assertSame($expected, $expectedTokens->generateCode());

        self::assertFalse($expectedTokens->isChanged());
    }

    private function lintSource(string $source): ?string
    {
        static $linter;

        if ($linter === null) {
            $linter = new Linter();
        }

        try {
            $linter->lintSource($source)->check();
        } catch (\Exception $exception) {
            return \sprintf('Linting "%s" failed with error: %s.', $source, $exception->getMessage());
        }

        return null;
    }
}
