<?php
namespace DoctrineMigrations;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
final class Version20261027000400 extends AbstractMigration
{
 public function getDescription(): string {return 'Hashed, expiring, single-use administrator password reset tokens';}
 public function up(Schema $schema): void {
  $table=$schema->createTable('admin_password_reset');$table->addColumn('user_id','integer');$table->setPrimaryKey(['user_id']);
  $table->addColumn('token_hash','string',['length'=>64]);$table->addUniqueIndex(['token_hash']);
  $table->addColumn('expires_at','integer');$table->addColumn('password_version','string',['length'=>64]);
 }
 public function down(Schema $schema): void {$schema->dropTable('admin_password_reset');}
}
