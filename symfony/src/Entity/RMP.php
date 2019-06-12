<?php

namespace App\Entity;

use App\Entity\MasterData\BusinessUnit;
use App\Entity\MasterData\SubSegment;
use App\Entity\Traits\TimestampableTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\RmpRepository")
 */
class RMP
{
    use TimestampableTrait;

    const DEFAULT_MAX_LOSS = 'rmp.default.max_loss';
    const DEFAULT_PROTECTION_PRICE = 'rmp.default.protection_price';
    const DEFAULT_PREMIUM_HEDGING_TOOL = 'rmp.default.premium_hedging_tool';

    const STATUS_ALL = -10;

    const STATUS_ARCHIVED = -1;
    const STATUS_DRAFT = 0;
    const STATUS_PENDING_APPROVAL_RISK_CONTROLLER = 1;
    const STATUS_PENDING_APPROVAL_BOARD_MEMBER = 2;
    const STATUS_APPROVED = 4;

    public static $statusLabels = [
        self::STATUS_DRAFT => 'rmp.status.draft',
        self::STATUS_PENDING_APPROVAL_RISK_CONTROLLER => 'rmp.status.pending_approval.risk_controller',
        self::STATUS_PENDING_APPROVAL_BOARD_MEMBER => 'rmp.status.pending_approval.board_member',
        self::STATUS_APPROVED => 'rmp.status.approved',
        self::STATUS_ARCHIVED => 'rmp.status.archived',
    ];

    const FILTER_FLAG_APPROVED_AUTOMATICALLY = 1;
    const FILTER_FLAG_BLOCKED = 2;
    const FILTER_FLAG_AMENDMENT = 3;

    public static $flagLabels = [
        self::FILTER_FLAG_APPROVED_AUTOMATICALLY => 'rmp.flags.approved_automatically',
        self::FILTER_FLAG_BLOCKED => 'rmp.flags.blocked',
        self::FILTER_FLAG_AMENDMENT => 'rmp.flags.amendment',
    ];

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $status;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $name;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $generalComment;

    /**
     * @ORM\Column(type="integer")
     */
    private $validityPeriod;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\MasterData\BusinessUnit", inversedBy="rmps")
     */
    private $businessUnit;

    /**
     * @ORM\Column(type="boolean")
     */
    private $active;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\RmpSubSegment", mappedBy="rmp", cascade={"persist", "remove"})
     */
    private $rmpSubSegments;

    /**
     * @ORM\Column(type="boolean")
     */
    private $approvedAutomatically = true;

    /**
     * @ORM\Column(type="boolean")
     */
    private $archivedAutomatically = false;

    /**
     * @ORM\Column(type="boolean")
     */
    private $amendment = true;

    /**
     * @ORM\Column(type="integer")
     */
    private $version = 1;

    /**
     * @ORM\Column(type="boolean")
     */
    private $blocked = false;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\RMP", inversedBy="rmps")
     */
    private $copiedFrom;

    /**
     * @ORM\Column(type="boolean")
     */
    private $n3Exists = false;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\User", inversedBy="rmps")
     */
    private $creator;

    /**
     * RMP constructor.
     */
    public function __construct()
    {
        $this->rmpSubSegments = new ArrayCollection();
    }

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @param int|null $id
     * @return self
     */
    public function setId(?int $id): self
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return null|string
     */
    public function getStatus(): ?string
    {
        return $this->status;
    }

    /**
     * @param string $status
     *
     * @return RMP
     */
    public function setStatus(string $status): self
    {
        $this->status = $status;

        return $this;
    }

    /**
     * @return null|string
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * @param string $name
     *
     * @return RMP
     */
    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return null|string
     */
    public function getGeneralComment(): ?string
    {
        return $this->generalComment;
    }

