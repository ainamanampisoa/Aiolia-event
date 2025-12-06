<?php

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class SvgExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('svg_to_base64', [$this, 'svgToBase64']),
        ];
    }

    public function svgToBase64(string $svg): string
    {
        // Nettoyer le SVG (enlever les espaces inutiles)
        $svg = trim($svg);
        
        // Encoder en base64
        $base64 = base64_encode($svg);
        
        return 'data:image/svg+xml;base64,' . $base64;
    }
}

