<?php

declare(strict_types = 1);

namespace Tests;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Yaml\Yaml;

/**
 * @internal
 * @coversNothing
 */
final class PrioritiesYamlTest extends TestCase
{
    /**
     * @dataProvider provideYamlEntryHasPriorityTestCases
     */
    public function testYamlEntryHasPriorityTest(string $firstFixerName, string $secondFixerName): void
    {
        $priorityTest = new PriorityTest();

        foreach ($priorityTest->providePriorityCases() as [$firstFixer, $secondFixer, $expected, $input]) {
            if (\in_array(
                [$firstFixerName,  $secondFixerName],
                [
                    ['DataProviderReturnTypeFixer', 'MethodArgumentSpaceFixer'], // MethodArgumentSpaceFixer -> ReturnTypeDeclarationFixer
                    ['MultilineCommentOpeningClosingFixer', 'CommentSurroundedBySpacesFixer'], // reverted order
                    ['MultilineCommentOpeningClosingFixer', 'MultilineCommentOpeningClosingAloneFixer'], // reverted order
                    ['NoExtraBlankLinesFixer', 'PhpUnitNoUselessReturnFixer'],
                    ['NoLeadingSlashInGlobalNamespaceFixer', 'PhpdocToCommentFixer'],
                    ['NoTrailingWhitespaceInCommentFixer', 'MultilineCommentOpeningClosingAloneFixer'], // reverted order
                    ['PhpdocAddMissingParamAnnotationFixer', 'PhpdocParamTypeFixer'],
                    ['PhpdocNoIncorrectVarAnnotationFixer', 'NoEmptyCommentFixer'],
                    ['PhpdocNoIncorrectVarAnnotationFixer', 'NoExtraBlankLinesFixer'],
                    ['PhpdocNoIncorrectVarAnnotationFixer', 'NoTrailingWhitespaceFixer'],
                    ['PhpdocNoIncorrectVarAnnotationFixer', 'NoWhitespaceInBlankLineFixer'],
                ],
                true
            )) {
                $this->addToAssertionCount(1); // @todo: ensure it is not needed case

                return;
            }
            if ($firstFixerName !== (new \ReflectionClass($firstFixer))->getShortName()) {
                continue;
            }
            if ($secondFixerName !== (new \ReflectionClass($secondFixer))->getShortName()) {
                continue;
            }
            $this->addToAssertionCount(1);

            return;
        }

        static::markTestIncomplete(\sprintf('Priority test for fixer %s to run before %s not found.', $firstFixerName, $secondFixerName));
    }

    public function provideYamlEntryHasPriorityTestCases(): iterable
    {
        foreach (Yaml::parseFile(__DIR__ . '/../dev-tools/priorities.yaml') as $secondFixerName => $firstFixerNames) {
            foreach ($firstFixerNames as $firstFixerName) {
                yield [$firstFixerName, $secondFixerName];
            }
        }
    }
}
