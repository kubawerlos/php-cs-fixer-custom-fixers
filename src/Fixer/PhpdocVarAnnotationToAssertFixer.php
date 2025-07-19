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

use PhpCsFixer\AbstractPhpdocToTypeDeclarationFixer;
use PhpCsFixer\DocBlock\Annotation;
use PhpCsFixer\DocBlock\DocBlock;
use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\FixerDefinition\FixerDefinitionInterface;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;
use PhpCsFixerCustomFixers\TokenRemover;

/**
 * @no-named-arguments
 */
final class PhpdocVarAnnotationToAssertFixer extends AbstractFixer
{
    public function getDefinition(): FixerDefinitionInterface
    {
        return new FixerDefinition(
            'Converts `@var` annotations to `assert` calls when used in assignments.',
            [new CodeSample('<?php
/** @var string $x */
$x = getValue();
')],
            '',
        );
    }

    /**
     * Must run before NativeFunctionInvocationFixer, ReturnAssignmentFixer.
     */
    public function getPriority(): int
    {
        return 2;
    }

    public function isCandidate(Tokens $tokens): bool
    {
        return $tokens->isAnyTokenKindsFound([\T_DOC_COMMENT, \T_VARIABLE]);
    }

    public function isRisky(): bool
    {
        return false;
    }

    public function fix(\SplFileInfo $file, Tokens $tokens): void
    {
        $tokensToInsert = [];
        $typesToExclude = [];

        foreach ($tokens->findGivenKind(\T_DOC_COMMENT) as $index => $token) {
            $typesToExclude = \array_merge($typesToExclude, self::getTypesToExclude($token->getContent()));

            $variableIndex = self::getVariableIndex($tokens, $index);
            if ($variableIndex === null) {
                continue;
            }

            $assertTokens = self::getAssertTokens($tokens, $index, $tokens[$variableIndex]->getContent(), $typesToExclude);
            if ($assertTokens === null) {
                continue;
            }

            $expressionEndIndex = self::getExpressionEnd($tokens, $variableIndex);

            if (!self::canBePlacedAfterExpression($tokens, $expressionEndIndex)) {
                continue;
            }

            if ($tokens[$variableIndex - 1]->isWhitespace()) {
                \array_unshift($assertTokens, new Token([\T_WHITESPACE, $tokens[$variableIndex - 1]->getContent()]));
            }

            $tokensToInsert[$expressionEndIndex + 1] = $assertTokens;

            TokenRemover::removeWithLinesIfPossible($tokens, $index);
        }

        $tokens->insertSlices($tokensToInsert);
    }

    /**
     * @return list<string>
     */
    private static function getTypesToExclude(string $content): array
    {
        /** @var null|\Closure(string): list<string> $getTypesToExclude */
        static $getTypesToExclude = null;

        if ($getTypesToExclude === null) {
            /** @var \Closure(string): list<string> $getTypesToExclude */
            $getTypesToExclude = \Closure::bind(
                static fn (string $content): array => AbstractPhpdocToTypeDeclarationFixer::getTypesToExclude($content),
                null,
                AbstractPhpdocToTypeDeclarationFixer::class,
            );
        }

        return $getTypesToExclude($content);
    }

    private static function getVariableIndex(Tokens $tokens, int $docCommentIndex): ?int
    {
        $prevIndex = $tokens->getPrevMeaningfulToken($docCommentIndex);
        if (!$tokens[$prevIndex]->equalsAny([';', '{', '}', [\T_OPEN_TAG]])) {
            return null;
        }

        $variableIndex = $tokens->getNextMeaningfulToken($docCommentIndex);
        if ($variableIndex === null) {
            return null;
        }
        if (!$tokens[$variableIndex]->isGivenKind([\T_VARIABLE])) {
            return null;
        }

        $assignmentIndex = $tokens->getNextMeaningfulToken($variableIndex);
        \assert(\is_int($assignmentIndex));

        if (!$tokens[$assignmentIndex]->equals('=')) {
            return null;
        }

        return $variableIndex;
    }

    /**
     * @param list<string> $typesToExclude
     *
     * @return null|list<Token>
     */
    private static function getAssertTokens(Tokens $tokens, int $docCommentIndex, string $variableName, array $typesToExclude): ?array
    {
        $annotation = self::getAnnotationForVariable($tokens, $docCommentIndex, $variableName);
        if ($annotation === null) {
            return null;
        }

        $typeExpression = $annotation->getTypeExpression();
        if ($typeExpression === null) {
            return null;
        }

        $assertCode = '<?php assert(';

        $assertions = [];
        foreach ($typeExpression->getTypes() as $type) {
            if (\substr($type, 0, 1) === '?') {
                $assertions['null'] = self::getCodeForType('null', $variableName);
                $type = \substr($type, 1);
            }
            if (\in_array($type, $typesToExclude, true)) {
                return null;
            }
            $assertions[$type] = self::getCodeForType($type, $variableName);
        }

        try {
            $tokens = Tokens::fromCode($assertCode . \implode(' || ', $assertions) . ');');
        } catch (\ParseError $exception) {
            return null;
        }

        /** @var list<Token> $arrayTokens */
        $arrayTokens = $tokens->toArray();

        return \array_slice($arrayTokens, 1);
    }

    private static function getAnnotationForVariable(Tokens $tokens, int $docCommentIndex, string $variableName): ?Annotation
    {
        $docBlock = new DocBlock($tokens[$docCommentIndex]->getContent());

        if (\count($docBlock->getAnnotations()) !== 1) {
            return null;
        }

        $varAnnotations = $docBlock->getAnnotationsOfType('var');
        if (\count($varAnnotations) !== 1) {
            return null;
        }

        $varAnnotation = \reset($varAnnotations);

        if ($varAnnotation->getVariableName() !== $variableName) {
            return null;
        }

        return $varAnnotation;
    }

    private static function getCodeForType(string $type, string $variableName): string
    {
        $typesMap = [
            'array' => 'is_array',
            'bool' => 'is_bool',
            'boolean' => 'is_bool',
            'callable' => 'is_callable',
            'double' => 'is_float',
            'float' => 'is_float',
            'int' => 'is_int',
            'integer' => 'is_int',
            'iterable' => 'is_iterable',
            'null' => 'is_null',
            'object' => 'is_object',
            'resource' => 'is_resource',
            'string' => 'is_string',
        ];

        if (\array_key_exists(\strtolower($type), $typesMap)) {
            return \sprintf('%s(%s)', $typesMap[\strtolower($type)], $variableName);
        }

        return \sprintf('%s instanceof %s', $variableName, $type);
    }

    private static function getExpressionEnd(Tokens $tokens, int $index): int
    {
        while (!$tokens[$index]->equals(';')) {
            $index = $tokens->getNextMeaningfulToken($index);
            \assert(\is_int($index));

            $blockType = Tokens::detectBlockType($tokens[$index]);
            if ($blockType !== null && $blockType['isStart']) {
                $index = $tokens->findBlockEnd($blockType['type'], $index);
            }
        }

        return $index;
    }

    private static function canBePlacedAfterExpression(Tokens $tokens, int $expressionEndIndex): bool
    {
        $afterExpressionIndex = $tokens->getNextMeaningfulToken($expressionEndIndex);

        if ($afterExpressionIndex === null) {
            return true;
        }

        if ($tokens[$afterExpressionIndex]->isGivenKind(\T_NS_SEPARATOR)) {
            $afterExpressionIndex = $tokens->getNextMeaningfulToken($afterExpressionIndex);
        }

        return !$tokens[$afterExpressionIndex]->equals([\T_STRING, 'assert'], false);
    }
}
