<?php

namespace App\Entity;

use App\Constant\Operations;
use App\Entity\MasterData\Currency;
use App\Entity\MasterData\HedgingTool;
use App\Entity\MasterData\Maturity;
use App\Entity\MasterData\PriceRiskClassification;
use App\Entity\MasterData\Product;
use App\Entity\MasterData\SubSegment;
use App\Entity\MasterData\UOM;
use App\Entity\Traits\TimestampableTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\HedgeRepository")
 * @ORM\HasLifecycleCallbacks()
 */
class Hedge
{
    use TimestampableTrait;

    /** To use in filters */
    const STATUS_ALL = -10;

    const STATUS_CANCELED = -1;
    const STATUS_DRAFT = 0;
    const STATUS_PENDING_APPROVAL_RISK_CONTROLLER = 1;
    const STATUS_PENDING_APPROVAL_BOARD_MEMBER = 2;
    const STATUS_PENDING_EXECUTION = 3;
    const STATUS_REALIZED = 4;

    public static $statusLabelsAll = [
        self::STATUS_DRAFT => 'hedge.status.draft',
        self::STATUS_PENDING_APPROVAL_RISK_CONTROLLER => 'hedge.status.pending_approval.risk_controller',
        self::STATUS_PENDING_APPROVAL_BOARD_MEMBER => 'hedge.status.pending_approval.board_member',
        self::STATUS_PENDING_EXECUTION => 'hedge.status.pending_execution',
        self::STATUS_REALIZED => 'hedge.status.realized',
        self::STATUS_CANCELED => 'hedge.status.canceled',
    ];

    public static $statusLabelsRestricted = [
        self::STATUS_PENDING_APPROVAL_RISK_CONTROLLER => 'hedge.status.pending_approval.risk_controller',
        self::STATUS_PENDING_APPROVAL_BOARD_MEMBER => 'hedge.status.pending_approval.board_member',
        self::STATUS_PENDING_EXECUTION => 'hedge.status.pending_execution',
        self::STATUS_REALIZED => 'hedge.status.realized',
        self::STATUS_CANCELED => 'hedge.status.canceled',
    ];

    const FILTER_FLAG_PARTIALLY_REALIZED = 1;
    const FILTER_FLAG_EXTRA_APPROVAL = 2;

    public static $flagLabels = [
        self::FILTER_FLAG_PARTIALLY_REALIZED => 'hedge.status.partially_realized',
        self::FILTER_FLAG_EXTRA_APPROVAL => 'hedge.status.extra_approval',
    ];

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
    private $creator;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\User")
     */
    private $canceler;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\User")
     */
    private $validatorLevel1;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\User")
     */
    private $validatorLevel2;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\User")
     */
    private $trader;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\MasterData\Product")
     */
    private $product1;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\MasterData\Product")
     */
    private $product2;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\RMP")
     */
    private $rmp;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\RMP")
     */
    private $firstRmp;

    /**
     * @ORM\Column(type="boolean")
     */
    private $waiverProduct = false;

    /**
     * @ORM\Column(type="boolean")
     */
    private $partiallyRealized = false;

    /**
     * @ORM\Column(type="boolean")
     */
    private $waiverClassRiskLevel = false;

    /**
     * @ORM\Column(type="boolean")
     */
    private $extraApproval = false;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\MasterData\Currency")
     * @ORM\JoinColumn(nullable=false)
     * @ORM\OrderBy({"code" = "DESC"})
     */
    private $currency;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\MasterData\UOM")
     * @ORM\JoinColumn(nullable=true)
     * @ORM\OrderBy({"code" = "DESC"})
     */
    private $uom;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\MasterData\HedgingTool")
     * @ORM\JoinColumn(nullable=false)
     */
    private $hedgingTool;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\HedgeLine", mappedBy="hedge", cascade={"persist", "remove"})
     */
    private $hedgeLines;

    /**
     * @ORM\Column(type="integer")
     */
    private $status;

