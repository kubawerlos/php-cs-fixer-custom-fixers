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
    public function testIsRisky(): void
    {
        self::assertFalse($this->fixer->isRisky());
    }

    /**
     * @dataProvider provideFixCases
     */
    public function testFix(string $expected, ?string $input = null): void
    {
        $this->doTest($expected, $input);
    }

    public static function provideFixCases(): iterable
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
/*
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
/** class Bar */
class Bar {}
',
            '<?php
/**
 * Created by PhpStorm.
 * User: root
 * Date: 01.01.70
 * Time: 12:34
 */
namespace Foo;
/** class Bar */
class Bar {}
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
