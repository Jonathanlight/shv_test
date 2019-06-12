<?php

namespace App\Entity;

use App\Constant\Operations;
use App\Entity\MasterData\HedgingTool;
use App\Entity\MasterData\Maturity;
use App\Entity\MasterData\Strategy;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;


/**
 * @ORM\Entity(repositoryClass="App\Repository\HedgeLineRepository")
 */
class HedgeLine
{
    const CODE_WAIVER_MATURITY = 'MATURITY';
    const CODE_WAIVER_VOLUME = 'VOLUME';
    const CODE_WAIVER_PRODUCT = 'Index';

    const CODE_WAIVER_VOLUME_MATURITY = 'VOL&MAT';
    const CODE_WAIVER_PRODUCT_VOLUME = 'Index&Vol';
    const CODE_WAIVER_PRODUCT_MATURITY = 'Index&Mat';

    const CODE_WAIVER_ALL = 'Index-Vol-Mat';


    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $quantity;

    /**
     * @ORM\Column(type="float", nullable=true)
     *
     */
    private $quantityRealized = 0;

    /**
     * @ORM\Column(type="float", nullable=true)
     *
     */
    private $quantityCanceled = 0;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Hedge", inversedBy="hedgeLines")
     */
    private $hedge;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\MasterData\Maturity")
     * @ORM\JoinColumn(nullable=false)
     */
    private $maturity;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\MasterData\Strategy")
     */
    private $strategy;

    /**
     * @ORM\Column(type="string")
     */
    private $protectionPrice;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private $maxLoss;

    /**
     * @ORM\Column(type="string")
     */
    private $premiumHedgingTool;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\RmpSubSegment")
     */
    private $rmpSubSegment;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\RmpSubSegment")
     */
    private $firstRmpSubSegment;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $swapPrice = 0;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $swap1Price = 0;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $swap2Price = 0;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $callStrike = 0;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $call1Strike = 0;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $call2Strike = 0;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $callPremium = 0;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $call1Premium = 0;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $call2Premium = 0;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $putPremium = 0;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $put1Premium = 0;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $put2Premium = 0;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $putStrike = 0;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $put1Strike = 0;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $put2Strike = 0;

    /**
     * @ORM\Column(type="boolean")
     */
    private $waiverVolume = false;

    /**
     * @ORM\Column(type="boolean")
     */
    private $waiverMaturity = false;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Trade", mappedBy="hedgeLine")
     */
    private $trades;

    public function __construct()
    {
        $this->trades = new ArrayCollection();
    }


    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return float|null
     */
    public function getQuantity(): ?float
    {
        return $this->quantity;
    }

    /**
     * @param float $quantity
     *
     * @return HedgeLine
     */
    public function setQuantity(float $quantity): self
    {
        $this->quantity = $quantity;

        return $this;
    }

    /**
     * @return float|null
     */
    public function getQuantityRealized(): ?float
    {
        return $this->quantityRealized;
    }

    /**
     * @param float $quantityRealized
     *
     * @return HedgeLine
     */
    public function setQuantityRealized(float $quantityRealized): self
    {
        $this->quantityRealized = $quantityRealized;

        return $this;
    }

    /**
     * @param float $quantityRealized
     *
     * @return HedgeLine
     */
    public function addQuantityRealized(float $quantityRealized): self
    {
        $this->quantityRealized += $quantityRealized;

        return $this;
    }

    /**
     * @return float|null
     */
    public function getQuantityCanceled(): ?float
    {
        return $this->quantityCanceled;
    }

    /**
     * @param float $quantityCanceled
     *
     * @return HedgeLine
     */
    public function setQuantityCanceled(float $quantityCanceled): self
    {
        $this->quantityCanceled = $quantityCanceled;

        return $this;
    }

    /**
     * @return Hedge|null
     */
    public function getHedge(): ?Hedge
    {
        return $this->hedge;
    }

    /**
     * @param Hedge|null $hedge
     *
     * @return HedgeLine
     */
    public function setHedge(?Hedge $hedge): self
    {
        $this->hedge = $hedge;

        return $this;
    }

    /**
     * @return Maturity|null
     */
    public function getMaturity(): ?Maturity
    {
        return $this->maturity;
    }

