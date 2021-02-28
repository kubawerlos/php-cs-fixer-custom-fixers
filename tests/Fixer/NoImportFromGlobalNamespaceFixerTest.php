<?php

/*
 * This file is part of PHP CS Fixer: custom fixers.
 *
 * (c) 2018 Kuba Werłos
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Tests\Fixer;

/**
 * @internal
 *
 * @covers \PhpCsFixerCustomFixers\Fixer\NoImportFromGlobalNamespaceFixer
 */
final class NoImportFromGlobalNamespaceFixerTest extends AbstractFixerTestCase
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

    /**
     * @return iterable<array{0: string, 1?: string}>
     */
    public static function provideFixCases(): iterable
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
class Bar {
    public function __construct(DateTime $dateTime) {}
}
',
            '<?php
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
    use \DateTime;
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

        yield [
            '<?php
namespace Foo;
class A {
    public function __construct(\DateTime $d) {}
}
namespace Bar;
class A {
    public function __construct(DateTime $d) {}
}
namespace Baz;
class A {
    public function __construct(\DateTime $d) {}
}
',
            '<?php
namespace Foo;
use DateTime;
class A {
    public function __construct(DateTime $d) {}
}
namespace Bar;
class A {
    public function __construct(DateTime $d) {}
}
namespace Baz;
use DateTime;
class A {
    public function __construct(DateTime $d) {}
}
',
        ];

        yield [
            '<?php
namespace Foo;
class Baz {
    const Bar = "THE_BAR";
    const C = 4;
}
',
            '<?php
namespace Foo;
use Bar;
class Baz {
    const Bar = "THE_BAR";
    const C = 4;
}
',
        ];

        yield [
            '<?php
namespace Foo;
/**
 * The class
 */
class Bar {
    /**
     * @param \DateTime $a
     */
    public function __construct($a) {}
}
',
            '<?php
namespace Foo;
/**
 * The class
 */
use DateTime;
class Bar {
    /**
     * @param DateTime $a
     */
    public function __construct($a) {}
}
',
        ];

        yield [
            '<?php
                namespace Foo;
                function Bar() {}
            ',
            '<?php
                namespace Foo;
                use Bar;
                function Bar() {}
            ',
        ];

        yield [
            '<?php
                namespace N1;  new \DateTime();
                namespace N2;  new \DateTime();
                namespace N3;  new \DateTime();
                namespace N4;  new \DateTime();
                namespace N5;  new \DateTime();
                namespace N6;   new \DateTime(); new \stdClass();
            ',
            '<?php
                namespace N1; use DateTime; new DateTime();
                namespace N2; use DateTime; new DateTime();
                namespace N3; use DateTime; new DateTime();
                namespace N4; use DateTime; new DateTime();
                namespace N5; use DateTime; new DateTime();
                namespace N6; use DateTime; use stdClass; new DateTime(); new stdClass();
            ',
        ];
    }

    /**
     * @requires PHP ^8.0
     */
    public function testFixOnPhp8(): void
    {
        $this->doTest(
            '<?php
                namespace Foo;
                function f(\Bar | \Baz $x) {}
                ',
            '<?php
                namespace Foo;
                use Bar;
                use Baz;
                function f(Bar | Baz $x) {}
                '
        );
    }
}
