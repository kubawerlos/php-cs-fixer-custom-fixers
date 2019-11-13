<?php

declare(strict_types = 1);

namespace Tests\Adapter;

use PhpCsFixer\Tokenizer\Analyzer\Analysis\NamespaceAnalysis;
use PhpCsFixer\Tokenizer\Tokens;
use PhpCsFixerCustomFixers\Adapter\NamespacesAnalyzerAdapter;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @covers \PhpCsFixerCustomFixers\Adapter\NamespacesAnalyzerAdapter
 */
final class NamespacesAnalyzerAdapterTest extends TestCase
{
    public function testGetDeclarations(): void
    {
        $tokens = Tokens::fromCode('<?php
            namespace Foo { class Foo1 {} }
            namespace Bar { class Bar1 {} }
        ');
        static::assertSame(
            \serialize([
                new NamespaceAnalysis('Foo', 'Foo', 2, 6, 2, 15),
                new NamespaceAnalysis('Bar', 'Bar', 17, 21, 17, 30),
            ]),
            \serialize(NamespacesAnalyzerAdapter::getDeclarations($tokens))
        );
    }
}
