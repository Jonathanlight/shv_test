<?php

namespace App\Tests\Service;

use App\Entity\Hedge;
use App\Service\HedgeLimitManager;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class HedgeLimitManagerTest extends KernelTestCase
{
    private $doctrine;

    private $hedgeLimitManager;

    public function setUp()
    {
        self::bootKernel();
        $this->doctrine = self::$container->get('doctrine');
        $this->hedgeLimitManager = self::$container->get(HedgeLimitManager::class);
    }

    public function testGetVolumeDetails()
    {
        $hedge = $this->doctrine->getRepository(Hedge::class)->find(7);
        $volumeAndLimits = $this->hedgeLimitManager->getVolumeAndLimitDetails($hedge);

        // Test open limits volume
        $this->assertEquals(1700, $volumeAndLimits['openLimits']['n']['volume']);
        $this->assertEquals(0, $volumeAndLimits['openLimits']['n1']['volume']);
        $this->assertEquals(0, $volumeAndLimits['openLimits']['n2']['volume']);
    }

    public function testGetRequestDetails()
    {
        $hedge = $this->doctrine->getRepository(Hedge::class)->find(7);
        $volumeAndLimits = $this->hedgeLimitManager->getVolumeAndLimitDetails($hedge);

        // Test request volume
        $this->assertEquals(100, $volumeAndLimits['requestHedging']['n']['volume']);
        $this->assertEquals(0, $volumeAndLimits['requestHedging']['n1']['volume']);
        $this->assertEquals(0, $volumeAndLimits['requestHedging']['n2']['volume']);
    }

}
