<?php

namespace App\Repository;

use App\Entity\MasterData\Segment;
use App\Entity\RMP;
use App\Entity\RmpSubSegment;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityRepository;

class SubSegmentRepository extends EntityRepository
{
    /**
     * @param RMP $rmp
     * @param Segment $segment
     * @return array
     */
    public function findByRmpAndSegmentAsCollection(RMP $rmp, Segment $segment): array
    {
        return $this->formatResult($rmp, $segment);
    }

    /**
     * @param RMP $rmp
     * @param Segment $segment
     * @return array
     */
    public function findByRmpAndSegmentAsArray(RMP $rmp, Segment $segment): array
    {
        return $this->formatResult($rmp, $segment, false);
    }

    /**
     * @return array
     */
    public function findByActiveAndUsed(): array
    {
        $qb = $this->createQueryBuilder('ss')
            ->where('ss.active = :active')
            ->orWhere('ss.active != :active AND ss.used = :used')
            ->setParameter('active', true)
            ->setParameter('used', true);

        return $qb->getQuery()->getResult();
    }

    /**
     * @param RMP $rmp
     * @param Segment $segment
     * @param bool $collection
     * @return array
     */
    private function formatResult(RMP $rmp, Segment $segment, bool $collection = true): array
    {
        $subSegmentsFormatted = $collection ? new ArrayCollection() : [];

        $subSegments = $this->createQueryBuilder('ss')
            ->join('App\Entity\RmpSubSegment', 'rmpSubSegment', 'WITH', 'rmpSubSegment.rmp = :rmp and rmpSubSegment.subSegment = ss')
            ->join('App\Entity\MasterData\UOM', 'uom', 'WITH', 'uom = rmpSubSegment.uom')
            ->select('ss, uom.code')
            ->where('ss.segment = :segment')
            ->andWhere('rmpSubSegment.active = :active')
            ->andWhere('(ss.active = :active OR (ss.active != :active AND ss.used = :used))')
            ->setParameter('active', true)
            ->setParameter('used', true)
            ->setParameter('segment', $segment)
            ->setParameter('rmp', $rmp)
            ->orderBy('ss.name', 'ASC')
            ->getQuery()->getResult();

        $rmpSubSegments = $rmp->getRmpSubSegments();

        $allSubSegments = new ArrayCollection();
        foreach ($rmpSubSegments as $rmpSubSegment) {
            if (!$allSubSegments->contains($rmpSubSegment->getSubSegment())) {
                $allSubSegments->add($rmpSubSegment->getSubSegment());
            }
        }

        foreach ($subSegments as $subSegment) {
            if ($collection) {
                if (!$subSegmentsFormatted->contains($subSegment)
                    && ($allSubSegments && $allSubSegments->contains($subSegment)) || !$allSubSegments) {
                    $subSegmentsFormatted->add($subSegment);
                }
            } else {
                if (!in_array(['id' => $subSegment[0]->getId(), 'name' => $subSegment[0]->getName()], $subSegmentsFormatted)
                    && (($allSubSegments && $allSubSegments->contains($subSegment[0])) || !$allSubSegments)) {
                    $subSegmentsFormatted[] = ['id' => $subSegment[0]->getId(), 'name' => $subSegment[0]->getName(), 'uom' => $subSegment['code']];
                }
            }
        }

        return $subSegmentsFormatted;
    }

}
