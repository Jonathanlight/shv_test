<?php

namespace App\EventListener;

use App\Entity\RMP;
use App\Entity\User;
use App\Service\NotificationManager;
use Doctrine\Common\Persistence\Event\LifecycleEventArgs;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Acl\Exception\Exception;

class RmpListener
{
    private $notificationManager;

    private $em;

    private $router;

    /**
     * RmpListener constructor.
     * @param NotificationManager $notificationManager
     * @param EntityManagerInterface $em
     * @param RouterInterface $router
     */
    public function __construct(NotificationManager $notificationManager, EntityManagerInterface $em, RouterInterface $router)
    {
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

        if (!$entity instanceof RMP) {
            return;
        }

        $entity->setCreatedAt(new \DateTime());
        $entity->setUpdatedAt(new \DateTime());
    }

    /**
     * @param LifecycleEventArgs $args
     */
    public function preUpdate(LifecycleEventArgs $args)
    {
        $entity = $args->getObject();

        if (!$entity instanceof RMP) {
            return;
        }

        $entity->setUpdatedAt(new \DateTime());
    }

    /**
     * @param LifecycleEventArgs $args
     */
    public function postUpdate(LifecycleEventArgs $args)
    {
        $entity = $args->getObject();

        if (!$entity instanceof RMP) {
            return;
        }

        $em     = $args->getEntityManager();
        $uow    = $em->getUnitOfWork();
        $changes = $uow->getEntityChangeSet($entity);
        $userRepository = $this->em->getRepository(User::class);

        if (isset($changes['status']) && $changes['status'][1] == RMP::STATUS_PENDING_APPROVAL_RISK_CONTROLLER) {
            $riskControllers = $userRepository->findByRole(User::ROLE_RISK_CONTROLLER);
            if ($entity->isAmendment()) {
                $this->notificationManager->sendNotification(NotificationManager::TYPE_RMP_AMENDMENT_PENDING_APPROVAL_RISK_CONTROLLER,
                                                            $entity,
                                                            $riskControllers,
                                                            ['rmpName' => $entity->getName(),
                                                             'url' => $this->router->generate('rmp_view',
                                                                                             ['rmp' => $entity->getId()],
                                                                                             UrlGeneratorInterface::ABSOLUTE_URL)]);
            } else {
                $this->notificationManager->sendNotification(NotificationManager::TYPE_RMP_PENDING_APPROVAL_RISK_CONTROLLER,
                                                            $entity,
                                                            $riskControllers,
                                                            ['rmpName' => $entity->getName(),
                                                             'url' => $this->router->generate('rmp_view',
                                                                                             ['rmp' => $entity->getId()],
                                                                                             UrlGeneratorInterface::ABSOLUTE_URL)]);
            }
        }
    }
}
