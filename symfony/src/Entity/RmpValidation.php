<?php

namespace App\Entity;

use App\Entity\Traits\TimestampableTrait;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\RmpValidationRepository")
 * @ORM\HasLifecycleCallbacks()
 */
class RmpValidation
{
    use TimestampableTrait;

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\User")
     * @ORM\JoinColumn(nullable=false)
     */
    private $user;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Rmp")
     * @ORM\JoinColumn(nullable=false)
     */
    private $rmp;

    /**
     * @ORM\Column(type="boolean")
     */
    private $active = 1;

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return User
     */
    public function getUser(): User
    {
        return $this->user;
    }

    /**
     * @param User $user
     *
     * @return RmpValidation
     */
    public function setUser(User $user): self
    {
        $this->user = $user;

        return $this;
    }

    /**
     * @return Rmp
     */
    public function getRmp(): Rmp
    {
        return $this->rmp;
    }

    /**
     * @param Rmp $rmp
     *
     * @return RmpValidation
     */
    public function setRmp(Rmp $rmp): self
    {
        $this->rmp = $rmp;

        return $this;
    }

    /**
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->active;
    }

    /**
     * @param bool $active
     *
     * @return RmpValidation
     */
    public function setActive(bool $active): self
    {
        $this->active = $active;

        return $this;
    }
}
