<?php

namespace App\Entity;

use App\Entity\Traits\ViewableTrait;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 * @ORM\Entity(repositoryClass="App\Repository\HedgeAlertUserRepository")
 */
class HedgeAlertUser
{
    use ViewableTrait;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\HedgeAlert")
     * @ORM\JoinColumn(nullable=false)
     */
    private $alert;

    /**
     * @return HedgeAlert
     */
    public function getAlert(): HedgeAlert
    {
        return $this->alert;
    }

    /**
     * @param HedgeAlert $alert
     *
     * @return $this
     */
    public function setAlert(HedgeAlert $alert)
    {
        $this->alert = $alert;

        return $this;
    }
}