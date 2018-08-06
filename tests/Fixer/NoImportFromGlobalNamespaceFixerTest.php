<?php

declare(strict_types = 1);

namespace Tests\Fixer;

use PhpCsFixer\Fixer\Phpdoc\PhpdocAlignFixer;

/**
 * @internal
 *
 * @covers \PhpCsFixerCustomFixers\Fixer\NoImportFromGlobalNamespaceFixer
 */
final class NoImportFromGlobalNamespaceFixerTest extends AbstractFixerTestCase
{
    public function testPriority(): void
    {
        static::assertGreaterThan((new PhpdocAlignFixer())->getPriority(), $this->fixer->getPriority());
    }

    public function testIsRisky(): void
    {
        static::assertFalse($this->fixer->isRisky());
    }

    /**
     * @param string      $expected
     * @param string|null $input
     *
     * @dataProvider provideFixCases
     */
    public function testFix(string $expected, string $input = null): void
    {
        $this->doTest($expected, $input);
    }

    public function provideFixCases(): \Iterator
    {
        yield ['<?php
namespace Foo;
use Bar\DateTime;
class Baz {}
'];

        yield ['<?php
namespace Foo;
use DateTime\Bar;
class Baz {}
'];

        yield [
            '<?php
namespace Foo;
class Bar {
    public function __construct(\DateTime $dateTime) {}
}
',
            '<?php
namespace Foo;
use DateTime;
class Bar {
    public function __construct(DateTime $dateTime) {}
}
',
        ];
        yield [
            '<?php
namespace Foo;
class Bar {
    public function __construct(\DateTime $dateTime) {}
}
',
            '<?php
namespace Foo;
use \DateTime;
class Bar {
    public function __construct(DateTime $dateTime) {}
}
',
        ];

        yield [
            '<?php
namespace Foo;
class Bar {
    public function __construct(\DateTime $dateTime) {}
}
',
            '<?php
namespace Foo;
use DateTime;
class Bar {
    public function __construct(\DateTime $dateTime) {}
}
',
        ];

        yield [
            '<?php
namespace Foo;
class Baz {
    use DateTime;
    public function __construct() {}
}
',
            '<?php
namespace Foo;
use DateTime;
class Baz {
    use DateTime;
    public function __construct() {}
}
',
        ];

        yield [
            '<?php
namespace Foo;
class Bar {
    public function __construct() {
        new \DateTime();
        new Baz\DateTime();
        \DateTime::createFromFormat("Y-m-d");
        \DateTime\Baz::createFromFormat("Y-m-d");
        Baz::DateTime();
        $baz->DateTime();
    }
}
',
            '<?php
namespace Foo;
use DateTime;
class Bar {
    public function __construct() {
        new DateTime();
        new Baz\DateTime();
        DateTime::createFromFormat("Y-m-d");
        DateTime\Baz::createFromFormat("Y-m-d");
        Baz::DateTime();
        $baz->DateTime();
    }
}
',
        ];

        yield [
            '<?php
namespace Foo;
class Bar {
    /** 
     * @param \DateTime $a
     * @param \DateTime $b
     * @param NotDateTime $c
     * @param Baz\DateTime $d
     * @param int|\DateTime $e
     * @param \DateTime|string $f
     * @param bool|\DateTime|string $g
     * @param \DateTime\Baz $h
     * @param DateTimeBaz $i
     */
    public function __construct($a, $b, $c, $d, $e, $f, $g, $h, $i) {}
}
',
            '<?php
namespace Foo;
use DateTime;
class Bar {
    /** 
     * @param DateTime $a
     * @param \DateTime $b
     * @param NotDateTime $c
     * @param Baz\DateTime $d
     * @param int|DateTime $e
     * @param DateTime|string $f
     * @param bool|DateTime|string $g
     * @param DateTime\Baz $h
     * @param DateTimeBaz $i
     */
    public function __construct($a, $b, $c, $d, $e, $f, $g, $h, $i) {}
}
',
        ];
    }
}
