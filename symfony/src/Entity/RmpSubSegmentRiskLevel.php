<?php

namespace App\Entity;

use App\Entity\MasterData\HedgingTool;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\RmpSubSegmentRiskLevelRepository")
 */
class RmpSubSegmentRiskLevel
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="decimal", precision=21, scale=15)
     */
    private $consumption = 0;

    /**
     * @ORM\Column(type="decimal", precision=21, scale=15)
     */
    private $waiverConsumption = 0;

    /**
     * @ORM\Column(type="float")
     */
    private $maximumVolume = 0;

    /**
     * @ORM\Column(type="integer")
     */
    private $riskLevel;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\RmpSubSegment")
     * @ORM\JoinColumn(nullable=false)
     */
    private $rmpSubSegment;

    /**
     * @ORM\Column(type="integer")
     */
    private $version = 1;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\RmpSubSegmentRiskLevel", inversedBy="rmpSubSegmentRiskLevels")
     */
    private $copiedFrom;

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
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return float|null
     */
    public function getConsumption(): ?float
    {
        return $this->consumption;
    }

    /**
     * @param float $consumption
     *
     * @return RmpSubSegmentRiskLevel
     */
    public function setConsumption(float $consumption): self
    {
        $this->consumption = $consumption;

        return $this;
    }

    /**
     * @param float $quantity
     * @return RmpSubSegmentRiskLevel
     */
    public function addConsumption(float $quantity): self
    {
        $this->consumption += $quantity;

        return $this;
    }

    /**
     * @param float $quantity
     * @return RmpSubSegmentRiskLevel
     */
    public function removeConsumption(float $quantity): self
    {
        $this->consumption -= $quantity;

        return $this;
    }

    /**
     * @return float|null
     */
    public function getWaiverConsumption(): ?float
    {
        return $this->waiverConsumption;
    }

    /**
     * @param float $quantity
     *
     * @return RmpSubSegmentRiskLevel
     */
    public function setWaiverConsumption(float $quantity): self
    {
        $this->waiverConsumption = $quantity;

        return $this;
    }

    /**
     * @param float $quantity
     * @return RmpSubSegmentRiskLevel
     */
    public function addWaiverConsumption(float $quantity): self
    {
        $this->waiverConsumption += $quantity;

        return $this;
    }

    /**
     * @param float $quantity
     * @return RmpSubSegmentRiskLevel
     */
    public function removeWaiverConsumption(float $quantity): self
    {
        $this->waiverConsumption -= $quantity;

        return $this;
    }

    /**
     * @return float|null
     */
    public function getMaximumVolume(): ?float
    {
        return $this->maximumVolume;
    }

    /**
     * @param float $maximumVolume
     *
     * @return RmpSubSegmentRiskLevel
     */
    public function setMaximumVolume(float $maximumVolume): self
    {
        $this->maximumVolume = $maximumVolume;

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
     * @return RmpSubSegmentRiskLevel
     */
    public function setRmpSubSegment(?RmpSubSegment $rmpSubSegment): self
    {
        $this->rmpSubSegment = $rmpSubSegment;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getRiskLevel(): ?int
    {
        return $this->riskLevel;
    }

    /**
     * @param int $riskLevel
     *
     * @return HedgingTool
     */
    public function setRiskLevel(int $riskLevel): self
    {
        $this->riskLevel = $riskLevel;

        return $this;
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
     * @return RmpSubSegmentRiskLevel|null
     */
    public function getCopiedFrom(): ?RmpSubSegmentRiskLevel
    {
        return $this->copiedFrom;
    }

    /**
     * @param RmpSubSegmentRiskLevel $rmpSubSegmentRiskLevel
     *
     * @return RmpSubSegmentRiskLevel
     */
    public function setCopiedFrom(RmpSubSegmentRiskLevel $rmpSubSegmentRiskLevel): RmpSubSegmentRiskLevel
    {
        $this->copiedFrom = $rmpSubSegmentRiskLevel;

        return $this;
    }

    /**
     * Clone function
     */
    public function __clone()
    {
        if ($this->id) {
            $this->setId(null);
        }
    }
}
