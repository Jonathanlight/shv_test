<?php

namespace App\Tests\Service;

use App\Entity\Hedge;
use App\Entity\RmpSubSegmentRiskLevel;
use App\Service\HedgeVolumeManager;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class HedgeVolumeManagerTest extends KernelTestCase
{
    private $doctrine;

    private $hedgeRepository;

    private $hedgeVolumeManager;

    public function setUp()
    {
        self::bootKernel();
        $this->doctrine = self::$container->get('doctrine');
        $this->hedgeRepository = $this->doctrine->getRepository(Hedge::class);
        $this->hedgeVolumeManager = self::$container->get(HedgeVolumeManager::class);
    }

    public function testUpdateVolumesNoWaiverDecreasingVolumeNotST()
    {
        // Test 1 - No waiver - Decreasing volume - Risk > ST
        $hedge = $this->hedgeRepository->find(1);
        $this->hedgeVolumeManager->updateVolumesByHedge($hedge);

        $rmpSubSegmentRiskLevel = $this->doctrine->getRepository(RmpSubSegmentRiskLevel::class)->findOneBy([
            'rmpSubSegment' => $hedge->getHedgeLines()->first()->getRmpSubSegment(),
            'riskLevel' => $hedge->getHedgingTool()->getRiskLevel()
        ]);

        $this->assertEquals(200, $rmpSubSegmentRiskLevel->getConsumption());
    }

    public function testUpdateVolumesWaiverProductDecreasingVolumeNotST()
    {
        // Test 2 - Waiver product - Decreasing volume - Risk > ST
        $hedge = $this->hedgeRepository->find(2);
        $this->hedgeVolumeManager->updateVolumesByHedge($hedge);

        $rmpSubSegmentRiskLevel = $this->doctrine->getRepository(RmpSubSegmentRiskLevel::class)->findOneBy([
            'rmpSubSegment' => $hedge->getHedgeLines()->first()->getRmpSubSegment(),
            'riskLevel' => $hedge->getHedgingTool()->getRiskLevel()
        ]);

        $this->assertEquals(300, $rmpSubSegmentRiskLevel->getConsumption());

    }

    public function testUpdateVolumesWaiverVolumeDecreasingVolumeNotST()
    {
        // Test 3 - Waiver volume - Decreasing volume - Risk > ST
        $hedge = $this->hedgeRepository->find(3);
        $this->hedgeVolumeManager->updateVolumesByHedge($hedge);

        $rmpSubSegmentRiskLevel = $this->doctrine->getRepository(RmpSubSegmentRiskLevel::class)->findOneBy([
            'rmpSubSegment' => $hedge->getHedgeLines()->first()->getRmpSubSegment(),
            'riskLevel' => $hedge->getHedgingTool()->getRiskLevel()
        ]);

        $this->assertEquals(2500, $rmpSubSegmentRiskLevel->getWaiverConsumption());

    }

    public function testUpdateVolumesNoWaiverDecreasingVolumeST()
    {
        // Test 4 - No waiver - Decreasing volume - Risk = ST
        $hedge = $this->hedgeRepository->find(4);
        $this->hedgeVolumeManager->updateVolumesByHedge($hedge);

        $rmpSubSegmentRiskLevel = $this->doctrine->getRepository(RmpSubSegmentRiskLevel::class)->findOneBy([
            'rmpSubSegment' => $hedge->getHedgeLines()->first()->getRmpSubSegment(),
            'riskLevel' => $hedge->getHedgingTool()->getRiskLevel()
        ]);

        $this->assertEquals(300, $rmpSubSegmentRiskLevel->getConsumption());
    }

    public function testUpdateVolumesNoWaiverIncreasingVolumeST()
    {
        // Test 5 - No waiver - Increasing volume - Risk = ST
        $hedge = $this->hedgeRepository->find(5);
        $this->hedgeVolumeManager->updateVolumesByHedge($hedge);

        $rmpSubSegmentRiskLevel = $this->doctrine->getRepository(RmpSubSegmentRiskLevel::class)->findOneBy([
            'rmpSubSegment' => $hedge->getHedgeLines()->first()->getRmpSubSegment(),
            'riskLevel' => $hedge->getHedgingTool()->getRiskLevel()
        ]);

        $this->assertEquals(0, $rmpSubSegmentRiskLevel->getConsumption());
    }

    public function testUpdateVolumesNoWaiverOperationSellDecreasingVolumeWithHedgingToolCallSell()
    {
        // Test 6 - No waiver - Sell Decreasing volume - Hedging tool = CALLSell
        $hedge = $this->hedgeRepository->find(6);
        $this->hedgeVolumeManager->updateVolumesByHedge($hedge);

        $rmpSubSegmentRiskLevel = $this->doctrine->getRepository(RmpSubSegmentRiskLevel::class)->findOneBy([
            'rmpSubSegment' => $hedge->getHedgeLines()->first()->getRmpSubSegment(),
            'riskLevel' => 0
        ]);

        $this->assertEquals(300, $rmpSubSegmentRiskLevel->getConsumption());
    }
}
