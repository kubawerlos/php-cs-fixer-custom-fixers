<?php

declare(strict_types = 1);

namespace PhpCsFixerCustomFixers\Fixer;

use PhpCsFixer\Fixer\DefinedFixerInterface;
use PhpCsFixer\Preg;

abstract class AbstractFixer implements DefinedFixerInterface
{
    final public static function name(): string
    {
        /** @var string $subject */
        $subject = Preg::replace('/^.*\\\\([a-zA-Z0-1]+)Fixer$/', '$1', static::class);

        return 'PhpCsFixerCustomFixers/' . \implode(
            '_',
            \array_map(
                'strtolower',
                Preg::split('/(?=[A-Z])/', $subject, 0, PREG_SPLIT_NO_EMPTY)
            )
        );
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
