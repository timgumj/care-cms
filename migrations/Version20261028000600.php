<?php
namespace DoctrineMigrations;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
final class Version20261028000600 extends AbstractMigration
{
    public function getDescription():string {return 'Administrator/editor accounts, invitations and account revocation';}
    public function up(Schema $schema):void {
        $table=$schema->getTable('AdminUser');
        $table->addColumn('displayName','string',['length'=>120,'default'=>'']);
        $table->addColumn('accessRole','string',['length'=>32,'default'=>'ROLE_ADMIN']);
        $table->addColumn('enabled','boolean',['default'=>true]);
        $table->addColumn('passwordSet','boolean',['default'=>true]);
        $table->addColumn('securityVersion','integer',['default'=>1]);
    }
    public function down(Schema $schema):void {foreach(['displayName','accessRole','enabled','passwordSet','securityVersion'] as $column)$schema->getTable('AdminUser')->dropColumn($column);}
}
