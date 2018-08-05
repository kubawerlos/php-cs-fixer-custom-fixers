<?php

declare(strict_types = 1);

namespace PhpCsFixerCustomFixers\Fixer;

use PhpCsFixer\Fixer\DefinedFixerInterface;

abstract class AbstractFixer implements DefinedFixerInterface
{
    final public static function name() : string
    {
        return 'PhpCsFixerCustomFixers/' . \preg_replace_callback(
            '/(^|[a-z0-9])([A-Z])/',
            static function (array $matches) : string {
                return \strtolower($matches[1] !== '' ? $matches[1] . '_' . $matches[2] : $matches[2]);
            },
            \preg_replace('/^.*\\\\([a-zA-Z0-1]+)Fixer$/', '$1', static::class)
        );
    }

    final public function getName() : string
    {
        return self::name();
    }

    final public function supports(\SplFileInfo $file) : bool
    {
        return true;
    }
}