    /**
     * @param Maturity|null $maturity
     *
     * @return HedgeLine
     */
    public function setMaturity(?Maturity $maturity): self
    {
        $this->maturity = $maturity;

        return $this;
    }

    /**
     * @return Strategy|null
     */
    public function getStrategy(): ?Strategy
    {
        return $this->strategy;
    }

    /**
     * @param Strategy|null $strategy
     *
     * @return HedgeLine
     */
    public function setStrategy(?Strategy $strategy): self
    {
        $this->strategy = $strategy;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getProtectionPrice(): ?string
    {
        return $this->protectionPrice;
    }

    /**
     * @param string|null $protectionPrice
     *
     * @return HedgeLine
     */
    public function setProtectionPrice(?string $protectionPrice): self
    {
        $this->protectionPrice = $protectionPrice;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getMaxLoss(): ?string
    {
        return $this->maxLoss;
    }

    /**
     * @param string|null $maxLoss
     *
     * @return HedgeLine
     */
    public function setMaxLoss(?string $maxLoss): self
    {
        $this->maxLoss = $maxLoss;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getPremiumHedgingTool(): ?string
    {
        return $this->premiumHedgingTool;
    }

    /**
     * @param string|null $premiumHedgingTool
     *
     * @return HedgeLine
     */
    public function setPremiumHedgingTool(?string $premiumHedgingTool): self
    {
        $this->premiumHedgingTool = $premiumHedgingTool;

        return $this;
    }

    /**
     * @return RmpSubSegment|null
     */
    public function getRmpSubSegment(): ?RmpSubSegment
    {
        return $this->rmpSubSegment;
    }

    /**
     * @param RmpSubSegment|null $rmpSubSegment
     *
     * @return HedgeLine
     */
    public function setRmpSubSegment(?RmpSubSegment $rmpSubSegment): self
    {
        $this->rmpSubSegment = $rmpSubSegment;

        return $this;
    }

    /**
     * @return RmpSubSegment|null
     */
    public function getFirstRmpSubSegment(): ?RmpSubSegment
    {
        return $this->firstRmpSubSegment;
    }

    /**
     * @param RmpSubSegment|null $firstRmpSubSegment
     *
     * @return HedgeLine
     */
    public function setFirstRmpSubSegment(?RmpSubSegment $firstRmpSubSegment): self
    {
        $this->firstRmpSubSegment = $firstRmpSubSegment;

        return $this;
    }

    /**
     * @return float|null
     */
    public function getSwapPrice(): ?float
    {
        return $this->swapPrice;
    }

    /**
     * @param float|null $swapPrice
     *
     * @return HedgeLine
     */
    public function setSwapPrice(?float $swapPrice): self
    {
        $this->swapPrice = $swapPrice;

        return $this;
    }

    /**
     * @return float|null
     */
    public function getSwap1Price(): ?float
    {
        return $this->swap1Price;
    }

    /**
     * @param float|null $swap1Price
     *
     * @return HedgeLine
     */
    public function setSwap1Price(?float $swap1Price): self
    {
        $this->swap1Price = $swap1Price;

        return $this;
    }

    /**
     * @return float|null
     */
    public function getSwap2Price(): ?float
    {
        return $this->swap2Price;
    }

    /**
     * @param float|null $swap2Price
     *
     * @return HedgeLine
     */
    public function setSwap2Price(?float $swap2Price): self
    {
        $this->swap2Price = $swap2Price;

        return $this;
    }

    /**
     * @return float|null
     */
    public function getCallStrike(): ?float
    {
        return $this->callStrike;
    }

    /**
     * @param float|null $callStrike
     *
     * @return HedgeLine
     */
    public function setCallStrike(?float $callStrike): self
    {
        $this->callStrike = $callStrike;

        return $this;
    }

    /**
     * @return float|null
     */
    public function getCall1Strike(): ?float
    {
        return $this->call1Strike;
    }

    /**
     * @param float|null $call1Strike
     *
     * @return HedgeLine
     */
    public function setCall1Strike(?float $call1Strike): self
    {
        $this->call1Strike = $call1Strike;

        return $this;
    }

    /**
     * @return float|null
     */
    public function getCall2Strike(): ?float
    {
        return $this->call2Strike;
    }

    /**
     * @param float|null $call2Strike
     *
     * @return HedgeLine
     */
    public function setCall2Strike(?float $call2Strike): self
    {
        $this->call2Strike = $call2Strike;

        return $this;
    }

    /**
     * @return float|null
     */
    public function getCallPremium(): ?float
    {
        return $this->callPremium;
    }

    /**
     * @param float|null $callPremium
     *
     * @return HedgeLine
     */
    public function setCallPremium(?float $callPremium): self
    {
        $this->callPremium = $callPremium;

        return $this;
    }

    /**
     * @return float|null
     */
    public function getCall1Premium(): ?float
    {
        return $this->call1Premium;
    }

    /**
     * @param float|null $call1Premium
     *
     * @return HedgeLine
     */
    public function setCall1Premium(?float $call1Premium): self
    {
        $this->call1Premium = $call1Premium;

        return $this;
    }

    /**
     * @return float|null
     */
    public function getCall2Premium(): ?float
    {
        return $this->call2Premium;
    }

    /**
     * @param float|null $call2Premium
     *
     * @return HedgeLine
     */
    public function setCall2Premium(?float $call2Premium): self
    {
        $this->call2Premium = $call2Premium;

        return $this;
    }

    /**
     * @return float|null
     */
    public function getPutPremium(): ?float
    {
        return $this->putPremium;
    }

    /**
     * @param float|null $putPremium
     *
     * @return HedgeLine
     */
    public function setPutPremium(?float $putPremium): self
    {
        $this->putPremium = $putPremium;

        return $this;
    }

    /**
     * @return float|null
     */
    public function getPut1Premium(): ?float
    {
        return $this->put1Premium;
    }

    /**
     * @param float|null $put1Premium
     *
     * @return HedgeLine
     */
    public function setPut1Premium(?float $put1Premium): self
    {
        $this->put1Premium = $put1Premium;

        return $this;
    }

    /**
     * @return float|null
     */
    public function getPut2Premium(): ?float
    {
        return $this->put2Premium;
    }

    /**
     * @param float|null $put2Premium
     *
     * @return HedgeLine
     */
    public function setPut2Premium(?float $put2Premium): self
    {
        $this->put2Premium = $put2Premium;

        return $this;
    }

    /**
     * @return float|null
     */
    public function getPutStrike(): ?float
    {
        return $this->putStrike;
    }

    /**
     * @param float|null $putStrike
     *
     * @return HedgeLine
     */
    public function setPutStrike(?float $putStrike): self
    {
        $this->putStrike = $putStrike;

        return $this;
    }

    /**
     * @return float|null
     */
    public function getPut1Strike(): ?float
    {
        return $this->put1Strike;
    }

    /**
     * @param float|null $put1Strike
     *
     * @return HedgeLine
     */
    public function setPut1Strike(?float $put1Strike): self
    {
        $this->put1Strike = $put1Strike;

        return $this;
    }

    /**
     * @return float|null
     */
    public function getPut2Strike(): ?float
    {
        return $this->put2Strike;
    }

    /**
     * @param float|null $put2Strike
     *
     * @return HedgeLine
     */
    public function setPut2Strike(?float $put2Strike): self
    {
        $this->put2Strike = $put2Strike;

        return $this;
    }

    /**
     * @return bool|null
     */
    public function isWaiverVolume(): ?bool
    {
        return $this->waiverVolume;
    }

    /**
     * @param bool|null $waiverVolume
     *
     * @return HedgeLine
     */
    public function setWaiverVolume(?bool $waiverVolume): self
    {
        $this->waiverVolume = $waiverVolume;

        return $this;
    }

    /**
     * @return bool|null
     */
    public function isWaiverMaturity(): ?bool
    {
        return $this->waiverMaturity;
    }

    /**
     * @param bool|null $waiverMaturity
     *
     * @return HedgeLine
     */
    public function setWaiverMaturity(?bool $waiverMaturity): self
    {
        $this->waiverMaturity = $waiverMaturity;

        return $this;
    }

    public function getWaiversAsText(): string
    {
        $waivers = [];
        if ($this->waiverMaturity) {
            $waivers[] = 'maturity';
        }

        if ($this->waiverVolume) {
            $waivers[] = 'volume';
        }

        return count($waivers) ? implode(' / ', $waivers) : '-';
    }

    /**
     * @return Collection|Trade[]
     */
    public function getTrades(): Collection
    {
        return $this->trades;
    }

    public function addTrade(Trade $trade): self
    {
        if (!$this->trades->contains($trade)) {
            $this->trades[] = $trade;
            $trade->setHedgeLine($this);
        }

        return $this;
    }

    public function removeTrade(Trade $trade): self
    {
        if ($this->trades->contains($trade)) {
            $this->trades->removeElement($trade);
            // set the owning side to null (unless already changed)
            if ($trade->getHedgeLine() === $this) {
                $trade->setHedgeLine(null);
            }
        }

        return $this;
    }

    public function getWaiversCodes()
    {
        if ($this->isWaiverMaturity() && $this->isWaiverVolume() && $this->hedge->isWaiverProduct()) {
            return self::CODE_WAIVER_ALL;
        } else if ($this->isWaiverVolume() && $this->isWaiverMaturity()) {
            return self::CODE_WAIVER_VOLUME_MATURITY;
        } else if ($this->isWaiverVolume() && $this->hedge->isWaiverProduct()) {
            return self::CODE_WAIVER_PRODUCT_VOLUME;
        } else if ($this->isWaiverMaturity() && $this->hedge->isWaiverProduct()) {
            return self::CODE_WAIVER_PRODUCT_MATURITY;
        } else if ($this->isWaiverVolume()) {
            return self::CODE_WAIVER_VOLUME;
        } else if ($this->isWaiverMaturity()) {
            return self::CODE_WAIVER_MATURITY;
        } else if ($this->hedge->isWaiverProduct()) {
            return self::CODE_WAIVER_PRODUCT;
        } else {
            return '';
        }
    }

    /**
     * @param string $operation
     * @return float
     */
    public function getStrikeByOperation(string $operation): float
    {
        return  call_user_func(array($this, 'get'.ucfirst($operation).'Strike'));
    }

    /**
     * @param string $operation
     * @return float
     */
    public function getPremiumByOperation(string $operation): float
    {
        return  call_user_func(array($this, 'get'.ucfirst($operation).'Premium'));
    }

    /**
     * @param string $operation
     * @return float
     */
    public function getSwapByOperation(string $operation): float
    {
        if ($operation == 'swaps') {
            $operation = 'swap';
        }

        return call_user_func(array($this, 'get'.ucfirst($operation).'Price'));
    }

    /**
     * @return bool
     */
    public function isDecreasingVolume(): bool
    {
        $rmpRiskLevel0 = $this->getRmpSubSegment()->getRmpSubSegmentRiskLevelByRiskLevel(HedgingTool::RISK_LEVEL_0);
        $hedgingTool = $this->getHedge()->getHedgingTool();
        return ($this->getHedge()->getOperationType() == Operations::OPERATION_TYPE_BUY || (($hedgingTool->isSpecialStorageTools() && $hedgingTool->getCode() != HedgingTool::HEDGING_TOOL_CALL_SELL) && $rmpRiskLevel0->getMaximumVolume()));
    }

    /**
     * @return bool
     */
    public function isWaiver()
    {
        return $this->hedge->isWaiverProduct() || $this->hedge->isWaiverClassRiskLevel() || $this->isWaiverMaturity() || $this->isWaiverMaturity();
    }

    /**
     * @return int|null
     */
    public function getCurrentRiskLevel(): int
    {
        if ($this->getRmpSubSegment()->getMaximumRiskLevel() == HedgingTool::RISK_LEVEL_0 && $this->getHedge()->getHedgingTool()->isSpecialStorageTools()) {
            $currentRiskLevel = HedgingTool::RISK_LEVEL_0;
        } else {
            $currentRiskLevel = $this->getHedge()->getHedgingTool()->getRiskLevel();
        }

        return $currentRiskLevel;
    }

    /**
     * Check whether HedgeLine is partially realized or not
     *
     * @return boolean
     */
    public function isPartiallyRealized()
    {
        return $this->getQuantity() > $this->getQuantityRealized();
    }
}
