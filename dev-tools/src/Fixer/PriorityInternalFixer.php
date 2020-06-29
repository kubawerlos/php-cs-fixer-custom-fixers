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

namespace PhpCsFixerCustomFixersDev\Fixer;

use PhpCsFixer\Fixer\FixerInterface;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;
use PhpCsFixer\Utils;
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
        /** @var int[] $indices */
        $indices = $tokens->findSequence([[T_EXTENDS], [T_STRING, 'AbstractFixer']]);

        /** @var int $sequencesStartIndex */
        $sequencesStartIndex = \key($indices);

        /** @var int $classNameIndex */
        $classNameIndex = $tokens->getPrevMeaningfulToken($sequencesStartIndex);

        /** @var Token $classNameToken */
        $classNameToken = $tokens[$classNameIndex];

        $className = $classNameToken->getContent();

        /** @var int $startIndex */
        $startIndex = $tokens->getNextTokenOfKind($sequencesStartIndex, ['{']);

        $endIndex = $tokens->findBlockEnd(Tokens::BLOCK_TYPE_CURLY_BRACE, $startIndex);

        /** @var int[] $indices */
        $indices = $tokens->findSequence([[T_PUBLIC], [T_FUNCTION], [T_STRING, 'getPriority']], $startIndex, $endIndex);

        /** @var int $sequencesStartIndex */
        $sequencesStartIndex = \key($indices);

        /** @var Token $commentToken */
        $commentToken = $tokens[$sequencesStartIndex - 2];

        $commentContent = $this->getCommentContent($className);

        if ($commentToken->isGivenKind(T_DOC_COMMENT)) {
            $tokens[$sequencesStartIndex - 2] = new Token([T_DOC_COMMENT, $commentContent]);
        } else {
            $tokens->insertAt(
                $sequencesStartIndex,
                [
                    new Token([T_DOC_COMMENT, $commentContent]),
                    new Token([T_WHITESPACE, "\n    "]),
                ]
            );
        }

        /** @var int $returnIndex */
        $returnIndex = $tokens->getNextTokenOfKind($sequencesStartIndex, [[T_RETURN]]);

        $priorityStartIndex = $returnIndex + 2;

        /** @var Token $priorityStartToken */
        $priorityStartToken = $tokens[$priorityStartIndex];

        if ($priorityStartToken->isGivenKind(T_VARIABLE)) {
            return;
        }

        /** @var int $nextIndex */
        $nextIndex = $tokens->getNextTokenOfKind($priorityStartIndex, [';']);

        $priorityEndIndex = $nextIndex - 1;

        $priorityCollection = PriorityCollection::create();
        $priority = $priorityCollection->getPriorityFixer($className)->getPriority();

        $priorityTokens = $priority < 0 ? [new Token('-')] : [];
        $priorityTokens[] = new Token([T_LNUMBER, (string) \abs($priority)]);

        $tokens->overrideRange($priorityStartIndex, $priorityEndIndex, $priorityTokens);
    }

    private function getCommentContent(string $className): string
    {
        $comment = "/**\n";
        $priorityCollection = PriorityCollection::create();

        $fixersToRunAfter = $priorityCollection->getPriorityFixer($className)->getFixerToRunAfterNames();
        if ($fixersToRunAfter !== []) {
            $comment .= \sprintf("     * Must run before %s.\n", \implode(', ', $fixersToRunAfter));
        }

        $fixersToRunBefore = $priorityCollection->getPriorityFixer($className)->getFixerToRunBeforeNames();
        if ($fixersToRunBefore !== []) {
            $comment .= \sprintf("     * Must run after %s.\n", \implode(', ', $fixersToRunBefore));
        }

        $comment .= '    */';

        return $comment;
    }
}
