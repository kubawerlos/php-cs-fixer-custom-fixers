<?php

/*
 * This file is part of PHP CS Fixer: custom fixers.
 *
 * (c) 2018 Kuba Werłos
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

namespace PhpCsFixerCustomFixers\Analyzer;

use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;
use PhpCsFixer\Tokenizer\TokensAnalyzer;
use PhpCsFixerCustomFixers\Analyzer\Analysis\ConstructorAnalysis;

/**
 * @internal
 */
final class ConstructorAnalyzer
{
    public function findConstructor(Tokens $tokens, int $classIndex, bool $allowAbstract): ?ConstructorAnalysis
    {
        if (!$tokens[$classIndex]->isGivenKind(\T_CLASS)) {
            throw new \InvalidArgumentException(\sprintf('Index %d is not a class.', $classIndex));
        }

        $tokensAnalyzer = new TokensAnalyzer($tokens);

        /**
         * @var int                             $index
         * @var array<string, int|string|Token> $element
         */
        foreach ($tokensAnalyzer->getClassyElements() as $index => $element) {
            if ($element['classIndex'] !== $classIndex) {
                continue;
            }

            if (!$this->isConstructor($tokens, $index, $element)) {
                continue;
            }

            $constructorAttributes = $tokensAnalyzer->getMethodAttributes($index);
            if (!$allowAbstract && $constructorAttributes['abstract']) {
                return null;
            }

            return new ConstructorAnalysis($tokens, $index);
        }

        return null;
    }

    /**
     * @param array<string, int|string|Token> $element
     */
    private function isConstructor(Tokens $tokens, int $index, array $element): bool
    {
        if ($element['type'] !== 'method') {
            return false;
        }

        /** @var int $functionNameIndex */
        $functionNameIndex = $tokens->getNextMeaningfulToken($index);

        return $tokens[$functionNameIndex]->equals([\T_STRING, '__construct'], false);
    }
}
