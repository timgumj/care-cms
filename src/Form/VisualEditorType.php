<?php
namespace App\Form;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
class VisualEditorType extends AbstractType
{
    public function getParent(): string { return TextareaType::class; }
    public function getBlockPrefix(): string { return 'visual_editor'; }
}
