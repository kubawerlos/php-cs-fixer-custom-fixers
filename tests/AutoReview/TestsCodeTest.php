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

/**
 * @internal
 *
 * @coversNothing
 */
final class TestsCodeTest extends TestCase
{
    /**
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
                \strpos($reflectionMethod->getName(), 'test') === 0 || Preg::match('/^provide.+Cases$/', $reflectionMethod->getName()) === 1,
                \sprintf(
                    'Data provider "%s" in class "%s" is not correctly named.',
                    $reflectionMethod->getName(),
                    $className
                )
            );
        }
    }

    /**
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
                    $className
                )
            );
        }
    }

    /**
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
            \assert(\is_array($dataSet) || $dataSet instanceof \Generator);

            if ($dataSet instanceof \Generator) {
                $dataSet = \iterator_to_array($dataSet);
            }

            foreach (\array_keys($dataSet) as $key) {
                if (!\is_string($key)) {
                    self::markTestIncomplete(\sprintf(
                        'Data provider "%s" in class "%s" has non-string keys.',
                        $dataProvider->getName(),
                        $className
                    ));
                }
                self::assertIsString($key);
                self::assertSame(\trim($key), $key);
                self::assertStringNotContainsString('  ', $key);
                self::assertStringNotContainsString('"', $key);
            }
        }
    }

    /**
     * @return array<\ReflectionMethod>
     */
    private function getDataProviders(string $className): array
    {
        return \array_filter(
            $this->getMethods($className, \ReflectionMethod::IS_PUBLIC),
            static function (\ReflectionMethod $reflectionMethod): bool {
                return \strpos($reflectionMethod->getName(), 'provide') === 0;
            }
        );
    }

    /**
     * @return array<\ReflectionMethod>
     */
    private function getMethods(string $className, ?int $methodFilter = null): array
    {
        $reflectionClass = new \ReflectionClass($className);

        return \array_filter(
            $reflectionClass->getMethods($methodFilter),
            static function (\ReflectionMethod $reflectionMethod) use ($reflectionClass): bool {
                return $reflectionMethod->getFileName() === $reflectionClass->getFileName();
            }
        );
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
        }

        return $tests;
    }
}