    /**
     * @ORM\Column(type="integer")
     */
    private $operationType;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $description;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\MasterData\SubSegment")
     * @ORM\JoinColumn(nullable=false)
     */
    private $subSegment;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\MasterData\PriceRiskClassification", inversedBy="hedges")
     */
    private $priceRiskClassification;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\MasterData\Maturity")
     * @ORM\JoinColumn(nullable=false)
     */
    private $firstMaturity;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\MasterData\Maturity")
     * @ORM\JoinColumn(nullable=false)
     */
    private $lastMaturity;

    /**
     * @ORM\Column(type="float")
     */
    private $totalVolume;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $orderDate;

    /**
     * @ORM\Column(type="boolean")
     */
    private $pendingCancelation = false;

    /**
     * @ORM\Column(type="boolean")
     */
    private $imported = false;

    /**
     * @ORM\Column(type="string", unique=true, nullable=true, length=25)
     */
    private $code;

    /**
     * Hedge constructor.
     */
    public function __construct()
    {
        $this->hedgeLines = new ArrayCollection();
    }

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return User|null
     */
    public function getCreator(): ?User
    {
        return $this->creator;
    }

    /**
     * @param User|null $creator
     *
     * @return Hedge
     */
    public function setCreator(?User $creator): self
    {
        $this->creator = $creator;

        return $this;
    }

    /**
     * @return User|null
     */
    public function getCanceler(): ?User
    {
        return $this->canceler;
    }

    /**
     * @param User|null $canceler
     *
     * @return Hedge
     */
    public function setCanceler(?User $canceler): self
    {
        $this->canceler = $canceler;

        return $this;
    }

    /**
     * @return User|null
     */
    public function getValidatorLevel1(): ?User
    {
        return $this->validatorLevel1;
    }

    /**
     * @param User|null $validatorLevel1
     *
     * @return Hedge
     */
    public function setValidatorLevel1(?User $validatorLevel1): self
    {
        $this->validatorLevel1 = $validatorLevel1;

        return $this;
    }

    /**
     * @return User|null
     */
    public function getValidatorLevel2(): ?User
    {
        return $this->validatorLevel2;
    }

    /**
     * @param User|null $validatorLevel2
     *
     * @return Hedge
     */
    public function setValidatorLevel2(?User $validatorLevel2): self
    {
        $this->validatorLevel2 = $validatorLevel2;

        return $this;
    }

    /**
     * @return User|null
     */
    public function getTrader(): ?User
    {
        return $this->trader;
    }

    /**
     * @param User|null $trader
     *
     * @return Hedge
     */
    public function setTrader(?User $trader): self
    {
        $this->trader = $trader;

        return $this;
    }

    /**
     * @return Product|null
     */
    public function getProduct1(): ?Product
    {
        return $this->product1;
    }

    /**
     * @param Product|null $product1
     *
     * @return Hedge
     */
    public function setProduct1(?Product $product1): self
    {
        $this->product1 = $product1;

        return $this;
    }

    /**
     * @return Product|null
     */
    public function getProduct2(): ?Product
    {
        return $this->product2;
    }

    /**
     * @param Product|null $product2
     *
     * @return Hedge
     */
    public function setProduct2(?Product $product2): self
    {
        $this->product2 = $product2;

        return $this;
    }

    /**
     * @return RMP|null
     */
    public function getRmp(): ?RMP
    {
        return $this->rmp;
    }

    /**
     * @param RMP|null $rmp
     *
     * @return Hedge
     */
    public function setRmp(?RMP $rmp): self
    {
        $this->rmp = $rmp;

        return $this;
    }

    /**
     * @return RMP|null
     */
    public function getFirstRmp(): ?RMP
    {
        return $this->firstRmp;
    }

    /**
     * @param RMP|null $firstRmp
     *
     * @return Hedge
     */
    public function setFirstRmp(?RMP $firstRmp): self
    {
        $this->firstRmp = $firstRmp;

        return $this;
    }

    /**
     * @return Currency|null
     */
    public function getCurrency(): ?Currency
    {
        return $this->currency;
    }

    /**
     * @param Currency|null $currency
     *
     * @return Hedge
     */
    public function setCurrency(?Currency $currency): self
    {
        $this->currency = $currency;

        return $this;
    }

