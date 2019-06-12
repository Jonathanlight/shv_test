<?php

namespace App\EventListener;

use App\Entity\CMS\Letter;
use App\Entity\Hedge;
use App\Entity\HedgeLog;
use App\Entity\User;
use App\Service\HedgeVolumeManager;
use App\Service\NotificationManager;
use Doctrine\Common\Persistence\Event\LifecycleEventArgs;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

class HedgeListener
{

    private $hedgeVolumeManager;

    private $notificationManager;

    private $em;

    private $router;

    /**
     * HedgeListener constructor.
     * @param HedgeVolumeManager $hedgeVolumeManager
     * @param NotificationManager $notificationManager
     * @param EntityManagerInterface $em
     * @param RouterInterface $router
     */
    public function __construct(HedgeVolumeManager $hedgeVolumeManager, NotificationManager $notificationManager, EntityManagerInterface $em, RouterInterface $router)
    {
        $this->hedgeVolumeManager = $hedgeVolumeManager;
        $this->notificationManager = $notificationManager;
        $this->em = $em;
        $this->router = $router;
    }

    /**
     * @param LifecycleEventArgs $args
     */
    public function prePersist(LifecycleEventArgs $args)
    {
        $entity = $args->getObject();

        if (!$entity instanceof Hedge) {
            return;
        }

        $entity->setOrderDate(new \DateTime());
        $entity->setCreatedAt(new \DateTime());
        $this->hedgeVolumeManager->updateHedgeTotalVolume($entity);
    }

    /**
     * @param LifecycleEventArgs $args
     */
    public function postPersist(LifecycleEventArgs $args)
    {
        $entity = $args->getObject();

        if (!$entity instanceof Hedge) {
            return;
        }

        $em     = $args->getEntityManager();

        if (!$entity->getCode()) {
            $entity->setCode($entity->getId());
            $em->flush();
        }

        if ($entity->isPendingExecution()) {
            $traders = $this->em->getRepository(User::class)->findByRole(User::ROLE_TRADER);
            $this->notificationManager->sendNotification(NotificationManager::TYPE_HEDGE_PENDING_EXECUTION,
                                                        $entity,
                                                        $traders,
                                                        ['hedgeId' => $entity->getId(),
                                                         'url' => $this->router->generate('hedge_edit',
                                                                                         ['hedge' => $entity->getId()],
                                                                                        UrlGeneratorInterface::ABSOLUTE_URL)]);
        } elseif ($entity->getStatus() == Hedge::STATUS_PENDING_APPROVAL_RISK_CONTROLLER) {
            $riskControllers = $this->em->getRepository(User::class)->findByRole(User::ROLE_RISK_CONTROLLER);
            $businessUnit = $entity->getRmp()->getBusinessUnit();
            $this->notificationManager->sendNotification(NotificationManager::TYPE_HEDGE_PENDING_APPROVAL_RISK_CONTROLLER,
                                                        $entity,
                                                        $riskControllers,
                                                        ['hedgeId' => $entity->getId(),
                                                        'buName' => $businessUnit,
                                                        'url' => $this->router->generate('hedge_edit',
                                                                                        ['hedge' => $entity->getId()],
                                                                                        UrlGeneratorInterface::ABSOLUTE_URL)]);
        }
    }

    /**
     * @param LifecycleEventArgs $args
     */
    public function preUpdate(LifecycleEventArgs $args)
    {
        $entity = $args->getObject();

        if (!$entity instanceof Hedge) {
            return;
        }

        $em     = $args->getEntityManager();
        $uow    = $em->getUnitOfWork();
        $changes = $uow->getEntityChangeSet($entity);

        if (!$entity->isPendingExecution() || (isset($changes['status']) && $changes['status'][1] == Hedge::STATUS_PENDING_EXECUTION)) {
           $entity->setOrderDate(new \DateTime());
        }

        $entity->setUpdatedAt(new \DateTime());
        $this->hedgeVolumeManager->updateHedgeTotalVolume($entity);
    }

