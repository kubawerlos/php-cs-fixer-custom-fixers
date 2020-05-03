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

    public function getPriority(): int
    {
        // must be run before PhpdocAlignFixer and PhpdocTypesOrderFixer
        return 1;
    }

    public function isCandidate(Tokens $tokens): bool
    {
        return $tokens->isTokenKindFound(T_DOC_COMMENT);
    }

    public function isRisky(): bool
    {
        return false;
    }

    public function fix(\SplFileInfo $file, Tokens $tokens): void
    {
        for ($index = $tokens->count() - 1; $index > 0; $index--) {
            /** @var Token $token */
            $token = $tokens[$index];

            if (!$token->isGivenKind([T_DOC_COMMENT])) {
                continue;
            }

            $docBlock = new DocBlock($token->getContent());

            foreach ($docBlock->getAnnotations() as $annotation) {
                if (!$annotation->supportTypes()) {
                    continue;
                }

                /** @var Line $line */
                $line = $docBlock->getLine($annotation->getStart());

                $content = $line->getContent();
                $newContent = $this->trimTypes($content);

                /** @var Line $line */
                $line = $docBlock->getLine($annotation->getStart());
                $line->setContent($newContent);
            }

            $newContent = $docBlock->getContent();
            if ($newContent === $token->getContent()) {
                continue;
            }

            $tokens[$index] = new Token([T_DOC_COMMENT, $newContent]);
        }
    }

    private function trimTypes(string $content): string
    {
        if (\strpos($content, '|') === false && \strpos($content, '&') === false) {
            return $content;
        }

        /** @var int $tagStartPosition */
        $tagStartPosition = \strpos($content, '@');

        /** @var int $typeStartPosition */
        $typeStartPosition = \strpos($content, ' ', $tagStartPosition);
        $typeStartPosition++;

        $variableStartPosition = \strpos($content, '$', $typeStartPosition);
        if ($variableStartPosition !== false) {
            $variableStartPosition++;
        } else {
            $variableStartPosition = \strlen($content);
        }

        Preg::match('/(?<!(\h|&|\|))\h(?!(\h|&(?!\$)|\|))/', $content, $matches, PREG_OFFSET_CAPTURE, $typeStartPosition + 1);
        if ($matches !== []) {
            $descriptionStartPosition = $matches[0][1];
        } else {
            $descriptionStartPosition = \strlen($content);
        }

        /** @var int $typeEndPosition */
        $typeEndPosition = \min($variableStartPosition, $descriptionStartPosition);

        $contentBeforeTypes = \substr($content, 0, $typeStartPosition);
        $contentTypes = \substr($content, $typeStartPosition, $typeEndPosition - $typeStartPosition);
        $contentAfterTypes = \substr($content, $typeEndPosition);

        /** @var string $trimmedContentTypes */
        $trimmedContentTypes = Preg::replace('/\h*(&(?!\h*\$)|\|)\h*/', '$1', $contentTypes);

        return $contentBeforeTypes . $trimmedContentTypes . $contentAfterTypes;
    }
}
