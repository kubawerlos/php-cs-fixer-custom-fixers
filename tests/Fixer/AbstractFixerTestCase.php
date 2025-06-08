<?php declare(strict_types=1);

/*
 * This file is part of PHP CS Fixer: custom fixers.
 *
 * (c) 2018 Kuba Werłos
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Tests\Fixer;

use PhpCsFixer\Fixer\Basic\EncodingFixer;
use PhpCsFixer\Fixer\ConfigurableFixerInterface;
use PhpCsFixer\Fixer\DeprecatedFixerInterface;
use PhpCsFixer\Fixer\FixerInterface;
use PhpCsFixer\Fixer\WhitespacesAwareFixerInterface;
use PhpCsFixer\FixerConfiguration\FixerOptionInterface;
use PhpCsFixer\Linter\Linter;
use PhpCsFixer\Linter\LinterInterface;
use PhpCsFixer\Linter\ProcessLinter;
use PhpCsFixer\Tokenizer\Tokens;
use PhpCsFixer\WhitespacesFixerConfig;
use PHPUnit\Framework\TestCase;
use Tests\AssertSameTokensTrait;

/**
 * @internal
 */
abstract class AbstractFixerTestCase extends TestCase
{
    use AssertSameTokensTrait;

    private const ALLOWED_TEST_METHOD_NAMES = [
        'testConfiguration',
        'testExampleWithAllTokensHasAllSpacesFixed',
        'testFix',
        'testFixPre80',
        'testFix80',
        'testFix81',
        'testFix82',
        'testFix84',
        'testIsRisky',
        'testReversingCodeSample',
        'testStringIsTheSame',
        'testSuccessorName',
        'testTokenIsUseful',
    ];

    final public function testFixerDefinitionSummaryStartWithCorrectCase(): void
    {
        self::assertMatchesRegularExpression('/^[A-Z].*\\.$/', self::getFixer()->getDefinition()->getSummary());
    }

    final public function testFixerDefinitionRiskyDescriptionStartWithLowercase(): void
    {
        if (!self::getFixer()->isRisky()) {
            $this->expectNotToPerformAssertions();

            return;
        }

        $riskyDescription = self::getFixer()->getDefinition()->getRiskyDescription();
        \assert(\is_string($riskyDescription));

        self::assertMatchesRegularExpression('/^[a-z]/', $riskyDescription);
    }

    final public function testFixerDefinitionRiskyDescriptionDoesNotEndWithDot(): void
    {
        if (!self::getFixer()->isRisky()) {
            $this->expectNotToPerformAssertions();

            return;
        }

        $riskyDescription = self::getFixer()->getDefinition()->getRiskyDescription();
        \assert(\is_string($riskyDescription));

        self::assertStringEndsNotWith('.', $riskyDescription);
    }

    final public function testFixerDefinitionHasExactlyOneCodeSample(): void
    {
        if (self::getFixer() instanceof DeprecatedFixerInterface) {
            self::assertGreaterThanOrEqual(1, \count(self::getFixer()->getDefinition()->getCodeSamples()));
        } else {
            self::assertCount(1, self::getFixer()->getDefinition()->getCodeSamples());
        }
    }

    final public function testCodeSampleEndsWithNewLine(): void
    {
        $codeSample = self::getFixer()->getDefinition()->getCodeSamples()[0];

        self::assertMatchesRegularExpression('/\\n$/', $codeSample->getCode());
    }

    final public function testCodeSampleIsChangedDuringFixing(): void
    {
        $fixer = self::getFixer();

        $codeSample = $fixer->getDefinition()->getCodeSamples()[0];
        if ($fixer instanceof ConfigurableFixerInterface) {
            $fixer->configure($codeSample->getConfiguration() ?? []);
        }

        Tokens::clearCache();
        $tokens = Tokens::fromCode($codeSample->getCode());

        $fixer->fix($this->createSplFileInfoDouble(), $tokens);

        self::assertNotSame($codeSample->getCode(), $tokens->generateCode());
    }

    final public function testPriority(): void
    {
        self::assertLessThan((new EncodingFixer())->getPriority(), self::getFixer()->getPriority());
    }

