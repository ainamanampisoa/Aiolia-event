<?php

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

class MathExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('cos', [$this, 'cos']),
            new TwigFilter('sin', [$this, 'sin']),
        ];
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('cos', [$this, 'cos']),
            new TwigFunction('sin', [$this, 'sin']),
        ];
    }

    public function cos(float $angle): float
    {
        return cos($angle);
    }

    public function sin(float $angle): float
    {
        return sin($angle);
    }
}

