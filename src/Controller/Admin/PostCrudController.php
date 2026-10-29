<?php
namespace App\Controller\Admin;
use App\Entity\Page;
use EasyCorp\Bundle\EasyAdminBundle\Config\{Action, Actions, Crud};
final class PostCrudController extends PageCrudController
{
    protected function isPostEditor(): bool { return true; }
    public function createEntity(string $entityFqcn): ?object { $post=parent::createEntity($entityFqcn);$post->path='/artikel/entwurf-'.bin2hex(random_bytes(8)).'/';return $post; }
    public function configureFields(string $pageName): iterable {
        foreach(parent::configureFields($pageName) as $field) {
            $property=$field->getAsDto()->getProperty();
            if(in_array($property,['translationKey','canonical'],true) || ($property==='path' && $pageName===Crud::PAGE_NEW))continue;
            yield $field;
        }
    }
    public function configureCrud(Crud $crud): Crud { return parent::configureCrud($crud)->setEntityLabelInPlural('Articles')->setEntityLabelInSingular('Article'); }
    public function configureActions(Actions $actions): Actions {
        return parent::configureActions($actions)->add(Crud::PAGE_INDEX, Action::new('englishDraft','Create English draft','internal:copy')
            ->linkToRoute('admin_content_action',fn(Page $page)=>['id'=>$page->id,'operation'=>'english'])
            ->displayIf(fn(Page $page)=>$page->locale==='de-AT' && !$page->deletedAt));
    }
}
