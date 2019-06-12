<?php
namespace App\Twig;

use App\Entity\MasterData\Commodity;
use App\Entity\MasterData\UOM;
use App\Service\UomConverterManager;
use Doctrine\ORM\EntityManagerInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class UomConverterExtension extends AbstractExtension
{
    private $em;

    private $uomConverterManager;

    /**
     * UomConverterExtension constructor.
     * @param EntityManagerInterface $em
     * @param UomConverterManager $uomConverterManager
     */
    public function __construct(EntityManagerInterface $em, UomConverterManager $uomConverterManager)
    {
        $this->em = $em;
        $this->uomConverterManager = $uomConverterManager;
    }

    /**
     * @return array
     */
    public function getFunctions()
    {
        return [
            new TwigFunction('uomConvert', array($this, 'uomConvert')),
        ];
    }

    /**
     * @param $value
     * @param Commodity $commodity
     * @param UOM $uomFrom
     * @param UOM $uomTo
     * @return string
     */
    public function uomConvert($value, Commodity $commodity, UOM $uomFrom, UOM $uomTo)
    {
        return number_format($this->uomConverterManager->convert($value, $commodity, $uomFrom, $uomTo), 0, '', '');
    }
}