    /**
     * @return UOM|null
     */
    public function getUom(): ?UOM
    {
        return $this->uom;
    }

    /**
     * @param UOM|null $uom
     *
     * @return Hedge
     */
    public function setUom(?UOM $uom): self
    {
        $this->uom = $uom;

        return $this;
    }

    /**
     * @return HedgingTool|null
     */
    public function getHedgingTool(): ?HedgingTool
    {
        return $this->hedgingTool;
    }

    /**
     * @param HedgingTool|null $hedgingTool
     *
     * @return Hedge
     */
    public function setHedgingTool(?HedgingTool $hedgingTool): self
    {
        $this->hedgingTool = $hedgingTool;

        return $this;
    }

    /**
     * @return Collection|HedgeLine[]
     */
    public function getHedgeLines(): Collection
    {
        return $this->hedgeLines;
    }

    /**
     * @param HedgeLine $hedgeLine
     *
     * @return Hedge
     */
    public function addHedgeLine(HedgeLine $hedgeLine): self
    {
        if (!$this->hedgeLines->contains($hedgeLine)) {
            $this->hedgeLines[] = $hedgeLine;
            $hedgeLine->setHedge($this);
        }

        return $this;
    }

    /**
     * @param HedgeLine $hedgeLine
     *
     * @return Hedge
     */
    public function removeHedgeLine(HedgeLine $hedgeLine): self
    {
        if ($this->hedgeLines->contains($hedgeLine)) {
            $this->hedgeLines->removeElement($hedgeLine);
            if ($hedgeLine->getHedge() === $this) {
                $hedgeLine->setHedge(null);
            }
        }

        return $this;
    }

    /**
     * @return int|null
     */
    public function getStatus(): ?int
    {
        return $this->status;
    }

