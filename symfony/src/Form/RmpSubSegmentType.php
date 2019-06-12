<?php

namespace App\Form;

use App\Entity\MasterData\Currency;
use App\Entity\MasterData\PriceRiskClassification;
use App\Entity\MasterData\Product;
use App\Entity\MasterData\Segment;
use App\Entity\MasterData\SubSegment;
use App\Entity\MasterData\UOM;
use App\Entity\RmpSubSegment;
use Doctrine\ORM\EntityRepository;
use Sonata\CoreBundle\Form\Type\CollectionType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\PercentType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RmpSubSegmentType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $rmpSubSegments = $options['activeRmpSubSegments'];
        $subSegmentsUsed = [];
        $currentRmpSubSegment = $options['data'];

        foreach ($rmpSubSegments as $rmpSubSegment) {
            if ($currentRmpSubSegment->getId() && $currentRmpSubSegment->getId() == $rmpSubSegment->getId()) {
                continue;
            }
            $subSegmentsUsed[] = $rmpSubSegment->getSubSegment();
        }
        $maximumMaturitiesOptions = [];
        for ($i = 1; $i <= 32; $i++) {
            $maximumMaturitiesOptions[$i] = $i;
        }

        $builder
            ->add('segment', EntityType::class, [
                'class' => Segment::class,
                'mapped' => false,
                'label' => 'rmp.segment',
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('s')
                        ->where('s.active = :active')
                        ->setParameter('active', 1)
                        ->orderBy('s.position', 'ASC');
                },
            ])
            ->add('subSegment', EntityType::class, [
                'class' => SubSegment::class,
                'label' => 'rmp.sub_segment',
                'query_builder' => function (EntityRepository $er) use ($subSegmentsUsed) {
                    $er =  $er->createQueryBuilder('ss')
                        ->where('ss.active = :active')
                        ->setParameter('active', 1)
                        ->orderBy('ss.name', 'ASC');
                    if (count($subSegmentsUsed)) {
                        $er->andWhere('ss NOT IN (:subSegmentsUsed)')
                            ->setParameter('subSegmentsUsed', $subSegmentsUsed);
                    }

                    return $er;
                },
            ])
            ->add('priceRiskClassification', EntityType::class, [
                'class' => PriceRiskClassification::class,
                'label' => 'rmp.price_risk_classification',
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('p')
                        ->where('p.active = :active')
                        ->setParameter('active', 1)
                        ->orderBy('p.code', 'ASC');
                },
                'choice_attr' => function ($choiceValue, $key, $value) {
                    if ($choiceValue instanceof PriceRiskClassification
                        && PriceRiskClassification::CODE_OTHER == $choiceValue->getCode()) {
                        $class = PriceRiskClassification::CODE_OTHER;
                    }

                    return ['class' => isset($class) ? $class : ''];
                },
            ])
            ->add('uom', EntityType::class, [
                'class' => UOM::class,
                'label' => 'rmp.uom',
                'preferred_choices' => function (UOM $uom) {
                    return in_array($uom->getCode(), UOM::$preferredUoms);
                },
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('uom')
                        ->where('uom.active = :active')
                        ->setParameter('active', 1)
                        ->orderBy('uom.code', 'ASC');
                },
            ])
            ->add('salesVolume', NumberType::class, [
                'label' => 'rmp.sales_volume'
            ])
            ->add('maximumLoss', NumberType::class, [
                'label' => 'rmp.maximum_loss',
                'required' => false
            ])
            ->add('maximumMaturities', ChoiceType::class, [
                'choices' => $maximumMaturitiesOptions,
                'label' => 'rmp.maximum_maturities'
            ])
            ->add('maximumVolume', NumberType::class, [
                'label' => 'rmp.maximum_volume'
            ])
            ->add('ratioMaximumVolumeSales', PercentType::class, [
                'type' => 'integer',
                'scale' => 2,
                'label' => 'rmp.ratio_maximum_volume_sales'
            ])
            ->add('productCategory', TextType::class, [
                'label' => 'rmp.product_category'
            ])
            ->add('products', EntityType::class, [
                'class' => Product::class,
                'multiple' => true,
                'label' => 'rmp.benchmark'
            ])
            ->add('currency', EntityType::class, [
                'class' => Currency::class,
                'label' => 'rmp.currency',
                'preferred_choices' => function (Currency $currency) {
                    return in_array($currency->getCode(), Currency::$preferredCurrencies);
                },
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('c')
                        ->where('c.active = :active')
                        ->setParameter('active', 1)
                        ->orderBy('c.id', 'ASC');
                },
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'save',
                'attr' => [
                    'class' => 'btn-primary',
                ],
            ]);
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => RmpSubSegment::class,
            'activeRmpSubSegments' => null
        ]);
    }
}