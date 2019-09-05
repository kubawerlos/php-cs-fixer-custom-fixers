<?php

declare(strict_types = 1);

namespace Tests\Fixer;

use PhpCsFixer\Fixer\PhpUnit\PhpUnitTestClassRequiresCoversFixer as PhpCsFixerPhpUnitTestClassRequiresCoversFixer;

/**
 * @author Dariusz Rumiński <dariusz.ruminski@gmail.com>
 * @author Kuba Werłos <werlos@gmail.com>
 *
 * @internal
 *
 * @covers \PhpCsFixerCustomFixers\Fixer\PhpUnitTestClassRequiresCoversFixer
 */
final class PhpUnitTestClassRequiresCoversFixerTest extends AbstractFixerTestCase
{
    public function testPriority(): void
    {
        static::assertGreaterThan((new PhpCsFixerPhpUnitTestClassRequiresCoversFixer())->getPriority(), $this->fixer->getPriority());
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
     * @dataProvider providePhpCsFixerFixCases
     */
    public function testFix(string $expected, ?string $input = null): void
    {
        $this->doTest($expected, $input);
    }

    public function provideFixCases(): iterable
    {
        yield [
            '<?php
use AcmeCorporation\FooBar;
/**
 * @covers \AcmeCorporation\FooBar
 */
class FooBarTest extends TestCase {
    public function testFoo() {
        $this->markTestSkipped();
        return;
    }
}',
            '<?php
use AcmeCorporation\FooBar;
class FooBarTest extends TestCase {
    public function testFoo() {
        $this->markTestSkipped();
        return;
    }
}',
        ];

        yield [
            '<?php
use AcmeCorporation\FooBar;
/**
 * @group foo
 * @covers \AcmeCorporation\FooBar
 */
class FooBarTest extends TestCase {
    public function testFoo() {
        $this->markTestSkipped();
        return;
    }
}',
            '<?php
use AcmeCorporation\FooBar;
/**
 * @group foo
 */
class FooBarTest extends TestCase {
    public function testFoo() {
        $this->markTestSkipped();
        return;
    }
}',
        ];

        yield [
            '<?php
use AcmeCorporation\FooBar;

/**
 * @covers \AcmeCorporation\FooBar
 */
class FooBarTest extends TestCase {
    public function testFoo() {
        $this->markTestSkipped();
        return;
    }
}',
            '<?php
use AcmeCorporation\FooBar;class FooBarTest extends TestCase {
    public function testFoo() {
        $this->markTestSkipped();
        return;
    }
}',
        ];
    }

    public function providePhpCsFixerFixCases(): iterable
    {
        return [
            'already with annotation: @covers' => [
                '<?php
                    /**
                     * @covers Foo
                     */
                    class FooTest extends \PHPUnit_Framework_TestCase {}
                ',
            ],
            'already with annotation: @coversDefaultClass' => [
                '<?php
                    /**
                     * @coversDefaultClass
                     */
                    class FooTest extends \PHPUnit_Framework_TestCase {}
                ',
            ],
            'without docblock #1' => [
                '<?php

                    /**
                     * @coversNothing
                     */
                    class FooTest extends \PHPUnit_Framework_TestCase {}
                ',
                '<?php

                    class FooTest extends \PHPUnit_Framework_TestCase {}
                ',
            ],
            'without docblock #2 (class is final)' => [
                '<?php

                    /**
                     * @coversNothing
                     */
                    final class FooTest extends \PHPUnit_Framework_TestCase {}
                ',
                '<?php

                    final class FooTest extends \PHPUnit_Framework_TestCase {}
                ',
            ],
            'without docblock #2 (class is abstract)' => [
                '<?php
                    abstract class FooTest extends \PHPUnit_Framework_TestCase {}
                ',
            ],
            'with docblock but annotation is missing' => [
                '<?php

                    /**
                     * Description.
                     *
                     * @since v2.2
                     * @coversNothing
                     */
                    final class FooTest extends \PHPUnit_Framework_TestCase {}
                ',
                '<?php

                    /**
                     * Description.
                     *
                     * @since v2.2
                     */
                    final class FooTest extends \PHPUnit_Framework_TestCase {}
                ',
            ],
            'with one-line docblock but annotation is missing' => [
                '<?php

                    /** Description. */
                    final class FooTest extends \PHPUnit_Framework_TestCase {}
                ',
            ],
            'with 2-lines docblock but annotation is missing #1' => [
                '<?php

                    /** Description.
                     * @coversNothing
                     */
                    final class FooTest extends \PHPUnit_Framework_TestCase {}
                ',
                '<?php

                    /** Description.
                     */
                    final class FooTest extends \PHPUnit_Framework_TestCase {}
                ',
            ],
            'with 2-lines docblock but annotation is missing #2' => [
                '<?php

                    /**
                     * @coversNothing
                     * Description. */
                    final class FooTest extends \PHPUnit_Framework_TestCase {}
                ',
                '<?php

                    /**
                     * Description. */
                    final class FooTest extends \PHPUnit_Framework_TestCase {}
                ',
            ],
            'with comment instead of docblock' => [
                '<?php
                    /*
                     * @covers Foo
                     */
                    /**
                     * @coversNothing
                     */
                    class FooTest extends \PHPUnit_Framework_TestCase {}
                ',
                '<?php
                    /*
                     * @covers Foo
                     */
                    class FooTest extends \PHPUnit_Framework_TestCase {}
                ',
            ],
            'not a test class' => [
                '<?php

                    class Foo {}
                ',
            ],
            'multiple classes in one file' => [
                '<?php /** */

                    use \PHPUnit\Framework\TestCase;

                    /**
                     * Foo
                     * @coversNothing
                     */
                    class FooTest extends \PHPUnit_Framework_TestCase {}

                    class Bar {}

                    /**
                     * @coversNothing
                     */
                    class Baz1 extends PHPUnit_Framework_TestCase {}

                    /**
                     * @coversNothing
                     */
                    class Baz2 extends \PHPUnit_Framework_TestCase {}

                    /**
                     * @coversNothing
                     */
                    class Baz3 extends \PHPUnit\Framework\TestCase {}

                    /**
                     * @coversNothing
                     */
                    class Baz4 extends TestCase {}
                ',
                '<?php /** */

                    use \PHPUnit\Framework\TestCase;

                    /**
                     * Foo
                     */
                    class FooTest extends \PHPUnit_Framework_TestCase {}

                    class Bar {}

                    class Baz1 extends PHPUnit_Framework_TestCase {}

                    class Baz2 extends \PHPUnit_Framework_TestCase {}

                    class Baz3 extends \PHPUnit\Framework\TestCase {}

                    class Baz4 extends TestCase {}
                ',
            ],
        ];
    }
}
