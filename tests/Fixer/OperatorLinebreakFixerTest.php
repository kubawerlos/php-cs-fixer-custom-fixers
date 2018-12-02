<?php

declare(strict_types = 1);

namespace Tests\Fixer;

/**
 * @internal
 *
 * @covers \PhpCsFixerCustomFixers\Fixer\OperatorLinebreakFixer
 */
final class OperatorLinebreakFixerTest extends AbstractFixerTestCase
{
    public function testPriority(): void
    {
        static::assertSame(0, $this->fixer->getPriority());
    }

    public function testConfiguration(): void
    {
        $options = $this->fixer->getConfigurationDefinition()->getOptions();
        static::assertArrayHasKey(0, $options);
        static::assertSame('only_booleans', $options[0]->getName());
        static::assertArrayHasKey(1, $options);
        static::assertSame('position', $options[1]->getName());
    }

    public function testIsRisky(): void
    {
        static::assertFalse($this->fixer->isRisky());
    }

    public function testDeprecatingPullRequest(): void
    {
        static::assertSame(4021, $this->fixer->getPullRequestId());
    }

    /**
     * @param string      $expected
     * @param null|string $input
     * @param null|array  $configuration
     *
     * @dataProvider provideFixCases
     */
    public function testFix(string $expected, ?string $input = null, ?array $configuration = null): void
    {
        if ($configuration !== null) {
            $this->fixer->configure($configuration);
        }

        $this->doTest($expected, $input);
    }

    public function provideFixCases(): \Generator
    {
        yield [
            '<?php
$foo
    = $bar;
',
            '<?php
$foo =
    $bar;
',
        ];

        yield [
            '<?php
return $foo
    + $bar;
',
            '<?php
return $foo +
    $bar;
',
        ];

        yield [
            '<?php
return $foo +
    $bar;
',
            null,
            ['only_booleans' => true],
        ];

        yield [
            '<?php
return $foo
    ? $bar
    : $baz;
',
            '<?php
return $foo ?
    $bar :
    $baz;
',
        ];

        yield [
            '<?php
return $foo
    || $bar
    || $baz;
',
            '<?php
return $foo ||
    $bar ||
    $baz;
',
        ];

        yield [
            '<?php
return $foo
    || $bar;
',
            '<?php
return $foo
    ||
    $bar;
',
        ];

        yield [
            '<?php
return $foo ||
    $bar ||
    $baz;
',
            '<?php
return $foo
    || $bar
    || $baz;
',
            ['position' => 'end'],
        ];

        yield [
            '<?php
return $foo ||
    $bar;
',
            '<?php
return $foo
    ||
    $bar;
',
            ['position' => 'end'],
        ];

        yield [
            '<?php
function foo() {
    return $a
        || $b;
}
',
            '<?php
function foo() {
    return $a||
        $b;
}
',
        ];

        yield [
            '<?php
function foo() {
    return $a ||
        $b;
}
',
            '<?php
function foo() {
    return $a
        ||$b;
}
',
            ['position' => 'end'],
        ];

        yield [
            '<?php
function getNewCuyamaTotal() {
    return 562 // Population
        + 2150 // Ft. above sea level
        + 1951; // Established
}
',
            '<?php
function getNewCuyamaTotal() {
    return 562 + // Population
        2150 + // Ft. above sea level
        1951; // Established
}
',
        ];

        yield [
            '<?php
function getNewCuyamaTotal() {
    return 562 /* Population */
        + 2150 /* Ft. above sea level */
        + 1951; /* Established */
}
',
            '<?php
function getNewCuyamaTotal() {
    return 562 + /* Population */
        2150 + /* Ft. above sea level */
        1951; /* Established */
}
',
        ];

        yield [
            '<?php
function foo() {
    return isThisTheRealLife() // First comment
        // Second comment
        // Third comment
        || isThisJustFantasy();
}
',
            '<?php
function foo() {
    return isThisTheRealLife() || // First comment
        // Second comment
        // Third comment
        isThisJustFantasy();
}
',
        ];

        yield [
            '<?php
function foo() {
    return $a
        && (
            $b
            || $c
        )
        && $d;
}
',
            '<?php
function foo() {
    return $a &&
        (
            $b ||
            $c
        ) &&
        $d;
}
',
        ];

        yield [
            '<?php
return $foo
    ?: $bar;
',
            '<?php
return $foo ?:
    $bar;
',
        ];

        yield [
            '<?php
return $foo ?:
    $bar;
',
            '<?php
return $foo
    ?: $bar;
',
            ['position' => 'end'],
        ];

        yield [
            '<?php
return $foo
    ?: $bar;
',
            '<?php
return $foo ? :
    $bar;
',
        ];

        yield [
            '<?php
return $foo/* Lorem ipsum */
    ?: $bar;
',
            '<?php
return $foo ?/* Lorem ipsum */:
    $bar;
',
        ];

        yield [
            '<?php
switch ($foo) {
    case 1:
        return true;
}
',
        ];
    }
}
