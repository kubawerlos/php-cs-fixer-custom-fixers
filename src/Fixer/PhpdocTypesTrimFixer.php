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

use PhpCsFixer\DocBlock\DocBlock;
use PhpCsFixer\DocBlock\Line;
use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\FixerDefinition\FixerDefinitionInterface;
use PhpCsFixer\Preg;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;

final class PhpdocTypesTrimFixer extends AbstractFixer
{
    public function getDefinition(): FixerDefinitionInterface
    {
        return new FixerDefinition(
            'PHPDoc types must be trimmed.',
            [new CodeSample('<?php
/**
 * @param null | string $x
 */
function foo($x) {}
')]
        );
    }

    /**
     * Must run before NoSuperfluousPhpdocTagsFixer, PhpdocAlignFixer.
     */
    public function getPriority(): int
    {
        return 7;
    }

    public function isCandidate(Tokens $tokens): bool
    {
        return $tokens->isTokenKindFound(\T_DOC_COMMENT);
    }

    public function isRisky(): bool
    {
        return false;
    }

    public function fix(\SplFileInfo $file, Tokens $tokens): void
    {
        for ($index = $tokens->count() - 1; $index > 0; $index--) {
            if (!$tokens[$index]->isGivenKind([\T_DOC_COMMENT])) {
                continue;
            }

            $docBlock = new DocBlock($tokens[$index]->getContent());

            foreach ($docBlock->getAnnotations() as $annotation) {
                if (!$annotation->supportTypes()) {
                    continue;
                }

                $line = $docBlock->getLine($annotation->getStart());
                \assert($line instanceof Line);

                $content = $line->getContent();
                $newContent = $this->trimTypes($content);

                $line = $docBlock->getLine($annotation->getStart());
                \assert($line instanceof Line);
                $line->setContent($newContent);
            }

            $newContent = $docBlock->getContent();
            if ($newContent === $tokens[$index]->getContent()) {
                continue;
            }

            $tokens[$index] = new Token([\T_DOC_COMMENT, $newContent]);
        }
    }

    private function trimTypes(string $content): string
    {
        $tagStartPosition = \strpos($content, '@');
        \assert(\is_int($tagStartPosition));

        $typeStartPosition = \strpos($content, ' ', $tagStartPosition);
        if ($typeStartPosition === false) {
            return $content;
        }
        $typeStartPosition++;

        $variableStartPosition = \strpos($content, '$', $typeStartPosition);
        if ($variableStartPosition !== false) {
            $variableStartPosition++;
        } else {
            $variableStartPosition = \strlen($content);
        }

        Preg::match('/(?<!(\h|&|\|))\h(?!(\h|&(?!\$)|\|))/', $content, $matches, \PREG_OFFSET_CAPTURE, $typeStartPosition + 1);
        if ($matches !== []) {
            $descriptionStartPosition = $matches[0][1];
        } else {
            $descriptionStartPosition = \strlen($content);
        }

        $typeEndPosition = \min($variableStartPosition, $descriptionStartPosition);
        \assert(\is_int($typeEndPosition));

        $contentBeforeTypes = \substr($content, 0, $typeStartPosition);
        $contentTypes = \substr($content, $typeStartPosition, $typeEndPosition - $typeStartPosition);
        $contentAfterTypes = \substr($content, $typeEndPosition);

        $trimmedContentTypes = Preg::replace('/\h*(&(?!\h*\$)|\|)\h*/', '$1', $contentTypes);

        return $contentBeforeTypes . $trimmedContentTypes . $contentAfterTypes;
    }
}
