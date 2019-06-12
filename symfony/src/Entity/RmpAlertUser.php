<?php

namespace App\Entity;

use App\Entity\Traits\ViewableTrait;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 * @ORM\Entity(repositoryClass="App\Repository\RmpAlertUserRepository")
 */
class RmpAlertUser
{
    use ViewableTrait;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\RmpAlert")
     * @ORM\JoinColumn(nullable=false)
     */
    private $alert;

    /**
     * @return RmpAlert
     */
    public function getAlert(): RmpAlert
    {
        return $this->alert;
    }

    /**
     * @param RmpAlert $alert
     *
     * @return $this
     */
    public function setAlert(RmpAlert $alert)
    {
        $this->alert = $alert;

        return $this;
    }
}