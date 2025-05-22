<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

/**
 * Controller for API endpoints
 */
class ApiController extends AbstractController
{
    private $parameterBag;

    public function __construct(ParameterBagInterface $parameterBag)
    {
        $this->parameterBag = $parameterBag;
    }

    #[Route('/api/ckeditor-config', name: 'api_ckeditor_config', methods: ['GET'])]
    public function getCkeditorConfig(): JsonResponse
    {
        // Default configurations if parameter not found
        $defaultConfigs = [
            'default' => [
                'toolbar' => ['heading', '|', 'bold', 'italic', 'link', 'bulletedList', 'numberedList', '|', 'undo', 'redo'],
                'height' => 300,
                'placeholder' => "Tapez votre contenu ici..."
            ],
            'minimal' => [
                'toolbar' => ['bold', 'italic', 'link', 'bulletedList', 'numberedList', '|', 'undo', 'redo'],
                'height' => 200,
                'placeholder' => "Texte court..."
            ],
            'full' => [
                'toolbar' => [
                    ['heading', '|', 'bold', 'italic', 'underline', 'strikethrough', 'link', '|', 'bulletedList', 'numberedList', '|', 'outdent', 'indent', '|', 'blockQuote'],
                    ['insertTable', 'horizontalLine', '|', 'alignment', '|', 'fontColor', 'fontBackgroundColor', '|', 'undo', 'redo']
                ],
                'height' => 400,
                'placeholder' => "Contenu détaillé..."
            ]
        ];
        
        // Try to get from parameters
        try {
            if ($this->parameterBag->has('ckeditor_configs')) {
                $configs = $this->parameterBag->get('ckeditor_configs');
            } elseif ($this->parameterBag->has('ckeditor.configs')) {
                $configs = $this->parameterBag->get('ckeditor.configs');
            } else {
                $configs = $defaultConfigs;
            }
        } catch (\Exception $e) {
            $configs = $defaultConfigs;
        }
        
        // Return as JSON
        return $this->json($configs);
    }
} 