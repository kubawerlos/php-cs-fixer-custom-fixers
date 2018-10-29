<?php

declare(strict_types = 1);

namespace Tests\Fixer;

use PhpCsFixer\Fixer\ArrayNotation\ArraySyntaxFixer;
use PhpCsFixer\Fixer\ControlStructure\YodaStyleFixer;
use PhpCsFixer\Fixer\Strict\StrictComparisonFixer;

/**
 * @internal
 *
 * @covers \PhpCsFixerCustomFixers\Fixer\EmptyFixer
 */
final class EmptyFixerTest extends AbstractFixerTestCase
{
    public function testPriority(): void
    {
        static::assertGreaterThan((new ArraySyntaxFixer())->getPriority(), $this->fixer->getPriority());
        static::assertGreaterThan((new StrictComparisonFixer())->getPriority(), $this->fixer->getPriority());
        static::assertGreaterThan((new YodaStyleFixer())->getPriority(), $this->fixer->getPriority());
    }

    public function testIsRisky(): void
    {
        static::assertFalse($this->fixer->isRisky());
    }

    /**
     * @param string      $expected
     * @param null|string $input
     *
     * @dataProvider provideFixCases
     */
    public function testFix(string $expected, string $input = null): void
    {
        $this->doTest($expected, $input);
    }

    public function provideFixCases(): \Generator
    {
        yield ['<?php $x = [] == json_decode($x);', '<?php $x = empty(json_decode($x));'];
        yield ['<?php $x = [] != json_decode($x);', '<?php $x = !empty(json_decode($x));'];
        yield ['<?php $x = [] != json_decode($x);', '<?php $x = ! empty(json_decode($x));'];
        yield ['<?php $x = [] != json_decode($x);', '<?php $x = ! empty( json_decode($x) );'];

        yield ['<?php $x = [] == json_decode($x).".dist";', '<?php $x = empty(json_decode($x)).".dist";'];
        yield ['<?php $x = [] != json_decode($x).".dist";', '<?php $x = !empty(json_decode($x)).".dist";'];

        yield [
            '<?php $x =
                [] == json_decode
                    (
                        $x
                    )

                ;',
            '<?php $x =
                empty

                (
                    json_decode
                    (
                        $x
                    )

                )

                ;',
        ];
        yield [
            '<?php $x = /**/[] == /**/ /** x*//**//** */json_decode($x)/***//*xx*/;',
            '<?php $x = /**/empty/**/ /** x*/(/**//** */json_decode($x)/***/)/*xx*/;',
        ];
        yield [
            '<?php $x = [] == ([] == $x ? z([] == $y) : z([] == $z));',
            '<?php $x = empty(empty($x) ? z(empty($y)) : z(empty($z)));',
        ];
        yield [
            '<?php $x = [] == ($x = array());',
            '<?php $x = empty($x = array());',
        ];
        yield [
            '<?php $d= [] == ($a)/*=?*/?>',
            "<?php \$d= empty(\n(\$a)/*=?*/\n)?>",
        ];

        // edge cases: empty wrapped into a binary operations
        yield [
            '<?php $result = (false === ([] == $a)); ?>',
            '<?php $result = (false === empty($a)); ?>',
        ];
        yield [
            '<?php $result = (([] == $a) === false); ?>',
            '<?php $result = (empty($a) === false); ?>',
        ];
        yield [
            '<?php $result = (false !== ([] == $a)); ?>',
            '<?php $result = (false !== empty($a)); ?>',
        ];
        yield [
            '<?php $result = (([] == $a) !== false); ?>',
            '<?php $result = (empty($a) !== false); ?>',
        ];
        yield [
            '<?php $result = (false == ([] == $a)); ?>',
            '<?php $result = (false == empty($a)); ?>',
        ];
        yield [
            '<?php $result = (([] == $a) == false); ?>',
            '<?php $result = (empty($a) == false); ?>',
        ];
        yield [
            '<?php $result = (false != ([] == $a)); ?>',
            '<?php $result = (false != empty($a)); ?>',
        ];
        yield [
            '<?php $result = (([] == $a) != false); ?>',
            '<?php $result = (empty($a) != false); ?>',
        ];
        yield [
            '<?php $result = (false <> ([] == $a)); ?>',
            '<?php $result = (false <> empty($a)); ?>',
        ];
        yield [
            '<?php $result = (([] == $a) <> false); ?>',
            '<?php $result = (empty($a) <> false); ?>',
        ];
        yield [
            '<?php if ([] == $x) echo "foo"; ?>',
            '<?php if (empty($x)) echo "foo"; ?>',
        ];
        // check with logical operator
        yield [
            '<?php if ([] == $x && $y) echo "foo"; ?>',
            '<?php if (empty($x) && $y) echo "foo"; ?>',
        ];
        yield [
            '<?php if ([] == $x || $y) echo "foo"; ?>',
            '<?php if (empty($x) || $y) echo "foo"; ?>',
        ];
        yield [
            '<?php if ([] == $x xor $y) echo "foo"; ?>',
            '<?php if (empty($x) xor $y) echo "foo"; ?>',
        ];
        yield [
            '<?php if ([] == $x and $y) echo "foo"; ?>',
            '<?php if (empty($x) and $y) echo "foo"; ?>',
        ];
        yield [
            '<?php if ([] == $x or $y) echo "foo"; ?>',
            '<?php if (empty($x) or $y) echo "foo"; ?>',
        ];
        yield [
            '<?php if (([] == $u or $v) and ($w || [] == $x) xor ([] != $y and $z)) echo "foo"; ?>',
            '<?php if ((empty($u) or $v) and ($w || empty($x)) xor (!empty($y) and $z)) echo "foo"; ?>',
        ];
    }
}
