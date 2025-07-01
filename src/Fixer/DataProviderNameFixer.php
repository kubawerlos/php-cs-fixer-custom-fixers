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

use PhpCsFixer\Fixer\ConfigurableFixerInterface;
use PhpCsFixer\Fixer\DeprecatedFixerInterface;
use PhpCsFixer\Fixer\PhpUnit\PhpUnitDataProviderNameFixer;
use PhpCsFixer\FixerConfiguration\FixerConfigurationResolverInterface;
use PhpCsFixer\FixerDefinition\FixerDefinitionInterface;
use PhpCsFixer\Tokenizer\Tokens;

/**
 * @deprecated
 *
 * @implements ConfigurableFixerInterface<array{prefix?: string, suffix?: string}, array{prefix: string, suffix: string}>
 *
 * @no-named-arguments
 */
final class DataProviderNameFixer extends AbstractFixer implements ConfigurableFixerInterface, DeprecatedFixerInterface
{
    private PhpUnitDataProviderNameFixer $phpUnitDataProviderNameFixer;

    public function __construct()
    {
        $this->phpUnitDataProviderNameFixer = new PhpUnitDataProviderNameFixer();
    }

    public function getDefinition(): FixerDefinitionInterface
    {
        return $this->phpUnitDataProviderNameFixer->getDefinition();
    }

    public function getConfigurationDefinition(): FixerConfigurationResolverInterface
    {
        return $this->phpUnitDataProviderNameFixer->getConfigurationDefinition();
    }

    public function configure(array $configuration): void
    {
        $this->phpUnitDataProviderNameFixer->configure($configuration);
    }

    public function getPriority(): int
    {
        return $this->phpUnitDataProviderNameFixer->getPriority();
    }

    public function isCandidate(Tokens $tokens): bool
    {
        return $this->phpUnitDataProviderNameFixer->isCandidate($tokens);
    }

    public function isRisky(): bool
    {
        return $this->phpUnitDataProviderNameFixer->isRisky();
    }

    public function fix(\SplFileInfo $file, Tokens $tokens): void
    {
        $this->phpUnitDataProviderNameFixer->fix($file, $tokens);
    }

    public function getSuccessorsNames(): array
    {
        return [$this->phpUnitDataProviderNameFixer->getName()];
    }
}
