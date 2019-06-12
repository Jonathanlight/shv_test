<?php

namespace App\EventListener;

use App\Entity\RmpSubSegmentRiskLevel;
use Doctrine\Common\Persistence\Event\LifecycleEventArgs;

class RmpSubSegmentRiskLevelListener
{
    public function prePersist(LifecycleEventArgs $args)
    {
        $entity = $args->getObject();

        if (!$entity instanceof RmpSubSegmentRiskLevel) {
            return;
        }

        if (!$entity->getMaximumVolume()) {
            $entity->setMaximumVolume(0);
        }
    }
}
