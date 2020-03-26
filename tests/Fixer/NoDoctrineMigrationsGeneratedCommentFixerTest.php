<?php

declare(strict_types=1);

namespace Tests\Fixer;

/**
 * @internal
 *
 * @covers \PhpCsFixerCustomFixers\Fixer\NoDoctrineMigrationsGeneratedCommentFixer
 */
final class NoDoctrineMigrationsGeneratedCommentFixerTest extends AbstractFixerTestCase
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
        yield 'do not remove when comments were changed' => [
            '<?php
namespace Migrations;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
/**
 * This migration will change the world!
 */
final class Version20180609123456 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this query is the best
        $this->addSql("UPDATE t1 SET col1 = col1 + 1");
    }
    public function down(Schema $schema) : void
    {
        $this->addSql("UPDATE t1 SET col1 = col1 - 1");
    }
}
',
        ];

        yield 'handle standard case' => [
            '<?php
namespace Migrations;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
final class Version20180609123456 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        $this->addSql("UPDATE t1 SET col1 = col1 + 1");
    }
    public function down(Schema $schema) : void
    {
        $this->addSql("UPDATE t1 SET col1 = col1 - 1");
    }
}
',
            '<?php
namespace Migrations;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20180609123456 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql("UPDATE t1 SET col1 = col1 + 1");
    }
    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql("UPDATE t1 SET col1 = col1 - 1");
    }
}
',
        ];

        yield 'handle without class comment' => [
            '<?php
namespace Migrations;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
final class Version20180609123456 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // Doing this is important
        $this->addSql("UPDATE t1 SET col1 = col1 + 1");
    }
    public function down(Schema $schema) : void
    {
        $this->addSql("UPDATE t1 SET col1 = col1 - 1");
    }
}
',
            '<?php
namespace Migrations;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
final class Version20180609123456 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        // Doing this is important
        $this->addSql("UPDATE t1 SET col1 = col1 + 1");
    }
    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql("UPDATE t1 SET col1 = col1 - 1");
    }
}
',
        ];

        yield 'handle with mixed comments' => [
            '<?php
namespace Migrations;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
final class Version20180609123456 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // Doing this is important
        $this->addSql("UPDATE t1 SET col1 = col1 + 1");
    }
    public function down(Schema $schema) : void
    {
        $this->addSql("UPDATE t1 SET col1 = col1 - 1");
    }
}
',
            '<?php
namespace Migrations;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20180609123456 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        // Doing this is important
        $this->addSql("UPDATE t1 SET col1 = col1 + 1");
    }
    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql("UPDATE t1 SET col1 = col1 - 1");
    }
}
',
        ];
    }
}
