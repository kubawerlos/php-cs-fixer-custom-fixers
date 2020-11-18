<?php

/*
 * This file is part of PHP CS Fixer: custom fixers.
 *
 * (c) 2018-2020 Kuba Werłos
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Tests\AutoReview;

use PhpCsFixer\DocBlock\DocBlock;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Tests\AssertRegExpTrait;

/**
 * @internal
 *
 * @coversNothing
 */
final class TestsCodeTest extends TestCase
{
    use AssertRegExpTrait;

    /**
     * @dataProvider provideDataProviderCases
     */
    public function testDataProviderName(string $dataProviderName, string $className): void
    {
        self::assertRegExp('/^provide[A-Z]\S+Cases$/', $dataProviderName, \sprintf(
            'Data provider "%s" in class "%s" is not correctly named.',
            $dataProviderName,
            $className
        ));
    }

    /**
     * @dataProvider provideDataProviderCases
     */
    public function testDataProviderReturnType(string $dataProviderName, string $className): void
    {
        $reflectionMethod = new \ReflectionMethod($className, $dataProviderName);

        self::assertSame('iterable', $reflectionMethod->getReturnType()->getName());
    }

    /**
     * @dataProvider provideDataProviderCases
     */
    public function testDataProviderIsStatic(string $dataProviderName, string $className): void
    {
        $reflectionMethod = new \ReflectionMethod($className, $dataProviderName);

        self::assertTrue($reflectionMethod->isStatic());
    }

    public static function provideDataProviderCases(): iterable
    {
        static $dataProviders;

        if ($dataProviders === null) {
            $finder = Finder::create()
                ->files()
                ->name('*.php')
                ->in(__DIR__ . '/..');

            $dataProviders = [];

            /** @var SplFileInfo $file */
            foreach ($finder as $file) {
                $className = 'Tests';
                if ($file->getRelativePath() !== '') {
                    $className .= '\\' . \str_replace('/', '\\', $file->getRelativePath());
                }
                $className .= '\\' . $file->getBasename('.php');
                foreach (self::getDataProviderMethodNames($className) as $dataProviderName) {
                    $dataProviders[\sprintf('%s::%s', $className, $dataProviderName)] = [$dataProviderName, $className];
                }
            }
        }

        foreach ($dataProviders as $name => $data) {
            yield $name => $data;
        }
    }

    /**
     * @return string[]
     */
    private static function getDataProviderMethodNames(string $className): array
    {
        $reflectionClass = new \ReflectionClass($className);

        $dataProviderMethodNames = [];

        foreach ($reflectionClass->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
            $docBlock = new DocBlock($method->getDocComment());
            $dataProviderAnnotations = $docBlock->getAnnotationsOfType('dataProvider');

            foreach ($dataProviderAnnotations as $dataProviderAnnotation) {
                if (\preg_match('/@dataProvider\s+(?P<methodName>\w+)/', $dataProviderAnnotation->getContent(), $matches) === 1) {
                    $dataProviderMethodNames[] = $matches['methodName'];
                }
            }
        }

        return \array_unique($dataProviderMethodNames);
    }
}
