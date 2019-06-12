<?php

namespace App\Entity\MasterData;

use App\Entity\User;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 */
class BusinessUnit
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=20)
     */
    private $fullName;

    /**
     * @ORM\Column(type="string", length=20)
     */
    private $listName;

    /**
     * @ORM\Column(type="string", length=20)
     */
    private $counterpartCode;

    /**
     * @ORM\Column(type="string", length=20, nullable=true)
     */
    private $groupName;

    /**
     * @ORM\ManyToMany(targetEntity="App\Entity\User", mappedBy="businessUnits")
     */
    private $users;

    /**
     * @ORM\Column(type="boolean")
     */
    private $active;

    /**
     * BusinessUnit constructor.
     */
    public function __construct()
    {
        $this->users = new ArrayCollection();
    }

    /**
     * @return int
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getFullName(): ?string
    {
        return $this->fullName;
    }

    /**
     * @param string $fullName
     *
     * @return BusinessUnit
     */
    public function setFullName(string $fullName): self
    {
        $this->fullName = $fullName;

        return $this;
    }

    /**
     * @return string
     */
    public function getListName(): ?string
    {
        return $this->listName;
    }

    /**
     * @param string $listName
     *
     * @return BusinessUnit
     */
    public function setListName(string $listName): self
    {
        $this->listName = $listName;

        return $this;
    }

    /**
     * @return string
     */
    public function getCounterpartCode(): ?string
    {
        return $this->counterpartCode;
    }

    /**
     * @param string $counterpartCode
     *
     * @return BusinessUnit
     */
    public function setCounterpartCode(string $counterpartCode): self
    {
        $this->counterpartCode = $counterpartCode;

        return $this;
    }

    /**
     * @return string
     */
    public function getGroupName(): ?string
    {
        return $this->groupName;
    }

    /**
     * @param string $groupName
     *
     * @return BusinessUnit
     */
    public function setGroupName(?string $groupName): self
    {
        $this->groupName = $groupName;

        return $this;
    }

    /**
     * @return Collection|User[]
     */
    public function getUsers(): Collection
    {
        return $this->users;
    }

    /**
     * @param User $user
     *
     * @return BusinessUnit
     */
    public function addUser(User $user): self
    {
        if (!$this->users->contains($user)) {
            $this->users[] = $user;
            $user->addBusinessUnit($this);
        }

        return $this;
    }

    /**
     * @param User $user
     *
     * @return BusinessUnit
     */
    public function removeUser(User $user): self
    {
        if ($this->users->contains($user)) {
            $this->users->removeElement($user);
            $user->removeBusinessUnit($this);
        }

        return $this;
    }

    /**
     * @return bool
     */
    public function isActive(): ?bool
    {
        return $this->active;
    }

    /**
     * @param bool $active
     *
     * @return BusinessUnit
     */
    public function setActive(bool $active): self
    {
        $this->active = $active;

        return $this;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return ''.$this->fullName;
    }
}
