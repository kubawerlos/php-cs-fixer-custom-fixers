<?php declare(strict_types=1);

/*
 * This file is part of PHP CS Fixer: custom fixers.
 *
 * (c) 2018 Kuba Werłos
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Tests;

use PhpCsFixer\Fixer\FixerInterface;
use PhpCsFixer\Fixer\WhitespacesAwareFixerInterface;
use PhpCsFixer\FixerFactory;
use PhpCsFixer\RuleSet\RuleSet;
use PhpCsFixer\Tokenizer\Tokens;
use PhpCsFixer\WhitespacesFixerConfig;
use PhpCsFixerCustomFixers\Fixers;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Finder\Finder;

/**
 * @internal
 *
 * @coversNothing
 *
 * @requires PHP 8.1
 */
final class PriorityTest extends TestCase
{
    use AssertSameTokensTrait;

    /**
     * @dataProvider providePriorityCases
     */
    public function testPriorities(FixerInterface $firstFixer, FixerInterface $secondFixer): void
    {
        self::assertLessThan($firstFixer->getPriority(), $secondFixer->getPriority());
    }

    /**
     * @dataProvider providePriorityCases
     */
    public function testInOrder(FixerInterface $firstFixer, FixerInterface $secondFixer, string $expected, string $input): void
    {
        Tokens::clearCache();
        $tokens = Tokens::fromCode($input);

        $firstFixer->fix($this->createSplFileInfoDouble(), $tokens);
        $tokens->clearEmptyTokens();

        $secondFixer->fix($this->createSplFileInfoDouble(), $tokens);
        $tokens->clearEmptyTokens();

        self::assertSame($expected, $tokens->generateCode());

        Tokens::clearCache();
        self::assertSameTokens(Tokens::fromCode($expected), $tokens);
    }

    /**
     * @dataProvider providePriorityCases
     */
    public function testInRevertedOrder(FixerInterface $firstFixer, FixerInterface $secondFixer, string $expected, string $input): void
    {
        Tokens::clearCache();
        $tokens = Tokens::fromCode($input);

        $secondFixer->fix($this->createSplFileInfoDouble(), $tokens);
        $tokens->clearEmptyTokens();

        $firstFixer->fix($this->createSplFileInfoDouble(), $tokens);
        $tokens->clearEmptyTokens();

        self::assertNotSame($expected, $tokens->generateCode());
    }

    /**
     * @return iterable<array{FixerInterface, FixerInterface, string, string}>
     */
    public static function providePriorityCases(): iterable
    {
        foreach (Finder::create()->files()->in(__DIR__ . '/priority_fixtures') as $test) {
            $fileName = $test->getBasename('.test');

            [$firstFixer, $secondFixer] = \explode(',', $fileName);

            \preg_match('/^
                --CONFIGURATION--\n(?<configuration>.*)\n
                --EXPECTED--\n(?<expected>.*)\n
                --INPUT--\n(?<input>.*)
            $/sx', $test->getContents(), $matches);

            /** @var array<string, array<string, string>|bool> $configuration */
            $configuration = \json_decode($matches['configuration'], true);

            yield $fileName => [
                self::getFixer($firstFixer, $configuration),
                self::getFixer($secondFixer, $configuration),
                $matches['expected'],
                $matches['input'],
            ];
        }
    }

    /**
     * @param array<string, array<string, string>|bool> $config
     */
    private static function getFixer(string $name, array $config): FixerInterface
    {
        $name = \preg_replace('/^Custom_/', 'PhpCsFixerCustomFixers/', $name);

        $fixers = (new FixerFactory())
            ->registerBuiltInFixers()
            ->registerCustomFixers(new Fixers())
            ->useRuleSet(new RuleSet($config))
            ->getFixers();

        foreach ($fixers as $fixer) {
            if ($name === $fixer->getName()) {
                if ($fixer instanceof WhitespacesAwareFixerInterface) {
                    $fixer->setWhitespacesConfig(new WhitespacesFixerConfig());
                }

                return $fixer;
            }
        }

        throw new \Exception(\sprintf('Fixer "%s" not found in config: "%s".', $name, \json_encode($config)));
    }

    private function createSplFileInfoDouble(): \SplFileInfo
    {
        return new class ('') extends \SplFileInfo {};
    }
}
