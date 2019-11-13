<?php

declare(strict_types = 1);

namespace Tests\Adapter;

use PhpCsFixer\Tokenizer\Tokens;
use PhpCsFixerCustomFixers\Adapter\PhpUnitTestCaseIndicatorAdapter;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @covers \PhpCsFixerCustomFixers\Adapter\PhpUnitTestCaseIndicatorAdapter
 */
final class PhpUnitTestCaseIndicatorAdapterTest extends TestCase
{
    public function testFindPhpUnitClasses(): void
    {
        $tokens = Tokens::fromCode('<?php
            class FooTest extends TestCase {}
            class BarTest extends TestCase {}
        ');
        static::assertSame(
            [
                [21, 22],
                [10, 11],
            ],
            \iterator_to_array(PhpUnitTestCaseIndicatorAdapter::findPhpUnitClasses($tokens))
        );
    }
}
