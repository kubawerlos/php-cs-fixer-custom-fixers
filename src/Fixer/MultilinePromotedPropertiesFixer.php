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
use PhpCsFixer\Fixer\FunctionNotation\MultilinePromotedPropertiesFixer as PhpCsFixerMultilinePromotedPropertiesFixer;
use PhpCsFixer\Fixer\WhitespacesAwareFixerInterface;
use PhpCsFixer\FixerConfiguration\FixerConfigurationResolverInterface;
use PhpCsFixer\FixerDefinition\FixerDefinitionInterface;
use PhpCsFixer\Tokenizer\Tokens;
use PhpCsFixer\WhitespacesFixerConfig;

/**
 * @deprecated
 *
 * @implements ConfigurableFixerInterface<_InputConfig, _Config>
 *
 * @phpstan-type _InputConfig array{keep_blank_lines?: bool, minimum_number_of_parameters?: int}
 * @phpstan-type _Config array{keep_blank_lines: bool, minimum_number_of_parameters: int}
 *
 * @no-named-arguments
 */
final class MultilinePromotedPropertiesFixer extends AbstractFixer implements ConfigurableFixerInterface, DeprecatedFixerInterface, WhitespacesAwareFixerInterface
{
    private PhpCsFixerMultilinePromotedPropertiesFixer $multilinePromotedPropertiesFixer;

    public function __construct()
    {
        $this->multilinePromotedPropertiesFixer = new PhpCsFixerMultilinePromotedPropertiesFixer();
    }

    public function getDefinition(): FixerDefinitionInterface
    {
        return $this->multilinePromotedPropertiesFixer->getDefinition();
    }

    public function getConfigurationDefinition(): FixerConfigurationResolverInterface
    {
        return $this->multilinePromotedPropertiesFixer->getConfigurationDefinition();
    }

    /**
     * @param array{minimum_number_of_parameters?: int, keep_blank_lines?: bool} $configuration
     */
    public function configure(array $configuration): void
    {
        $this->multilinePromotedPropertiesFixer->configure($configuration);
    }

    public function setWhitespacesConfig(WhitespacesFixerConfig $config): void
    {
        $this->multilinePromotedPropertiesFixer->setWhitespacesConfig($config);
    }

    /**
     * Must run before BracesPositionFixer.
     * Must run after PromotedConstructorPropertyFixer.
     */
    public function getPriority(): int
    {
        return $this->multilinePromotedPropertiesFixer->getPriority();
    }

    public function isCandidate(Tokens $tokens): bool
    {
        return $this->multilinePromotedPropertiesFixer->isCandidate($tokens);
    }

    public function isRisky(): bool
    {
        return $this->multilinePromotedPropertiesFixer->isRisky();
    }

    public function fix(\SplFileInfo $file, Tokens $tokens): void
    {
        $this->multilinePromotedPropertiesFixer->fix($file, $tokens);
    }

    /**
     * @return list<string>
     */
    public function getSuccessorsNames(): array
    {
        return [$this->multilinePromotedPropertiesFixer->getName()];
    }
}
