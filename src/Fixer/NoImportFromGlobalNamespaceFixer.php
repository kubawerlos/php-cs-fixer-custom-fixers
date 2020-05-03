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

use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\FixerDefinition\FixerDefinitionInterface;
use PhpCsFixer\Preg;
use PhpCsFixer\Tokenizer\Analyzer\NamespacesAnalyzer;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;
use PhpCsFixerCustomFixers\TokenRemover;

final class NoImportFromGlobalNamespaceFixer extends AbstractFixer
{
    public function getDefinition(): FixerDefinitionInterface
    {
        return new FixerDefinition(
            'There must be no import from global namespace.',
            [new CodeSample('<?php
namespace Foo;
use DateTime;
class Bar {
    public function __construct(DateTime $dateTime) {}
}
')]
        );
    }

    public function getPriority(): int
    {
        // must be run before PhpdocAlignFixer
        return 0;
    }

    public function isCandidate(Tokens $tokens): bool
    {
        return $tokens->isTokenKindFound(T_USE);
    }

    public function isRisky(): bool
    {
        return false;
    }

    public function fix(\SplFileInfo $file, Tokens $tokens): void
    {
        foreach (\array_reverse((new NamespacesAnalyzer())->getDeclarations($tokens)) as $namespace) {
            $this->fixImports($tokens, $namespace->getScopeStartIndex(), $namespace->getScopeEndIndex(), $namespace->getFullName() === '');
        }
    }

    private function fixImports(Tokens $tokens, int $startIndex, int $endIndex, bool $isInGlobalNamespace): void
    {
        $imports = [];

        for ($index = $startIndex; $index < $endIndex; $index++) {
            /** @var Token $token */
            $token = $tokens[$index];

            if ($token->isGivenKind(T_USE)) {
                $imports = $this->removeImportFromGlobalNamespace($tokens, $imports, $index);
                continue;
            }

            if ($isInGlobalNamespace) {
                continue;
            }

            if ($token->isGivenKind(T_DOC_COMMENT)) {
                $this->updateComment($tokens, $imports, $index);
                continue;
            }

            if (!$token->isGivenKind(T_STRING)) {
                continue;
            }

            $this->updateUsage($tokens, $imports, $index);
        }
    }

    /**
     * @param string[] $imports
     *
     * @return string[]
     */
    private function removeImportFromGlobalNamespace(Tokens $tokens, array $imports, int $index): array
    {
        /** @var int $classNameIndex */
        $classNameIndex = $tokens->getNextMeaningfulToken($index);

        /** @var Token $classNameToken */
        $classNameToken = $tokens[$classNameIndex];

        if ($classNameToken->isGivenKind(T_NS_SEPARATOR)) {
            /** @var int $classNameIndex */
            $classNameIndex = $tokens->getNextMeaningfulToken($classNameIndex);

            /** @var Token $classNameToken */
            $classNameToken = $tokens[$classNameIndex];
        }

        /** @var int $semicolonIndex */
        $semicolonIndex = $tokens->getNextMeaningfulToken($classNameIndex);

        /** @var Token $semicolonToken */
        $semicolonToken = $tokens[$semicolonIndex];

        if ($semicolonToken->equals(';')) {
            $imports[] = $classNameToken->getContent();
            $tokens->clearRange($index, $semicolonIndex);
            TokenRemover::removeWithLinesIfPossible($tokens, $semicolonIndex);
        }

        return $imports;
    }

    /**
     * @param string[] $imports
     */
    private function updateComment(Tokens $tokens, array $imports, int $index): void
    {
        /** @var Token $token */
        $token = $tokens[$index];

        $content = $token->getContent();

        foreach ($imports as $import) {
            $content = Preg::replace(\sprintf('/\b(?<!\\\\)%s\b/', $import), '\\' . $import, $content);
        }

        if ($content !== $token->getContent()) {
            $tokens[$index] = new Token([T_DOC_COMMENT, $content]);
        }
    }

    /**
     * @param string[] $imports
     */
    private function updateUsage(Tokens $tokens, array $imports, int $index): void
    {
        /** @var Token $token */
        $token = $tokens[$index];

        if (!\in_array($token->getContent(), $imports, true)) {
            return;
        }

        /** @var int $prevIndex */
        $prevIndex = $tokens->getPrevMeaningfulToken($index);

        /** @var Token $prevToken */
        $prevToken = $tokens[$prevIndex];

        if ($prevToken->isGivenKind([T_CONST, T_DOUBLE_COLON, T_NS_SEPARATOR, T_OBJECT_OPERATOR])) {
            return;
        }

        $tokens->insertAt($index, new Token([T_NS_SEPARATOR, '\\']));
    }
}
