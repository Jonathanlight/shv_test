<?php

namespace App\Form;

use App\Constant\Operations;
use App\Entity\Hedge;
use App\Entity\MasterData\BusinessUnit;
use App\Entity\MasterData\Currency;
use App\Entity\MasterData\HedgingTool;
use App\Entity\MasterData\Maturity;
use App\Entity\MasterData\PriceRiskClassification;
use App\Entity\MasterData\Product;
use App\Entity\MasterData\Segment;
use App\Entity\MasterData\SubSegment;
use App\Entity\MasterData\UOM;
use App\Entity\RMP;
use App\Entity\User;
use App\Repository\CurrencyRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class HedgeType extends AbstractType
{
    protected $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $hedge = $options['data'];
        $rmpRepository = $this->em->getRepository(RMP::class);

        if ($hedge->getRmp()) {
            $selectedBusinessUnit = $hedge->getRmp()->getBusinessUnit();
        } else {
            $selectedBusinessUnit = isset($options['selectedBusinessUnit']) ? $options['selectedBusinessUnit'] : $options['user']->getBusinessUnits()->first();
        }

        if ($hedge->getRmp()) {
            $selectedRmp = $hedge->getRmp();
        } elseif ($selectedBusinessUnit instanceof BusinessUnit) {
            $selectedRmp = $rmpRepository->findFirstByBusinessUnits([$selectedBusinessUnit]);
        } else {
            $selectedRmp = $rmpRepository->findByActive(1, ['name' => 'ASC'])[0];
        }

        if ($options['user']->hasRole(User::ROLE_TRADER)) {
            $rmps = $rmpRepository->findForTraderHedgingRequests();
            $selectedRmp = $rmps[0];
        } else {
            $rmps = $selectedBusinessUnit instanceof BusinessUnit ? $rmpRepository->findByApprovedAndBusinessUnit($selectedBusinessUnit) : $rmpRepository->findAllApprovedFromNow();
        }


        if (!$hedge->isDraft()) {
            $rmps = [$hedge->getRmp()];
        }

        if ($options['loadAllRmps']) {
            $rmps = $rmpRepository->findAll();
        }

        $disabled = false;
        if ($hedge->getStatus() != Hedge::STATUS_DRAFT) {
            $disabled = true;
        }

        $disableAll = false;
        if ($options['disableAll']) {
            $disableAll = true;
        }

        $builder
            ->add('rmp', EntityType::class, [
                'class' => RMP::class,
                'choices' => $rmps,
                'label' => 'hedge.rmp',
                'disabled' => $disabled || $disableAll
            ])
            ->add('hedgingTool', EntityType::class, [
                'class' => HedgingTool::class,
                'label' => 'hedge.hedging_tool',
                'disabled' => $disabled || $disableAll,
                'choice_attr' => function ($choiceValue, $key, $value) {
                    $class = '';

                    if ($choiceValue instanceof HedgingTool) {
                        if (in_array($choiceValue->getCode(), HedgingTool::$notPremiumHedgingTool)) {
                            $class .= ' ' . HedgingTool::NOT_PREMIUM_CLASS;
                        }

                        if ($choiceValue->getRiskLevel() == 1) {
                            $class .= ' risk-level-1';
                        }

                        if ($choiceValue->getCode() == HedgingTool::HEDGING_TOOL_SPREAD_BUY || $choiceValue->getCode() == HedgingTool::HEDGING_TOOL_SPREAD_SELL) {
                            $class .= ' spread';
                        }

                        $class .= ' ' . $choiceValue->getCode();
                    }

                    return ['class' => $class];
                },
            ])
            ->add('product1', EntityType::class, [
                'class' => Product::class,
                'label' => 'hedge.product_1',
                'disabled' => $disabled || $disableAll
            ])
            ->add('product2', EntityType::class, [
                'class' => Product::class,
                'label' => 'hedge.product_2',
                'placeholder' => '-',
                'required' => false,
                'empty_data' => null,
                'disabled' => $disabled || $disableAll
            ])
            ->add('currency', EntityType::class, [
                'class' => Currency::class,
                'label' => 'hedge.currency',
                'disabled' => $disabled || $disableAll,
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
            ->add('uom', EntityType::class, [
                'class' => UOM::class,
                'label' => 'hedge.uom',
                'disabled' => $disabled || $disableAll,
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('u')
                        ->where('u.active = :active')
                        ->setParameter('active', 1)
                        ->orderBy('u.id', 'ASC');
                },
                'preferred_choices' => function (UOM $uom) {
                    return in_array($uom->getCode(), UOM::$preferredUoms);
                }
            ])
            ->add('priceRiskClassification', EntityType::class, [
                'class' => PriceRiskClassification::class,
                'label' => 'hedge.price_risk_classification',
                'disabled' => $disabled || $disableAll,
                'choice_attr' => function ($choiceValue, $key, $value) {
                    if ($choiceValue instanceof PriceRiskClassification
                        && PriceRiskClassification::CODE_DIFFERENTIAL_PRICE == $choiceValue->getCode()) {
                        $class = PriceRiskClassification::CODE_DIFFERENTIAL_PRICE;
                    }

                    return ['class' => isset($class) ? $class : ''];
                },
            ])
            ->add('segment', EntityType::class, [
                    'class' => Segment::class,
                    'query_builder' => function (EntityRepository $er) {
                        return $er->createQueryBuilder('s')
                            ->where('s.active = :active')
                            ->setParameter('active', 1)
                            ->orderBy('s.position', 'ASC');
                    },
                    'mapped' => false,
                    'label' => 'hedge.segment',
                    'disabled' => $disabled || $disableAll
                ]
            )
            ->add('subSegment', EntityType::class, [
                    'class' => SubSegment::class,
                    'label' => 'hedge.sub_segment',
                    'disabled' => $disabled || $disableAll,
                ]
            )
            ->add('firstMaturity', EntityType::class, [
                    'class' => Maturity::class,
                    'label' => 'hedge.first_maturity',
                    'query_builder' => function (EntityRepository $er) use ($selectedRmp, $hedge) {
                        return $er->queryByRmpAndHedge($selectedRmp, $hedge);
                    },
                    'disabled' => $disabled || $disableAll
                ]
            )
            ->add('lastMaturity', EntityType::class, [
                    'class' => Maturity::class,
                    'label' => 'hedge.last_maturity',
                    'query_builder' => function (EntityRepository $er) use ($selectedRmp, $hedge) {
                        return $er->queryByRmpAndHedge($selectedRmp, $hedge);
                    },
                    'disabled' => $disabled || $disableAll
                ]
            )
            ->add('description', TextareaType::class, [
                'label' => 'hedge.description',
                'required' => false,
                'disabled' => $disabled || $disableAll
            ])
            ->add('hedgeLines', CollectionType::class, [
                'entry_type' => HedgeLineType::class,
                'entry_options' => [
                    'label' => false,
                    'hedge' => $hedge,
                    'user' => $options['user'],
                    'status' => $options['status'],
                    'disableAll' => $disableAll,
                ],
                'label' => false,
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
            ]);

            $submitClass = 'no-print';
            if ($hedge->isRealized() || ($hedge->getStatus() > Hedge::STATUS_DRAFT && !$options['user']->hasRole(User::ROLE_TRADER))) {
                $submitClass .= ' d-none';
            } else {
                $submitClass .= ' btn-primary';
            }

            $builder->add('submit', SubmitType::class, [
                'label' => 'save',
                'attr' => [
                    'class' => $submitClass,
                    'data-keep-required' => $hedge->isPendingExecution() ? 1 : '',
                ],
            ]);

        $builder->addEventListener(FormEvents::POST_SET_DATA, [$this, 'onPostSetData']);
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Hedge::class,
            'selectedBusinessUnit' => null,
            'user' => null,
            'status' => null,
            'loadAllRmps' => null,
            'disableAll' => null,
        ]);
    }

    public function onPostSetData(FormEvent $event)
    {
        $hedge = $event->getData();
        $form = $event->getForm();

        $disabled = false;
        if ($hedge->getStatus() != Hedge::STATUS_DRAFT) {
            $disabled = true;
        }

        $form->add('operationType', ChoiceType::class, [
            'choices' => array_flip(Operations::$operationTypeChoices),
            'expanded' => true,
            'multiple' => false,
            'label' => false,
            'data' => $hedge->getOperationType() ?: Operations::OPERATION_TYPE_BUY,
            'disabled' => $disabled,
        ]);
    }
}
