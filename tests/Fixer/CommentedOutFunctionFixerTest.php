<?php declare(strict_types=1);

/*
 * This file is part of PHP CS Fixer: custom fixers.
 *
 * (c) 2018 Kuba Werłos
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Tests\Fixer;

use PhpCsFixer\Fixer\ConfigurableFixerInterface;

/**
 * @internal
 *
 * @property ConfigurableFixerInterface $fixer
 *
 * @covers \PhpCsFixerCustomFixers\Fixer\CommentedOutFunctionFixer
 */
final class CommentedOutFunctionFixerTest extends AbstractFixerTestCase
{
    public function testConfiguration(): void
    {
        $options = $this->fixer->getConfigurationDefinition()->getOptions();
        self::assertArrayHasKey(0, $options);
        self::assertSame('functions', $options[0]->getName());
    }

    public function testIsRisky(): void
    {
        self::assertTrue($this->fixer->isRisky());
    }

    /**
     * @param null|array<string, array<string>> $configuration
     *
     * @dataProvider provideFixCases
     */
    public function testFix(string $expected, ?string $input = null, ?array $configuration = null): void
    {
        $this->fixer->configure($configuration ?? []);
        $this->doTest($expected, $input);
    }

    /**
     * @return iterable<array{0: string, 1?: string, 2?: array<string, array<string>>}>
     */
    public static function provideFixCases(): iterable
    {
        yield 'ignore method' => ['<?php $printingHelper->var_dump($foo);'];
        yield 'ignore in function' => ['<?php prettify(print_r($x, true));'];
        yield 'ignore static method' => ['<?php PrintingHelper::var_dump($foo);'];
        yield 'ignore namespaced function' => ['<?php PrintingHelper\var_dump($foo);'];
        yield 'ignore constant' => ['<?php define("var_dump", "foo"); var_dump; bar($baz);'];
        yield 'ignore function definition' => ['<?php namespace Foo; function var_dump($bar) { return $baz; }'];
        yield 'ignore when not separate statement' => ['<?php echo "<pre>" . print_r($foo, true) . "</pre>";'];
        yield 'ignore when with statement before' => ['<?php "Name:" . print_r($foo, true);'];
        yield 'ignore when with statement after' => ['<?php print_r($foo, true) . " was here";'];
        yield 'ignore when in ternary operator' => ['<?php $isPost ? var_export($_POST) : var_export($_GET);'];

        yield 'after open tag' => [
            '<?php //var_dump($x);',
            '<?php var_dump($x);',
        ];

        yield 'new line' => [
            '<?php
//var_dump($x);
',
            '<?php
var_dump($x);
',
        ];

        yield 'multiple' => [
            '<?php
//    var_dump($a);
//    var_dump($b);
//    var_dump($c);
',
            '<?php
    var_dump($a);
    var_dump($b);
    var_dump($c);
',
        ];

        yield 'after few empty lines' => [
            '<?php
            ' . '

            ' . '

//var_dump($x);',
            '<?php
            ' . '

            ' . '

var_dump($x);',
        ];

        yield 'between statements' => [
            '<?php
$x = foo();
//var_dump($x);
bar($x);
',
            '<?php
$x = foo();
var_dump($x);
bar($x);
',
        ];

        yield 'uppercase' => [
            '<?php //VAR_DUMP($x);',
            '<?php VAR_DUMP($x);',
        ];

        yield 'in condition' => [
            '<?php if ($foo) {
//                    var_dump($_SESSION);
                };',
            '<?php if ($foo) {
                    var_dump($_SESSION);
                };',
        ];

        yield 'in switch' => [
            '<?php switch ($foo) {
                    case 1:
//                        var_dump($_SESSION);
                };',
            '<?php switch ($foo) {
                    case 1:
                        var_dump($_SESSION);
                };',
        ];

        yield 'after condition' => [
            '<?php if ($foo) {
                    return true;
                }
//                var_dump($_SESSION);
                ',
            '<?php if ($foo) {
                    return true;
                }
                var_dump($_SESSION);
                ',
        ];

        yield 'with leading backslash' => [
            '<?php //\var_dump($x);',
            '<?php \var_dump($x);',
        ];

        yield 'with custom function' => [
            '<?php var_dump($x);// foo($y);',
            '<?php var_dump($x); foo($y);',
            ['functions' => ['foo']],
        ];

        yield 'multiline call' => [
            '<?php
//                var_dump(foo(
//                    100,
//                    bar($x + 4)
//                ));
            ',
            '<?php
                var_dump(foo(
                    100,
                    bar($x + 4)
                ));
            ',
        ];

        yield 'having statement after' => [
            '<?php /*var_dump($x);*/ foo();',
            '<?php var_dump($x); foo();',
        ];

        yield 'having comment inside' => [
            '<?php //var_dump($x/*, $y*/);',
            '<?php var_dump($x/*, $y*/);',
        ];

        yield 'having comment inside and statement after' => [
            '<?php //var_dump($x/*, $y*/);
foo();',
            '<?php var_dump($x/*, $y*/);foo();',
        ];

        yield 'having comment inside and comment after' => [
            '<?php //var_dump($x/*, $y*/);
 /* foo */ foo();',
            '<?php var_dump($x/*, $y*/); /* foo */ foo();',
        ];

        yield 'multiline call and statement after' => [
            '<?php
//                var_dump(foo(
//                    100, /* 10 * 10 */
//                    bar($x + 4) // comment
//                ));
baz();
            ',
            '<?php
                var_dump(foo(
                    100, /* 10 * 10 */
                    bar($x + 4) // comment
                ));baz();
            ',
        ];

        yield 'with close tag' => [
            '<?php /*var_dump($x)*/ ?>',
            '<?php var_dump($x) ?>',
        ];

        yield 'complex' => [
            '<?php
//                var_dump($x);
                print_r($x, true) . " is input";
                "Result: " . print_r($y, true);
//                var_dump($y);
            ',
            '<?php
                var_dump($x);
                print_r($x, true) . " is input";
                "Result: " . print_r($y, true);
                var_dump($y);
            ',
        ];
    }

    /**
     * @requires PHP ^7.4
     */
    public function testWithCommentBetweenBackslashAndFunctionCall(): void
    {
        $this->doTest(
            '<?php //\/* foo */var_dump/** bar */($x);',
            '<?php \/* foo */var_dump/** bar */($x);',
        );
    }
}
