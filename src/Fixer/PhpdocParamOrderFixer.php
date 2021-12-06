<?php declare(strict_types=1);

/*
 * This file is part of PHP CS Fixer: custom fixers.
 *
 * (c) 2018 Kuba Werłos
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace PhpCsFixerCustomFixers\Fixer;

use PhpCsFixer\DocBlock\Annotation;
use PhpCsFixer\DocBlock\DocBlock;
use PhpCsFixer\DocBlock\Line;
use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\FixerDefinition\FixerDefinitionInterface;
use PhpCsFixer\Preg;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;

final class PhpdocParamOrderFixer extends AbstractFixer
{
    public function getDefinition(): FixerDefinitionInterface
    {
        return new FixerDefinition(
            'The `@param` annotations must be in the same order as the function parameters.',
            [new CodeSample('<?php
/**
 * @param int $b
 * @param int $a
 * @param int $c
 */
function foo($a, $b, $c) {}
')]
        );
    }

    /**
     * Must run before PhpdocAlignFixer.
     * Must run after CommentToPhpdocFixer, PhpdocAddMissingParamAnnotationFixer.
     */
    public function getPriority(): int
    {
        return 0;
    }

    public function isCandidate(Tokens $tokens): bool
    {
        return $tokens->isAllTokenKindsFound([\T_DOC_COMMENT, \T_FUNCTION]);
    }

    public function isRisky(): bool
    {
        return false;
    }

    public function fix(\SplFileInfo $file, Tokens $tokens): void
    {
        for ($index = 0; $index < $tokens->count(); $index++) {
            if (!$tokens[$index]->isGivenKind(\T_DOC_COMMENT)) {
                continue;
            }

            $functionIndex = $tokens->getTokenNotOfKindSibling($index, 1, [[\T_ABSTRACT], [\T_COMMENT], [\T_FINAL], [\T_PRIVATE], [\T_PROTECTED], [\T_PUBLIC], [\T_STATIC], [\T_WHITESPACE]]);

            if ($functionIndex === null) {
                return;
            }

            if (!$tokens[$functionIndex]->isGivenKind(\T_FUNCTION)) {
                continue;
            }

            $paramNames = $this->getParamNames($tokens, $functionIndex);

            $docBlock = new DocBlock($tokens[$index]->getContent());
            $sorted = $this->getSortedAnnotations($docBlock->getAnnotations(), $paramNames);

            foreach ($sorted as $annotationIndex => $annotationContent) {
                $annotation = $docBlock->getAnnotation($annotationIndex);
                \assert($annotation instanceof Annotation);
                $annotation->remove();

                $line = $docBlock->getLine($annotation->getStart());
                \assert($line instanceof Line);
                $line->setContent($annotationContent);
            }

            if ($docBlock->getContent() === $tokens[$index]->getContent()) {
                continue;
            }

            $tokens[$index] = new Token([\T_DOC_COMMENT, $docBlock->getContent()]);
        }
    }

    /**
     * @return array<string>
     */
    private function getParamNames(Tokens $tokens, int $functionIndex): array
    {
        $paramBlockStartIndex = $tokens->getNextTokenOfKind($functionIndex, ['(']);
        \assert(\is_int($paramBlockStartIndex));

        $paramBlockEndIndex = $tokens->findBlockEnd(Tokens::BLOCK_TYPE_PARENTHESIS_BRACE, $paramBlockStartIndex);

        $paramNames = [];
        for ($index = $paramBlockStartIndex; $index < $paramBlockEndIndex; $index++) {
            if ($tokens[$index]->isGivenKind(\T_VARIABLE)) {
                $paramNames[] = $tokens[$index]->getContent();
            }
        }

        return $paramNames;
    }

    /**
     * @param array<Annotation> $annotations
     * @param array<string>     $paramNames
     *
     * @return array<int, string>
     */
    private function getSortedAnnotations(array $annotations, array $paramNames): array
    {
        $paramFound = false;
        $annotationsBeforeParams = [];
        $paramsByName = \array_combine($paramNames, \array_fill(0, \count($paramNames), null));
        $superfluousParams = [];
        $annotationsAfterParams = [];

        foreach ($annotations as $annotation) {
            if ($annotation->getTag()->getName() === 'param') {
                $paramFound = true;
                $paramName = $this->getParamName($paramNames, $paramsByName, $annotation->getContent());

                if ($paramName === null) {
                    $superfluousParams[] = $annotation->getContent();
                } else {
                    $paramsByName[$paramName] = $annotation->getContent();
                }

                continue;
            }

            if ($paramFound) {
                $annotationsAfterParams[] = $annotation->getContent();
                continue;
            }

            $annotationsBeforeParams[] = $annotation->getContent();
        }

        return \array_merge($annotationsBeforeParams, \array_values(\array_filter($paramsByName)), $superfluousParams, $annotationsAfterParams);
    }

    /**
     * @param array<string>              $paramNames
     * @param array<string, null|string> $paramsByName
     */
    private function getParamName(array $paramNames, array $paramsByName, string $annotation): ?string
    {
        foreach ($paramNames as $paramName) {
            if (Preg::match(\sprintf('/@param\s+(?:[^\$](?:[^<\s]|<[^>]*>)*\s+)?(?:&|\.\.\.)?\s*(\Q%s\E)\b/', $paramName), $annotation, $matches) !== 1) {
                continue;
            }

            if (\array_key_exists($matches[1], $paramsByName) && $paramsByName[$matches[1]] !== null) {
                return null;
            }

            return $matches[1];
        }

        return null;
    }
}
