<?php
namespace DoctrineMigrations;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
final class Version20261027000100 extends AbstractMigration
{
 public function getDescription(): string { return 'Local SQLite baseline: migration pages and admin accounts'; }
 public function up(Schema $schema): void {
 $this->abortIf($this->connection->getDatabasePlatform()::class !== \Doctrine\DBAL\Platforms\SQLitePlatform::class, 'This local baseline targets SQLite. Generate and test a production-platform migration after hosting is confirmed.');
 $this->addSql('CREATE TABLE AdminUser (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, email VARCHAR(180) NOT NULL, password VARCHAR(255) NOT NULL)');
 $this->addSql('CREATE UNIQUE INDEX UNIQ_943CB15BE7927C74 ON AdminUser (email)');
 $this->addSql('CREATE TABLE Page (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, path VARCHAR(255) NOT NULL, kind VARCHAR(80) NOT NULL, title VARCHAR(255) NOT NULL, description CLOB NOT NULL, content CLOB NOT NULL, canonical VARCHAR(512) NOT NULL, schema CLOB NOT NULL, bodyClass CLOB NOT NULL, headExtra CLOB NOT NULL, published BOOLEAN NOT NULL, updatedAt DATETIME NOT NULL)');
 $this->addSql('CREATE UNIQUE INDEX UNIQ_B438191EB548B0F ON Page (path)');
 }
 public function down(Schema $schema): void { $this->addSql('DROP TABLE page'); $this->addSql('DROP TABLE AdminUser'); }
}

