<?php

namespace App\Form;

use App\Entity\RmpSubSegmentRiskLevel;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RmpSubSegmentRiskLevelType extends AbstractType
{

    private static $count = 0;
    private $suffix;

    public function __construct()
    {
        $this->suffix = self::$count++;
    }
    public function getName()
    {
        return 'rmp_sub_segment_risk_level_'.$this->suffix;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('maximumVolume', NumberType::class, [
                'label' => false,
                'empty_data' => 0,
                'required' => false
            ]);
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => RmpSubSegmentRiskLevel::class,
        ]);
    }
}