    /**
     * @param null|string $generalComment
     *
     * @return RMP
     */
    public function setGeneralComment(?string $generalComment): self
    {
        $this->generalComment = $generalComment;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getValidityPeriod(): ?int
    {
        return $this->validityPeriod;
    }

    /**
     * @param int $validityPeriod
     *
     * @return RMP
     */
    public function setValidityPeriod(int $validityPeriod): self
    {
        $this->validityPeriod = $validityPeriod;

        return $this;
    }

    /**
     * @return BusinessUnit|null
     */
    public function getBusinessUnit(): ?BusinessUnit
    {
        return $this->businessUnit;
    }

    /**
     * @param BusinessUnit|null $businessUnit
     *
     * @return RMP
     */
    public function setBusinessUnit(?BusinessUnit $businessUnit): self
    {
        $this->businessUnit = $businessUnit;

        return $this;
    }

    /**
     * @return bool|null
     */
    public function isActive(): ?bool
    {
        return $this->active;
    }

    /**
     * @param bool $active
     *
     * @return RMP
     */
    public function setActive(bool $active): self
    {
        $this->active = $active;

        return $this;
    }

    /**
     * @return bool|null
     */
    public function isApprovedAutomatically(): ?bool
    {
        return $this->approvedAutomatically;
    }

    /**
     * @param bool $approvedAutomatically
     *
     * @return RMP
     */
    public function setApprovedAutomatically(bool $approvedAutomatically): self
    {
        $this->approvedAutomatically = $approvedAutomatically;

        return $this;
    }

    /**
     * @return bool|null
     */
    public function isArchivedAutomatically(): ?bool
    {
        return $this->archivedAutomatically;
    }

    /**
     * @param bool $archivedAutomatically
     *
     * @return RMP
     */
    public function setArchivedAutomatically(bool $archivedAutomatically): self
    {
        $this->archivedAutomatically = $archivedAutomatically;

        return $this;
    }

    /**
     * @return bool|null
     */
    public function isAmendment(): ?bool
    {
        return $this->amendment;
    }

    /**
     * @param bool $amendment
     *
     * @return RMP
     */
    public function setAmendment(bool $amendment): self
    {
        $this->amendment = $amendment;

        return $this;
    }

    /**
     * @return Collection|RmpSubSegment[]
     */
    public function getActiveRmpSubSegments(): Collection
    {
        $rmpSubSegments = $this->getRmpSubSegments()->filter(function($entry) {
            return $entry->isActive() == true;
        });

        return $rmpSubSegments;
    }

    /**
     * @return Collection|RmpSubSegment[]
     */
    public function getRmpSubSegments(): Collection
    {
        return $this->rmpSubSegments;
    }

    /**
     * @param SubSegment $subSegment
     * @return RmpSubSegment|null
     */
    public function getActiveRmpSubSegmentBySubSegment(SubSegment $subSegment): ?RmpSubSegment
    {
        $rmpSubSegment =  $this->getActiveRmpSubSegments()->filter(function($entry) use ($subSegment) {
            return $entry->getSubSegment()->getId() == $subSegment->getId();
        })->first();

        return $rmpSubSegment ?: null;
    }

    /**
     * @param RmpSubSegment $rmpSubSegment
     *
     * @return RMP
     */
    public function addRmpSubSegment(RmpSubSegment $rmpSubSegment): self
    {
        if (!$this->rmpSubSegments->contains($rmpSubSegment)) {
            $this->rmpSubSegments[] = $rmpSubSegment;
            $rmpSubSegment->setRmp($this);
        }

        return $this;
    }

    /**
     * @param RmpSubSegment $rmpSubSegment
     *
     * @return RMP
     */
    public function removeRmpSubSegment(RmpSubSegment $rmpSubSegment): self
    {
        if ($this->rmpSubSegments->contains($rmpSubSegment)) {
            $this->rmpSubSegments->removeElement($rmpSubSegment);
            if ($rmpSubSegment->getRmp() === $this) {
                $rmpSubSegment->setRmp(null);
            }
        }

        return $this;
    }

    /**
     * @return null|string
     */
    public function getStatusLabel(): ?string
    {
        return self::$statusLabels[$this->status];
    }

    /**
     * @return bool
     */
    public function isPendingApproval(): bool
    {
        return self::STATUS_PENDING_APPROVAL_RISK_CONTROLLER == $this->status || self::STATUS_PENDING_APPROVAL_BOARD_MEMBER == $this->status;
    }

    /**
     * @return int
     */
    public function getVersion(): int
    {
        return $this->version;
    }

    /**
     * @param int $version
     * @return self
     */
    public function setVersion(int $version): self
    {
        $this->version = $version;

        return $this;
    }

    /**
     * @return RMP|null
     */
    public function getCopiedFrom(): ?RMP
    {
        return $this->copiedFrom;
    }

    /**
     * @param RMP|null $rmp
     *
     * @return RMP
     */
    public function setCopiedFrom(?RMP $rmp): RMP
    {
        $this->copiedFrom = $rmp;

        return $this;
    }

    /**
     * @return bool
     */
    public function isBlocked(): bool
    {
        return $this->blocked;
    }

    /**
     * @param bool $blocked
     *
     * @return RMP
     */
    public function setBlocked(bool $blocked): self
    {
        $this->blocked = $blocked;

        return $this;
    }

    /**
     * @return bool
     */
    public function isN3Exists(): bool
    {
        return $this->n3Exists;
    }

    /**
     * @param bool $n3Exists
     *
     * @return RMP
     */
    public function setN3Exists(bool $n3Exists): self
    {
        $this->n3Exists = $n3Exists;

        return $this;
    }

    /**
     * @return User
     */
    public function getCreator(): User
    {
        return $this->creator;
    }

    /**
     * @param User $creator
     *
     * @return RMP
     */
    public function setCreator(User $creator): self
    {
        $this->creator = $creator;

        return $this;
    }

    /**
     * @return bool
     */
    public function isDraft(): bool
    {
        return $this->getStatus() == self::STATUS_DRAFT;
    }

    /**
     * @return bool
     */
    public function isApproved(): bool
    {
        return $this->getStatus() == self::STATUS_APPROVED;
    }

    /**
     * @return bool
     */
    public function isArchived(): bool
    {
        return $this->getStatus() == self::STATUS_ARCHIVED;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->name;
    }

    /**
     * Clone function
     */
    public function __clone()
    {
       if ($this->id) {
           $this->setId(null);
           $this->setBlocked(false);
           $this->setGeneralComment('');

           $rmpSubSegments = new ArrayCollection();
           foreach ($this->rmpSubSegments as $rmpSubSegment) {
               if (!$rmpSubSegment->isActive()) {
                   continue;
               }
               $rmpSubSegmentClone = clone $rmpSubSegment;
               $rmpSubSegmentClone->setCopiedFrom($rmpSubSegment);
               $rmpSubSegmentClone->setRmp($this);
               $rmpSubSegments->add($rmpSubSegmentClone);
           }

           $this->rmpSubSegments = $rmpSubSegments;
       }
    }
}
