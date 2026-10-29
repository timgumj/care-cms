<?php
namespace DoctrineMigrations;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
final class Version20261027000200 extends AbstractMigration
{
    public function getDescription(): string { return 'Add explicit language pairs and Care Studio page shells'; }
    public function up(Schema $schema): void {
        $this->addSql("ALTER TABLE Page ADD COLUMN locale VARCHAR(10) DEFAULT 'de-AT' NOT NULL");
        $this->addSql('ALTER TABLE Page ADD COLUMN translationKey VARCHAR(100) DEFAULT NULL');
        $this->addSql("ALTER TABLE Page ADD COLUMN headerHtml CLOB DEFAULT '' NOT NULL");
        $this->addSql("ALTER TABLE Page ADD COLUMN footerHtml CLOB DEFAULT '' NOT NULL");
        $this->addSql('CREATE UNIQUE INDEX uniq_page_translation_language ON Page (translationKey, locale)');
    }
    public function down(Schema $schema): void {
        $this->abortIf(true,'Restore the pre-migration backup to roll back multilingual content safely.');
    }
}
