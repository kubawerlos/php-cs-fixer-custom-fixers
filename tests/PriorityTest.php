<?php

declare(strict_types = 1);

namespace Tests;

use PhpCsFixer\Fixer\Comment\CommentToPhpdocFixer;
use PhpCsFixer\Fixer\Comment\MultilineCommentOpeningClosingFixer;
use PhpCsFixer\Fixer\FixerInterface;
use PhpCsFixer\Fixer\FunctionNotation\NoUnreachableDefaultArgumentValueFixer;
use PhpCsFixer\Fixer\Import\NoUnusedImportsFixer;
use PhpCsFixer\Fixer\Operator\ConcatSpaceFixer;
use PhpCsFixer\Fixer\Phpdoc\NoEmptyPhpdocFixer;
use PhpCsFixer\Fixer\Phpdoc\PhpdocAlignFixer;
use PhpCsFixer\Fixer\Phpdoc\PhpdocTrimConsecutiveBlankLineSeparationFixer;
use PhpCsFixer\Fixer\Phpdoc\PhpdocTrimFixer;
use PhpCsFixer\Fixer\Whitespace\NoExtraBlankLinesFixer;
use PhpCsFixer\Tests\Test\Assert\AssertTokensTrait;
use PhpCsFixer\Tokenizer\Tokens;
use PhpCsFixerCustomFixers\Fixer\CommentSurroundedBySpacesFixer;
use PhpCsFixerCustomFixers\Fixer\MultilineCommentOpeningClosingAloneFixer;
use PhpCsFixerCustomFixers\Fixer\NoCommentedOutCodeFixer;
use PhpCsFixerCustomFixers\Fixer\NoUnneededConcatenationFixer;
use PhpCsFixerCustomFixers\Fixer\NoUselessCommentFixer;
use PhpCsFixerCustomFixers\Fixer\NullableParamStyleFixer;
use PhpCsFixerCustomFixers\Fixer\PhpdocNoIncorrectVarAnnotationFixer;
use PhpCsFixerCustomFixers\Fixer\PhpdocOnlyAllowedAnnotationsFixer;
use PhpCsFixerCustomFixers\Fixer\PhpdocParamTypeFixer;
use PhpCsFixerCustomFixers\Fixer\PhpUnitNoUselessReturnFixer;
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

    public function testProvidePriorityCasesIsSorted(): void
    {
        $cases = \array_map(
            static function (array $case): string {
                return \sprintf(
                    '%s_%s',
                    (new \ReflectionClass($case[0]))->getShortName(),
                    (new \ReflectionClass($case[1]))->getShortName()
                );
            },
            \iterator_to_array($this->providePriorityCases())
        );

        $sorted = $cases;
        \sort($sorted);

        static::assertSame($sorted, $cases);
    }

    public function providePriorityCases(): iterable
    {
        yield [
            new CommentSurroundedBySpacesFixer(),
            new MultilineCommentOpeningClosingFixer(),
            '<?php /** foo */',
            '<?php /**foo**/',
        ];

        yield [
            new CommentToPhpdocFixer(),
            new PhpdocParamTypeFixer(),
            '<?php /* header comment */ $foo = true;
                /**
                 * @param mixed $x
                 */
                function bar($x) {}
            ',
            '<?php /* header comment */ $foo = true;
                /*
                 * @param $x
                 */
                function bar($x) {}
            ',
        ];

        yield [
            new MultilineCommentOpeningClosingAloneFixer(),
            new PhpdocTrimFixer(),
            '<?php
                /**
                 * foo
                 */
            ',
            '<?php
                /**    
                 * foo
                 */
            ',
        ];

        yield [
            new NoCommentedOutCodeFixer(),
            new NoUnusedImportsFixer(),
            '<?php
                use Foo\Bar;
                $x = new Bar();
            ',
            '<?php
                use Foo\Bar;
                use Foo\Baz;
                $x = new Bar();
                // $y = new Baz();
            ',
        ];

        yield [
            new NoUselessCommentFixer(),
            new NoEmptyPhpdocFixer(),
            '<?php
                
                 class Foo {}
            ',
            '<?php
                /**
                 * Class Foo
                 */
                 class Foo {}
            ',
        ];

        yield [
            new NoUselessCommentFixer(),
            new PhpdocTrimConsecutiveBlankLineSeparationFixer(),
            '<?php
                /**
                 * @version 1.0
                 *
                 * @author John Doe
                 */
                 class Foo {}
            ',
            '<?php
                /**
                 * @version 1.0
                 *
                 * Class Foo
                 *
                 * @author John Doe
                 */
                 class Foo {}
            ',
        ];

        yield [
            new NoUselessCommentFixer(),
            new PhpdocTrimFixer(),
            '<?php
                /**
                 * @author John Doe
                 */
                 class Foo {}
            ',
            '<?php
                /**
                 * Class Foo
                 *
                 * @author John Doe
                 */
                 class Foo {}
            ',
        ];

        yield [
            new NullableParamStyleFixer(),
            new NoUnreachableDefaultArgumentValueFixer(),
            '<?php
                function foo(
                    ?int $x,
                    int $y
                ) {}
            ',
            '<?php
                function foo(
                    int $x = null,
                    int $y
                ) {}
            ',
        ];

        $noExtraBlankLinesFixer = new NoExtraBlankLinesFixer();
        $noExtraBlankLinesFixer->configure(['tokens' => ['curly_brace_block']]);
        yield [
            new PhpUnitNoUselessReturnFixer(),
            $noExtraBlankLinesFixer,
            '<?php
                class FooTest extends TestCase {
                    public function testFoo() {
                        $this->markTestSkipped();
                    }
                }
            ',
            '<?php
                class FooTest extends TestCase {
                    public function testFoo() {
                        $this->markTestSkipped();

                        return;

                    }
                }
            ',
        ];

        yield [
            new PhpdocNoIncorrectVarAnnotationFixer(),
            new NoEmptyPhpdocFixer(),
            '<?php
                
                $y = 2;
            ',
            '<?php
                /**
                 * @var int $x
                 */
                $y = 2;
            ',
        ];

        yield [
            new PhpdocNoIncorrectVarAnnotationFixer(),
            new NoUnusedImportsFixer(),
            '<?php
                $y = 2;
            ',
            '<?php
                use Foo\Bar;
                /** @var Bar $x */
                $y = 2;
            ',
        ];

        yield [
            new PhpdocOnlyAllowedAnnotationsFixer(),
            new NoEmptyPhpdocFixer(),
            '<?php
                
                class Foo {}
            ',
            '<?php
                /**
                 * @author John Doe
                 */
                class Foo {}
            ',
        ];

        yield [
            new PhpdocParamTypeFixer(),
            new PhpdocAlignFixer(),
            '<?php
                /**
                 * @param int   $x
                 * @param mixed $y
                 */
                function foo($x, $y) {}
            ',
            '<?php
                /**
                 * @param int $x
                 * @param     $y
                 */
                function foo($x, $y) {}
            ',
        ];

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
