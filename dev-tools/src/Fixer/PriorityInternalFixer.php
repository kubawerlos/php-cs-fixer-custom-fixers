<?php

declare(strict_types = 1);

namespace PhpCsFixerCustomFixersDev\Fixer;

use PhpCsFixer\Fixer\FixerInterface;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;
use PhpCsFixer\Utils;
use PhpCsFixerCustomFixers\TokenRemover;
use PhpCsFixerCustomFixersDev\Priority\PriorityCollection;

/**
 * @internal
 */
final class PriorityInternalFixer implements FixerInterface
{
    public function getName(): string
    {
        return 'Internal/' . \strtolower(\str_replace('\\', '_', Utils::camelCaseToUnderscore(__CLASS__)));
    }

    public function getPriority(): int
    {
        return 1000;
    }

    public function supports(\SplFileInfo $file): bool
    {
        return true;
    }

    public function isCandidate(Tokens $tokens): bool
    {
        return $tokens->findSequence([[T_EXTENDS], [T_STRING, 'AbstractFixer']]) !== null;
    }

    public function isRisky(): bool
    {
        return false;
    }

    public function fix(\SplFileInfo $file, Tokens $tokens): void
    {
        $indices = $tokens->findSequence([[T_EXTENDS], [T_STRING, 'AbstractFixer']]);

        $classNameIndex = $tokens->getPrevMeaningfulToken(\key($indices));
        $className = $tokens[$classNameIndex]->getContent();

        /** @var int $startIndex */
        $startIndex = $tokens->getNextTokenOfKind(\key($indices), ['{']);
        $endIndex = $tokens->findBlockEnd(Tokens::BLOCK_TYPE_CURLY_BRACE, $startIndex);

        $indices = $tokens->findSequence([[T_PUBLIC], [T_FUNCTION], [T_STRING, 'getPriority']], $startIndex, $endIndex);

        /** @var int $startIndex */
        $startIndex = $tokens->getNextTokenOfKind(\key($indices), ['{']);
        $endIndex = $tokens->findBlockEnd(Tokens::BLOCK_TYPE_CURLY_BRACE, $startIndex);

        $commentsToInsert = $this->getCommentsToInsert($className);

        for ($index = $startIndex; $index < $endIndex; $index++) {
            if (!$tokens[$index]->isGivenKind(T_COMMENT)) {
                continue;
            }

            if ($commentsToInsert === []) {
                TokenRemover::removeWithLinesIfPossible($tokens, $index);
                continue;
            }

            $tokens[$index] = \array_shift($commentsToInsert);
        }

        $returnIndex = $tokens->getNextTokenOfKind($startIndex, [[T_RETURN]]);

        foreach (\array_reverse($commentsToInsert) as $comment) {
            $tokens->insertAt(
                $returnIndex - 1,
                [
                    new Token([T_WHITESPACE, "\n    "]),
                    $comment,
                ]
            );
        }
    }

    /**
     * @return Token[]
     */
    private function getCommentsToInsert(string $className): array
    {
        $comments = [];
        $priorityCollection = PriorityCollection::create();

        if (!$priorityCollection->hasPriorityFixer($className)) {
            return [];
        }

        $fixersToRunBefore = $priorityCollection->getPriorityFixer($className)->getFixersToRunBefore();
        if ([] !== $fixersToRunBefore) {
            $comments[] = new Token([
                T_COMMENT,
                \sprintf('// must be run after %s', \str_replace('`', '', Utils::naturalLanguageJoinWithBackticks($fixersToRunBefore))),
            ]);
        }

        $fixersToRunAfter = $priorityCollection->getPriorityFixer($className)->getFixersToRunAfter();
        if ([] !== $fixersToRunAfter) {
            \sort($fixersToRunAfter);
            $comments[] = new Token([
                T_COMMENT,
                \sprintf('// must be run before %s', \str_replace('`', '', Utils::naturalLanguageJoinWithBackticks($fixersToRunAfter))),
            ]);
        }

        return $comments;
    }
}
