<?php

/*
 * This file is part of PHP CS Fixer: custom fixers.
 *
 * (c) 2018-2020 Kuba Werłos
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

namespace PhpCsFixerCustomFixers\Fixer;

use PhpCsFixer\Fixer\ConfigurationDefinitionFixerInterface;
use PhpCsFixer\Fixer\DeprecatedFixerInterface;
use PhpCsFixer\FixerConfiguration\FixerConfigurationResolverInterface;
use PhpCsFixer\FixerDefinition\FixerDefinitionInterface;
use PhpCsFixer\Tokenizer\Tokens;

/**
 * @deprecated
 */
final class OperatorLinebreakFixer extends AbstractFixer implements ConfigurationDefinitionFixerInterface, DeprecatedFixerInterface
{
    /** @var \PhpCsFixer\Fixer\Operator\OperatorLinebreakFixer */
    private $fixer;

    public function __construct()
    {
        $this->fixer = new \PhpCsFixer\Fixer\Operator\OperatorLinebreakFixer();
    }

    public function getDefinition(): FixerDefinitionInterface
    {
        return $this->fixer->getDefinition();
    }

    public function getConfigurationDefinition(): FixerConfigurationResolverInterface
    {
        return $this->fixer->getConfigurationDefinition();
    }

    /**
     * @param null|array<string, bool|string> $configuration
     */
    public function configure(?array $configuration = null): void
    {
        $this->fixer->configure($configuration);
    }

    public function getPriority(): int
    {
        return $this->fixer->getPriority();
    }

    public function isCandidate(Tokens $tokens): bool
    {
        return $this->fixer->isCandidate($tokens);
    }

    public function isRisky(): bool
    {
        return $this->fixer->isRisky();
    }

    public function fix(\SplFileInfo $file, Tokens $tokens): void
    {
        $this->fixer->fix($file, $tokens);
    }

    /**
     * @return string[]
     */
    public function getSuccessorsNames(): array
    {
        return [$this->fixer->getName()];
    }
}
