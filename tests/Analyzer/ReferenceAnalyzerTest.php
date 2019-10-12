<?php

declare(strict_types = 1);

namespace Tests\Analyzer;

use PhpCsFixer\Tokenizer\Tokens;
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

        static::assertFalse($analyzer->isReference(Tokens::fromCode('<?php $a;$b;$c;'), 3));
    }

    /**
     * @dataProvider provideReferenceCases
     */
    public function testReference(string $code): void
    {
        $analyzer = new ReferenceAnalyzer();

        $tokens = Tokens::fromCode($code);
        foreach ($tokens as $index => $token) {
            if ($token->getContent() === '&') {
                static::assertTrue($analyzer->isReference($tokens, $index));
            }
        }
    }

    public function provideReferenceCases(): iterable
    {
        yield ['<?php $a =& $b;'];

        yield ['<?php function foo(&$x) {};'];

        yield ['<?php
class foo {
    public $value = 42;

    public function &getValue() {
        return $this->value;
    }
}'];

        yield ['<?php function foo(int &$x) {};'];

        yield ['<?php function foo(?int &$x) {};'];
    }

    /**
     * @dataProvider provideNonReferenceCases
     */
    public function testNonReference(string $code): void
    {
        $analyzer = new ReferenceAnalyzer();

        $tokens = Tokens::fromCode($code);
        foreach ($tokens as $index => $token) {
            if ($token->getContent() === '&') {
                static::assertFalse($analyzer->isReference($tokens, $index));
            }
        }
    }

    public function provideNonReferenceCases(): iterable
    {
        yield ['<?php $a & $b;'];

        yield ['<?php Foo::bar & $x;'];

        yield ['<?php foo(1, 2) & $x;'];
    }
}
