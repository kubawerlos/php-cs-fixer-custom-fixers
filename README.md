# PHP CS Fixer: custom fixers

[![Latest stable version](https://img.shields.io/packagist/v/kubawerlos/php-cs-fixer-custom-fixers.svg?label=current%20version)](https://packagist.org/packages/kubawerlos/php-cs-fixer-custom-fixers)
[![PHP version](https://img.shields.io/packagist/php-v/kubawerlos/php-cs-fixer-custom-fixers.svg)](https://php.net)
[![License](https://img.shields.io/github/license/kubawerlos/php-cs-fixer-custom-fixers.svg)](LICENSE)
![Tests](https://img.shields.io/badge/tests-3454-brightgreen.svg)
[![Downloads](https://img.shields.io/packagist/dt/kubawerlos/php-cs-fixer-custom-fixers.svg)](https://packagist.org/packages/kubawerlos/php-cs-fixer-custom-fixers)

[![CI Status](https://github.com/kubawerlos/php-cs-fixer-custom-fixers/workflows/CI/badge.svg?branch=main)](https://github.com/kubawerlos/php-cs-fixer-custom-fixers/actions)
[![Code coverage](https://img.shields.io/coveralls/github/kubawerlos/php-cs-fixer-custom-fixers/main.svg)](https://coveralls.io/github/kubawerlos/php-cs-fixer-custom-fixers?branch=main)
[![Mutation testing badge](https://img.shields.io/endpoint?style=flat&url=https%3A%2F%2Fbadge-api.stryker-mutator.io%2Fgithub.com%2Fkubawerlos%2Fphp-cs-fixer-custom-fixers%2Fmain)](https://dashboard.stryker-mutator.io/reports/github.com/kubawerlos/php-cs-fixer-custom-fixers/main)
[![Psalm type coverage](https://shepherd.dev/github/kubawerlos/php-cs-fixer-custom-fixers/coverage.svg)](https://shepherd.dev/github/kubawerlos/php-cs-fixer-custom-fixers)

A set of custom fixers for [PHP CS Fixer](https://github.com/FriendsOfPHP/PHP-CS-Fixer).

## Installation
PHP CS Fixer: custom fixers can be installed by running:
```bash
composer require --dev kubawerlos/php-cs-fixer-custom-fixers
```


## Usage
In your PHP CS Fixer configuration register fixers and use them:
```diff
 <?php
 return (new PhpCsFixer\Config())
+    ->registerCustomFixers(new PhpCsFixerCustomFixers\Fixers())
     ->setRules([
         '@PSR2' => true,
         'array_syntax' => ['syntax' => 'short'],
+        PhpCsFixerCustomFixers\Fixer\NoLeadingSlashInGlobalNamespaceFixer::name() => true,
+        PhpCsFixerCustomFixers\Fixer\PhpdocNoSuperfluousParamFixer::name() => true,
     ]);
```
:warning: When PHP CS Fixer is installed via [`php-cs-fixer/shim`](https://github.com/PHP-CS-Fixer/shim) package,
requiring bootstrap may be needed to load `PhpCsFixerCustomFixers` classes:
```php
require __DIR__ . '/vendor/kubawerlos/php-cs-fixer-custom-fixers/bootstrap.php';
```


## Fixers
#### CommentSurroundedBySpacesFixer
Comments must be surrounded by spaces.
```diff
 <?php
-/*foo*/
+/* foo */
```

#### CommentedOutFunctionFixer
The configured functions must be commented out.
  *Risky: when any of the configured functions have side effects or are overwritten.*
Configuration options:
- `functions` (`array`): list of functions to comment out; defaults to `['print_r', 'var_dump', 'var_export']`
```diff
 <?php
-var_dump($x);
+//var_dump($x);
```

#### ConstructorEmptyBracesFixer
Constructor's empty braces must be single line.
```diff
 <?php
 class Foo {
     public function __construct(
         $param1,
         $param2
-    ) {
-    }
+    ) {}
 }
```

#### DataProviderNameFixer
Data provider names must match the name of the test.
  DEPRECATED: use `php_unit_data_provider_name` instead.
  *Risky: when relying on name of data provider function.*
Configuration options:
- `prefix` (`string`): prefix that replaces "test"; defaults to `'provide'`
- `suffix` (`string`): suffix to be added at the end"; defaults to `'Cases'`
```diff
 <?php
 class FooTest extends TestCase {
     /**
-     * @dataProvider dataProvider
+     * @dataProvider provideSomethingCases
      */
     public function testSomething($expected, $actual) {}
-    public function dataProvider() {}
+    public function provideSomethingCases() {}
 }
```

#### DataProviderReturnTypeFixer
The return type of PHPUnit data provider must be `iterable`.
  DEPRECATED: use `php_unit_data_provider_return_type` instead.
  *Risky: when relying on signature of data provider.*
```diff
 <?php
 class FooTest extends TestCase {
     /**
      * @dataProvider provideSomethingCases
      */
     public function testSomething($expected, $actual) {}
-    public function provideSomethingCases(): array {}
+    public function provideSomethingCases(): iterable {}
 }
```

#### DataProviderStaticFixer
Data providers must be static.
  DEPRECATED: use `php_unit_data_provider_static` instead.
  *Risky: when `force` is set to `true`.*
Configuration options:
- `force` (`bool`): whether to make static data providers having dynamic class calls; defaults to `false`
```diff
 <?php
 class FooTest extends TestCase {
     /**
      * @dataProvider provideSomethingCases
      */
     public function testSomething($expected, $actual) {}
-    public function provideSomethingCases() {}
+    public static function provideSomethingCases() {}
 }
```

#### DeclareAfterOpeningTagFixer
Declare statement for strict types must be placed in the same line, after opening tag.
```diff
-<?php
+<?php declare(strict_types=1);
 $foo;
-declare(strict_types=1);
 $bar;
```

#### EmptyFunctionBodyFixer
Empty function body must be abbreviated as `{}` and placed on the same line as the previous symbol, separated by a space.
```diff
 <?php function foo(
     int $x
-)
-{
-}
+) {}
```

#### InternalClassCasingFixer
Classes defined internally by extension or core must be referenced with the correct case.
  DEPRECATED: use `class_reference_name_casing` instead.
```diff
 <?php
-$foo = new STDClass();
+$foo = new stdClass();
```

#### IssetToArrayKeyExistsFixer
Function `array_key_exists` must be used over `isset` when possible.
  *Risky: when array is not defined, is multi-dimensional or behaviour is relying on the null value.*
```diff
 <?php
-if (isset($array[$key])) {
+if (array_key_exists($key, $array)) {
     echo $array[$key];
 }
```

#### MultilineCommentOpeningClosingAloneFixer
Multiline comments or PHPDocs must contain an opening and closing line with no additional content.
```diff
 <?php
-/** Hello
+/**
+ * Hello
  * World!
  */;
```

#### MultilinePromotedPropertiesFixer
A constructor with promoted properties must have them in separate lines.
Configuration options:
- `minimum_number_of_parameters` (`int`): minimum number of parameters in the constructor to fix; defaults to `1`
- `keep_blank_lines` (`bool`): whether to keep blank lines between properties; defaults to `false`
```diff
 <?php class Foo {
-    public function __construct(private array $a, private bool $b, private int $i) {}
+    public function __construct(
+        private array $a,
+        private bool $b,
+        private int $i
+    ) {}
 }
```

#### NoCommentedOutCodeFixer
There can be no commented out code.
```diff
 <?php
-//var_dump($_POST);
 print_r($_POST);
```

#### NoDoctrineMigrationsGeneratedCommentFixer
There can be no comments generated by Doctrine Migrations.
```diff
 <?php
 namespace Migrations;
 use Doctrine\DBAL\Schema\Schema;
-/**
- * Auto-generated Migration: Please modify to your needs!
- */
 final class Version20180609123456 extends AbstractMigration
 {
     public function up(Schema $schema)
     {
-        // this up() migration is auto-generated, please modify it to your needs
         $this->addSql("UPDATE t1 SET col1 = col1 + 1");
     }
     public function down(Schema $schema)
     {
-        // this down() migration is auto-generated, please modify it to your needs
         $this->addSql("UPDATE t1 SET col1 = col1 - 1");
     }
 }
```

#### NoDuplicatedArrayKeyFixer
There can be no duplicate array keys.
Configuration options:
- `ignore_expressions` (`bool`): whether to keep duplicated expressions (as they might return different values) or not; defaults to `true`
```diff
 <?php
 $x = [
-    "foo" => 1,
     "bar" => 2,
     "foo" => 3,
 ];
```

#### NoDuplicatedImportsFixer
There can be no duplicate `use` statements.
```diff
 <?php
 namespace FooBar;
 use Foo;
-use Foo;
 use Bar;
```

#### NoImportFromGlobalNamespaceFixer
There can be no imports from the global namespace.
```diff
 <?php
 namespace Foo;
-use DateTime;
 class Bar {
-    public function __construct(DateTime $dateTime) {}
+    public function __construct(\DateTime $dateTime) {}
 }
```

#### NoLeadingSlashInGlobalNamespaceFixer
Classes in the global namespace cannot contain leading slashes.
```diff
 <?php
-$x = new \Foo();
+$x = new Foo();
 namespace Bar;
 $y = new \Baz();
```

#### NoNullableBooleanTypeFixer
There can be no nullable boolean types.
  *Risky: when the null is used.*
```diff
 <?php
-function foo(?bool $bar) : ?bool
+function foo(bool $bar) : bool
 {
      return $bar;
  }
```

#### NoPhpStormGeneratedCommentFixer
There can be no comments generated by PhpStorm.
```diff
 <?php
-/**
- * Created by PhpStorm.
- * User: root
- * Date: 01.01.70
- * Time: 12:00
- */
 namespace Foo;
```

#### NoReferenceInFunctionDefinitionFixer
There must be no parameters with reference in funcion methods.
  *Risky: when rely on reference.*
```diff
 <?php
-function foo(&$x) {}
+function foo($x) {}
```

#### NoSuperfluousConcatenationFixer
There should be no superfluous concatenation of strings.
Configuration options:
- `allow_preventing_trailing_spaces` (`bool`): whether to keep concatenation if it prevents having trailing spaces in string; defaults to `false`
```diff
 <?php
-echo 'foo' . 'bar';
+echo 'foobar';
```

#### NoTrailingCommaInSinglelineFixer
Trailing comma in the list on the same line as the end of the block must be removed.
```diff
 <?php
-$x = ['foo', 'bar', ];
+$x = ['foo', 'bar'];
```

#### NoUselessCommentFixer
There must be no useless comments.
```diff
 <?php
 /**
- * Class Foo
  * Class to do something
  */
 class Foo {
     /**
-     * Get bar
      */
     function getBar() {}
 }
```

#### NoUselessDirnameCallFixer
Function `dirname` call must be removed if not needed.
```diff
 <?php
-require dirname(__DIR__) . "/vendor/autoload.php";
+require __DIR__ . "/../vendor/autoload.php";
```

#### NoUselessDoctrineRepositoryCommentFixer
There can be no comments generated by Doctrine ORM.
```diff
 <?php
-/**
- * FooRepository
- *
- * This class was generated by the Doctrine ORM. Add your own custom
- * repository methods below.
- */
 class FooRepository extends EntityRepository {}
```

#### NoUselessParenthesisFixer
There must be no useless parentheses.
```diff
 <?php
-foo(($bar));
+foo($bar);
```

#### NoUselessStrlenFixer
The `strlen` or` mb_strlen` functions should not be compared against 0.
  *Risky: when the function `strlen` is overridden.*
```diff
 <?php
-$isEmpty = strlen($string) === 0;
-$isNotEmpty = strlen($string) > 0;
+$isEmpty = $string === '';
+$isNotEmpty = $string !== '';
```

#### NumericLiteralSeparatorFixer
Numeric literals must have configured separators.
Configuration options:
- `binary` (`bool`, `null`): whether add, remove or ignore separators in binary numbers.; defaults to `false`
- `decimal` (`bool`, `null`): whether add, remove or ignore separators in decimal numbers.; defaults to `false`
- `float` (`bool`, `null`): whether add, remove or ignore separators in float numbers.; defaults to `false`
- `hexadecimal` (`bool`, `null`): whether add, remove or ignore separators in hexadecimal numbers.; defaults to `false`
- `octal` (`bool`, `null`): whether add, remove or ignore separators in octal numbers.; defaults to `false`
```diff
 <?php
-echo 0b01010100_01101000; // binary
-echo 135_798_642; // decimal
-echo 1_234.456_78e-4_321; // float
-echo 0xAE_B0_42_FC; // hexadecimal
-echo 0123_4567; // octal
+echo 0b0101010001101000; // binary
+echo 135798642; // decimal
+echo 1234.45678e-4321; // float
+echo 0xAEB042FC; // hexadecimal
+echo 01234567; // octal
```

#### PhpUnitAssertArgumentsOrderFixer
PHPUnit assertions must have expected argument before actual one.
  *Risky: when original PHPUnit methods are overwritten.*
```diff
 <?php
 class FooTest extends TestCase {
     public function testFoo() {
-        self::assertSame($value, 10);
+        self::assertSame(10, $value);
     }
 }
```

#### PhpUnitDedicatedAssertFixer
PHPUnit assertions like `assertCount` and `assertInstanceOf` must be used over `assertEquals`/`assertSame`.
  *Risky: when original PHPUnit methods are overwritten.*
```diff
 <?php
 class FooTest extends TestCase {
     public function testFoo() {
-        self::assertSame($size, count($elements));
-        self::assertSame($className, get_class($object));
+        self::assertCount($size, $elements);
+        self::assertInstanceOf($className, $object);
     }
 }
```

#### PhpUnitNoUselessReturnFixer
PHPUnit `fail`, `markTestIncomplete` and `markTestSkipped` functions should not be followed directly by return.
  *Risky: when original PHPUnit methods are overwritten.*
```diff
 <?php
 class FooTest extends TestCase {
     public function testFoo() {
         $this->markTestSkipped();
-        return;
     }
 }
```

#### PhpdocArrayStyleFixer
Generic array style should be used in PHPDoc.
```diff
 <?php
 /**
- * @return int[]
+ * @return array<int>
  */
  function foo() { return [1, 2]; }
```

#### PhpdocNoIncorrectVarAnnotationFixer
The `@var` annotations must be used correctly in code.
```diff
 <?php
-/** @var Foo $foo */
 $bar = new Foo();
```

#### PhpdocNoSuperfluousParamFixer
There must be no superfluous parameters in PHPDoc.
```diff
 <?php
 /**
  * @param bool $b
- * @param int $i
  * @param string $s this is string
- * @param string $s duplicated
  */
 function foo($b, $s) {}
```

#### PhpdocOnlyAllowedAnnotationsFixer
Only the listed annotations are allowed in PHPDoc.
Configuration options:
- `elements` (`array`): list of annotations to keep in PHPDoc; defaults to `[]`
```diff
 <?php
 /**
  * @author John Doe
- * @package foo
- * @subpackage bar
  * @version 1.0
  */
 function foo_bar() {}
```

#### PhpdocParamOrderFixer
The `@param` annotations must be in the same order as the function parameters.
  DEPRECATED: use `phpdoc_param_order` instead.
```diff
 <?php
 /**
+ * @param int $a
  * @param int $b
- * @param int $a
  * @param int $c
  */
 function foo($a, $b, $c) {}
```

#### PhpdocParamTypeFixer
The `@param` annotations must have a type.
```diff
 <?php
 /**
  * @param string $foo
- * @param        $bar
+ * @param mixed  $bar
  */
 function a($foo, $bar) {}
```

#### PhpdocSelfAccessorFixer
In PHPDoc, the class or interface element `self` should be preferred over the class name itself.
```diff
 <?php
 class Foo {
     /**
-     * @var Foo
+     * @var self
      */
      private $instance;
 }
```

#### PhpdocSingleLineVarFixer
The `@var` annotations must be on a single line if they are the only content.
```diff
 <?php
 class Foo {
-    /**
-     * @var string
-     */
+    /** @var string */
     private $name;
 }
```

#### PhpdocTypesCommaSpacesFixer
PHPDoc types commas must not be preceded by whitespace, and must be succeeded by single whitespace.
```diff
-<?php /** @var array<int,string> */
+<?php /** @var array<int, string> */
```

#### PhpdocTypesTrimFixer
PHPDoc types must be trimmed.
```diff
 <?php
 /**
- * @param null | string $x
+ * @param null|string $x
  */
 function foo($x) {}
```

#### PhpdocVarAnnotationToAssertFixer
Converts `@var` annotations to `assert` calls when used in assignments.
```diff
 <?php
-/** @var string $x */
 $x = getValue();
+assert(is_string($x));
```

#### PromotedConstructorPropertyFixer
Constructor properties must be promoted if possible.
Configuration options:
- `promote_only_existing_properties` (`bool`): whether to promote only properties that are defined in class; defaults to `false`
```diff
 <?php
 class Foo {
-    private string $bar;
-    public function __construct(string $bar) {
-        $this->bar = $bar;
+    public function __construct(private string $bar) {
     }
 }
```

#### ReadonlyPromotedPropertiesFixer
Promoted properties must readonly.
  *Risky: when property is written.*
```diff
 <?php class Foo {
     public function __construct(
-        public array $a,
-        public bool $b,
+        public readonly array $a,
+        public readonly bool $b,
     ) {}
 }
```

#### SingleSpaceAfterStatementFixer
Statements not followed by a semicolon must be followed by a single space.
Configuration options:
- `allow_linebreak` (`bool`): whether to allow statement followed by linebreak; defaults to `false`
```diff
 <?php
-$foo = new    Foo();
-echo$foo->bar();
+$foo = new Foo();
+echo $foo->bar();
```

#### SingleSpaceBeforeStatementFixer
Statements not preceded by a line break must be preceded by a single space.
```diff
 <?php
-$foo =new Foo();
+$foo = new Foo();
```

#### StringableInterfaceFixer
A class that implements the `__toString ()` method must explicitly implement the `Stringable` interface.
```diff
 <?php
-class Foo
+class Foo implements \Stringable
 {
    public function __toString()
    {
         return "Foo";
    }
 }
```


## Contributing
Request feature or report bug by creating [issue](https://github.com/kubawerlos/php-cs-fixer-custom-fixers/issues).

Alternatively, fork the repository, develop your changes, make sure everything is fine:
```bash
composer verify
```
and submit pull request.
