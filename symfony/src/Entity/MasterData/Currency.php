<?php

namespace App\Entity\MasterData;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 */
class Currency
{
    public static $preferredCurrencies = [
        'USD',
        'EUR'
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
     * @ORM\Column(type="string", length=20)
     */
    private $code;

    /**
     * @ORM\Column(type="boolean")
     */
    private $active;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\MasterData\UOM")
     * @ORM\JoinColumn(nullable=false)
     */
    private $uom;

    /**
     * @return int
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
     * @return Currency
     */
    public function setName(string $name): self
    {
        $this->name = $name;

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
     * @return Currency
     */
    public function setCode(string $code): self
    {
        $this->code = $code;

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
     * @return Currency
     */
    public function setActive(bool $active): self
    {
        $this->active = $active;

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
     * @return Currency
     */
    public function setUom(UOM $uom): self
    {
        $this->uom = $uom;

        return $this;
    }

    public function __toString()
    {
        $name = '' . $this->code;
        $name .= $this->getUom() ? ' / ' . $this->getUom()->getCode() : '';

        return $name;
    }
}