    final public function testMethodNames(): void
    {
        $fixerReflection = new \ReflectionObject($this);

        $testMethods = \array_filter(
            $fixerReflection->getMethods(\ReflectionMethod::IS_PUBLIC),
            static fn (\ReflectionMethod $reflectionMethod): bool => $reflectionMethod->getFileName() === $fixerReflection->getFileName()
                && \str_starts_with($reflectionMethod->getName(), 'test'),
        );

        foreach ($testMethods as $testMethod) {
            self::assertContains(
                $testMethod->getName(),
                self::ALLOWED_TEST_METHOD_NAMES,
                \sprintf(
                    'Method "%s" found, allowed names are: "%s".',
                    $testMethod->getName(),
                    \implode('", "', self::ALLOWED_TEST_METHOD_NAMES),
                ),
            );
        }
    }

    final protected static function getFixer(): FixerInterface
    {
        $className = \str_replace('Tests', 'PhpCsFixerCustomFixers', \substr(static::class, 0, -4));

        $fixer = new $className();
        \assert($fixer instanceof FixerInterface);

        if ($fixer instanceof WhitespacesAwareFixerInterface) {
            $fixer->setWhitespacesConfig(new WhitespacesFixerConfig());
        }

        return $fixer;
    }

    /**
     * @param null|array<string, mixed> $configuration
     */
    final protected function doTest(string $expected, ?string $input = null, ?array $configuration = null): void
    {
        $fixer = self::getFixer();

        if ($fixer instanceof ConfigurableFixerInterface) {
            $fixer->configure($configuration ?? []);
        }

        if ($expected === $input) {
            throw new \InvalidArgumentException('Expected must be different to input.');
        }

        self::assertNull($this->lintSource($expected));

        Tokens::clearCache();
        $expectedTokens = Tokens::fromCode($expected);

        if ($input !== null) {
            self::assertNull($this->lintSource($input));

            Tokens::clearCache();
            $inputTokens = Tokens::fromCode($input);

            self::assertTrue($fixer->isCandidate($inputTokens));

            $fixer->fix($this->createSplFileInfoDouble(), $inputTokens);
            $inputTokens->clearEmptyTokens();

            self::assertSame(
                $expected,
                $actual = $inputTokens->generateCode(),
                \sprintf(
                    "Expected code:\n```\n%s\n```\nGot:\n```\n%s\n```\n",
                    $expected,
                    $actual,
                ),
            );

            self::assertSameTokens($expectedTokens, $inputTokens);
        }

        $fixer->fix($this->createSplFileInfoDouble(), $expectedTokens);

        self::assertSame($expected, $expectedTokens->generateCode());

        self::assertFalse($expectedTokens->isChanged());
    }

    /**
     * @return list<FixerOptionInterface>
     */
    final protected function getConfigurationOptions(): array
    {
        $fixer = self::getFixer();
        self::assertInstanceOf(ConfigurableFixerInterface::class, $fixer);

        return $fixer->getConfigurationDefinition()->getOptions();
    }

    final protected function lintSource(string $source): ?string
    {
        /** @var null|LinterInterface $linter */
        static $linter;

        if ($linter === null) {
            $linter = \getenv('FAST_LINT_TEST_CASES') === '1' ? new Linter() : new ProcessLinter();
        }

        try {
            $linter->lintSource($source)->check();
        } catch (\Exception $exception) {
            return \sprintf('Linting "%s" failed with error: %s.', $source, $exception->getMessage());
        }

        return null;
    }

    final protected function assertRiskiness(bool $isRisky): void
    {
        self::assertSame($isRisky, self::getFixer()->isRisky());
    }

    final protected function assertSuccessorName(string $successorName): void
    {
        $fixer = self::getFixer();
        self::assertInstanceOf(DeprecatedFixerInterface::class, $fixer);
        self::assertSame([$successorName], $fixer->getSuccessorsNames());
    }

    private function createSplFileInfoDouble(): \SplFileInfo
    {
        return new class (\getcwd() . \DIRECTORY_SEPARATOR . 'src' . \DIRECTORY_SEPARATOR . 'file.php') extends \SplFileInfo {
            public function __construct(string $filename)
            {
                parent::__construct($filename);
            }

            public function getRealPath(): string
            {
                return $this->getPathname();
            }
        };
    }
}
