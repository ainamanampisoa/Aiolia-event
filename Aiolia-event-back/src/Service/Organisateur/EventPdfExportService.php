<?php

namespace App\Service\Organisateur;

use App\Entity\Event;
use Dompdf\Dompdf;
use Dompdf\Options;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Response;
use Twig\Environment;

class EventPdfExportService
{
    private const TIMEZONE = 'Indian/Antananarivo';
    private const TEMPLATE_PATH = 'Organisateur/events/pdf_export.html.twig';

    public function __construct(
        private Environment $twig,
        #[Autowire('%kernel.project_dir%')]
        private string $projectDir
    ) {
    }

    
    public function generatePdf(
        Event $event,
        $primaryOrganizer,
        $coOrganizers,
        array $ticketTypes,
        array $statistics
    ): Response {
        
        $exportDateTime = new \DateTime('now', new \DateTimeZone(self::TIMEZONE));
        $exportDateFormatted = $exportDateTime->format('d/m/Y à H:i');
        
        $html = $this->twig->render(self::TEMPLATE_PATH, [
            'event' => $event,
            'primaryOrganizer' => $primaryOrganizer,
            'coOrganizers' => $coOrganizers,
            'ticketTypes' => $ticketTypes,
            'statistics' => $statistics,
            'exportDateFormatted' => $exportDateFormatted,
        ]);
        
        
        $html = $this->cleanHtmlForPdf($html);
        
        
        $html = $this->convertSvgToBase64Images($html);

        
        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', false);
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('isFontSubsettingEnabled', true);
        $options->set('isPhpEnabled', false);
        $options->set('debugKeepTemp', false);
        $options->set('enableCssFloat', true);
        $options->set('chroot', realpath($this->projectDir . '/public'));
        
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html, 'UTF-8');
        
        $dompdf->setPaper('A4', 'portrait');
        
        
        $dompdf->render();
        
        
        $output = $dompdf->output();
        
        if (empty($output) || strlen($output) < 100) {
            throw new \RuntimeException('La génération du PDF a échoué. Le fichier généré est vide ou invalide.');
        }
        
        
        if (substr($output, 0, 4) !== '%PDF') {
            throw new \RuntimeException('La génération du PDF a échoué. Le contenu généré n\'est pas un PDF valide.');
        }
        
        
        $filename = $this->generateFilename($event);
        
        
        $response = new Response($output);
        $response->headers->set('Content-Type', 'application/pdf');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '"');
        $response->headers->set('Content-Length', (string) strlen($output));
        $response->headers->set('Cache-Control', 'private, max-age=0, must-revalidate');
        $response->headers->set('Pragma', 'public');
        
        return $response;
    }

    
    private function cleanHtmlForPdf(string $html): string
    {
        
        $html = preg_replace('/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/is', '', $html);
        
        
        $html = preg_replace('/style="[^"]*transition[^"]*"/i', '', $html);
        $html = preg_replace('/style="[^"]*hover[^"]*"/i', '', $html);
        
        
        $html = preg_replace('/class="[^"]*hover[^"]*"/i', '', $html);
        
        return $html;
    }

    
    private function convertSvgToBase64Images(string $html): string
    {
        
        $pattern = '/<svg[^>]*>.*?<\/svg>/is';
        
        return preg_replace_callback($pattern, function ($matches) {
            try {
                $svgContent = $matches[0];
                
                
                
                $svgContent = preg_replace('/xmlns:xlink="[^"]*"/i', '', $svgContent);
                $svgContent = preg_replace('/xlink:href="[^"]*"/i', '', $svgContent);
                $svgContent = preg_replace('/version="[^"]*"/i', '', $svgContent);
                
                
                $svgContent = preg_replace('/\s+/', ' ', $svgContent);
                $svgContent = str_replace(['> <', '> <'], ['><', '><'], $svgContent);
                $svgContent = trim($svgContent);
                
                
                if (empty($svgContent) || !str_contains($svgContent, '<svg')) {
                    return ''; 
                }
                
                
                $base64 = base64_encode($svgContent);
                
                
                return '<img src="data:image/svg+xml;base64,' . $base64 . '" style="max-width: 100%; height: auto;" alt="Graphique" />';
            } catch (\Exception $e) {
                
                return '<div style="width: 100%; height: 200px; background: #f0f0f0; display: flex; align-items: center; justify-content: center; color: #666;">Graphique non disponible</div>';
            }
        }, $html);
    }

    
    private function generateFilename(Event $event): string
    {
        $slug = $event->getSlug() ?: 'evenement-' . $event->getId();
        $slug = preg_replace('/[^a-z0-9-]/', '-', strtolower($slug));
        return 'evenement-' . $slug . '-' . date('Y-m-d') . '.pdf';
    }
}

