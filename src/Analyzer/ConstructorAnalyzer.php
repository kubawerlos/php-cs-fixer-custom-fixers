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

use PhpCsFixer\Tokenizer\Tokens;
use PhpCsFixer\Tokenizer\TokensAnalyzer;
use PhpCsFixerCustomFixers\Analyzer\Analysis\ConstructorAnalysis;

/**
 * @internal
 */
final class ConstructorAnalyzer
{
    public function findNonAbstractConstructor(Tokens $tokens, int $classIndex): ?ConstructorAnalysis
    {
        if (!$tokens[$classIndex]->isGivenKind(\T_CLASS)) {
            throw new \InvalidArgumentException(\sprintf('Index %d is not a class.', $classIndex));
        }

        $tokensAnalyzer = new TokensAnalyzer($tokens);

        /** @var int $index */
        foreach ($tokensAnalyzer->getClassyElements() as $index => $element) {
            if ($element['classIndex'] !== $classIndex) {
                continue;
            }
            if ($element['type'] !== 'method') {
                continue;
            }

            /** @var int $functionNameIndex */
            $functionNameIndex = $tokens->getNextMeaningfulToken($index);

            if ($tokens[$functionNameIndex]->equals([\T_STRING, '__construct'], false)) {
                $constructorData = $tokensAnalyzer->getMethodAttributes($index);
                if ($constructorData['abstract']) {
                    return null;
                }

                return new ConstructorAnalysis($tokens, $index);
            }
        }

        return null;
    }
}
