<?php

namespace App\Service\Organisateur;

use Imagine\Image\Box;
use Imagine\Image\Point;
use Imagine\Gd\Imagine;
use Imagine\Image\Palette\RGB;

/**
 * Service pour convertir les SVG en images PNG (base64)
 * Note: Imagine ne supporte pas directement SVG, cette classe est une base
 * pour une future implémentation avec une bibliothèque de conversion SVG
 */
class SvgToImageConverter
{
    private ?Imagine $imagine = null;

    public function __construct()
    {
        // Vérifier si GD est disponible
        if (extension_loaded('gd')) {
            try {
                $this->imagine = new Imagine();
            } catch (\Exception $e) {
                // GD n'est pas disponible
                $this->imagine = null;
            }
        }
    }

    /**
     * Convertit un SVG en image PNG base64
     * Pour l'instant, retourne le SVG tel quel car Imagine ne supporte pas SVG
     * 
     * @param string $svgContent Le contenu SVG
     * @param int $width Largeur de l'image
     * @param int $height Hauteur de l'image
     * @return string Image base64 ou SVG si la conversion n'est pas possible
     */
    public function convertSvgToBase64(string $svgContent, int $width = 800, int $height = 300): string
    {
        // Pour l'instant, on retourne le SVG encodé en base64
        // Dans une implémentation future, on pourrait utiliser une bibliothèque
        // comme enshrined/svg-sanitize avec une conversion via une API ou une bibliothèque externe
        
        // Encoder le SVG en base64 pour l'inclure dans le HTML
        $base64 = base64_encode($svgContent);
        return 'data:image/svg+xml;base64,' . $base64;
    }

    /**
     * Vérifie si la conversion SVG est disponible
     */
    public function isConversionAvailable(): bool
    {
        // Pour l'instant, la conversion n'est pas disponible
        // Il faudrait installer une bibliothèque de conversion SVG
        return false;
    }
}

