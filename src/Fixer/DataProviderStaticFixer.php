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
use PhpCsFixer\Fixer\PhpUnit\PhpUnitDataProviderStaticFixer;
use PhpCsFixer\FixerConfiguration\FixerConfigurationResolverInterface;
use PhpCsFixer\FixerDefinition\FixerDefinitionInterface;
use PhpCsFixer\Tokenizer\Tokens;

/**
 * @deprecated
 *
 * @implements ConfigurableFixerInterface<array{force?: bool}, array{force: bool}>
 *
 * @no-named-arguments
 */
final class DataProviderStaticFixer extends AbstractFixer implements ConfigurableFixerInterface, DeprecatedFixerInterface
{
    private PhpUnitDataProviderStaticFixer $phpUnitDataProviderStaticFixer;

    public function __construct()
    {
        $this->phpUnitDataProviderStaticFixer = new PhpUnitDataProviderStaticFixer();
    }

    public function getDefinition(): FixerDefinitionInterface
    {
        return $this->phpUnitDataProviderStaticFixer->getDefinition();
    }

    public function getConfigurationDefinition(): FixerConfigurationResolverInterface
    {
        return $this->phpUnitDataProviderStaticFixer->getConfigurationDefinition();
    }

    public function configure(array $configuration): void
    {
        $this->phpUnitDataProviderStaticFixer->configure($configuration);
    }

    public function getPriority(): int
    {
        return $this->phpUnitDataProviderStaticFixer->getPriority();
    }

    public function isCandidate(Tokens $tokens): bool
    {
        return $this->phpUnitDataProviderStaticFixer->isCandidate($tokens);
    }

    public function isRisky(): bool
    {
        return $this->phpUnitDataProviderStaticFixer->isRisky();
    }

    public function fix(\SplFileInfo $file, Tokens $tokens): void
    {
        $this->phpUnitDataProviderStaticFixer->fix($file, $tokens);
    }

    public function getSuccessorsNames(): array
    {
        return [$this->phpUnitDataProviderStaticFixer->getName()];
    }
}
