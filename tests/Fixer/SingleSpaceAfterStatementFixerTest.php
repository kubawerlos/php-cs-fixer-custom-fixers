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

use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;
use PhpCsFixerCustomFixers\Fixer\SingleSpaceAfterStatementFixer;

/**
 * @internal
 *
 * @covers \PhpCsFixerCustomFixers\Fixer\SingleSpaceAfterStatementFixer
 */
final class SingleSpaceAfterStatementFixerTest extends AbstractFixerTestCase
{
    private const EXAMPLE_WITH_ALL_TOKENS = '<?php
namespace    FooNamespace;

use    Foo\\Bar as    FooBar;
use    const       SOME_CONST;
use    function    someFunction;

abstract    class    FooClass extends    AbstractFoo implements    FooInterface
{
    use    FooTrait, BarTrait {
        BarTrait::helloWorld insteadof    FooTrait;
    }

    private    const    C = 42;

    protected    $prop;

    var    $var;

    public    function    doStuff()
    {
        global    $globalVariableSadFace;

        $j = clone    $this;

        if    ($isIt) {
            echo    "Oh no";
        } else    if    ($isItMore) {
            print   "Oh no, no";
        } elseif    ($x instanceof    self) {
            return    "Oh yes";
        }

        foreach    ([1, 2, 3] as $number) {
            switch    ($x) {
                case    1:
                    break    2;
                case    2:
                    continue    1;
                case  666:
                    goto    hell;
                    hell:
                    throw    new    DoNotUseGotoException();
                default:
                    yield    0;
                    yield from    0;
            }
        }

        try    {
            for    ($i = 0; $i < 10; $i++ ) {
                doThis();
            }
        } catch    (\\Exception $x) {
            do    {
                $i++;
            } while    (false);
        } finally    {
            return function    () use    ($i) {
                include         "File1.php";
                include_once    "File2.php";
                require         "File3.php";
                require_once    "File4.php";
            };
        }
    }
}

final    class    FooFinal {
}

trait    FooTrait {
}

interface    FooInterface {
}
';

    public function testConfiguration(): void
    {
        $options = self::getConfigurationOptions();
        self::assertArrayHasKey(0, $options);
        self::assertSame('allow_linebreak', $options[0]->getName());
    }

    public function testIsRisky(): void
    {
        self::assertRiskiness(false);
    }

    /**
     * @param array<string, bool> $configuration
     *
     * @dataProvider provideFixCases
     */
    public function testFix(string $expected, ?string $input = null, array $configuration = []): void
    {
        $this->doTest($expected, $input, $configuration);
    }

    /**
     * @return iterable<array{0: string, 1?: null|string, 2?: array<string, bool>}>
     */
    public static function provideFixCases(): iterable
    {
        yield ['<?php echo 1; array(1, 2, 3);'];
        yield ['<?php echo 1; foo(2);'];
        yield ['<?php echo 1; foo    (2);'];
        yield ['<?php echo 1; isset($foo["bar"]);'];
        yield ['<?php echo (self::class);'];
        yield ['<?php return;'];
        yield ['<?php if (true): echo "yes"; else: echo "no"; endif;'];
        yield ['<?php new class() {};'];

        yield [
            '<?php new class {};',
            '<?php new class{};',
        ];

        yield [
            '<?php echo "100";',
            '<?php echo"100";',
        ];

        yield [
            '<?php echo "100";',
            '<?php echo    "100";',
        ];

        yield [
            "<?php echo '100';",
            "<?php echo\t'100';",
        ];

        yield [
            "<?php echo '100';",
            "<?php echo\n'100';",
        ];

        yield [
            "<?php echo\n'100';",
            null,
            ['allow_linebreak' => true],
        ];

        yield [
            '<?php return $x;',
            '<?php return$x;',
        ];

        yield [
            '<?php new class extends Exception {};',
            '<?php new class    extends Exception {};',
        ];

        yield [
            '<?php
function foo () {
    try {
        yield -1;
    } catch (Throwable $e) {
        echo "oh no";
    }
}',
            '<?php
function foo () {
    try {
        yield -1;
    } catch    (Throwable $e) {
        echo "oh no";
    }
}',
        ];

        yield [
            '<?php
$x = new Foo();
$y = clone $x;
',
            '<?php
$x = new Foo();
$y = clone    $x;
',
        ];

        yield [
            '<?php
if ($isIt) {
    $i++;
} else if ($isItSomethingElse) {
    $j++;
} elseif ($isItSomethingSpecial) {
    $k++;
} else {
    $i--;
}',
            '<?php
if    ($isIt) {
    $i++;
} else    if    ($isItSomethingElse) {
    $j++;
} elseif    ($isItSomethingSpecial) {
    $k++;
} else    {
    $i--;
}',
        ];

        yield [
            '<?php
foreach ($foos as $foo) {
    foreach ($foos as $foo) {
        if ($foo === 0) {
            break 2;
        }
        continue 2;
    }
}',
            '<?php
foreach ($foos as $foo) {
    foreach ($foos as $foo) {
        if ($foo === 0) {
            break    2;
        }
        continue    2;
    }
}',
        ];

        yield [
            '<?php
do {
    $i++;
} while (isEnough());
',
            '<?php
do    {
    $i++;
} while (isEnough());
',
        ];

        yield [
            '<?php
                class Foo {}
                $a = new Foo();
                $b = new
                         Foo();
            ',
            '<?php
                class    Foo {}
                $a = new Foo();
                $b = new
                         Foo();
            ',
            ['allow_linebreak' => true],
        ];
    }

    public function testExampleWithAllTokensHasAllSpacesFixed(): void
    {
        $tokens = Tokens::fromCode(self::EXAMPLE_WITH_ALL_TOKENS);
        self::getFixer()->fix(self::createSplFileInfoDouble(), $tokens);

        self::assertDoesNotMatchRegularExpression('/[^\\n ] {2,}/', $tokens->generateCode());
    }

    /**
     * @dataProvider provideTokenIsUsefulCases
     */
    public function testTokenIsUseful(int $token): void
    {
        $fixer = self::getFixer();

        $expectedTokens = Tokens::fromCode(self::EXAMPLE_WITH_ALL_TOKENS);
        $fixer->fix(self::createSplFileInfoDouble(), $expectedTokens);

        $reflectionObject = new \ReflectionObject($fixer);
        $property = $reflectionObject->getProperty('tokens');
        $property->setAccessible(true);

        /** @var list<int> $tokens */
        $tokens = $property->getValue($fixer);

        $property->setValue($fixer, \array_diff($tokens, [$token]));

        $tokens = Tokens::fromCode(self::EXAMPLE_WITH_ALL_TOKENS);
        $fixer->fix(self::createSplFileInfoDouble(), $tokens);

        self::assertNotSame(
            $expectedTokens->generateCode(),
            $tokens->generateCode(),
            \sprintf('Removing token %s did not broke fixing', Token::getNameForId($token)),
        );
    }

    /**
     * @return iterable<array{int}>
     */
    public static function provideTokenIsUsefulCases(): iterable
    {
        $fixer = new SingleSpaceAfterStatementFixer();
        $reflectionObject = new \ReflectionObject($fixer);
        $property = $reflectionObject->getProperty('tokens');
        $property->setAccessible(true);

        /** @var list<int> $tokens */
        $tokens = $property->getValue($fixer);

        foreach ($tokens as $token) {
            yield [$token];
        }
    }

    private static function createSplFileInfoDouble(): \SplFileInfo
    {
        return new class ('') extends \SplFileInfo {};
    }
}
