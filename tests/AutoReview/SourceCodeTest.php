<?php

declare(strict_types = 1);

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
final class SourceCodeTest extends TestCase
{
    /**
     * @dataProvider provideFixerCases
     */
    public function testFixerExtendsAbstractFixer(FixerInterface $fixer): void
    {
        static::assertInstanceOf(AbstractFixer::class, $fixer);
    }

    /**
     * @dataProvider provideFixerCases
     */
    public function testFixerHasValidName(FixerInterface $fixer): void
    {
        $validator = new FixerNameValidator();

        static::assertTrue(
            $validator->isValid($fixer->getName(), true),
            \sprintf('Fixer name "%s" is incorrect', $fixer->getName())
        );
    }

    /**
     * @dataProvider provideFixerCases
     */
    public function testFixerIsFinal(FixerInterface $fixer): void
    {
        static::assertTrue((new \ReflectionClass($fixer))->isFinal());
    }

    /**
     * @dataProvider provideFixerCases
     */
    public function testFixerIsNotBothDeprecatingAndDeprecated(FixerInterface $fixer): void
    {
        static::assertFalse($fixer instanceof DeprecatingFixerInterface && $fixer instanceof DeprecatedFixerInterface);
    }

    /**
     * @dataProvider provideFixerCases
     */
    public function testDeprecatedFixerHasAnnotation(FixerInterface $fixer): void
    {
        $comment = (new \ReflectionClass($fixer))->getDocComment();
        static::assertSame(
            $fixer instanceof DeprecatedFixerInterface,
            \strpos($comment === false ? '' : $comment, '@deprecated') !== false
        );
    }

    public function provideFixerCases(): iterable
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

        static::assertTrue($fixer->supports($this->createMock(\SplFileInfo::class)));
    }

    /**
     * @dataProvider provideThereIsNoPregFunctionUsedDirectlyCases
     */
    public function testThereIsNoPregFunctionUsedDirectly(string $className): void
    {
        $rc = new \ReflectionClass($className);
        $tokens = Tokens::fromCode(\file_get_contents($rc->getFileName()));
        $stringTokens = \array_filter(
            $tokens->toArray(),
            static function (Token $token) {
                return $token->isGivenKind(T_STRING);
            }
        );
        $strings = \array_map(
            static function (Token $token) {
                return $token->getContent();
            },
            $stringTokens
        );
        $strings = \array_unique($strings);
        $message = \sprintf('Class %s must not use preg_*, it shall use Preg::* instead.', $className);

        static::assertNotContains('preg_filter', $strings, $message);
        static::assertNotContains('preg_grep', $strings, $message);
        static::assertNotContains('preg_match', $strings, $message);
        static::assertNotContains('preg_match_all', $strings, $message);
        static::assertNotContains('preg_replace', $strings, $message);
        static::assertNotContains('preg_replace_callback', $strings, $message);
        static::assertNotContains('preg_split', $strings, $message);
    }

    public function provideThereIsNoPregFunctionUsedDirectlyCases(): iterable
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
                $className .= '\\' . $file->getRelativePath();
            }

            yield [$className . '\\' . $file->getBasename('.php')];
        }
    }
}
