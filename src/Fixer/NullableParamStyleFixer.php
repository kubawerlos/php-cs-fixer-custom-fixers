<?php

declare(strict_types = 1);

namespace PhpCsFixerCustomFixers\Fixer;

use PhpCsFixer\Fixer\ConfigurationDefinitionFixerInterface;
use PhpCsFixer\Fixer\DeprecatedFixerInterface;
use PhpCsFixer\Fixer\FunctionNotation\NullableTypeDeclarationForDefaultNullValueFixer;
use PhpCsFixer\FixerConfiguration\FixerConfigurationResolver;
use PhpCsFixer\FixerConfiguration\FixerOptionBuilder;
use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\FixerDefinition\FixerDefinitionInterface;
use PhpCsFixer\Tokenizer\Tokens;

/**
 * @deprecated use "nullable_type_declaration_for_default_null_value" instead
 */
final class NullableParamStyleFixer extends AbstractFixer implements ConfigurationDefinitionFixerInterface, DeprecatedFixerInterface
{
    /** @var NullableTypeDeclarationForDefaultNullValueFixer */
    private $fixer;

    public function __construct()
    {
        $this->fixer = new NullableTypeDeclarationForDefaultNullValueFixer();
    }

    public function getDefinition(): FixerDefinitionInterface
    {
        return new FixerDefinition(
            'Nullable parameters must be written in the consistent style.',
            [new CodeSample('<?php
function foo(int $x = null) {
}
')]
        );
    }

    public function getConfigurationDefinition(): FixerConfigurationResolver
    {
        return new FixerConfigurationResolver([
            (new FixerOptionBuilder('style', 'whether nullable parameter type should be prefixed or not with question mark'))
                ->setDefault('with_question_mark')
                ->setAllowedValues(['with_question_mark', 'without_question_mark'])
                ->getOption(),
        ]);
    }

    public function configure(?array $configuration = null): void
    {
        if (isset($configuration['style']) && $configuration['style'] === 'without_question_mark') {
            $this->fixer->configure(['use_nullable_type_declaration' => false]);
        }
    }

    public function getPriority(): int
    {
        // must be run before NoUnreachableDefaultArgumentValueFixer
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
