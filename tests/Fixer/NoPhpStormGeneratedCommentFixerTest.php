<?php

declare(strict_types = 1);

namespace Tests\Fixer;

/**
 * @internal
 *
 * @covers \PhpCsFixerCustomFixers\Fixer\NoPhpStormGeneratedCommentFixer
 */
final class NoPhpStormGeneratedCommentFixerTest extends AbstractFixerTestCase
{
    public function testPriority() : void
    {
        static::assertSame(0, $this->fixer->getPriority());
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
namespace Foo;
',
            '<?php
/**
 * Created by PhpStorm.
 * User: root
 * Date: 01.01.70
 * Time: 12:34
 */
namespace Foo;
',
        ];

        yield [
            '<?php

namespace Foo;
',
            '<?php

/**
 * Created by PHPStorm.
 */
namespace Foo;
',
        ];

        yield [
            '<?php

namespace Foo;
',
            '<?php
/**
 * Created by PHPStorm.
 */

namespace Foo;
',
        ];

        yield [
            '<?php


    namespace Foo;
',
            '<?php

    /**
     * Created by PHPStorm.
     */

    namespace Foo;
',
        ];

        yield [
            '<?php
                namespace Foo;
',
            '<?php
                /**
                 * Created by PHPStorm.
                 */
                namespace Foo;
',
        ];

        yield [
            '<?php
namespace Foo;
',
            '<?php
/** Created by PhpStorm */namespace Foo;
',
        ];

        yield [
            '<?php
    namespace Foo;
',
            '<?php
/** Created by PhpStorm */    namespace Foo;
',
        ];

        yield [
            '<?php
/**
 * Created by not PhpStorm.
 */
namespace Foo;
',
        ];
    }
}
