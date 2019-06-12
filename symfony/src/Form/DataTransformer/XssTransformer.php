<?php

namespace App\Form\DataTransformer;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\DataTransformerInterface;

class XssTransformer implements DataTransformerInterface
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * Remove tags that can cause XSS.
     *
     * @param  string $text
     * @return string
     */
    public function transform($text)
    {
        if (null === $text) {
            return '';
        }

        return $text;
    }

    /**
     * Return the text cleaned.
     *
     * @param  string $text
     * @return string
     */
    public function reverseTransform($text)
    {
        if (!$text) {
            return '';
        }

        return strip_tags($text, '<p><strong><em><ul><li><ol>');
    }
}