    /**
     * @param LifecycleEventArgs $args
     */
    public function postUpdate(LifecycleEventArgs $args)
    {
        $entity = $args->getObject();

        if (!$entity instanceof Hedge) {
            return;
        }

        $em     = $args->getEntityManager();
        $uow    = $em->getUnitOfWork();
        $changes = $uow->getEntityChangeSet($entity);
        $userRepository = $this->em->getRepository(User::class);

        if (isset($changes['status'])) {
            $newStatus = $changes['status'][1];
            $businessUnit = $entity->getRmp()->getBusinessUnit();

            if ($newStatus == Hedge::STATUS_PENDING_APPROVAL_RISK_CONTROLLER) {
                $riskControllers = $userRepository->findByRole(User::ROLE_RISK_CONTROLLER);
                $this->notificationManager->sendNotification(NotificationManager::TYPE_HEDGE_PENDING_APPROVAL_RISK_CONTROLLER,
                                                                $entity,
                                                                $riskControllers,
                                                                ['hedgeId' => $entity->getId(),
                                                                 'buName' => $businessUnit,
                                                                 'url' => $this->router->generate('hedge_edit',
                                                                                                 ['hedge' => $entity->getId()],
                                                                                                 UrlGeneratorInterface::ABSOLUTE_URL)]);
            } else if ($newStatus == Hedge::STATUS_PENDING_EXECUTION) {
                $traders = $this->em->getRepository(User::class)->findByRole(User::ROLE_TRADER);
                $this->notificationManager->sendNotification(NotificationManager::TYPE_HEDGE_PENDING_EXECUTION,
                                                            $entity,
                                                            $traders,
                                                            ['hedgeId' => $entity->getId(),
                                                            'url' => $this->router->generate('hedge_edit',
                                                                                            ['hedge' => $entity->getId()],
                                                                                            UrlGeneratorInterface::ABSOLUTE_URL)]);
            } else if ($newStatus == Hedge::STATUS_REALIZED) {
                $this->hedgeVolumeManager->updateVolumesByHedge($entity);
                $this->notificationManager->sendNotification(NotificationManager::TYPE_HEDGE_REALIZED,
                                                            $entity,
                                                            [$entity->getCreator()],
                                                            ['hedgeId' => $entity->getId(),
                                                             'url' => $this->router->generate('hedge_edit',
                                                                                             ['hedge' => $entity->getId()],
                                                                                             UrlGeneratorInterface::ABSOLUTE_URL)]);
            }
        }

        if (isset($changes['partiallyRealized']) && $changes['partiallyRealized'][1]) {
            $this->notificationManager->sendNotification(NotificationManager::TYPE_HEDGE_PARTIALLY_REALIZED,
                                                        $entity,
                                                        [$entity->getCreator()],
                                                        ['hedgeId' => $entity->getId(),
                                                         'url' => $this->router->generate('hedge_edit',
                                                                                         ['hedge' => $entity->getId()],
                                                                                        UrlGeneratorInterface::ABSOLUTE_URL)]);
        }

        if (isset($changes['pendingCancelation']) && $changes['pendingCancelation'][1]) {
            $traders = $this->em->getRepository(User::class)->findByRole(User::ROLE_TRADER);
            $this->notificationManager->sendNotification(NotificationManager::TYPE_HEDGE_CANCELLATION_REQUESTED,
                                                        $entity,
                                                        $traders,
                                                        ['url' => $this->router->generate('hedge_edit',
                                                                                         ['hedge' => $entity->getId()],
                                                                                         UrlGeneratorInterface::ABSOLUTE_URL)]);
        }

        if (((isset($changes['partiallyRealized']) && $changes['partiallyRealized'][1])
            || (isset($changes['status']) && $changes['status'][1] == Hedge::STATUS_REALIZED)) && $entity->isPendingCancelation()) {
            $this->notificationManager->sendNotification(NotificationManager::TYPE_HEDGE_CANCELLATION_REFUSED,
                                                        $entity,
                                                        [$entity->getCreator()],
                                                        ['hedgeId' => $entity->getId(),
                                                        'url' => $this->router->generate('hedge_edit',
                                                                                        ['hedge' => $entity->getId()],
                                                                                        UrlGeneratorInterface::ABSOLUTE_URL)]);
        }
    }
}
