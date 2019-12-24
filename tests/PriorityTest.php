<?php

declare(strict_types = 1);

namespace Tests;

use PhpCsFixer\Fixer\Comment\CommentToPhpdocFixer;
use PhpCsFixer\Fixer\Comment\MultilineCommentOpeningClosingFixer;
use PhpCsFixer\Fixer\Comment\NoEmptyCommentFixer;
use PhpCsFixer\Fixer\FixerInterface;
use PhpCsFixer\Fixer\FunctionNotation\NoUnreachableDefaultArgumentValueFixer;
use PhpCsFixer\Fixer\FunctionNotation\ReturnTypeDeclarationFixer;
use PhpCsFixer\Fixer\Import\NoUnusedImportsFixer;
use PhpCsFixer\Fixer\Operator\ConcatSpaceFixer;
use PhpCsFixer\Fixer\Phpdoc\NoEmptyPhpdocFixer;
use PhpCsFixer\Fixer\Phpdoc\PhpdocAddMissingParamAnnotationFixer;
use PhpCsFixer\Fixer\Phpdoc\PhpdocAlignFixer;
use PhpCsFixer\Fixer\Phpdoc\PhpdocTrimConsecutiveBlankLineSeparationFixer;
use PhpCsFixer\Fixer\Phpdoc\PhpdocTrimFixer;
use PhpCsFixer\Fixer\Phpdoc\PhpdocTypesOrderFixer;
use PhpCsFixer\Fixer\StringNotation\SingleQuoteFixer;
use PhpCsFixer\Fixer\Whitespace\NoExtraBlankLinesFixer;
use PhpCsFixer\Tests\Test\Assert\AssertTokensTrait;
use PhpCsFixer\Tokenizer\Tokens;
use PhpCsFixerCustomFixers\Fixer\CommentSurroundedBySpacesFixer;
use PhpCsFixerCustomFixers\Fixer\DataProviderReturnTypeFixer;
use PhpCsFixerCustomFixers\Fixer\MultilineCommentOpeningClosingAloneFixer;
use PhpCsFixerCustomFixers\Fixer\NoCommentedOutCodeFixer;
use PhpCsFixerCustomFixers\Fixer\NoImportFromGlobalNamespaceFixer;
use PhpCsFixerCustomFixers\Fixer\NoSuperfluousConcatenationFixer;
use PhpCsFixerCustomFixers\Fixer\NoUselessCommentFixer;
use PhpCsFixerCustomFixers\Fixer\NullableParamStyleFixer;
use PhpCsFixerCustomFixers\Fixer\PhpdocNoIncorrectVarAnnotationFixer;
use PhpCsFixerCustomFixers\Fixer\PhpdocNoSuperfluousParamFixer;
use PhpCsFixerCustomFixers\Fixer\PhpdocOnlyAllowedAnnotationsFixer;
use PhpCsFixerCustomFixers\Fixer\PhpdocParamOrderFixer;
use PhpCsFixerCustomFixers\Fixer\PhpdocParamTypeFixer;
use PhpCsFixerCustomFixers\Fixer\PhpdocTypesTrimFixer;
use PhpCsFixerCustomFixers\Fixer\PhpUnitNoUselessReturnFixer;
use PhpCsFixerCustomFixers\Fixer\SingleLineThrowFixer;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
final class PriorityTest extends TestCase
{
    use AssertTokensTrait;

    /**
     * @dataProvider providePriorityCases
     */
    public function testPriorities(FixerInterface $firstFixer, FixerInterface $secondFixer, string $expected, string $input): void
    {
        static::assertLessThan($firstFixer->getPriority(), $secondFixer->getPriority());
    }

    /**
     * @dataProvider providePriorityCases
     */
    public function testInOrder(FixerInterface $firstFixer, FixerInterface $secondFixer, string $expected, string $input): void
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
    public function testInRevertedOrder(FixerInterface $firstFixer, FixerInterface $secondFixer, string $expected, string $input): void
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

