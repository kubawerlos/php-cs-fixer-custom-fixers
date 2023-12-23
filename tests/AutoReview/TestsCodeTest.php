<?php declare(strict_types=1);

/*
 * This file is part of PHP CS Fixer: custom fixers.
 *
 * (c) 2018 Kuba Werłos
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Tests\AutoReview;

use PhpCsFixer\Preg;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Tests\Fixer\AbstractFixerTestCase;

/**
 * @internal
 *
 * @coversNothing
 */
final class TestsCodeTest extends TestCase
{
    /**
     * @param class-string $className
     *
     * @dataProvider provideTestClassCases
     */
    public function testClassContainsCorrectMethods(string $className): void
    {
        if ((new \ReflectionClass($className))->isTrait()) {
            foreach ($this->getMethods($className) as $reflectionMethod) {
                self::assertStringStartsWith('assert', $reflectionMethod->getName());
            }

            return;
        }

        foreach ($this->getMethods($className, \ReflectionMethod::IS_PUBLIC) as $reflectionMethod) {
            self::assertTrue(
                \strpos($reflectionMethod->getName(), 'test') === 0 || Preg::match('/^provide.+Cases$/', $reflectionMethod->getName()),
                \sprintf(
                    'Data provider "%s" in class "%s" is not correctly named.',
                    $reflectionMethod->getName(),
                    $className,
                ),
            );
        }
    }

    /**
     * @param class-string $className
     *
     * @dataProvider provideTestClassCases
     */
    public function testDataProvidersAreStatic(string $className): void
    {
        $dataProviders = $this->getDataProviders($className);

        if ($dataProviders === []) {
            $this->expectNotToPerformAssertions();
        }

        foreach ($dataProviders as $dataProvider) {
            self::assertTrue(
                $dataProvider->isStatic(),
                \sprintf(
                    'Data provider "%s" in class "%s" is not static.',
                    $dataProvider->getName(),
                    $className,
                ),
            );
        }
    }

    /**
     * @param class-string $className
     *
     * @dataProvider provideTestClassCases
     */
    public function testDataProvidersKeys(string $className): void
    {
        $dataProviders = $this->getDataProviders($className);

        if ($dataProviders === []) {
            $this->expectNotToPerformAssertions();
        }

        foreach ($dataProviders as $dataProvider) {
            $dataSet = $dataProvider->invoke(null);
            \assert($dataSet instanceof \Iterator);

            $keyType = null;
            foreach (\array_keys(\iterator_to_array($dataSet)) as $key) {
                // based on the type of first key determine what type should be for all keys
                if ($keyType === null) {
                    $keyType = \is_int($key) ? 'int' : 'string';
                }

                if (\is_int($key)) {
                    self::assertSame('int', $keyType);
                    continue;
                }

                self::assertSame('string', $keyType);
                self::assertSame(\trim($key), $key);
                self::assertStringNotContainsString('  ', $key);
                self::assertStringNotContainsString('"', $key);
            }
        }
    }

    /**
     * @dataProvider provideTestClassCases
     */
    public function testDataProvidersValues(string $className): void
    {
        if (!\is_subclass_of($className, AbstractFixerTestCase::class)) {
            $this->expectNotToPerformAssertions();

            return;
        }

        $dataProviders = $this->getDataProviders($className);

        foreach ($dataProviders as $dataProvider) {
            /** @var \Iterator<array<int, null|string>> $dataSet */
            $dataSet = $dataProvider->invoke(null);
            $dataSet = \iterator_to_array($dataSet);

            $doNotChangeCases = [];
            foreach ($dataSet as $value) {
                if (\array_key_exists(1, $value) && $value[1] !== null) {
                    continue;
                }
                $doNotChangeCases[] = $value[0];
            }
            foreach ($dataSet as $value) {
                if (!\array_key_exists(1, $value) || $value[1] === null) {
                    continue;
                }
                self::assertFalse(
                    \in_array($value[0], $doNotChangeCases, true),
                    \sprintf(
                        "Expected value:\n%s\nis already tested if it is not changing, it does not need separate test case (%s::%s).",
                        $value[0],
                        $className,
                        $dataProvider->getName(),
                    ),
                );
            }
        }
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function provideTestClassCases(): iterable
    {
        static $tests;

        if ($tests === null) {
            $finder = Finder::create()
                ->files()
                ->name('*.php')
                ->notName('autoload.php')
                ->in(__DIR__ . '/..');

            $tests = [];

            /** @var SplFileInfo $file */
            foreach ($finder as $file) {
                $className = 'Tests';
                if ($file->getRelativePath() !== '') {
                    $className .= '\\' . \str_replace('/', '\\', $file->getRelativePath());
                }

                $className .= '\\' . $file->getBasename('.php');
                $tests[$className] = [$className];
            }

            $tests = new \ArrayIterator($tests);
        }

        return $tests;
    }

    /**
     * @param class-string $className
     *
     * @return array<\ReflectionMethod>
     */
    private function getDataProviders(string $className): array
    {
        return \array_filter(
            $this->getMethods($className, \ReflectionMethod::IS_PUBLIC),
            static fn (\ReflectionMethod $reflectionMethod): bool => \strpos($reflectionMethod->getName(), 'provide') === 0,
        );
    }

    /**
     * @param class-string $className
     *
     * @return array<\ReflectionMethod>
     */
    private function getMethods(string $className, ?int $methodFilter = null): array
    {
        $reflectionClass = new \ReflectionClass($className);

        return \array_filter(
            $reflectionClass->getMethods($methodFilter),
            static fn (\ReflectionMethod $reflectionMethod): bool => $reflectionMethod->getFileName() === $reflectionClass->getFileName(),
        );
    }
}
