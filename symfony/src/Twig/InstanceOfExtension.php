<?php
namespace App\Twig;
class InstanceOfExtension extends \Twig_Extension
{
    public function getTests()
    {
        return [
            'instanceof' => new \Twig_Test('instanceof', [$this, 'isInstanceOf']),
        ];
    }
    public function isInstanceOf($var, $instance)
    {
        return $var instanceof $instance;
    }
}