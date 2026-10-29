<?php
namespace DoctrineMigrations;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
final class Version20261029000200 extends AbstractMigration
{
 public function getDescription():string{return 'Reversible starter sites and self-hosted font library';}
 public function up(Schema $schema):void{
  $this->addSql('CREATE TABLE starter_site (id VARCHAR(16) PRIMARY KEY, pack_key VARCHAR(30) NOT NULL, name VARCHAR(100) NOT NULL, prefix VARCHAR(100) UNIQUE NOT NULL, active BOOLEAN NOT NULL DEFAULT 0, created_at INTEGER NOT NULL)');
  $this->addSql('CREATE UNIQUE INDEX starter_one_active ON starter_site (active) WHERE active=1');
  $this->addSql('CREATE TABLE site_font (id VARCHAR(64) PRIMARY KEY, label VARCHAR(80) NOT NULL, bytes INTEGER NOT NULL, created_at INTEGER NOT NULL)');
  $this->addSql('CREATE TABLE site_design (id INTEGER PRIMARY KEY, body_font VARCHAR(64) NOT NULL, heading_font VARCHAR(64) NOT NULL)');
  $this->addSql("INSERT INTO site_design VALUES (1,'pack-default','pack-default')");
 }
 public function down(Schema $schema):void{$this->addSql('DROP TABLE starter_site');$this->addSql('DROP TABLE site_font');$this->addSql('DROP TABLE site_design');}
}
