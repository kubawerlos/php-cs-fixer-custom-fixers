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

namespace Tests;

use PhpCsFixer\Fixer as Fixer;
use PhpCsFixer\Fixer\FixerInterface;
use PhpCsFixer\Tests\Test\Assert\AssertTokensTrait;
use PhpCsFixer\Tokenizer\Tokens;
use PhpCsFixerCustomFixers\Fixer as CustomFixer;
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
        self::assertLessThan($firstFixer->getPriority(), $secondFixer->getPriority());
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

        self::assertSame($expected, $tokens->generateCode());

        Tokens::clearCache();
        self::assertTokens(Tokens::fromCode($expected), $tokens);
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

        self::assertNotSame($expected, $tokens->generateCode());
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

        self::assertSame($sorted, $cases);
    }

    /**
     * @return FixerInterface[][]
     */
    public static function providePriorityCases(): iterable
    {
        yield [
            new CustomFixer\CommentSurroundedBySpacesFixer(),
            new Fixer\Comment\MultilineCommentOpeningClosingFixer(),
            '<?php /** foo */',
            '<?php /**foo**/',
        ];

        yield [
            new Fixer\Comment\CommentToPhpdocFixer(),
            new CustomFixer\PhpdocNoSuperfluousParamFixer(),
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
            new Fixer\Comment\CommentToPhpdocFixer(),
            new CustomFixer\PhpdocOnlyAllowedAnnotationsFixer(),
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
            new Fixer\Comment\CommentToPhpdocFixer(),
            new CustomFixer\PhpdocParamOrderFixer(),
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
            new Fixer\Comment\CommentToPhpdocFixer(),
            new CustomFixer\PhpdocParamTypeFixer(),
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
            new CustomFixer\CommentedOutFunctionFixer(),
            new CustomFixer\CommentSurroundedBySpacesFixer(),
            '<?php
$x = foo();
// var_dump($x);
bar($x);
            ',
            '<?php
$x = foo();
var_dump($x);
bar($x);
            ',
        ];

        yield [
            new CustomFixer\CommentedOutFunctionFixer(),
            new CustomFixer\NoCommentedOutCodeFixer(),
            '<?php
                $x = foo();
                bar($x);
            ',
            '<?php
                $x = foo();
                var_dump($x);
                bar($x);
            ',
        ];

        $returnTypeDeclarationFixer = new Fixer\FunctionNotation\ReturnTypeDeclarationFixer();
        $returnTypeDeclarationFixer->configure(['space_before' => 'one']);
        yield [
            new CustomFixer\DataProviderReturnTypeFixer(),
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
            new CustomFixer\MultilineCommentOpeningClosingAloneFixer(),
            new Fixer\Comment\MultilineCommentOpeningClosingFixer(),
            '<?php /**
                    * foo
                    */',
            '<?php /**foo
                    *******/',
        ];

        yield [
            new CustomFixer\NoCommentedOutCodeFixer(),
            new Fixer\Whitespace\NoExtraBlankLinesFixer(),
            '<?php
                use Foo\Bar;

                $y = new Bar();
            ',
            '<?php
                use Foo\Bar;

                // $x = new Bar();

                $y = new Bar();
            ',
        ];

        yield [
            new CustomFixer\NoCommentedOutCodeFixer(),
            new Fixer\Whitespace\NoTrailingWhitespaceFixer(),
            '<?php
                $foo;
            ',
            '<?php
                $foo; // $bar;
            ',
        ];

        yield [
            new CustomFixer\NoCommentedOutCodeFixer(),
            new Fixer\Import\NoUnusedImportsFixer(),
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
            new CustomFixer\NoImportFromGlobalNamespaceFixer(),
            new Fixer\Phpdoc\PhpdocAlignFixer(),
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
            new CustomFixer\NoUselessCommentFixer(),
            new Fixer\Comment\NoEmptyCommentFixer(),
            '<?php
                ' . '
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
            new CustomFixer\NoUselessCommentFixer(),
            new Fixer\Phpdoc\NoEmptyPhpdocFixer(),
            '<?php
                ' . '
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
            new CustomFixer\NoUselessCommentFixer(),
            new Fixer\Phpdoc\PhpdocTrimConsecutiveBlankLineSeparationFixer(),
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
            new CustomFixer\NoUselessCommentFixer(),
            new Fixer\Phpdoc\PhpdocTrimFixer(),
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

        $noExtraBlankLinesFixer = new Fixer\Whitespace\NoExtraBlankLinesFixer();
        $noExtraBlankLinesFixer->configure(['tokens' => ['curly_brace_block']]);
        yield [
            new CustomFixer\PhpUnitNoUselessReturnFixer(),
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
            new Fixer\Phpdoc\PhpdocAddMissingParamAnnotationFixer(),
            new CustomFixer\PhpdocParamOrderFixer(),
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

        $phpdocLineSpanFixer = new Fixer\Phpdoc\PhpdocLineSpanFixer();
        $phpdocLineSpanFixer->configure(['property' => 'multi']);
        yield [
            $phpdocLineSpanFixer,
            new CustomFixer\PhpdocSingleLineVarFixer(),
            '<?php
                class Foo {
                    /** @var string */
                    private $bar;
                }',
            '<?php
                class Foo {
                    /**
                     * @var string
                     */
                    private $bar;
                }',
        ];

        yield [
            new CustomFixer\PhpdocNoIncorrectVarAnnotationFixer(),
            new Fixer\Phpdoc\NoEmptyPhpdocFixer(),
            '<?php
                ' . '
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
            new CustomFixer\PhpdocNoIncorrectVarAnnotationFixer(),
            new Fixer\Whitespace\NoExtraBlankLinesFixer(),
            '<?php

                $y = 2;
            ',
            '<?php

                /** @var int $x */

                $y = 2;
            ',
        ];

        yield [
            new CustomFixer\PhpdocNoIncorrectVarAnnotationFixer(),
            new Fixer\Import\NoUnusedImportsFixer(),
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
            new CustomFixer\PhpdocNoIncorrectVarAnnotationFixer(),
            new Fixer\Phpdoc\PhpdocTrimConsecutiveBlankLineSeparationFixer(),
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
            new CustomFixer\PhpdocNoIncorrectVarAnnotationFixer(),
            new Fixer\Phpdoc\PhpdocTrimFixer(),
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
            new CustomFixer\PhpdocNoSuperfluousParamFixer(),
            new Fixer\Phpdoc\NoEmptyPhpdocFixer(),
            '<?php
                ' . '
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
            new CustomFixer\PhpdocOnlyAllowedAnnotationsFixer(),
            new Fixer\Phpdoc\NoEmptyPhpdocFixer(),
            '<?php
                ' . '
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
            new CustomFixer\PhpdocParamOrderFixer(),
            new Fixer\Phpdoc\PhpdocAlignFixer(),
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
            new CustomFixer\PhpdocParamTypeFixer(),
            new Fixer\Phpdoc\NoSuperfluousPhpdocTagsFixer(),
            '<?php
                /**
                 */
                 function f($x) {}
            ',
            '<?php
                /**
                 * @param $x
                 */
                 function f($x) {}
            ',
        ];

        yield [
            new CustomFixer\PhpdocParamTypeFixer(),
            new Fixer\Phpdoc\PhpdocAlignFixer(),
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
            new CustomFixer\PhpdocSelfAccessorFixer(),
            new Fixer\Phpdoc\NoSuperfluousPhpdocTagsFixer(),
            '<?php
                class Foo
                {
                    /**
                     */
                    public function bar(): self
                    {}
                }
            ',
            '<?php
                class Foo
                {
                    /**
                     * @return Foo
                     */
                    public function bar(): self
                    {}
                }
            ',
        ];

        yield [
            new CustomFixer\PhpdocTypesTrimFixer(),
            new Fixer\Phpdoc\PhpdocAlignFixer(),
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
            new CustomFixer\PhpdocTypesTrimFixer(),
            new Fixer\Phpdoc\PhpdocTypesOrderFixer(),
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
            new Fixer\FunctionNotation\SingleLineThrowFixer(),
            new CustomFixer\NoSuperfluousConcatenationFixer(),
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