    /**
     * @param int|null $status
     *
     * @return Hedge
     */
    public function setStatus(?int $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getStatusLabel(): ?string
    {
        return self::$statusLabelsAll[$this->status];
    }

    /**
     * @return string|null
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * @param string|null $description
     *
     * @return Hedge
     */
    public function setDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getOperationType(): ?int
    {
        return $this->operationType;
    }

    /**
     * @param int|null $operationType
     *
     * @return Hedge
     */
    public function setOperationType(?int $operationType): self
    {
        $this->operationType = $operationType;

        return $this;
    }

    /**
     * @return PriceRiskClassification|null
     */
    public function getPriceRiskClassification(): ?PriceRiskClassification
    {
        return $this->priceRiskClassification;
    }

    /**
     * @param PriceRiskClassification|null $priceRiskClassification
     *
     * @return Hedge
     */
    public function setPriceRiskClassification(PriceRiskClassification $priceRiskClassification): self
    {
        $this->priceRiskClassification = $priceRiskClassification;

        return $this;
    }

    /**
     * @return SubSegment|null
     */
    public function getSubSegment(): ?SubSegment
    {
        return $this->subSegment;
    }

    /**
     * @param SubSegment|null $subSegment
     *
     * @return Hedge
     */
    public function setSubSegment(?SubSegment $subSegment): self
    {
        $this->subSegment = $subSegment;

        return $this;
    }

    /**
     * @return Maturity|null
     */
    public function getFirstMaturity(): ?Maturity
    {
        return $this->firstMaturity;
    }

    /**
     * @param Maturity|null $firstMaturity
     *
     * @return Hedge
     */
    public function setFirstMaturity(?Maturity $firstMaturity): self
    {
        $this->firstMaturity = $firstMaturity;

        return $this;
    }

    /**
     * @return Maturity|null
     */
    public function getLastMaturity(): ?Maturity
    {
        return $this->lastMaturity;
    }

    /**
     * @param Maturity|null $lastMaturity
     *
     * @return Hedge
     */
    public function setLastMaturity(?Maturity $lastMaturity): self
    {
        $this->lastMaturity = $lastMaturity;

        return $this;
    }

    /**
     * @return float|null
     */
    public function getTotalVolume(): ?float
    {
        return $this->totalVolume;
    }

    /**
     * @param float $totalVolume
     *
     * @return Hedge
     */
    public function setTotalVolume($totalVolume): self
    {
        $this->totalVolume = $totalVolume;

        return $this;
    }

    /**
     * @return bool
     */
    public function isPendingApproval(): bool
    {
        return self::STATUS_PENDING_APPROVAL_RISK_CONTROLLER == $this->status || self::STATUS_PENDING_APPROVAL_BOARD_MEMBER == $this->status;
    }

    /**
     * @return bool
     */
    public function isPendingExecution(): bool
    {
        return self::STATUS_PENDING_EXECUTION == $this->status;
    }

    /**
     * @return bool
     */
    public function isRealized(): bool
    {
        return self::STATUS_REALIZED == $this->status;
    }

    public function isDraft(): bool
    {
        return self::STATUS_DRAFT == $this->status;
    }

    /**
     * @return bool|null
     */
    public function isWaiverProduct(): ?bool
    {
        return $this->waiverProduct;
    }

    /**
     * @param bool|null $waiverProduct
     *
     * @return Hedge
     */
    public function setWaiverProduct(?bool $waiverProduct): self
    {
        $this->waiverProduct = $waiverProduct;

        return $this;
    }

    /**
     * @return bool|null
     */
    public function isPartiallyRealized(): ?bool
    {
        return $this->partiallyRealized;
    }

    /**
     * @param bool|null $partiallyRealized
     *
     * @return Hedge
     */
    public function setPartiallyRealized(?bool $partiallyRealized): self
    {
        $this->partiallyRealized = $partiallyRealized;

        return $this;
    }

    /**
     * @return bool|null
     */
    public function isExtraApproval(): ?bool
    {
        return $this->extraApproval;
    }

    /**
     * @param bool|null $extraApproval
     *
     * @return Hedge
     */
    public function setExtraApproval(?bool $extraApproval): self
    {
        $this->extraApproval = $extraApproval;

        return $this;
    }

    /**
     * @return bool|null
     */
    public function isWaiverClassRiskLevel(): ?bool
    {
        return $this->waiverClassRiskLevel;
    }

    /**
     * @param bool|null $waiverClassRiskLevel
     *
     * @return Hedge
     */
    public function setWaiverClassRiskLevel(?bool $waiverClassRiskLevel): self
    {
        $this->waiverClassRiskLevel = $waiverClassRiskLevel;

        return $this;
    }

    /**
     * @param \DateTime $orderDate
     *
     * @return $this
     */
    public function setOrderDate($orderDate): self
    {
        $this->orderDate = $orderDate;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getOrderDate(): ?\DateTime
    {
        return $this->orderDate;
    }

    /** @param bool $pendingCancelation
     *
     * @return $this
     */
    public function setPendingCancelation(bool $pendingCancelation): self
    {
        $this->pendingCancelation = $pendingCancelation;

        return $this;
    }

    /**
     * @return bool
     */
    public function isPendingCancelation(): bool
    {
        return $this->pendingCancelation;
    }

    /** @param bool $imported
     *
     * @return $this
     */
    public function setImported(bool $imported): self
    {
        $this->imported = $imported;

        return $this;
    }

    /**
     * @return bool
     */
    public function isImported(): bool
    {
        return $this->imported;
    }

    /** @param string|null $code
     *
     * @return $this
     */
    public function setCode(?string $code): self
    {
        $this->code = $code;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getCode(): ?string
    {
        return $this->code;
    }

    /**
     * @return bool
     */
    public function isWaiver(): bool
    {
        $waiver = false;
        if ($this->isWaiverClassRiskLevel() || $this->isWaiverProduct()) {
            $waiver = true;
        }
        foreach ($this->getHedgeLines() as $hedgeLine) {
            if ($hedgeLine->isWaiverMaturity() || $hedgeLine->isWaiverVolume()) {
                $waiver = true;
            }
        }

        return $waiver;
    }

    /**
     * @return bool
     */
    public function isWaiverMaturity(): bool
    {
       $isWaiverMaturity = false;
       foreach ($this->getHedgeLines() as $hedgeLine) {
           if ($hedgeLine->isWaiverMaturity()) {
               $isWaiverMaturity = true;
           }
       }

       return $isWaiverMaturity;
    }
}
