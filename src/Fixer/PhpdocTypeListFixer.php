<?php declare(strict_types=1);

/*
 * This file is part of PHP CS Fixer: custom fixers.
 *
 * (c) 2018 Kuba Werłos
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace PhpCsFixerCustomFixers\Fixer;

use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\FixerDefinition\FixerDefinitionInterface;
use PhpCsFixer\Preg;

final class PhpdocTypeListFixer extends AbstractTypesFixer
{
    public function getDefinition(): FixerDefinitionInterface
    {
        return new FixerDefinition(
            'PHPDoc type `list` must be used instead of `array` without a key type.',
            [new CodeSample('<?php
/**
 * @param array<string>
 */
function foo($x) {}
')],
            '',
        );
    }

    /**
     * Must run before PhpdocAlignFixer, PhpdocTypesOrderFixer.
     * Must run after CommentToPhpdocFixer, PhpdocArrayStyleFixer.
     */
    public function getPriority(): int
    {
        return 1;
    }

    protected function fixType(string $type): string
    {
        return Preg::replace('/array(?=<[^,]+(>|<|{|\\())/', 'list', $type);
    }
}
