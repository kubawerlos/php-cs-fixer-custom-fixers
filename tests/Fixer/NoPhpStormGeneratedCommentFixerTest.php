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
        $this->assertSame(0, $this->fixer->getPriority());
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
