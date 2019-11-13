<?php

declare(strict_types = 1);

namespace PhpCsFixerCustomFixers\Fixer;

use PhpCsFixer\Fixer\DefinedFixerInterface;
use PhpCsFixerCustomFixers\Adapter\PregAdapter;

abstract class AbstractFixer implements DefinedFixerInterface
{
    final public static function name(): string
    {
        /** @var string $name */
        $name = PregAdapter::replace('/^.*\\\\([a-zA-Z0-1]+)Fixer$/', '$1', static::class);

        /** @var string $name */
        $name = PregAdapter::replace('/[A-Z]/', '_$0', \lcfirst($name));

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
}
