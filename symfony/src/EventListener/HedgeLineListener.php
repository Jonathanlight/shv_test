<?php

namespace App\EventListener;

use App\Entity\Hedge;
use App\Entity\HedgeLine;
use App\Service\HedgeVolumeManager;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PreFlushEventArgs;

class HedgeLineListener
{
    /**
     * @param OnFlushEventArgs $args
     */
    public function onFlush(OnFlushEventArgs $args)
    {
        $em = $args->getEntityManager();
        $uow = $em->getUnitOfWork();

        $entities = array_merge(
            $uow->getScheduledEntityInsertions(),
            $uow->getScheduledEntityUpdates()
        );

        foreach ($entities as $entity) {
            if (!($entity instanceof HedgeLine)) {
                continue;
            }

            $hedge = $entity->getHedge();
            if ($hedge instanceof Hedge) {
                $hedge->setUpdatedAt(new \DateTime());
                $em->persist($hedge);
                $md = $em->getClassMetadata('App\Entity\Hedge');
                $uow->recomputeSingleEntityChangeSet($md, $hedge);
                $hedge->addHedgeLine($entity);
            }
        }
    }
}
