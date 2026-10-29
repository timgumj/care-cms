<?php
namespace DoctrineMigrations;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
final class Version20261027000300 extends AbstractMigration
{
    public function getDescription(): string { return 'Private single-use email login challenges; no plaintext codes'; }
    public function up(Schema $schema): void {
        $table=$schema->createTable('admin_email_challenge');
        $table->addColumn('user_id','integer'); $table->setPrimaryKey(['user_id']);
        $table->addColumn('code_hash','string',['length'=>64]);
        $table->addColumn('session_hash','string',['length'=>64]);
        $table->addColumn('expires_at','integer'); $table->addColumn('attempts','integer',['default'=>0]);
    }
    public function down(Schema $schema): void { $schema->dropTable('admin_email_challenge'); }
}
