<?php

namespace App\Service\Organisateur;

use Imagine\Image\Box;
use Imagine\Image\Point;
use Imagine\Gd\Imagine;
use Imagine\Image\Palette\RGB;


class SvgToImageConverter
{
    private ?Imagine $imagine = null;

    public function __construct()
    {
        
        if (extension_loaded('gd')) {
            try {
                $this->imagine = new Imagine();
            } catch (\Exception $e) {
                
                $this->imagine = null;
            }
        }
    }

    
    public function convertSvgToBase64(string $svgContent, int $width = 800, int $height = 300): string
    {
        
        
        
        
        
        $base64 = base64_encode($svgContent);
        return 'data:image/svg+xml;base64,' . $base64;
    }

    
    public function isConversionAvailable(): bool
    {
        
        
        return false;
    }
}

