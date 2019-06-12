<?php

namespace App\EventListener;

use App\Entity\MasterData\Segment;
use App\Entity\RmpSubSegment;
use Doctrine\Common\Persistence\Event\LifecycleEventArgs;

class RmpSubSegmentListener
{
    public function prePersist(LifecycleEventArgs $args)
    {
        $entity = $args->getObject();

        if (!$entity instanceof RmpSubSegment) {
            return;
        }

        if (!$entity->getMaximumVolume()) {
            $entity->setMaximumVolume($entity->getSalesVolume() * $entity->getRatioMaximumVolumeSales() / 100);
        } elseif (!$entity->getRatioMaximumVolumeSales()) {
            $entity->setRatioMaximumVolumeSales($entity->getSalesVolume() / $entity->getMaximumVolume() * 100);
        }
    }
}
