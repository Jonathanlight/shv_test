<?php

namespace App\EventListener;

use App\Entity\MasterData\SubSegment;
use App\Entity\RmpSubSegment;
use Doctrine\Common\Persistence\Event\LifecycleEventArgs;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;

class SubSegmentListener
{

    /**
     * @param LifecycleEventArgs $args
     */
    public function preUpdate(LifecycleEventArgs $args)
    {
        $entity = $args->getObject();

        if (!$entity instanceof SubSegment) {
            return;
        }

        $em      = $args->getEntityManager();
        $uow     = $em->getUnitOfWork();
        $changes = $uow->getEntityChangeSet($entity);

        if (isset($changes['active']) && !$changes['active'][1]) {
            $rmpSubSegments = $em->getRepository(RmpSubSegment::class)->findBy(['subSegment' => $entity]);

            if (count($rmpSubSegments)) {
                $entity->setUsed(true);
            } else {
                $entity->setUsed(false);
            }

        }
    }
}
