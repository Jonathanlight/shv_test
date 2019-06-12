<?php

namespace App\Service;

use App\Entity\MasterData\Maturity;
use App\Entity\RMP;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Csrf\TokenStorage\TokenStorageInterface;

class MaturityManager
{

    private $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    /**
     * @param Maturity $maturity
     *
     * @return int
     */
    public function getMaturityIntervalByRmp(Maturity $maturity)
    {
        $currentYear = date('Y');
        $curentMonth = date('m');

        return $maturity->getMonth() - $curentMonth + ($maturity->getYear() - $currentYear) * 12;
    }
}