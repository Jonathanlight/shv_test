<?php

namespace App\Form;

use App\Entity\Hedge;
use App\Entity\HedgeLine;
use App\Entity\MasterData\HedgingTool;
use App\Entity\MasterData\Maturity;
use App\Entity\MasterData\Strategy;
use App\Entity\RMP;
use App\Entity\RmpSubSegment;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatorInterface;

class HedgeLineType extends AbstractType
{
    protected $em;

    protected $translator;

    public function __construct(EntityManagerInterface $em, TranslatorInterface $translator)
    {
        $this->em = $em;
        $this->translator = $translator;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $hedge = $options['hedge'];

        $disabled = false;
        if ($hedge->getStatus() != Hedge::STATUS_DRAFT) {
            $disabled = true;
        }

        $disableAll = false;
        if ($options['disableAll']) {
            $disableAll = true;
        }

        $builder
            ->add('quantity', NumberType::class, [
                'label' => false,
                'disabled' => $disabled || $disableAll
            ])
            ->add('rmpSubSegment', EntityType::class, [
                'label' => false,
                'class' => RmpSubSegment::class,
                'disabled' => $disabled || $disableAll
            ])
            ->add('maturity', EntityType::class, [
                'label' => false,
                'class' => Maturity::class,
                'disabled' => $disabled || $disableAll
            ])
            ->add('protectionPrice', TextType::class, [
                'label' => false,
                'disabled' => $disabled || $disableAll,
            ])
            ->add('maxLoss', TextType::class, [
                'label' => false,
                'disabled' => $disabled || $disableAll,
            ])
            ->add('premiumHedgingTool', TextType::class, [
                'label' => false,
                'disabled' => $disabled || $disableAll,
            ]);

        $builder->addEventListener(FormEvents::POST_SET_DATA, [$this, 'onPreSetData']);
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => HedgeLine::class,
            'hedge' => null,
            'user' => null,
            'status' => null,
            'disableAll' => null,
        ]);
    }

    public function onPreSetData(FormEvent $event)
    {
        $form = $event->getForm();
        $hedgeLine = $event->getData();

        if (!$hedgeLine instanceof HedgeLine) {
            return false;
        }

        $disableAll = false;
        if ($form->getConfig()->getOption('disableAll')) {
            $disableAll = true;
        }

        $disabled = false;
        if ($hedgeLine->getHedge()->getStatus() != Hedge::STATUS_DRAFT) {
            $disabled = true;
        }

        $form->add('protectionPrice', TextType::class, [
            'label' => false,
            'disabled' => $disabled || $disableAll,
            'attr' => [
                'value' => $hedgeLine && $hedgeLine->getProtectionPrice() ? $hedgeLine->getProtectionPrice() : $this->translator->trans(RMP::DEFAULT_PROTECTION_PRICE),
                'data-default-value' => $this->translator->trans(RMP::DEFAULT_PROTECTION_PRICE),
                'defaultValue' => $this->translator->trans(RMP::DEFAULT_PROTECTION_PRICE),
            ],
        ])
            ->add('maxLoss', TextType::class, [
                'label' => false,
                'disabled' => $disabled || $disableAll,
                'attr' => [
                    'value' => $hedgeLine && $hedgeLine->getMaxLoss() ? $hedgeLine->getMaxLoss() : $this->translator->trans(RMP::DEFAULT_MAX_LOSS),
                    'data-default-value' => $this->translator->trans(RMP::DEFAULT_MAX_LOSS),
                    'defaultValue' => $this->translator->trans(RMP::DEFAULT_MAX_LOSS),
                ],
            ])
            ->add('premiumHedgingTool', TextType::class, [
                'label' => false,
                'disabled' => $disabled || $disableAll,
                'attr' => [
                    'value' => $hedgeLine && $hedgeLine->getPremiumHedgingTool() ? $hedgeLine->getPremiumHedgingTool() : $this->translator->trans(RMP::DEFAULT_PREMIUM_HEDGING_TOOL),
                    'data-default-value' => $this->translator->trans(RMP::DEFAULT_PREMIUM_HEDGING_TOOL),
                    'defaultValue' => $this->translator->trans(RMP::DEFAULT_PREMIUM_HEDGING_TOOL),
                ],
            ]);

        $user = $form->getConfig()->getOption('user');
        $hedgeStatus = $form->getConfig()->getOption('status');
        $hedge = $form->getConfig()->getOption('hedge');
        if (($user->hasRole(User::ROLE_TRADER) && $hedge->isPendingExecution()) || $hedge->isRealized()) {
            $disabled = false;
            if ($hedgeStatus != Hedge::STATUS_DRAFT
                && $hedgeStatus != Hedge::STATUS_PENDING_EXECUTION) {
                $disabled = true;
            }

            $hedgingTool = $hedge->getHedgingTool();
            if (!$hedgingTool instanceof HedgingTool) {
                $hedgingTool = $this->em->getRepository(HedgingTool::class)->findOneBy(['operationType' => HedgingTool::OPERATION_TYPE_BUY], ['name' => 'ASC']);
            }

            $form->add('swapPrice', TextType::class, [
                'label' => false,
                'disabled' => $disabled,
                'required' => in_array('swapPrice', $hedgingTool->getColumns())
            ])
                ->add('swap1Price', TextType::class, [
                    'label' => false,
                    'disabled' => $disabled,
                    'required' => in_array('swap1Price', $hedgingTool->getColumns())
                ])
                ->add('swap2Price', TextType::class, [
                    'label' => false,
                    'disabled' => $disabled,
                    'required' => in_array('swap2Price', $hedgingTool->getColumns())
                ])
                ->add('callStrike', TextType::class, [
                    'label' => false,
                    'disabled' => $disabled,
                    'required' => in_array('callStrike', $hedgingTool->getColumns())
                ])
                ->add('call1Strike', TextType::class, [
                    'label' => false,
                    'disabled' => $disabled,
                    'required' => in_array('call1Strike', $hedgingTool->getColumns())
                ])
                ->add('call2Strike', TextType::class, [
                    'label' => false,
                    'disabled' => $disabled,
                    'required' => in_array('call2Strike', $hedgingTool->getColumns())
                ])
                ->add('callPremium', TextType::class, [
                    'label' => false,
                    'disabled' => $disabled,
                    'required' => in_array('callPremium', $hedgingTool->getColumns())
                ])
                ->add('call1Premium', TextType::class, [
                    'label' => false,
                    'disabled' => $disabled,
                    'required' => in_array('call1Premium', $hedgingTool->getColumns())
                ])
                ->add('call2Premium', TextType::class, [
                    'label' => false,
                    'disabled' => $disabled,
                    'required' => in_array('call2Premium', $hedgingTool->getColumns())
                ])
                ->add('putPremium', TextType::class, [
                    'label' => false,
                    'disabled' => $disabled,
                    'required' => in_array('putPremium', $hedgingTool->getColumns())
                ])
                ->add('put1Premium', TextType::class, [
                    'label' => false,
                    'disabled' => $disabled,
                    'required' => in_array('put1Premium', $hedgingTool->getColumns())
                ])
                ->add('put2Premium', TextType::class, [
                    'label' => false,
                    'disabled' => $disabled,
                    'required' => in_array('put2Premium', $hedgingTool->getColumns())
                ])
                ->add('putStrike', TextType::class, [
                    'label' => false,
                    'disabled' => $disabled,
                    'required' => in_array('putStrike', $hedgingTool->getColumns())
                ])
                ->add('put1Strike', TextType::class, [
                    'label' => false,
                    'disabled' => $disabled,
                    'required' => in_array('put1Strike', $hedgingTool->getColumns())
                ])
                ->add('put2Strike', TextType::class, [
                    'label' => false,
                    'disabled' => $disabled,
                    'required' => in_array('put2Strike', $hedgingTool->getColumns())
                ]);
            if ($user->hasRole(User::ROLE_TRADER)) {
                $form->add('strategy', EntityType::class, [
                    'class' => Strategy::class,
                    'label' => false,
                    'disabled' => $disabled,
                    'query_builder' => function (EntityRepository $er) {
                        return $er->createQueryBuilder('s')
                            ->where('s.date >= :date')
                            ->andWhere('s.active = :active')
                            ->setParameter('date', date('Y-m', strtotime('-1 year')))
                            ->setParameter('active', 1)
                            ->orderBy('s.date', 'ASC');
                    },
                ]);
            }
        }
        if ($hedge->isRealized() or $hedge->isPartiallyRealized()) {
            $form->add('quantityRealized', TextType::class, [
                'label' => false,
                'disabled' => true
            ]);
        }
    }
}
