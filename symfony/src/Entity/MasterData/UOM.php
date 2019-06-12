<?php

namespace App\Entity\MasterData;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 */
class UOM
{
    const TYPE_VOLUME = 'Volume';
    const TYPE_ENERGY = 'Energy';
    const TYPE_CURRENCY = 'Currency';
    const TYPE_LENGTH = 'Length';
    const TYPE_WEIGHT = 'Weight';

    const BASE_UOM_CODE = 'MT';

    public static $preferredUoms = [
        'MT'
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
     * @ORM\Column(type="string", length=20)
     */
    private $type;

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
     * @return UOM
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
     * @return UOM
     */
    public function setCode(string $code): self
    {
        $this->code = $code;

        return $this;
    }

    /**
     * @return null|string
     */
    public function getType(): ?string
    {
        return $this->type;
    }

    /**
     * @param string $type
     *
     * @return UOM
     */
    public function setType(string $type): self
    {
        $this->type = $type;

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
     * @return UOM
     */
    public function setActive(bool $active): self
    {
        $this->active = $active;

        return $this;
    }

    public function __toString()
    {
        return ''.$this->code;
    }
}
