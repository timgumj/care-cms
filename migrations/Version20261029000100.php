<?php
namespace DoctrineMigrations;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
final class Version20261029000100 extends AbstractMigration
{
 public function getDescription(): string { return 'Single practitioner demo booking and availability (SQLite)'; }
 public function up(Schema $schema): void {
  $this->addSql('CREATE TABLE booking_schedule (id INTEGER PRIMARY KEY, revision INTEGER NOT NULL DEFAULT 0, settings CLOB NOT NULL)');
  $this->addSql("INSERT INTO booking_schedule (id, revision, settings) VALUES (1,0,'{}')");
  $this->addSql("CREATE TABLE appointment (id INTEGER PRIMARY KEY AUTOINCREMENT, starts_at INTEGER NOT NULL, ends_at INTEGER NOT NULL, appointment_type VARCHAR(30) NOT NULL, attendee_name VARCHAR(100) NOT NULL, email VARCHAR(180) NOT NULL, cancel_hash VARCHAR(64) NOT NULL UNIQUE, status VARCHAR(20) NOT NULL DEFAULT 'confirmed', created_at INTEGER NOT NULL)");
  $this->addSql('CREATE INDEX appointment_overlap ON appointment (status, starts_at, ends_at)');
 }
 public function down(Schema $schema): void { $this->addSql('DROP TABLE appointment');$this->addSql('DROP TABLE booking_schedule'); }
}
