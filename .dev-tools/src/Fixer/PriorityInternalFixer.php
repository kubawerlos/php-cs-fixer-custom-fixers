<?php declare(strict_types=1);

/*
 * This file is part of PHP CS Fixer: custom fixers.
 *
 * (c) 2018 Kuba Werłos
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace PhpCsFixerCustomFixersDev\Fixer;

use PhpCsFixer\Fixer\FixerInterface;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\FixerDefinition\FixerDefinitionInterface;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;
use PhpCsFixer\Utils;
use PhpCsFixerCustomFixersDev\Priority\PriorityCollection;

/**
 * @internal
 */
final class PriorityInternalFixer implements FixerInterface
{
    public function getDefinition(): FixerDefinitionInterface
    {
        return new FixerDefinition('Internal fixer for priorities.', []);
    }

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
        return $tokens->findSequence([[\T_NAMESPACE], [\T_STRING, 'PhpCsFixerCustomFixers']]) !== null
            && $tokens->findSequence([[\T_FUNCTION], [\T_STRING, 'getDefinition']]) !== null
            && !$tokens->findGivenKind(\T_ABSTRACT);
    }

    public function isRisky(): bool
    {
        return false;
    }

    public function fix(\SplFileInfo $file, Tokens $tokens): void
    {
        /** @var array<int> $indices */
        $indices = $tokens->findSequence([[\T_CLASS]]);

        $classStartIndex = \key($indices);
        \assert(\is_int($classStartIndex));

        $classNameIndex = $tokens->getNextMeaningfulToken($classStartIndex);
        \assert(\is_int($classNameIndex));

        $className = $tokens[$classNameIndex]->getContent();

        $startIndex = $tokens->getNextTokenOfKind($classNameIndex, ['{']);
        \assert(\is_int($startIndex));

        $endIndex = $tokens->findBlockEnd(Tokens::BLOCK_TYPE_CURLY_BRACE, $startIndex);

        /** @var array<int> $indices */
        $indices = $tokens->findSequence([[\T_PUBLIC], [\T_FUNCTION], [\T_STRING, 'getPriority']], $startIndex, $endIndex);

        $sequencesStartIndex = \key($indices);
        \assert(\is_int($sequencesStartIndex));

        $commentContent = $this->getCommentContent($className);

        if ($tokens[$sequencesStartIndex - 2]->isGivenKind(\T_DOC_COMMENT)) {
            $tokens[$sequencesStartIndex - 2] = new Token([\T_DOC_COMMENT, $commentContent]);
        } else {
            $tokens->insertAt(
                $sequencesStartIndex,
                [
                    new Token([\T_DOC_COMMENT, $commentContent]),
                    new Token([\T_WHITESPACE, "\n    "]),
                ],
            );
        }

        $returnIndex = $tokens->getNextTokenOfKind($sequencesStartIndex, [[\T_RETURN]]);
        \assert(\is_int($returnIndex));

        $priorityStartIndex = $returnIndex + 2;

        if ($tokens[$priorityStartIndex]->isGivenKind(\T_VARIABLE)) {
            return;
        }

        $nextIndex = $tokens->getNextTokenOfKind($priorityStartIndex, [';']);
        \assert(\is_int($nextIndex));

        $priorityEndIndex = $nextIndex - 1;

        $priorityCollection = PriorityCollection::create();
        $priority = $priorityCollection->getPriorityFixer($className)->getPriority();

        $priorityTokens = $priority < 0 ? [new Token('-')] : [];
        $priorityTokens[] = new Token([\T_LNUMBER, (string) \abs($priority)]);

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

        $comment .= '     */';

        return $comment;
    }
}
