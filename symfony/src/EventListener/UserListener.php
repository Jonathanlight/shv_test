<?php

namespace App\EventListener;

use Doctrine\Common\Persistence\Event\LifecycleEventArgs;
use App\Entity\User;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\Security\Core\Security;

class UserListener
{
    private $security;

    private $twig;

    public function __construct(Security $security, \Twig_Environment $twig)
    {
        $this->security = $security;
        $this->twig = $twig;
    }

    public function prePersist(LifecycleEventArgs $args)
    {
        $entity = $args->getObject();

        if (!$entity instanceof User) {
            return;
        }

        $entity->setUsername($entity->getEmail());
        $entity->setUsernameCanonical($entity->getEmail());
        $entity->setNameId($entity->getEmail());
    }

    /**
     * @param LifecycleEventArgs $args
     */
    public function preUpdate(LifecycleEventArgs $args)
    {
        $entity = $args->getObject();

        if (!$entity instanceof User) {
            return;
        }

        $em     = $args->getEntityManager();
        $uow    = $em->getUnitOfWork();
        $changes = $uow->getEntityChangeSet($entity);

        if (isset($changes['email'])) {
            $entity->setUsername($changes['email'][1]);
            $entity->setUsernameCanonical($changes['email'][1]);
            $entity->setEmailCanonical($changes['email'][1]);
            $entity->setNameId($changes['email'][1]);
        }
    }
}
