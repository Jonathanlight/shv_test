<?php

namespace App\Repository;

use App\Entity\MasterData\PriceRiskClassification;
use App\Entity\MasterData\Segment;
use App\Entity\RMP;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityRepository;

class SegmentRepository extends EntityRepository
{
    /**
     * @return array
     */
    public function findByActiveAndUsed(): array
    {
        $qb = $this->createQueryBuilder('s')
            ->where('s.active = :active')
            ->orWhere('s.active != :active AND s.used = :used')
            ->orderBy('s.position', 'ASC')
            ->setParameter('active', true)
            ->setParameter('used', true);

        return $qb->getQuery()->getResult();
    }

    /**
     * @param RMP $rmp
     * @return array
     */
    public function findByRmpAsFormattedArray(RMP $rmp): array
    {
        $segmentsList = [];
        $rmpSubSegments = $rmp->getRmpSubSegments();

        foreach ($rmpSubSegments as $k => $rmpSubSegment) {
            if ($rmpSubSegment->isActive()) {
                $segment = $rmpSubSegment->getSubSegment()->getSegment();
                if (!in_array(['id' => $segment->getId(), 'name' => $segment->getName(), 'position' => $segment->getPosition()], $segmentsList)
                    && $segment->isActive()
                    && $rmpSubSegment->getPriceRiskClassification()->getCode() != PriceRiskClassification::CODE_OTHER) {
                    $segmentsList[] = ['id' => $segment->getId(), 'name' => $segment->getName(), 'position' => $segment->getPosition()];
                }
            }
        }

        usort($segmentsList, function($a, $b) {
            return $a['position'] <=> $b['position'];
        });

        return $segmentsList;
    }

    /**
     * @param RMP $rmp
     * @return ArrayCollection
     */
    public function findByRmpAsCollection(RMP $rmp): ArrayCollection
    {
        $segmentsCollection = new ArrayCollection();
        $rmpSubSegments = $rmp->getRmpSubSegments();

        foreach ($rmpSubSegments as $k => $rmpSubSegment) {
            $segment = $rmpSubSegment->getSubSegment()->getSegment();
            if (!$segmentsCollection->contains($segment)) {
                $segmentsCollection->add($segment);
            }
        }

        return $segmentsCollection;
    }
}
