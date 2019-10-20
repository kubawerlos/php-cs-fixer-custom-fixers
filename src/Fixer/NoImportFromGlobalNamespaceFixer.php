<?php

declare(strict_types = 1);

namespace PhpCsFixerCustomFixers\Fixer;

use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\FixerDefinition\FixerDefinitionInterface;
use PhpCsFixer\Preg;
use PhpCsFixer\Tokenizer\Analyzer\NamespacesAnalyzer;
use PhpCsFixer\Tokenizer\CT;
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

    public function isCandidate(Tokens $tokens): bool
    {
        return $tokens->isTokenKindFound(T_USE);
    }

    public function isRisky(): bool
    {
        return false;
    }

    public function getPriority(): int
    {
        return 0;
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
            $token = $tokens[$index];

            if ($token->isGivenKind(T_USE)) {
                /** @var int $classNameIndex */
                $classNameIndex = $tokens->getNextMeaningfulToken($index);

                if ($tokens[$classNameIndex]->isGivenKind(T_NS_SEPARATOR)) {
                    /** @var int $classNameIndex */
                    $classNameIndex = $tokens->getNextMeaningfulToken($classNameIndex);
                }
                /** @var int $semicolonIndex */
                $semicolonIndex = $tokens->getNextMeaningfulToken($classNameIndex);
                if ($tokens[$semicolonIndex]->getContent() === ';') {
                    $imports[] = $tokens[$classNameIndex]->getContent();
                    $tokens->clearRange($index, $semicolonIndex);
                    TokenRemover::removeWithLinesIfPossible($tokens, $semicolonIndex);
                    $index = $semicolonIndex + 1;
                }
                continue;
            }

            if ($token->isGivenKind(T_DOC_COMMENT)) {
                $content = $token->getContent();
                foreach ($imports as $import) {
                    $content = Preg::replace(\sprintf('/\b(?<!\\\\)%s\b/', $import), '\\' . $import, $content);
                }
                if ($content !== $token->getContent()) {
                    $tokens[$index] = new Token([T_DOC_COMMENT, $content]);
                }
                continue;
            }

            if (!$token->isGivenKind(T_STRING)) {
                continue;
            }

            if (!\in_array($token->getContent(), $imports, true)) {
                continue;
            }

            $prevIndex = $tokens->getPrevMeaningfulToken($index);
            if ($tokens[$prevIndex]->isGivenKind([T_CONST, T_DOUBLE_COLON, T_NS_SEPARATOR, T_OBJECT_OPERATOR, CT::T_USE_TRAIT])) {
                continue;
            }

            if (!$isInGlobalNamespace) {
                $tokens->insertAt($index, new Token([T_NS_SEPARATOR, '\\']));
                $index++;
            }
        }
    }
}
