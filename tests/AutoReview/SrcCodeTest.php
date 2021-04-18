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

namespace Tests\AutoReview;

use PhpCsFixer\Fixer\DeprecatedFixerInterface;
use PhpCsFixer\Fixer\FixerInterface;
use PhpCsFixer\FixerNameValidator;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;
use PhpCsFixerCustomFixers\Fixer\AbstractFixer;
use PhpCsFixerCustomFixers\Fixer\DeprecatingFixerInterface;
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
            \sprintf('Fixer name "%s" is incorrect', $fixer->getName())
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
    public function testFixerIsNotBothDeprecatingAndDeprecated(FixerInterface $fixer): void
    {
        self::assertFalse($fixer instanceof DeprecatingFixerInterface && $fixer instanceof DeprecatedFixerInterface);
    }

    /**
     * @dataProvider provideFixerCases
     */
    public function testDeprecatedFixerHasAnnotation(FixerInterface $fixer): void
    {
        $comment = (new \ReflectionObject($fixer))->getDocComment();
        self::assertSame(
            $fixer instanceof DeprecatedFixerInterface,
            \strpos($comment === false ? '' : $comment, '@deprecated') !== false
        );
    }

    /**
     * @return iterable<array<FixerInterface>>
     */
    public static function provideFixerCases(): iterable
    {
        return \array_map(
            static function (FixerInterface $fixer): array {
                return [$fixer];
            },
            \iterator_to_array(new Fixers())
        );
    }

    public function testFixerSupportsAllFilesByDefault(): void
    {
        $fixer = $this->getMockForAbstractClass(AbstractFixer::class);

        self::assertTrue($fixer->supports($this->createMock(\SplFileInfo::class)));
    }

    /**
     * @dataProvider provideThereIsNoPregFunctionUsedDirectlyCases
     */
    public function testThereIsNoPregFunctionUsedDirectly(string $className): void
    {
        $reflectionClass = new \ReflectionClass($className);

        /** @var string $fileName */
        $fileName = $reflectionClass->getFileName();

        /** @var string $content */
        $content = \file_get_contents($fileName);

        $tokens = Tokens::fromCode($content);
        $stringTokens = \array_filter(
            $tokens->toArray(),
            static function (Token $token): bool {
                return $token->isGivenKind(\T_STRING);
            }
        );
        $strings = \array_map(
            static function (Token $token): string {
                return $token->getContent();
            },
            $stringTokens
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
    }

    /**
     * @return iterable<array{string}>
     */
    public static function provideThereIsNoPregFunctionUsedDirectlyCases(): iterable
    {
        $finder = Finder::create()
            ->files()
            ->in(__DIR__ . '/../../src')
            ->notName('php-cs-fixer.config.*.php')
            ->notName('run')
            ->sortByName();

        /** @var SplFileInfo $file */
        foreach ($finder as $file) {
            $namespace = 'PhpCsFixerCustomFixers';
            if ($file->getRelativePath() !== '') {
                $namespace .= '\\' . \str_replace('/', '\\', $file->getRelativePath());
            }

            yield [$namespace . '\\' . $file->getBasename('.php')];
        }
    }
}
