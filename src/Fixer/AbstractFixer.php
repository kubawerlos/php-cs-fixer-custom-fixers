<?php

declare(strict_types = 1);

namespace PhpCsFixerCustomFixers\Fixer;

use PhpCsFixer\Fixer\DefinedFixerInterface;
use PhpCsFixer\Preg;

abstract class AbstractFixer implements DefinedFixerInterface
{
    final public static function name(): string
    {
        /** @var string $name */
        $name = Preg::replace('/(?<!^)(?=[A-Z])/', '_', \substr(static::class, 29, -5));

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
