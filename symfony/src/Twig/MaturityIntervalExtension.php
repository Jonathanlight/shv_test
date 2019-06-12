<?php
namespace App\Twig;

use App\Entity\MasterData\Maturity;
use App\Entity\RMP;
use App\Service\MaturityManager;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class MaturityIntervalExtension extends AbstractExtension
{
    private $maturityManager;

    public function __construct(MaturityManager $maturityManager)
    {
        $this->maturityManager = $maturityManager;
    }

    /**
     * @return array
     */
    public function getFunctions()
    {
        return [
            new TwigFunction('intervalAsText', array($this, 'intervalAsText')),
            new TwigFunction('interval', array($this, 'interval')),
        ];
    }

    /**
     * @param Maturity $maturity
     * @return string
     */
    public function intervalAsText(Maturity $maturity)
    {
        $interval = $this->maturityManager->getMaturityIntervalByRmp($maturity);

        return $interval >= 0 ? 'M+'.$interval : 'M'.$interval;
    }

    /**
     * @param Maturity $maturity
     * @return string
     */
    public function interval(Maturity $maturity)
    {
        $interval = $this->maturityManager->getMaturityIntervalByRmp($maturity);

        return $interval;
    }
}