<?php

declare(strict_types = 1);

namespace PhpCsFixerCustomFixers\Fixer;

use PhpCsFixer\DocBlock\DocBlock;
use PhpCsFixer\DocBlock\Line;
use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\FixerDefinition\FixerDefinitionInterface;
use PhpCsFixer\Indicator\PhpUnitTestCaseIndicator;
use PhpCsFixer\Preg;
use PhpCsFixer\Tokenizer\Analyzer\NamespaceUsesAnalyzer;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;

/**
 * @author Dariusz Rumiński <dariusz.ruminski@gmail.com>
 * @author Kuba Werłos <werlos@gmail.com>
 */
final class PhpUnitTestClassRequiresCoversFixer extends AbstractFixer
{
    public function getDefinition(): FixerDefinitionInterface
    {
        return new FixerDefinition(
            'Test class must have `@covers*` annotation.',
            [new CodeSample('<?php
use AcmeCorporation\FooBar;
class FooBarTest extends TestCase {
    public function testFoo() {
        $this->markTestSkipped();
        return;
    }
}
')]
        );
    }

    public function isCandidate(Tokens $tokens): bool
    {
        return $tokens->isTokenKindFound(T_CLASS);
    }

    public function isRisky(): bool
    {
        return false;
    }

    public function getPriority(): int
    {
        // must be run before PhpCsFixer\Fixer\PhpUnit\PhpUnitTestClassRequiresCoversFixer
        return 1;
    }

    public function fix(\SplFileInfo $file, Tokens $tokens): void
    {
        $phpUnitTestCaseIndicator = new PhpUnitTestCaseIndicator();

        foreach ($phpUnitTestCaseIndicator->findPhpUnitClasses($tokens) as $indexes) {
            $this->addRequiresCover($tokens, $indexes[0]);
        }
    }

    private function addRequiresCover(Tokens $tokens, int $startIndex): void
    {
        /** @var int $classIndex */
        $classIndex = $tokens->getPrevTokenOfKind($startIndex, [[T_CLASS]]);

        $prevIndex = $tokens->getPrevMeaningfulToken($classIndex);

        // don't add `@covers` annotation for abstract base classes
        if ($tokens[$prevIndex]->isGivenKind(T_ABSTRACT)) {
            return;
        }

        /** @var int $index */
        $index = $tokens[$prevIndex]->isGivenKind(T_FINAL) ? $prevIndex : $classIndex;

        /** @var string $indent */
        $indent = $tokens[$index - 1]->isGivenKind(T_WHITESPACE)
            ? Preg::replace('/^.*\R*/', '', $tokens[$index - 1]->getContent())
            : '';

        /** @var int $prevIndex */
        $prevIndex = $tokens->getPrevNonWhitespace($index);

        if ($tokens[$prevIndex]->isGivenKind(T_DOC_COMMENT)) {
            $docIndex = $prevIndex;
            $docContent = $tokens[$docIndex]->getContent();

            // ignore one-line phpdocs like `/** foo */`, as there is no place to put new annotations
            if (\strpos($docContent, "\n") === false) {
                return;
            }

            $doc = new DocBlock($docContent);

            // skip if already has annotation
            if ($doc->getAnnotationsOfType(['covers', 'coversDefaultClass', 'coversNothing']) !== []) {
                return;
            }
        } else {
            $docIndex = $index;
            $tokens->insertAt($docIndex, [
                new Token([T_DOC_COMMENT, \sprintf('/**%s%s */', "\n", $indent)]),
                new Token([T_WHITESPACE, \sprintf('%s%s', "\n", $indent)]),
            ]);

            if (!$tokens[$docIndex - 1]->isGivenKind(T_WHITESPACE)) {
                $extraNewLines = "\n";

                if (!$tokens[$docIndex - 1]->isGivenKind(T_OPEN_TAG)) {
                    $extraNewLines .= "\n";
                }

                $tokens->insertAt($docIndex, [
                    new Token([T_WHITESPACE, $extraNewLines . $indent]),
                ]);
                $docIndex++;
            }

            $doc = new DocBlock($tokens[$docIndex]->getContent());
        }

        /** @var int $classIndex */
        $classIndex = $tokens->getPrevTokenOfKind($startIndex + 1, [[T_CLASS]]);

        $lines = $doc->getLines();
        \array_splice(
            $lines,
            \count($lines) - 1,
            0,
            [
                new Line(\sprintf(
                    '%s * @%s%s',
                    $indent,
                    $this->calculateAnnotation($tokens, $classIndex),
                    "\n"
                )),
            ]
        );

        $tokens[$docIndex] = new Token([T_DOC_COMMENT, \implode('', $lines)]);
    }

    private function calculateAnnotation(Tokens $tokens, int $classIndex): string
    {
        $classNameIndex = $tokens->getNextMeaningfulToken($classIndex);
        $className = $tokens[$classNameIndex]->getContent();

        foreach ((new NamespaceUsesAnalyzer())->getDeclarationsFromTokens($tokens) as $import) {
            if ($import->getShortName() . 'Test' === $className) {
                return 'covers \\' . $import->getFullName();
            }
        }

        return 'coversNothing';
    }
}
