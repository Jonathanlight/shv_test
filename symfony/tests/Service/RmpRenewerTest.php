<?php

namespace App\Tests\Service;

use App\Entity\RMP;
use App\Service\RmpManager;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class RmpRenewerTest extends KernelTestCase
{
    public function setUp()
    {
        self::bootKernel();
    }

    public function testRmpRenewer()
    {
        $doctrine = self::$container->get('doctrine');
        $rmpRepository = $doctrine->getRepository(RMP::class);
        $rmpManager = self::$container->get(RmpManager::class);

        $rmp = $rmpRepository->find(4);
        $newRmp = $rmpManager->renewRmp($rmp);

        $this->assertEquals($rmp->getValidityPeriod() + 1, $newRmp->getValidityPeriod());
        $this->assertEquals(RMP::STATUS_APPROVED, $newRmp->getStatus());
        $this->assertEquals(true, $newRmp->isApprovedAutomatically());
        $this->assertEquals(false, $newRmp->isAmendment());
        $this->assertEquals($rmp->getActiveRmpSubSegments()->count(), $newRmp->getActiveRmpSubSegments()->count());
    }
}
