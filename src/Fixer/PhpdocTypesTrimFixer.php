<?php

declare(strict_types = 1);

namespace PhpCsFixerCustomFixers\Fixer;

use PhpCsFixer\DocBlock\DocBlock;
use PhpCsFixer\DocBlock\Line;
use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\FixerDefinition\FixerDefinitionInterface;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;

final class PhpdocTypesTrimFixer extends AbstractFixer
{
    public function getDefinition(): FixerDefinitionInterface
    {
        return new FixerDefinition(
            'PHPDpc must be trimmed.',
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
        return 0;
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
            $token = $tokens[$index];

            if (!$token->isGivenKind([T_DOC_COMMENT])) {
                continue;
            }

            $docBlock = new DocBlock($token->getContent());

            foreach ($docBlock->getAnnotations() as $annotation) {
                if (!$annotation->supportTypes()) {
                    continue;
                }

                $content = $annotation->getContent();

                $variableStartPosition = \strpos($content, '$');

                if ($variableStartPosition === false) {
                    $variableStartPosition = \strlen($content);
                }

                $types = $this->trimTypes(\substr($content, 0, $variableStartPosition));

                $newContent = $types . \substr($content, $variableStartPosition);

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

    private function trimTypes(string $typesContent): string
    {
        $types = \explode('|', $typesContent);

        if (\count($types) < 2) {
            return $typesContent;
        }
        $lastIndex = \count($types) - 1;

        foreach ($types as $key => $type) {
            if ($key === 0) {
                $types[$key] = \rtrim($type);
            } elseif ($key === $lastIndex) {
                $types[$key] = \ltrim($type);
            } else {
                $types[$key] = \trim($type);
            }
        }

        return \implode('|', $types);
    }
}
