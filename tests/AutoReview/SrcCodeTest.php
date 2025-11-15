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

use PhpCsFixer\Fixer\DeprecatedFixerInterface;
use PhpCsFixer\Fixer\FixerInterface;
use PhpCsFixer\FixerDefinition\FixerDefinitionInterface;
use PhpCsFixer\FixerNameValidator;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;
use PhpCsFixerCustomFixers\Fixer\AbstractFixer;
use PhpCsFixerCustomFixers\Fixers;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

/**
 * @internal
 *
 * @covers \PhpCsFixerCustomFixers\Fixer\AbstractFixer
 */
final class SrcCodeTest extends TestCase
{
    /**
     * @dataProvider provideFixerCases
     */
    public function testFixerExtendsAbstractFixer(FixerInterface $fixer): void
    {
        self::assertInstanceOf(AbstractFixer::class, $fixer);
    }

    /**
     * @dataProvider provideFixerCases
     */
    public function testFixerHasValidName(FixerInterface $fixer): void
    {
        $validator = new FixerNameValidator();

        self::assertTrue(
            $validator->isValid($fixer->getName(), true),
            \sprintf('Fixer name "%s" is incorrect', $fixer->getName()),
        );
    }

    /**
     * @dataProvider provideFixerCases
     */
    public function testFixerIsFinal(FixerInterface $fixer): void
    {
        self::assertTrue((new \ReflectionObject($fixer))->isFinal());
    }

    /**
     * @dataProvider provideFixerCases
     */
    public function testDeprecatedFixerHasAnnotation(FixerInterface $fixer): void
    {
        $comment = (new \ReflectionObject($fixer))->getDocComment();
        self::assertSame(
            $fixer instanceof DeprecatedFixerInterface,
            \strpos($comment === false ? '' : $comment, '@deprecated') !== false,
        );
    }

    /**
     * @return iterable<list<FixerInterface>>
     */
    public static function provideFixerCases(): iterable
    {
        foreach (new Fixers() as $fixer) {
            yield \get_class($fixer) => [$fixer];
        }
    }

    public function testFixerSupportsAllFilesByDefault(): void
    {
        self::assertTrue(self::createAbstractFixerDouble()->supports(self::createSplFileInfoDouble()));
    }

    /**
     * @param class-string $className
     *
     * @dataProvider provideThereIsNoDisallowedFunctionUsedDirectlyCases
     */
    public function testThereIsNoDisallowedFunctionUsedDirectly(string $className): void
    {
        $reflectionClass = new \ReflectionClass($className);

        $fileName = $reflectionClass->getFileName();
        \assert(\is_string($fileName));

        $content = \file_get_contents($fileName);
        \assert(\is_string($content));

        /** @var list<Token> $tokens */
        $tokens = Tokens::fromCode($content)->toArray();

        $stringTokens = \array_filter(
            $tokens,
            static fn (Token $token): bool => $token->isGivenKind(\T_STRING),
        );
        $strings = \array_map(
            static fn (Token $token): string => $token->getContent(),
            $stringTokens,
        );
        $strings = \array_unique($strings);

        $message = \sprintf('Class %s must not use preg_*, it shall use Preg::* instead.', $className);
        self::assertNotContains('preg_filter', $strings, $message);
        self::assertNotContains('preg_grep', $strings, $message);
        self::assertNotContains('preg_match', $strings, $message);
        self::assertNotContains('preg_match_all', $strings, $message);
        self::assertNotContains('preg_replace', $strings, $message);
        self::assertNotContains('preg_replace_callback', $strings, $message);
        self::assertNotContains('preg_split', $strings, $message);

        self::assertNotContains('defined', $strings);
    }

    /**
     * @return iterable<array{class-string}>
     */
    public static function provideThereIsNoDisallowedFunctionUsedDirectlyCases(): iterable
    {
        $finder = Finder::create()
            ->files()
            ->in(__DIR__ . '/../../src')
            ->sortByName();

        /** @var SplFileInfo $file */
        foreach ($finder as $file) {
            $namespace = 'PhpCsFixerCustomFixers';
            if ($file->getRelativePath() !== '') {
                $namespace .= '\\' . \str_replace('/', '\\', $file->getRelativePath());
            }

            /** @var class-string $className */
            $className = $namespace . '\\' . $file->getBasename('.php');

            yield $className => [$className];
        }
    }

    private static function createAbstractFixerDouble(): AbstractFixer
    {
        return new class () extends AbstractFixer {
            public function isCandidate(Tokens $tokens): bool
            {
                throw new \BadMethodCallException('Not implemented.');
            }

            public function isRisky(): bool
            {
                throw new \BadMethodCallException('Not implemented.');
            }

            public function fix(\SplFileInfo $file, Tokens $tokens): void
            {
                throw new \BadMethodCallException('Not implemented.');
            }

            public function getDefinition(): FixerDefinitionInterface
            {
                throw new \BadMethodCallException('Not implemented.');
            }

            public function getPriority(): int
            {
                throw new \BadMethodCallException('Not implemented.');
            }
        };
    }

    private static function createSplFileInfoDouble(): \SplFileInfo
    {
        return new class ('') extends \SplFileInfo {};
    }
}
