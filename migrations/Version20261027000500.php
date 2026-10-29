<?php
namespace DoctrineMigrations;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
final class Version20261027000500 extends AbstractMigration
{
    public function getDescription(): string { return 'Recoverable trash for pages and articles'; }
    public function up(Schema $schema): void { $schema->getTable('Page')->addColumn('deletedAt','datetime_immutable',['notnull'=>false]); }
    public function down(Schema $schema): void { $schema->getTable('Page')->dropColumn('deletedAt'); }
}
