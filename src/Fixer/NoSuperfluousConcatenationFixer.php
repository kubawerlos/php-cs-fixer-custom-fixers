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
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;

final class NoSuperfluousConcatenationFixer extends AbstractFixer implements ConfigurationDefinitionFixerInterface
{
    /** @var bool */
    private $allowPreventingTrailingSpaces = false;

    public function getDefinition(): FixerDefinitionInterface
    {
        return new FixerDefinition(
            'There should not be superfluous inline concatenation of strings.',
            [new CodeSample("<?php\necho 'foo' . 'bar';\n")]
        );
    }

    public function getConfigurationDefinition(): FixerConfigurationResolver
    {
        return new FixerConfigurationResolver([
            (new FixerOptionBuilder('allow_preventing_trailing_spaces', 'whether to keep concatenation if it prevents having trailing spaces in string'))
                ->setAllowedTypes(['bool'])
                ->setDefault($this->allowPreventingTrailingSpaces)
                ->getOption(),
        ]);
    }

    public function configure(?array $configuration = null): void
    {
        $this->allowPreventingTrailingSpaces = isset($configuration['allow_preventing_trailing_spaces']) && $configuration['allow_preventing_trailing_spaces'] === true;
    }

    public function getPriority(): int
    {
        // must be run after SingleLineThrowFixer
        return 0;
    }

    public function isCandidate(Tokens $tokens): bool
    {
        return $tokens->isAllTokenKindsFound(['.', T_CONSTANT_ENCAPSED_STRING]);
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

            if (!$token->equals('.')) {
                continue;
            }

            /** @var int $firstIndex */
            $firstIndex = $tokens->getPrevMeaningfulToken($index);

            /** @var Token $firstToken */
            $firstToken = $tokens[$firstIndex];

            if (!$firstToken->isGivenKind(T_CONSTANT_ENCAPSED_STRING)) {
                continue;
            }
            if (!$this->areOnlyHorizontalWhitespacesBetween($tokens, $firstIndex, $index)) {
                continue;
            }

            /** @var int $secondIndex */
            $secondIndex = $tokens->getNextMeaningfulToken($index);

            /** @var Token $secondToken */
            $secondToken = $tokens[$secondIndex];

            if (!$secondToken->isGivenKind(T_CONSTANT_ENCAPSED_STRING)) {
                continue;
            }
            if (!$this->areOnlyHorizontalWhitespacesBetween($tokens, $index, $secondIndex)) {
                continue;
            }

            $this->fixConcat($tokens, $firstIndex, $secondIndex);
        }
    }

    private function areOnlyHorizontalWhitespacesBetween(Tokens $tokens, int $indexStart, int $indexEnd): bool
    {
        for ($index = $indexStart + 1; $index < $indexEnd; $index++) {
            /** @var Token $token */
            $token = $tokens[$index];

            if (!$token->isGivenKind(T_WHITESPACE)) {
                return false;
            }
            if (Preg::match('/\R/', $token->getContent()) === 1) {
                return false;
            }
        }

        return true;
    }

    private function fixConcat(Tokens $tokens, int $firstIndex, int $secondIndex): void
    {
        /** @var Token $firstToken */
        $firstToken = $tokens[$firstIndex];

        /** @var Token $secondToken */
        $secondToken = $tokens[$secondIndex];

        $prefix = '';
        $firstContent = $firstToken->getContent();
        $secondContent = $secondToken->getContent();

        if ($this->allowPreventingTrailingSpaces
            && Preg::match('/\h(\\\'|")$/', $firstContent) === 1
            && Preg::match('/^(\\\'|")\R/', $secondContent) === 1
        ) {
            return;
        }

        if (\strtolower($firstContent[0]) === 'b') {
            $prefix = $firstContent[0];
            $firstContent = \ltrim($firstContent, 'bB');
        }

        $secondContent = \ltrim($secondContent, 'bB');

        $border = $firstContent[0] === '"' || $secondContent[0] === '"' ? '"' : "'";

        $tokens->overrideRange(
            $firstIndex,
            $secondIndex,
            [
                new Token(
                    [T_CONSTANT_ENCAPSED_STRING,
                        $prefix . $border . $this->getContentForBorder($firstContent, $border) . $this->getContentForBorder($secondContent, $border) . $border,
                    ]
                ),
            ]
        );
    }

    private function getContentForBorder(string $content, string $targetBorder): string
    {
        $currentBorder = $content[0];
        $content = \substr($content, 1, -1);
        if ($currentBorder === '"') {
            return $content;
        }
        if ($targetBorder === "'") {
            return $content;
        }

        return \str_replace(
            [
                "\\'",
                '"',
                '$',
            ],
            [
                "'",
                '\\"',
                '\\$',
            ],
            $content
        );
    }
}
