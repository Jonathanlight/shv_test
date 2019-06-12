<?php

namespace App\Tests\Service;

use App\Entity\MasterData\Commodity;
use App\Entity\MasterData\UOM;
use App\Service\UomConverterManager;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class UomConverterManagerTest extends KernelTestCase
{
    public function setUp()
    {
        self::bootKernel();
    }

    public function testConvert()
    {
        $doctrine = self::$container->get('doctrine');
        $uomConverterManager = self::$container->get(UomConverterManager::class);
        $commodity = $doctrine->getRepository(Commodity::class)->find(1);
        $uomBbl = $doctrine->getRepository(UOM::class)->find(1);
        $uomGal = $doctrine->getRepository(UOM::class)->find(2);

        $result = $uomConverterManager->convert(100000, $commodity, $uomBbl, $uomGal);
        $this->assertEquals('4200000.00000000729400000201', $result);
    }
}
