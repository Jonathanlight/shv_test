<?php
namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class PercentExtension extends AbstractExtension
{
    /**
     * @return array
     */
    public function getFunctions()
    {
        return [
            new TwigFunction('percent', array($this, 'percent')),
        ];
    }

    public function percent($value, $total)
    {
        if (!$value && !$total) {
            return 0;
        } else {
            return number_format($value / $total * 100, 2, ',', ' ');
        }
    }
}