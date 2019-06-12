<?php

namespace App\Tests\Service;

use App\Entity\Hedge;
use App\Entity\RMP;
use App\Service\RmpManager;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class RmpManagerTest extends KernelTestCase
{
    public function setUp()
    {
        self::bootKernel();
    }

    public function testMergeRmpHedgeReport()
    {
        $doctrine = self::$container->get('doctrine');
        $rmpRepository = $doctrine->getRepository(RMP::class);
        $rmpManager = self::$container->get(RmpManager::class);

        $rmp = $rmpRepository->find(5);
        $rmpManager->mergeRmp($rmp);

        // Test 1 : check if hedge are moved to new RMP
        $hedges = $doctrine->getRepository(Hedge::class)->findAll();
        foreach ($hedges as $hedge) {
            $this->assertEquals($rmp->getId(), $hedge->getRmp()->getId());
        }

        // Test 2 : Report des volumes
        foreach ($rmp->getActiveRmpSubSegments() as $rmpSubSegment) {
            foreach ($rmpSubSegment->getRmpSubSegmentRiskLevels() as $rmpSubSegmentRiskLevel) {
                $this->assertEquals($rmpSubSegmentRiskLevel->getCopiedFrom()->getConsumption(), $rmpSubSegmentRiskLevel->getConsumption());
                $this->assertEquals($rmpSubSegmentRiskLevel->getCopiedFrom()->getWaiverConsumption(), $rmpSubSegmentRiskLevel->getWaiverConsumption());
            }
        }
    }
}
