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

use PhpCsFixer\Fixer\DeprecatedFixerInterface;
use PhpCsFixer\Fixer\Phpdoc\PhpdocArrayStyleFixer as SuccessorFixer;
use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\FixerDefinition\FixerDefinitionInterface;
use PhpCsFixer\Tokenizer\Tokens;

/**
 * @deprecated
 */
final class PhpdocTypeListFixer extends AbstractFixer implements DeprecatedFixerInterface
{
    private SuccessorFixer $successorFixer;

    public function __construct()
    {
        $this->successorFixer = new SuccessorFixer();
        $this->successorFixer->configure(['strategy' => 'array_to_list']);
    }

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

    public function isCandidate(Tokens $tokens): bool
    {
        return $this->successorFixer->isCandidate($tokens);
    }

    public function isRisky(): bool
    {
        return $this->successorFixer->isRisky();
    }

    public function fix(\SplFileInfo $file, Tokens $tokens): void
    {
        $this->successorFixer->fix($file, $tokens);
    }

    /**
     * @return list<string>
     */
    public function getSuccessorsNames(): array
    {
        return [$this->successorFixer->getName()];
    }
}
