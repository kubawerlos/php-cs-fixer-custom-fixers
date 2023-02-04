<?php declare(strict_types=1);

/*
 * This file is part of PHP CS Fixer: custom fixers.
 *
 * (c) 2018 Kuba Werłos
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace PhpCsFixerCustomFixers\Analyzer;

use PhpCsFixer\Preg;
use PhpCsFixer\Tokenizer\Tokens;
use PhpCsFixerCustomFixers\Analyzer\Analysis\DataProviderAnalysis;

/**
 * @internal
 */
final class DataProviderAnalyzer
{
    /**
     * @return array<DataProviderAnalysis>
     */
    public function getDataProviders(Tokens $tokens, int $startIndex, int $endIndex): array
    {
        $methods = $this->getMethods($tokens, $startIndex, $endIndex);

        $dataProviders = [];
        foreach ($methods as $methodIndex) {
            $docCommentIndex = $tokens->getTokenNotOfKindSibling(
                $methodIndex,
                -1,
                [[\T_ABSTRACT], [\T_COMMENT], [\T_FINAL], [\T_FUNCTION], [\T_PRIVATE], [\T_PROTECTED], [\T_PUBLIC], [\T_STATIC], [\T_WHITESPACE]],
            );
            \assert(\is_int($docCommentIndex));

            if (!$tokens[$docCommentIndex]->isGivenKind(\T_DOC_COMMENT)) {
                continue;
            }

            Preg::matchAll('/@dataProvider\s+([a-zA-Z0-9._:-\\\\x7f-\xff]+)/', $tokens[$docCommentIndex]->getContent(), $matches);

            /** @var array<string> $matches */
            $matches = $matches[1];

            foreach ($matches as $dataProviderName) {
                $dataProviders[$dataProviderName][] = $docCommentIndex;
            }
        }

        $dataProviderAnalyses = [];
        foreach ($dataProviders as $dataProviderName => $dataProviderUsages) {
            $lowercaseDataProviderName = \strtolower($dataProviderName);
            if (!\array_key_exists($lowercaseDataProviderName, $methods)) {
                continue;
            }
            $dataProviderAnalyses[$methods[$lowercaseDataProviderName]] = new DataProviderAnalysis(
                $tokens[$methods[$lowercaseDataProviderName]]->getContent(),
                $methods[$lowercaseDataProviderName],
                $dataProviderUsages,
            );
        }

        \ksort($dataProviderAnalyses);

        return \array_values($dataProviderAnalyses);
    }

    /**
     * @return array<string, int>
     */
    private function getMethods(Tokens $tokens, int $startIndex, int $endIndex): array
    {
        $functions = [];
        for ($index = $startIndex; $index < $endIndex; $index++) {
            if (!$tokens[$index]->isGivenKind(\T_FUNCTION)) {
                continue;
            }

            $functionNameIndex = $tokens->getNextNonWhitespace($index);
            \assert(\is_int($functionNameIndex));

            if (!$tokens[$functionNameIndex]->isGivenKind(\T_STRING)) {
                continue;
            }

            $functions[\strtolower($tokens[$functionNameIndex]->getContent())] = $functionNameIndex;
        }

        return $functions;
    }
}
