<?php

namespace App\Entity\MasterData;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 */
class ConversionTable
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\MasterData\Commodity")
     * @ORM\JoinColumn(nullable=false)
     */
    private $commodity;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\MasterData\UOM")
     * @ORM\JoinColumn(nullable=false)
     */
    private $uom;

    /**
     * @ORM\Column(type="string")
     */
    private $value;

    /**
     * Commodity constructor.
     */
    public function __construct()
    {
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @param int $id
     *
     * @return $this
     */
    public function setId(int $id): self
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return Commodity|null
     */
    public function getCommodity(): ?Commodity
    {
        return $this->commodity;
    }

    /**
     * @param Commodity $commodity
     *
     * @return $this
     */
    public function setCommodity(Commodity $commodity): self
    {
        $this->commodity = $commodity;

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
     * @param UOM $uom
     *
     * @return $this
     */
    public function setUom(UOM $uom): self
    {
        $this->uom = $uom;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getValue(): ?string
    {
        return $this->value;
    }

    /**
     * @param string $value
     *
     * @return $this
     */
    public function setValue(string $value): self
    {
        $this->value = $value;

        return $this;
    }
}