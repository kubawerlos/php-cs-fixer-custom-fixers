<?php

declare(strict_types = 1);

namespace Tests\Fixer;

use PhpCsFixer\Fixer\Whitespace\NoTrailingWhitespaceFixer;
use PhpCsFixer\Fixer\Whitespace\NoWhitespaceInBlankLineFixer;

/**
 * @internal
 *
 * @covers \PhpCsFixerCustomFixers\Fixer\NoTwoConsecutiveEmptyLinesFixer
 */
final class NoTwoConsecutiveEmptyLinesFixerTest extends AbstractFixerTestCase
{
    public function testPriority() : void
    {
        static::assertLessThan((new NoTrailingWhitespaceFixer())->getPriority(), $this->fixer->getPriority());
        static::assertLessThan((new NoWhitespaceInBlankLineFixer())->getPriority(), $this->fixer->getPriority());
    }

    public function testIsRisky() : void
    {
        static::assertFalse($this->fixer->isRisky());
    }

    /**
     * @param string      $expected
     * @param string|null $input
     *
     * @dataProvider provideFixCases
     */
    public function testFix(string $expected, string $input = null) : void
    {
        $this->doTest($expected, $input);
    }

    public function provideFixCases() : \Iterator
    {
        yield [
            '<?php

class Foo {};
',
        ];

        yield [
            '<?php

class Foo {};
',
            '<?php


class Foo {};
',
        ];

        yield [
            '<?php
namespace Foo;

class Foo {};
',
        ];

        yield [
            '<?php
namespace Foo;

class Foo {};
',
            '<?php
namespace Foo;


class Foo {};
',
        ];

        yield [
            '<?php
namespace Foo;

class Foo {};
',
            '<?php
namespace Foo;




class Foo {};
',
        ];
    }
}
