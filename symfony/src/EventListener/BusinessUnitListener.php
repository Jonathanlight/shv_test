<?php

namespace App\EventListener;

use App\Entity\MasterData\BusinessUnit;
use App\Entity\RMP;
use App\Service\RmpManager;
use Doctrine\Common\Persistence\Event\LifecycleEventArgs;

class BusinessUnitListener
{
    private $rmpManager;

    /**
     * BusinessUnitListener constructor.
     * @param RmpManager $rmpManager
     */
    public function __construct(RmpManager $rmpManager)
    {
        $this->rmpManager = $rmpManager;
    }

    /**
     * @param LifecycleEventArgs $args
     */
    public function postPersist(LifecycleEventArgs $args)
    {
        $entity = $args->getObject();

        if (!$entity instanceof BusinessUnit) {
            return;
        }

        $this->rmpManager->createBlankRmpsByBusinessUnit($entity);
    }
}