    public static function providePriorityCases(): iterable
    {
        yield [
            new CommentSurroundedBySpacesFixer(),
            new MultilineCommentOpeningClosingFixer(),
            '<?php /** foo */',
            '<?php /**foo**/',
        ];

        yield [
            new CommentToPhpdocFixer(),
            new PhpdocNoSuperfluousParamFixer(),
            '<?php /* header comment */ $foo = true;
                /**
                 */
                 function bar() {}
            ',
            '<?php /* header comment */ $foo = true;
                /*
                 * @param $x
                 */
                 function bar() {}
            ',
        ];

        yield [
            new CommentToPhpdocFixer(),
            new PhpdocOnlyAllowedAnnotationsFixer(),
            '<?php /* header comment */ $foo = true;
                /**
                 */
                 function bar() {}
            ',
            '<?php /* header comment */ $foo = true;
                /*
                 * @param $x
                 */
                 function bar() {}
            ',
        ];

        yield [
            new CommentToPhpdocFixer(),
            new PhpdocParamOrderFixer(),
            '<?php /* header comment */ $foo = true;
                /**
                 * @param $a
                 * @param $b
                 */
                 function bar($a, $b) {}
            ',
            '<?php /* header comment */ $foo = true;
                /*
                 * @param $b
                 * @param $a
                 */
                 function bar($a, $b) {}
            ',
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

        $returnTypeDeclarationFixer = new ReturnTypeDeclarationFixer();
        $returnTypeDeclarationFixer->configure(['space_before' => 'one']);
        yield [
            new DataProviderReturnTypeFixer(),
            $returnTypeDeclarationFixer,
            '<?php
                class FooTest extends TestCase {
                    /**
                     * @dataProvider provideFooCases
                     */
                    function testFoo() {}
                    function provideFooCases() : iterable {}
                }
            ',
            '<?php
                class FooTest extends TestCase {
                    /**
                     * @dataProvider provideFooCases
                     */
                    function testFoo() {}
                    function provideFooCases() {}
                }
            ',
        ];

        yield [
            new MultilineCommentOpeningClosingAloneFixer(),
            new MultilineCommentOpeningClosingFixer(),
            '<?php /**
                    * foo
                    */',
            '<?php /**foo
                    *******/',
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
            new NoImportFromGlobalNamespaceFixer(),
            new PhpdocAlignFixer(),
            '<?php
                namespace Foo;
                /**
                 * @param bool      $b
                 * @param \DateTime $d
                 */
                 function bar($b, $d) {}
            ',
            '<?php
                namespace Foo;
                use DateTime;
                /**
                 * @param bool     $b
                 * @param DateTime $d
                 */
                 function bar($b, $d) {}
            ',
        ];

        yield [
            new NoUselessCommentFixer(),
            new NoEmptyCommentFixer(),
            '<?php
                
                 class Foo {}
            ',
            '<?php
                /*
                 * Class Foo
                 */
                 class Foo {}
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
            new PhpdocAddMissingParamAnnotationFixer(),
            new PhpdocParamOrderFixer(),
            '<?php /* header comment */ $foo = true;
                /**
                 * @param mixed $a
                 * @param mixed $b
                 */
                 function bar($a, $b) {}
            ',
            '<?php /* header comment */ $foo = true;
                /**
                 * @param mixed $b
                 */
                 function bar($a, $b) {}
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
            new NoExtraBlankLinesFixer(),
            '<?php

                $y = 2;
            ',
            '<?php

                /** @var int $x */

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
            new PhpdocNoIncorrectVarAnnotationFixer(),
            new PhpdocTrimConsecutiveBlankLineSeparationFixer(),
            '<?php
                /**
                 * Foo
                 *
                 * @see example.com
                 */
                $y = 2;
            ',
            '<?php
                /**
                 * Foo
                 *
                 * @var int $x
                 *
                 * @see example.com
                 */
                $y = 2;
            ',
        ];

        yield [
            new PhpdocNoIncorrectVarAnnotationFixer(),
            new PhpdocTrimFixer(),
            '<?php
                /**
                 * Foo
                 */
                $y = 2;
            ',
            '<?php
                /**
                 * Foo
                 *
                 * @var int $x
                 */
                $y = 2;
            ',
        ];

        yield [
            new PhpdocNoSuperfluousParamFixer(),
            new NoEmptyPhpdocFixer(),
            '<?php
                
                 function foo() {}
            ',
            '<?php
                /**
                 * @param $x
                 */
                 function foo() {}
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
            new PhpdocParamOrderFixer(),
            new PhpdocAlignFixer(),
            '<?php /* header comment */ $foo = true;
                /**
                 * @param int    $a
                 * @param string $b
                 * @author John Doe
                 */
                 function bar($a, $b) {}
            ',
            '<?php /* header comment */ $foo = true;
                /**
                 * @param string $b
                 * @author John Doe
                 * @param int $a
                 */
                 function bar($a, $b) {}
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
            new PhpdocTypesTrimFixer(),
            new PhpdocAlignFixer(),
            '<?php
                /**
                 * @param Foo|Bar $x
                 * @param Foo     $y
                 */
                function foo($x, $y) {}
            ',
            '<?php
                /**
                 * @param Foo | Bar $x
                 * @param Foo       $y
                 */
                function foo($x, $y) {}
            ',
        ];

        yield [
            new PhpdocTypesTrimFixer(),
            new PhpdocTypesOrderFixer(),
            '<?php
                /**
                 * @param Bar|Foo $x
                 */
                function foo($x) {}
            ',
            '<?php
                /**
                 * @param Foo | Bar $x
                 */
                function foo($x) {}
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
            new NoSuperfluousConcatenationFixer(),
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

        yield [
            new SingleQuoteFixer(),
            new NoSuperfluousConcatenationFixer(),
            '<?php
                $x = \'FooBar\';
            ',
            '<?php
                $x = "Foo" . \'Bar\';
            ',
        ];
    }
}
