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
        $this->doTest($expected, $input, $configuration);
    }

    public function provideFixCases(): iterable
    {
        foreach ($this->pairs() as $key => $value) {
            yield \sprintf('%s when position is "beginning"', $key) => $value;
        }

        foreach ($this->pairs() as $key => $value) {
            yield \sprintf('%s when position is "end"', $key) => [
                $value[1],
                $value[0],
                ['position' => 'end'],
            ];
        }

        yield 'ignore add operator when only booleans enabled' => [
            '<?php
return $foo
    +
    $bar;
',
            null,
            ['only_booleans' => true],
        ];

        yield 'handle operator when on separate line when position is "beginning"' => [
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

        yield 'handle operator when on separate line when position is "end"' => [
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

        yield 'handle Elvis operator with space inside' => [
            '<?php
return $foo
    ?: $bar;
',
            '<?php
return $foo ? :
    $bar;
',
        ];

        yield 'handle Elvis operator with space inside when position is "end"' => [
            '<?php
return $foo ?:
    $bar;
',
            '<?php
return $foo
    ? : $bar;
',
            ['position' => 'end'],
        ];

        yield 'handle Elvis operator with comment inside' => [
            '<?php
return $foo/* Lorem ipsum */
    ?: $bar;
',
            '<?php
return $foo ?/* Lorem ipsum */:
    $bar;
',
        ];

        yield 'handle Elvis operators with comment inside when position is "end"' => [
            '<?php
return $foo ?:
    /* Lorem ipsum */$bar;
',
            '<?php
return $foo
    ?/* Lorem ipsum */: $bar;
',
            ['position' => 'end'],
        ];

        yield 'nullable type when position is "end"' => [
            '<?php
                function foo(
                    ?int $x,
                    ?int $y,
                    ?int $z
                ) {};',
            null,
            ['position' => 'end'],
        ];

        yield 'return type' => [
            '<?php
                function foo()
                :
                bool
                {};',
        ];

        yield 'assign by reference' => [
            '<?php
                $a
                    = $b;
                $c =&
                     $d;
            ',
            '<?php
                $a =
                    $b;
                $c =&
                     $d;
            ',
        ];

        yield 'passing by reference' => [
            '<?php
                function foo(
                    &$a,
                    &$b,
                    int
                        &$c,
                    \Bar\Baz
                        &$d
                ) {};',
            null,
            ['position' => 'end'],
        ];
    }

    private function pairs(): iterable
    {
        yield 'handle equal sign' => [
            '<?php
$foo
    = $bar;
',
            '<?php
$foo =
    $bar;
',
        ];

        yield 'handle add operator' => [
            '<?php
return $foo
    + $bar;
',
            '<?php
return $foo +
    $bar;
',
            ['only_booleans' => false],
        ];

        yield 'handle uppercase operator' => [
            '<?php
return $foo
    AND $bar;
',
            '<?php
return $foo AND
    $bar;
',
        ];

        yield 'handle concatenation operator' => [
            '<?php
return $foo
    .$bar;
',
            '<?php
return $foo.
    $bar;
',
        ];

        yield 'handle ternary operator' => [
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

        yield 'handle multiple operators' => [
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

        yield 'handle operator when no whitespace is before' => [
            '<?php
function foo() {
    return $a
        ||$b;
}
',
            '<?php
function foo() {
    return $a||
        $b;
}
',
        ];

        yield 'handle operator with one-line comments' => [
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

        yield 'handle operator with PHPDoc comments' => [
            '<?php
function getNewCuyamaTotal() {
    return 562 /** Population */
        + 2150 /** Ft. above sea level */
        + 1951; /** Established */
}
',
            '<?php
function getNewCuyamaTotal() {
    return 562 + /** Population */
        2150 + /** Ft. above sea level */
        1951; /** Established */
}
',
        ];

        yield 'handle operator with multiple comments next to each other' => [
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

        yield 'handle nested operators' => [
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

        yield 'handle Elvis operator' => [
            '<?php
return $foo
    ?: $bar;
',
            '<?php
return $foo ?:
    $bar;
',
        ];

        yield 'handle ternary operator inside of switch' => [
            '<?php
switch ($foo) {
    case 1:
        return $isOK ? 1 : -1;
    case (
            $a
            ? 2
            : 3
        ) :
        return 23;
    case $b[
            $a
            ? 4
            : 5
        ]
        : return 45;
}
',
            '<?php
switch ($foo) {
    case 1:
        return $isOK ? 1 : -1;
    case (
            $a ?
            2 :
            3
        ) :
        return 23;
    case $b[
            $a ?
            4 :
            5
        ]
        : return 45;
}
',
        ];

        yield 'handle ternary operator with switch inside' => [
            '<?php
                $a
                    ? array_map(
                        function () {
                            switch (true) {
                                case 1:
                                    return true;
                            }
                        },
                        [1, 2, 3]
                    )
                    : false;
            ',
            '<?php
                $a ?
                    array_map(
                        function () {
                            switch (true) {
                                case 1:
                                    return true;
                            }
                        },
                        [1, 2, 3]
                    ) :
                    false;
            ',
        ];

        foreach ([
            '+', '-', '*', '/', '%', '**', // Arithmetic
            '+=', '-=', '*=', '/=', '%=', '**=', // Arithmetic assignment
            '=', // Assignment
            '&', '|', '^', '<<', '>>', // Bitwise
            '&=', '|=', '^=', '<<=', '>>=', // Bitwise assignment
            '==', '===', '!=', '<>', '!==', '<', '>', '<=', '>=', '<=>', // Comparison
            'and', 'or', 'xor', '&&', '||', // Logical
            '.', '.=', // String
            '??', // Null Coalescing
            '->', // Object
            '::', // Scope Resolution
        ] as $operator) {
            yield \sprintf('handle %s operator', $operator) => [
                \sprintf('<?php
                    $foo
                        %s $bar;
                ', $operator),
                \sprintf('<?php
                    $foo %s
                        $bar;
                ', $operator),
            ];
        }

        yield 'handle => operator' => [
            '<?php
[$foo
    => $bar];
',
            '<?php
[$foo =>
    $bar];
',
        ];

        yield 'handle & operator with constant' => [
            '<?php
\Foo::bar
    & $baz;
',
            '<?php
\Foo::bar &
    $baz;
',
        ];
    }
}
