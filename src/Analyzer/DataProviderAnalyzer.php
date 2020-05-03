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

namespace PhpCsFixerCustomFixers\Analyzer;

use PhpCsFixer\Preg;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;
use PhpCsFixerCustomFixers\Analyzer\Analysis\DataProviderAnalysis;

/**
 * @internal
 */
final class DataProviderAnalyzer
{
    /**
     * @return DataProviderAnalysis[]
     */
    public function getDataProviders(Tokens $tokens, int $startIndex, int $endIndex): array
    {
        $methods = $this->getMethods($tokens, $startIndex, $endIndex);

        $dataProviders = [];
        foreach ($methods as $methodIndex) {
            /** @var int $docCommentIndex */
            $docCommentIndex = $tokens->getTokenNotOfKindSibling(
                $methodIndex,
                -1,
                [[T_ABSTRACT], [T_COMMENT], [T_FINAL], [T_FUNCTION], [T_PRIVATE], [T_PROTECTED], [T_PUBLIC], [T_STATIC], [T_WHITESPACE]]
            );

            /** @var Token $docCommentToken */
            $docCommentToken = $tokens[$docCommentIndex];

            if (!$docCommentToken->isGivenKind(T_DOC_COMMENT)) {
                continue;
            }

            Preg::matchAll('/@dataProvider\s+([a-zA-Z0-9._:-\\\\x7f-\xff]+)/', $docCommentToken->getContent(), $matches);

            /** @var string[] $matches */
            $matches = $matches[1];

            foreach ($matches as $dataProviderName) {
                $dataProviders[$dataProviderName][] = $docCommentIndex;
            }
        }

        $dataProviderAnalyses = [];
        foreach ($dataProviders as $dataProviderName => $dataProviderUsages) {
            if (!isset($methods[$dataProviderName])) {
                continue;
            }
            $dataProviderAnalyses[$methods[$dataProviderName]] = new DataProviderAnalysis(
                $dataProviderName,
                $methods[$dataProviderName],
                $dataProviderUsages
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
            /** @var Token $token */
            $token = $tokens[$index];

            if (!$token->isGivenKind(T_FUNCTION)) {
                continue;
            }

            /** @var int $functionNameIndex */
            $functionNameIndex = $tokens->getNextNonWhitespace($index);

            /** @var Token $functionNameToken */
            $functionNameToken = $tokens[$functionNameIndex];

            if (!$functionNameToken->isGivenKind(T_STRING)) {
                continue;
            }

            $functions[$functionNameToken->getContent()] = $functionNameIndex;
        }

        return $functions;
    }
}
