<?php

namespace App\Entity;

use App\Entity\MasterData\PriceRiskClassification;
use App\Entity\MasterData\Product;
use App\Entity\MasterData\SubSegment;
use App\Entity\MasterData\UOM;
use App\Entity\MasterData\Currency;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\RmpSubSegmentRepository")
 */
class RmpSubSegment
{
    public static $fieldsToCompare = ['salesVolume', 'maximumVolume', 'ratioMaximumVolumeSales', 'maximumMaturities',
        'maximumLoss', 'priceRiskClassification', 'productCategory'];

    public static $fieldsKeyViewTab = ['salesVolume', 'priceRiskClassification', 'ratioMaximumVolumeSales', 'maximumMaturities',
        'maximumLoss'];

    public static $fieldsCommentsTab = ['productCategory'];


    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\RMP", inversedBy="rmpSubSegments")
     * @ORM\JoinColumn(nullable=false)
     */
    private $rmp;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\MasterData\SubSegment")
     * @ORM\JoinColumn(nullable=false)
     */
    private $subSegment;

    /**
     * @ORM\Column(type="float")
     */
    private $salesVolume;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $maximumVolume;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $ratioMaximumVolumeSales;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $maximumLoss;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private $productCategory;

    /**
     * @ORM\ManyToMany(targetEntity="App\Entity\MasterData\Product", inversedBy="rmpSubSegments")
     */
    private $products;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\MasterData\PriceRiskClassification")
     */
    private $priceRiskClassification;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $maximumMaturities;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\MasterData\UOM")
     * @ORM\JoinColumn(nullable=false)
     */
    private $uom;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\RmpSubSegmentRiskLevel", mappedBy="rmpSubSegment", cascade={"persist", "remove"})
     */
    private $rmpSubSegmentRiskLevels;

    /**
     * @ORM\Column(type="integer")
     */
    private $version = 1;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\RmpSubSegment", inversedBy="rmpSubSegments")
     */
    private $copiedFrom;

    /**
     * @ORM\Column(type="boolean")
     */
    private $active = true;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\MasterData\Currency")
     * @ORM\JoinColumn(nullable=false)
     * @ORM\OrderBy({"code" = "DESC"})
     */
    private $currency;

