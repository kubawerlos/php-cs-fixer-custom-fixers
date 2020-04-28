<?php

declare(strict_types = 1);

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
$fooOrBar = new Foo();
// ...
/** @var Foo|Bar $fooOrBar */
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

            $next = $tokens->getNextMeaningfulToken($index);
            if (
                $next === null
                ||
                $tokens[(int)$next]->isGivenKind([\T_CONST, \T_PRIVATE, \T_PROTECTED, \T_PUBLIC, \T_VAR, \T_STATIC])
            ) {
                continue;
            }

            if (\stripos($token->getContent(), '@var') === false) {
                continue;
            }

            if (\strpos($token->getContent(), '- skip converting into "assert()"-call') !== false) {
                continue;
            }

            $this->convertVarAnnotationMatchingPattern(
                $tokens,
                $index,
                '/@var\s+(?<type>[\|\?\\\\a-z_\x7f-\xff]*)\s+(?<variable>[$a-z_\x7f-\xff]*)\s+(?<comment>[^\*]*)/isu'
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
    private function convertTypeHelper(string $tmpType, string $variable): string
    {
        // init
        $str = '';

        if ($tmpType === 'false') {
            $str .= $variable . ' === false';
        } elseif ($tmpType === 'true') {
            $str .= $variable . ' === true';
        } elseif ($tmpType === 'null') {
            $str .= $variable . ' === null';
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
     * @param string                       $pattern
     */
    private function convertVarAnnotationMatchingPattern(Tokens $tokens, int $index, string $pattern): void
    {
        $content = (new DocBlock($tokens[$index]->getContent()))->getContent();

        if (\substr_count($content, '@') > 1) {
            return;
        }

        $matches = [];
        Preg::match($pattern, $content, $matches);

        if (!isset($matches['variable'], $matches['type'])) {
            return;
        }

        $variable = \trim($matches['variable']);
        $type = \trim($matches['type']);

        if ($variable === '' || $type === '') {
            return;
        }

        if (\strpos($type, 'mixed') !== false) {
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

            if ($str !== '') {
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

            if ($str !== '') {
                $str .= ');';
            }
        } else {
            $str = 'assert(' . $this->convertTypeHelper($type, $variable) . ');';
        }

        // re-add comment, if needed
        if ($comment !== '') {
            $str .= ' // ' . $comment;
        }

        $analyze = $this->analyze($tokens);

        // replace "@var" with assert call

        $foundPreviousVariable = $analyze->getPreviousVariable($index, $matches['variable'], false);
        if ($foundPreviousVariable !== null) {
            $tokens->clearAt($index);

            $indention = $analyze->detectIndent($foundPreviousVariable);
            $foundSemiColonAfterNextVariable = $analyze->getNextString(';', $foundPreviousVariable, true);
            if ($foundSemiColonAfterNextVariable !== null) {
                $tokens->insertAt(
                    $foundSemiColonAfterNextVariable + 1,
                    new Token(
                        [
                            \T_STRING,
                            "\n" . $indention . $str,
                        ]
                    )
                );

                return;
            }
        }

        $foundNextVariable = $analyze->getNextVariable($index, $matches['variable'], false);
        if ($foundNextVariable !== null) {
            $tokens->clearAt($index);

            $indention = $analyze->detectIndent($foundNextVariable);
            $foundSemiColonAfterNextVariable = $analyze->getNextString(';', $foundNextVariable, true);
            if ($foundSemiColonAfterNextVariable !== null) {
                $tokens->insertAt(
                    $foundSemiColonAfterNextVariable + 1,
                    new Token(
                        [
                            \T_STRING,
                            "\n" . $indention . $str,
                        ]
                    )
                );

                return;
            }
        }

        $tokens[$index] = new Token([\T_STRING, $str]);
    }
}
