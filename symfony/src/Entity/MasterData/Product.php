<?php

namespace App\Entity\MasterData;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 */
class Product
{
    const PRICING_TYPE_POSTED = 'Posted';
    const PRICING_TYPE_FULL_MONTH = 'Full Month';
    const PRICING_TYPE_MA = 'MA';

    const IMPORT_IDENTIFIER_INDEX = 'quote_def_cd';
    const IMPORT_NB_COLS = 45;

    const DEFAULT_IMPORT_FILE = '/resources/imports/tests/responseTPTquotes.txt';

    public static $importColsMapping = [
        0 => 'code', // 0 = Num
        1 => 'name', // 1 = CODE
        2 => 'longName',
        5 => Commodity::class,
        8 => UOM::class,
        9 => Currency::class,
        10 => 'pricingType',
        42 => 'updatedAt'
    ];

    public static $importXMLColsMapping = [
        'quote_def_num' => 'code',
        'quote_def_cd' => 'name',
        'quote_def_long' => 'longName',
        'cmdty_cd' => Commodity::class,
        'quote_uom_cd' => UOM::class,
        'quote_curr_cd' => Currency::class,
        'calendar_cd' => 'pricingType',
        'last_modify_dt' => 'updatedAt'
    ];

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=50)
     */
    private $name;

    /**
     * @ORM\Column(type="string", length=50)
     */
    private $longName;

    /**
     * @ORM\Column(type="string", length=20)
     */
    private $code;

    /**
     * @ORM\Column(type="string")
     */
    private $pricingType;

    /**
     * @ORM\Column(type="string", length=20, nullable=true)
     */
    private $fxQuote;

    /**
     * @ORM\Column(type="datetime")
     */
    private $updatedAt;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\MasterData\UOM", inversedBy="products")
     */
    private $uom;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\MasterData\Currency", inversedBy="products")
     */
    private $currency;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\MasterData\Commodity", inversedBy="products", cascade={"persist"})
     */
    private $commodity;

    /**
     * @ORM\Column(type="boolean")
     */
    private $active;

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
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
     * @return Product
     */
    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return null|string
     */
    public function getLongName(): ?string
    {
        return $this->longName;
    }

    /**
     * @param string $longName
     *
     * @return Product
     */
    public function setLongName(string $longName): self
    {
        $this->longName = $longName;

        return $this;
    }

    /**
     * @return null|string
     */
    public function getCode(): ?string
    {
        return $this->code;
    }

    /**
     * @param string $code
     *
     * @return Product
     */
    public function setCode(string $code): self
    {
        $this->code = $code;

        return $this;
    }

    /**
     * @return null|string
     */
    public function getPricingType(): ?string
    {
        return $this->pricingType;
    }

    /**
     * @param string $pricingType
     *
     * @return Product
     */
    public function setPricingType(string $pricingType): self
    {
        $this->pricingType = $pricingType;

        return $this;
    }

    /**
     * @return null|string
     */
    public function getFxQuote(): ?string
    {
        return $this->fxQuote;
    }

    /**
     * @param string $fxQuote
     *
     * @return Product
     */
    public function setFxQuote(string $fxQuote): self
    {
        $this->fxQuote = $fxQuote;

        return $this;
    }

    /**
     * @return \DateTimeInterface|null
     */
    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    /**
     * @param \DateTimeInterface $updatedAt
     *
     * @return Product
     */
    public function setUpdatedAt(\DateTimeInterface $updatedAt): self
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    /**
     * @return UOM|null
     */
    public function getUOM(): ?UOM
    {
        return $this->uom;
    }

    /**
     * @param UOM|null $uom
     *
     * @return Product
     */
    public function setUOM(?UOM $uom): self
    {
        $this->uom = $uom;

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
     * @return Product
     */
    public function setCurrency(?Currency $currency): self
    {
        $this->currency = $currency;

        return $this;
    }

    /**
     * @return Commodity|null
     */
    public function getCommodity(): Commodity
    {
        return $this->commodity;
    }

    /**
     * @param Commodity|null $commodity
     *
     * @return Product
     */
    public function setCommodity(Commodity $commodity): self
    {
        $this->commodity = $commodity;

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
     * @return Product
     */
    public function setActive(bool $active): self
    {
        $this->active = $active;

        return $this;
    }

    public function __toString()
    {
        return $this->name;
    }
}
