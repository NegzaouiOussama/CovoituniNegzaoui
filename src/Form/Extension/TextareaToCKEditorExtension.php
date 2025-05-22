<?php

namespace App\Form\Extension;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TextareaToCKEditorExtension extends AbstractTypeExtension
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'ckeditor' => false,
            'ckeditor_config' => 'default',
        ]);
    }

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        if ($options['ckeditor']) {
            // Add the ckeditor-enable class for the traditional JS approach
            $view->vars['attr']['class'] = isset($view->vars['attr']['class']) 
                ? $view->vars['attr']['class'] . ' ckeditor-enable' 
                : 'ckeditor-enable';
            
            // Also add data attributes for the Stimulus controller
            $view->vars['attr']['data-controller'] = 'ckeditor';
            
            // Add config name as data attribute
            $configName = $options['ckeditor_config'];
            $view->vars['attr']['data-config'] = $configName;
            $view->vars['attr']['data-ckeditor-config'] = $configName;
        }
    }

    public static function getExtendedTypes(): iterable
    {
        return [TextareaType::class];
    }
} 