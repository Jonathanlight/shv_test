<?php

namespace App\EventListener;

use App\Entity\MasterData\Segment;
use App\Entity\MasterData\SubSegment;
use App\Entity\RmpSubSegment;
use Doctrine\ORM\Event\OnFlushEventArgs;

class SegmentListener
{

    /**
     * @param OnFlushEventArgs $args
     */
    public function onFlush(OnFlushEventArgs $args)
    {
        $em     = $args->getEntityManager();
        $uow    = $em->getUnitOfWork();

        $entities = array_merge(
            $uow->getScheduledEntityInsertions(),
            $uow->getScheduledEntityUpdates()
        );

        foreach ($entities as $entity) {
            if (!$entity instanceof Segment) {
                continue;
            }
            $changes = $uow->getEntityChangeSet($entity);
            if (isset($changes['active']) && !$changes['active'][1]) {
                $subSegments = $em->getRepository(SubSegment::class)->findBy(['segment' => $entity]);
                $rmpSubSegments = $em->getRepository(RmpSubSegment::class)->findBy(['subSegment' => $subSegments]);

                if (count($rmpSubSegments)) {
                    $entity->setUsed(true);
                } else {
                    $entity->setUsed(false);
                }

                foreach($subSegments as $subSegment) {
                    $subSegment->setActive(false);
                    $em->persist($subSegment);
                    $metaData = $em->getClassMetadata(SubSegment::class);
                    $uow->computeChangeSet($metaData, $subSegment);
                }
            }
        }
    }
}
