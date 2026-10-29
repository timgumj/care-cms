<?php
namespace App\Controller\Admin;
use App\Entity\Page;
use App\Form\VisualEditorType;
use EasyCorp\Bundle\EasyAdminBundle\Config\Assets;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Filter\{ChoiceFilter,BooleanFilter};
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Field\{TextField,TextEditorField,TextareaField,BooleanField,DateTimeField,ChoiceField,FormField};
class PageCrudController extends AbstractCrudController
{
    protected function isPostEditor(): bool { return false; }
    public function __construct(private \App\Security\EditorContentSanitizer $contentSanitizer,private \App\Service\ContentLifecycle $lifecycle,private ?\Symfony\Component\HttpFoundation\RequestStack $requests=null) {}
    public static function getEntityFqcn(): string { return Page::class; }
    public function configureCrud(Crud $crud): Crud { return $crud->setEntityLabelInPlural('Pages')->setEntityLabelInSingular('Seite')->setSearchFields(['title','description','path','kind','locale','translationKey'])->setDefaultSort(['path'=>'ASC'])->addFormTheme('admin/form/visual_editor.html.twig'); }
    public function configureAssets(Assets $assets): Assets {
        return $assets->addCssFile('/assets/vendor/grapesjs/grapes.min.css')
            ->addCssFile('/assets/page-editor.css?v=12')
            ->addJsFile('/assets/editor-recovery.js?v=3')
            ->addJsFile('/assets/vendor/grapesjs/grapes.min.js')
            ->addJsFile('/assets/page-editor.js?v=8');
    }
    public function configureFilters(Filters $filters): Filters { return $filters->add(ChoiceFilter::new('locale','Sprache')->setChoices(['Deutsch'=>'de-AT','English'=>'en-AT']))->add(ChoiceFilter::new('kind','Inhaltstyp')->setChoices(['Seite'=>'Seite','Beitrag'=>'Beitrag']))->add(BooleanFilter::new('published','VerÃ¶ffentlicht')); }
    public function configureFields(string $pageName): iterable {
        yield FormField::addTab('Inhalt','internal:file-lines')->onlyOnForms();
        yield TextField::new('title','Seitentitel / SEO-Titel')->setColumns(12);
        yield TextareaField::new('description','Seitenbeschreibung')->setMaxLength(180)->setNumOfRows(3)->setColumns(12)->setHelp('Diese Beschreibung wird auch als Meta-Beschreibung verwendet.');
        yield BooleanField::new('published','VerÃ¶ffentlicht')->setColumns(6);
        yield TextareaField::new('content','Visueller Seiteneditor')->hideOnIndex()->setFormType(VisualEditorType::class)->setColumns(12)->setNumOfRows(20);
        yield FormField::addTab('Footer / Fußzeile','internal:file-lines')->onlyOnForms();
        yield TextareaField::new('footerHtml','Footer / certifications')->hideOnIndex()->setFormType(VisualEditorType::class)->setColumns(12)->setNumOfRows(16)->setHelp('Edit contact details, opening hours, links and replace the four demo certificate images using the media library.');
        yield FormField::addTab('Sprache & SEO','internal:gear')->onlyOnForms();
        yield TextField::new('path','URL-Pfad')->setDisabled($pageName !== Crud::PAGE_NEW)->setHelp('Bestehende Pfade bleiben zum Schutz der URLs gesperrt.')->setColumns(6);
        yield ChoiceField::new('locale','Sprache')->setChoices(['Deutsch'=>'de-AT','English'=>'en-AT'])->setDisabled($pageName !== Crud::PAGE_NEW || $this->isPostEditor())->setColumns(6);
        yield TextField::new('translationKey','Ãœbersetzungsgruppe')->hideOnIndex()->setHelp('ZusammengehÃ¶rige deutsche und englische Seiten verwenden denselben SchlÃ¼ssel (z. B. about).')->setColumns(12);
        yield TextField::new('canonical','Canonical URL')->hideOnIndex()->setColumns(12);
        yield DateTimeField::new('updatedAt','Aktualisiert')->hideOnForm();
    }
    public function configureActions(\EasyCorp\Bundle\EasyAdminBundle\Config\Actions $actions): \EasyCorp\Bundle\EasyAdminBundle\Config\Actions {
        return $actions->disable(\EasyCorp\Bundle\EasyAdminBundle\Config\Action::BATCH_DELETE, \EasyCorp\Bundle\EasyAdminBundle\Config\Action::DELETE)
            ->add(Crud::PAGE_INDEX, \EasyCorp\Bundle\EasyAdminBundle\Config\Action::new('moveToTrash','Move to Trash','internal:delete')->setCssClass('content-trash-action')->linkToRoute('admin_content_action', fn(Page $page)=>['id'=>$page->id,'operation'=>'trash']));
    }
    public function createIndexQueryBuilder(\EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDto $searchDto, \EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto $entityDto, \EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection $fields, \EasyCorp\Bundle\EasyAdminBundle\Collection\FilterCollection $filters): \Doctrine\ORM\QueryBuilder {
        $query=parent::createIndexQueryBuilder($searchDto,$entityDto,$fields,$filters);
        return $query->andWhere('entity.deletedAt IS NULL')->andWhere($this->isPostEditor()?'entity.kind = :contentKind':'entity.kind != :contentKind')->setParameter('contentKind','Beitrag');
    }
    public function createEntity(string $entityFqcn): ?object { $page=new Page();$page->kind=$this->isPostEditor()?'Beitrag':'Seite';$page->published=false;$page->locale='de-AT';$this->lifecycle->applyTheme($page);return $page; }
    public function deleteEntity(EntityManagerInterface $em, object $entity): void { $this->lifecycle->trash($entity); }
    private function savedMarker(Page $page,bool $created): void { $request=$this->requests?->getCurrentRequest();if($request&&$request->hasSession()&&!$request->isXmlHttpRequest())$request->getSession()->set('_editor_saved_page',['id'=>$page->id,'kind'=>$page->kind==='Beitrag'?'post':'page','created'=>$created]); }
    public function persistEntity(EntityManagerInterface $em, $entity): void { $entity->kind=$this->isPostEditor()?'Beitrag':'Seite';if($this->isPostEditor())$this->lifecycle->preparePost($entity);$entity->content=$this->contentSanitizer->sanitize($entity->content);$entity->footerHtml=$this->contentSanitizer->sanitize($entity->footerHtml);parent::persistEntity($em,$entity);$this->savedMarker($entity,true); }
    public function updateEntity(EntityManagerInterface $em, $entity): void { if($entity->deletedAt)throw $this->createNotFoundException();if(($em->getUnitOfWork()->getOriginalEntityData($entity)['content']??null)!==$entity->content)$entity->content=$this->contentSanitizer->sanitize($entity->content); if(($em->getUnitOfWork()->getOriginalEntityData($entity)['footerHtml']??null)!==$entity->footerHtml)$entity->footerHtml=$this->contentSanitizer->sanitize($entity->footerHtml); $entity->updatedAt=new \DateTimeImmutable(); parent::updateEntity($em,$entity);$this->savedMarker($entity,false); }
}