    /**
     * RmpSubSegment constructor.
     */
    public function __construct()
    {
        $this->products = new ArrayCollection();
        $this->rmpSubSegmentRiskLevels = new ArrayCollection();
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
     * @return RMP|null
     */
    public function getRmp(): ?RMP
    {
        return $this->rmp;
    }

    /**
     * @param RMP|null $rmp
     *
     * @return RmpSubSegment
     */
    public function setRmp(?RMP $rmp): self
    {
        $this->rmp = $rmp;

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
     * @return RmpSubSegment
     */
    public function setSubSegment(?SubSegment $subSegment): self
    {
        $this->subSegment = $subSegment;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getMaximumMaturities(): ?int
    {
        return $this->maximumMaturities;
    }

    /**
     * @param int $maximumMaturities
     *
     * @return RMP
     */
    public function setMaximumMaturities(int $maximumMaturities): self
    {
        $this->maximumMaturities = $maximumMaturities;

        return $this;
    }

    /**
     * @param Collection $products
     * @return RmpSubSegment
     */
    public function setProducts(Collection $products): self
    {
        $this->products = $products;

        return $this;
    }

    /**
     * @return Collection|Product[]
     */
    public function getProducts(): Collection
    {
        return $this->products;
    }

    /**
     * @param Product $product
     *
     * @return RmpSubSegment
     */
    public function addProduct(Product $product): self
    {
        if (!$this->products->contains($product)) {
            $this->products[] = $product;
        }

        return $this;
    }

    /**
     * @param Product $product
     *
     * @return RmpSubSegment
     */
    public function removeProduct(Product $product): self
    {
        if ($this->products->contains($product)) {
            $this->products->removeElement($product);
        }

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
     * @return RmpSubSegment
     */
    public function setPriceRiskClassification(?PriceRiskClassification $priceRiskClassification): self
    {
        $this->priceRiskClassification = $priceRiskClassification;

        return $this;
    }

    /**
     * @return float|null
     */
    public function getSalesVolume(): ?float
    {
        return $this->salesVolume;
    }

    /**
     * @param float|null $salesVolume
     *
     * @return RmpSubSegment
     */
    public function setSalesVolume(float $salesVolume): self
    {
        $this->salesVolume = $salesVolume;

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
     * @param float|null $maximumVolume
     *
     * @return RmpSubSegment
     */
    public function setMaximumVolume(float $maximumVolume): self
    {
        $this->maximumVolume = $maximumVolume;

        return $this;
    }

    /**
     * @return float|null
     */
    public function getRatioMaximumVolumeSales(): ?float
    {
        return $this->ratioMaximumVolumeSales;
    }

    /**
     * @param float|null $ratioMaximumVolumeSales
     *
     * @return RmpSubSegment
     */
    public function setRatioMaximumVolumeSales(float $ratioMaximumVolumeSales): self
    {
        $this->ratioMaximumVolumeSales = $ratioMaximumVolumeSales;

        return $this;
    }

    /**
     * @return float|null
     */
    public function getMaximumLoss(): ?float
    {
        return $this->maximumLoss;
    }

    /**
     * @param float|null $maximumLoss
     *
     * @return RmpSubSegment
     */
    public function setMaximumLoss(?float $maximumLoss): self
    {
        $this->maximumLoss = $maximumLoss;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getProductCategory(): ?string
    {
        return $this->productCategory;
    }

    /**
     * @param string|null $productCategory
     *
     * @return RmpSubSegment
     */
    public function setProductCategory(string $productCategory): self
    {
        $this->productCategory = $productCategory;

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
     * @return RmpSubSegment
     */
    public function setUom(UOM $uom): self
    {
        $this->uom = $uom;

        return $this;
    }

    /**
     * @return array
     */
    public function getProductsNamesAsArray(): array
    {
        $productNames = [];
        foreach ($this->products as $product) {
            if (!in_array($product->getName(), $productNames)) {
                $productNames[] = $product->getName();
            }
        }

        return $productNames;
    }

    /**
     * @return Collection|RmpSubSegmentRiskLevel[]
     */
    public function getRmpSubSegmentRiskLevels(): Collection
    {
        return $this->rmpSubSegmentRiskLevels;
    }

    /**
     * @param RmpSubSegmentRiskLevel $rmpSubSegmentRiskLevel
     *
     * @return RmpSubSegment
     */
    public function addRmpSubSegmentRiskLevel(RmpSubSegmentRiskLevel $rmpSubSegmentRiskLevel): self
    {
        if (!$this->rmpSubSegmentRiskLevels->contains($rmpSubSegmentRiskLevel)) {
            $this->rmpSubSegmentRiskLevels->add($rmpSubSegmentRiskLevel);
        }

        return $this;
    }

    /**
     * @param RmpSubSegmentRiskLevel $rmpSubSegmentRiskLevel
     *
     * @return RmpSubSegment
     */
    public function removeRmpSubSegmentRiskLevel(RmpSubSegmentRiskLevel $rmpSubSegmentRiskLevel): self
    {
        if ($this->rmpSubSegmentRiskLevels->contains($rmpSubSegmentRiskLevel)) {
            $this->rmpSubSegmentRiskLevels->removeElement($rmpSubSegmentRiskLevel);
        }

        return $this;
    }

    /**
     * @return int
     */
    public function getMaximumRiskLevel(): int
    {
        $maximumRiskLevel = 999;
        foreach ($this->getRmpSubSegmentRiskLevels() as $rmpSubSegmentRiskLevel) {
            if ($rmpSubSegmentRiskLevel->getRiskLevel() < $maximumRiskLevel && $rmpSubSegmentRiskLevel->getMaximumVolume()) {
                $maximumRiskLevel = $rmpSubSegmentRiskLevel->getRiskLevel();
            }
        }

        return $maximumRiskLevel;
    }

    /**
     * @param int $riskLevel
     * @return mixed
     */
    public function getRmpSubSegmentRiskLevelByRiskLevel(int $riskLevel)
    {
        return $this->getRmpSubSegmentRiskLevels()->filter(function($entry) use ($riskLevel) {
            return $entry->getRiskLevel() == $riskLevel;
        })->first();
    }

    public function __toString(): string
    {
        return $this->getRmp()->getName() . ' - ' . $this->getSubSegment()->getName();
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
     * @return RmpSubSegment|null
     */
    public function getCopiedFrom(): ?RmpSubSegment
    {
        return $this->copiedFrom;
    }

    /**
     * @param RmpSubSegment $rmpSubSegment
     *
     * @return RmpSubSegment
     */
    public function setCopiedFrom(RmpSubSegment $rmpSubSegment): RmpSubSegment
    {
        $this->copiedFrom = $rmpSubSegment;

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
     * @return RmpSubSegment
     */
    public function setActive(bool $active): self
    {
        $this->active = $active;

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
     * Clone function
     */
    public function __clone()
    {
        if ($this->id) {
            $this->setId(null);

            $rmpSubSegmentsRiskLevels = new ArrayCollection();
            foreach ($this->rmpSubSegmentRiskLevels as $rmpSubSegmentRiskLevel) {
                $rmpSubSegmentRiskLevelClone = clone $rmpSubSegmentRiskLevel;
                $rmpSubSegmentRiskLevelClone->setCopiedFrom($rmpSubSegmentRiskLevel);
                $rmpSubSegmentRiskLevelClone->setRmpSubSegment($this);
                $rmpSubSegmentsRiskLevels->add($rmpSubSegmentRiskLevelClone);
            }

            $this->rmpSubSegmentRiskLevels = $rmpSubSegmentsRiskLevels;
        }
    }
}
