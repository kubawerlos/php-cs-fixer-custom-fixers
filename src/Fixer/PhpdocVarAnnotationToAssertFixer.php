<?php

declare(strict_types=1);

namespace PhpCsFixerCustomFixers\Fixer;

use PhpCsFixer\DocBlock\DocBlock;
use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\Preg;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;

final class PhpdocVarAnnotationToAssertFixer extends AbstractFixer
{
    public function getDefinition(): FixerDefinition
    {
        return new FixerDefinition(
            '`@var` can be replaced with assert() call.',
            [new CodeSample('<?php
/** @var Foo|Bar $fooOrBar */
$bar = new Foo();
')]
        );
    }

    public function isRisky(): bool
    {
        return false;
    }

    /**
     * @param \SplFileInfo                 $file
     * @param \PhpCsFixer\Tokenizer\Tokens $tokens
     */
    public function fix(\SplFileInfo $file, Tokens $tokens): void
    {
        foreach ($tokens as $index => $token) {
            if (!$token->isGivenKind(\T_DOC_COMMENT)) {
                continue;
            }

            if (\stripos($token->getContent(), '@var') === false) {
                continue;
            }

            $this->convertVarAnnotationMatchingPattern(
                $tokens,
                $index,
                '/@var\s+(?<type>[\|\?\\\\a-zA-Z_\x7f-\xff]*)\s+(?<variable>[$a-zA-Z_\x7f-\xff]*)\s+(?<comment>[^\*]*)/'
            );
        }
    }

    /**
     * Runs after:
     * - PhpdocNoIncorrectVarAnnotationFixer.
     */
    public function getPriority(): int
    {
        return 5;
    }

    /**
     * @param \PhpCsFixer\Tokenizer\Tokens $tokens
     *
     * @return bool
     */
    public function isCandidate(Tokens $tokens): bool
    {
        return $tokens->isTokenKindFound(\T_DOC_COMMENT);
    }

    /**
     * @param string $tmpType
     * @param string $variable
     *
     * @return string
     */
    private function convertTypeHelper($tmpType, string $variable): string
    {
        // init
        $str = '';

        if ($tmpType === 'false') {
            $str .= 'false === ' . $variable;
        } elseif ($tmpType === 'true') {
            $str .= 'true === ' . $variable;
        } elseif ($tmpType === 'null') {
            $str .= 'is_null(' . $variable . ')';
        } elseif ($tmpType === 'bool' || $tmpType === 'boolean') {
            $str .= 'is_bool(' . $variable . ')';
        } elseif ($tmpType === 'string') {
            $str .= 'is_string(' . $variable . ')';
        } elseif ($tmpType === 'int') {
            $str .= 'is_int(' . $variable . ')';
        } elseif ($tmpType === 'float' || $tmpType === 'double') {
            $str .= 'is_float(' . $variable . ')';
        } elseif ($tmpType === 'array') {
            $str .= 'is_array(' . $variable . ')';
        } else {
            $str .= $variable . ' instanceof ' . $tmpType;
        }

        return $str;
    }

    /**
     * @param \PhpCsFixer\Tokenizer\Tokens $tokens
     * @param int                          $index
     * @param null|string                  $pattern
     */
    private function convertVarAnnotationMatchingPattern(Tokens $tokens, int $index, $pattern): void
    {
        $doc = new DocBlock($tokens[$index]->getContent());
        $content = $doc->getContent();

        if (substr_count($content, '@') > 1) {
            return;
        }

        $matches = [];
        Preg::match($pattern, $content, $matches);

        if (!isset($matches['variable'], $matches['type'])) {
            return;
        }

        $variable = \trim($matches['variable']);
        $type = \trim($matches['type']);

        if (!$variable || !$type) {
            return;
        }

        $comment = \trim($matches['comment']);

        if (\strpos($type, '|') !== false) {
            $str = '';
            $tmpCounter = 0;
            foreach (\explode('|', $type) as $tmpType) {
                $tmpCounter++;

                if ($tmpCounter === 1) {
                    $str .= 'assert(';
                } else {
                    $str .= ' || ';
                }

                $str .= $this->convertTypeHelper($tmpType, $variable);
            }

            if ($str) {
                $str .= ');';
            }
        } elseif (\strpos($type, '&') !== false) {
            $str = '';
            $tmpCounter = 0;
            foreach (\explode('&', $type) as $tmpType) {
                $tmpCounter++;

                if ($tmpCounter === 1) {
                    $str .= 'assert(';
                } else {
                    $str .= ' && ';
                }

                $str .= $this->convertTypeHelper($tmpType, $variable);
            }

            if ($str) {
                $str .= ');';
            }
        } else {
            $str = 'assert(' . $this->convertTypeHelper($type, $variable) . ');';
        }

        // remove the "@var" comment
        foreach ($doc->getAnnotationsOfType(['var']) as $annotation) {
            \assert($annotation instanceof \PhpCsFixer\DocBlock\Annotation);
            if ($pattern === null
                || Preg::match($pattern, $annotation->getContent()) !== 1
            ) {
                $annotation->remove();
            }
        }

        // re-add comment, if needed
        if ($comment) {
            $str .= ' // ' . $comment;
        }

        // add new assert call
        $tokens[$index] = new Token([\T_STRING, $str]);
    }
}
