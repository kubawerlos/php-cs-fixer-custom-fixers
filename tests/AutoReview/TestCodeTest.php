<?php

declare(strict_types = 1);

namespace Tests\AutoReview;

use PhpCsFixer\DocBlock\DocBlock;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

/**
 * @internal
 *
 * @covers \PhpCsFixerCustomFixers\Fixer\AbstractFixer
 */
final class TestCodeTest extends TestCase
{
    /**
     * @dataProvider provideDataProviderCases
     */
    public function testThatDataProviderIsCorrectlyNamed(string $dataProviderName, string $className): void
    {
        static::assertRegExp('/^provide[A-Z]\S+Cases$/', $dataProviderName, \sprintf(
            'Data provider "%s" in  class "%s"  is not correctly named.',
            $dataProviderName,
            $className
        ));
    }

    public function provideDataProviderCases(): iterable
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
                $parts = ['Tests'];
                if ($file->getRelativePath() !== '') {
                    $parts[] = $file->getRelativePath();
                }
                $parts[] = $file->getBasename('.php');
                $className = \implode('\\', $parts);
                foreach ($this->getDataProviderMethodNames($className) as $dataProviderName) {
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
    private function getDataProviderMethodNames(string $className): array
    {
        $reflection = new \ReflectionClass($className);

        $dataProviderMethodNames = [];

        foreach ($reflection->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
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
