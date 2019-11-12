<?php

declare(strict_types = 1);

namespace Tests\Analyzer;

use PhpCsFixer\Tokenizer\Tokens;
use PhpCsFixerCustomFixers\Adapter\TokensAdapter;
use PhpCsFixerCustomFixers\Analyzer\ReferenceAnalyzer;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @covers \PhpCsFixerCustomFixers\Analyzer\ReferenceAnalyzer
 */
final class ReferenceAnalyzerTest extends TestCase
{
    public function testNonAmpersand(): void
    {
        $analyzer = new ReferenceAnalyzer();

        static::assertFalse($analyzer->isReference(new TokensAdapter(Tokens::fromCode('<?php $foo;$bar;$baz;')), 3));
    }

    public function testReferenceAndNonReferenceTogether(): void
    {
        $analyzer = new ReferenceAnalyzer();

        $tokens = new TokensAdapter(Tokens::fromCode('<?php function foo(&$bar = BAZ & QUX) {};'));

        static::assertTrue($analyzer->isReference($tokens, 5));
        static::assertFalse($analyzer->isReference($tokens, 12));
    }

    /**
     * @dataProvider provideReferenceCases
     */
    public function testReference(string $code): void
    {
        $this->doTestCode(true, $code);
    }

    /**
     * @dataProvider provideNonReferenceCases
     */
    public function testNonReference(string $code): void
    {
        $this->doTestCode(false, $code);
    }

    public function provideReferenceCases(): iterable
    {
        yield ['<?php $foo =& $bar;'];
        yield ['<?php $foo =& find_var($bar);'];
        yield ['<?php $foo["bar"] =& $baz;'];
        yield ['<?php function foo(&$bar) {};'];
        yield ['<?php function foo($bar, &$baz) {};'];
        yield ['<?php function &() {};'];
        yield ['<?php
class Foo {
    public $value = 42;
    public function &getValue() {
        return $this->value;
    }
}'];
        yield ['<?php function foo(\Bar\Baz &$qux) {};'];
        yield ['<?php function foo(array &$bar) {};'];
        yield ['<?php function foo(callable &$bar) {};'];
        yield ['<?php function foo(int &$bar) {};'];
        yield ['<?php function foo(string &$bar) {};'];
        yield ['<?php function foo(?int &$bar) {};'];
        yield ['<?php foreach($foos as &$foo) {}'];
        yield ['<?php foreach($foos as $key => &$foo) {}'];
    }

    public function provideNonReferenceCases(): iterable
    {
        yield ['<?php $foo & $bar;'];
        yield ['<?php FOO & $bar;'];
        yield ['<?php Foo::BAR & $baz;'];
        yield ['<?php foo(1, 2) & $bar;'];
        yield ['<?php foo($bar & $baz);'];
        yield ['<?php foo($bar, $baz & $qux);'];
        yield ['<?php foo($bar->baz & $qux);'];
        yield ['<?php foo(Bar::BAZ & $qux);'];
        yield ['<?php foo(Bar\Baz::qux & $quux);'];
        yield ['<?php foo(\Bar\Baz::qux & $quux);'];
        yield ['<?php foo($bar["mode"] & $baz);'];
        yield ['<?php foo($bar{"mode"} & $baz);'];
        yield ['<?php foo(0b11111111 & $bar);'];
        yield ['<?php foo(127 & $bar);'];
        yield ['<?php foo("bar" & $baz);'];
        yield ['<?php foo($bar = BAZ & $qux);'];
        yield ['<?php function foo($bar = BAZ & QUX) {};'];
        yield ['<?php function foo($bar = BAZ::QUX & QUUX) {};'];
        yield ['<?php function foo(array $bar = BAZ & QUX) {};'];
        yield ['<?php function foo(callable $bar = BAZ & QUX) {};'];
        yield ['<?php function foo(?int $bar = BAZ & QUX) {};'];
        yield ['<?php foreach($foos as $foo) { $foo & $bar; }'];
        yield ['<?php if ($foo instanceof Bar & 0b01010101) {}'];
    }

    private function doTestCode(bool $expected, string $code): void
    {
        $analyzer = new ReferenceAnalyzer();

        $tokens = new TokensAdapter(Tokens::fromCode($code));

        foreach ($tokens->toArray() as $index => $token) {
            if ($token->getContent() === '&') {
                static::assertSame($expected, $analyzer->isReference($tokens, $index));
            }
        }
    }
}
