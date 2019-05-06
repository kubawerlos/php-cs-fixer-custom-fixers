<?php

declare(strict_types = 1);

namespace PhpCsFixerCustomFixers\Fixer;

use PhpCsFixer\Fixer\DefinedFixerInterface;
use PhpCsFixer\Preg;
use PhpCsFixer\Tokenizer\Tokens;
use PhpCsFixerCustomFixers\TokenAnalyzer;

abstract class AbstractFixer implements DefinedFixerInterface
{
    final public static function name(): string
    {
        $name = Preg::replace('/^.*\\\\([a-zA-Z0-1]+)Fixer$/', '$1', static::class);

        \assert(\is_string($name));

        return 'PhpCsFixerCustomFixers/' . \strtolower($name);
    }

    final public function getName(): string
    {
        return self::name();
    }

    final public function supports(\SplFileInfo $file): bool
    {
        return true;
    }

    protected function analyze(Tokens $tokens): TokenAnalyzer
    {
        return new TokenAnalyzer($tokens);
    }
}
