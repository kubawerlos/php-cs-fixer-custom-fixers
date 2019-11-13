<?php

declare(strict_types = 1);

namespace Tests\Fixer;

/**
 * @internal
 *
 * @covers \PhpCsFixerCustomFixers\Fixer\NoTwoConsecutiveEmptyLinesFixer
 */
final class NoTwoConsecutiveEmptyLinesFixerTest extends AbstractFixerTestCase
{
    public function testIsRisky(): void
    {
        static::assertFalse($this->fixer->isRisky());
    }

    public function testSuccessorName(): void
    {
        static::assertContains('no_extra_blank_lines', $this->fixer->getSuccessorsNames());
    }

    /**
     * @dataProvider provideFixCases
     */
    public function testFix(string $expected, ?string $input = null): void
    {
        $this->doTest($expected, $input);
    }

    public function provideFixCases(): iterable
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
