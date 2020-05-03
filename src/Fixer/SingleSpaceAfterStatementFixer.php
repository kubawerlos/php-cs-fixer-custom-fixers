<?php

declare(strict_types=1);

/*
 * This file is part of PHP CS Fixer: custom fixers.
 *
 * (c) 2018-2020 Kuba Werłos
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace PhpCsFixerCustomFixers\Fixer;

use PhpCsFixer\Fixer\ConfigurationDefinitionFixerInterface;
use PhpCsFixer\FixerConfiguration\FixerConfigurationResolver;
use PhpCsFixer\FixerConfiguration\FixerOptionBuilder;
use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\FixerDefinition\FixerDefinitionInterface;
use PhpCsFixer\Preg;
use PhpCsFixer\Tokenizer\CT;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;

final class SingleSpaceAfterStatementFixer extends AbstractFixer implements ConfigurationDefinitionFixerInterface
{
    /** @var int[] */
    private $tokens = [
        T_ABSTRACT,
        T_AS,
        T_BREAK,
        T_CASE,
        T_CATCH,
        T_CLASS,
        T_CLONE,
        T_CONST,
        T_CONTINUE,
        T_DO,
        T_ECHO,
        T_ELSE,
        T_ELSEIF,
        T_EXTENDS,
        T_FINAL,
        T_FINALLY,
        T_FOR,
        T_FOREACH,
        T_FUNCTION,
        T_GLOBAL,
        T_GOTO,
        T_IF,
        T_IMPLEMENTS,
        T_INCLUDE,
        T_INCLUDE_ONCE,
        T_INSTANCEOF,
        T_INSTEADOF,
        T_INTERFACE,
        T_NAMESPACE,
        T_NEW,
        T_PRINT,
        T_PRIVATE,
        T_PROTECTED,
        T_PUBLIC,
        T_REQUIRE,
        T_REQUIRE_ONCE,
        T_RETURN,
        T_SWITCH,
        T_THROW,
        T_TRAIT,
        T_TRY,
        T_USE,
        T_VAR,
        T_WHILE,
        T_YIELD,
        T_YIELD_FROM,
        CT::T_CONST_IMPORT,
        CT::T_FUNCTION_IMPORT,
        CT::T_USE_TRAIT,
        CT::T_USE_LAMBDA,
    ];

    /** @var bool */
    private $allowLinebreak = false;

    public function getDefinition(): FixerDefinitionInterface
    {
        return new FixerDefinition(
            'Single space must follow - not followed by semicolon - statement.',
            [new CodeSample("<?php\n\$foo = new    Foo();\necho\$foo->__toString();\n")]
        );
    }

    public function getConfigurationDefinition(): FixerConfigurationResolver
    {
        return new FixerConfigurationResolver([
            (new FixerOptionBuilder('allow_linebreak', 'whether to allow statement followed by linebreak'))
                ->setAllowedTypes(['bool'])
                ->setDefault($this->allowLinebreak)
                ->getOption(),
        ]);
    }

    public function configure(?array $configuration = null): void
    {
        $this->allowLinebreak = isset($configuration['allow_linebreak']) && $configuration['allow_linebreak'] === true;
    }

    public function getPriority(): int
    {
        return 0;
    }

    public function isCandidate(Tokens $tokens): bool
    {
        return $tokens->isAnyTokenKindsFound($this->tokens);
    }

    public function isRisky(): bool
    {
        return false;
    }

    public function fix(\SplFileInfo $file, Tokens $tokens): void
    {
        for ($index = $tokens->count() - 1; $index > 0; $index--) {
            /** @var Token $token */
            $token = $tokens[$index];

            if (!$token->isGivenKind($this->tokens)) {
                continue;
            }

            if (!$this->canAddSpaceAfter($tokens, $index)) {
                continue;
            }

            /** @var Token $nextToken */
            $nextToken = $tokens[$index + 1];

            if ($nextToken->isGivenKind(T_WHITESPACE)) {
                $tokens[$index + 1] = new Token([T_WHITESPACE, ' ']);
                continue;
            }

            $tokens->insertAt($index + 1, new Token([T_WHITESPACE, ' ']));
        }
    }

    private function canAddSpaceAfter(Tokens $tokens, int $index): bool
    {
        /** @var Token $token */
        $token = $tokens[$index];

        /** @var Token $nextToken */
        $nextToken = $tokens[$index + 1];

        if ($nextToken->isGivenKind(T_WHITESPACE)) {
            return !$this->allowLinebreak || Preg::match('/\R/', $nextToken->getContent()) !== 1;
        }

        if ($token->isGivenKind(T_CLASS) && $nextToken->equals('(')) {
            return false;
        }

        return !\in_array($nextToken->getContent(), [';', ':'], true);
    }
}
