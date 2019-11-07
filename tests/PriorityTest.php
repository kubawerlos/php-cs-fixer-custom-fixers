<?php

declare(strict_types = 1);

namespace Tests;

use PhpCsFixer\Fixer\FixerInterface;
use PhpCsFixer\Fixer\Operator\ConcatSpaceFixer;
use PhpCsFixer\Tests\Test\Assert\AssertTokensTrait;
use PhpCsFixer\Tokenizer\Tokens;
use PhpCsFixerCustomFixers\Fixer\NoUnneededConcatenationFixer;
use PhpCsFixerCustomFixers\Fixer\SingleLineThrowFixer;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 * @coversNothing
 */
final class PriorityTest extends TestCase
{
    use AssertTokensTrait;

    /**
     * @dataProvider providePriorityCases
     */
    public function testCorrectOrderWorks(FixerInterface $firstFixer, FixerInterface $secondFixer, string $expected, string $input): void
    {
        Tokens::clearCache();
        $tokens = Tokens::fromCode($input);

        $firstFixer->fix($this->createMock(\SplFileInfo::class), $tokens);
        $tokens->clearEmptyTokens();

        $secondFixer->fix($this->createMock(\SplFileInfo::class), $tokens);
        $tokens->clearEmptyTokens();

        static::assertSame($expected, $tokens->generateCode());

        Tokens::clearCache();
        static::assertTokens(Tokens::fromCode($expected), $tokens);
    }

    /**
     * @dataProvider providePriorityCases
     */
    public function testIncorrectOrderDoesNotWork(FixerInterface $firstFixer, FixerInterface $secondFixer, string $expected, string $input): void
    {
        Tokens::clearCache();
        $tokens = Tokens::fromCode($input);

        $secondFixer->fix($this->createMock(\SplFileInfo::class), $tokens);
        $tokens->clearEmptyTokens();

        $firstFixer->fix($this->createMock(\SplFileInfo::class), $tokens);
        $tokens->clearEmptyTokens();

        static::assertNotSame($expected, $tokens->generateCode());
    }

    public function providePriorityCases(): iterable
    {
        yield [
            new SingleLineThrowFixer(),
            new ConcatSpaceFixer(),
            '<?php
                throw new Exception("This should"."not happen");
            ',
            '<?php
                throw new Exception(
                    "This should"
                    . "not happen"
                );
            ',
        ];

        yield [
            new SingleLineThrowFixer(),
            new NoUnneededConcatenationFixer(),
            '<?php
                throw new Exception("This should not happen");
            ',
            '<?php
                throw new Exception(
                    "This should"
                    . " not happen"
                );
            ',
        ];
    }
}
