<?php

namespace App\Entity\MasterData;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\SubSegmentRepository")
 */
class SubSegment
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string")
     */
    private $name;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private $code;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\MasterData\Segment", inversedBy="subsegments")
     */
    private $segment;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\MasterData\CustomerSegment", inversedBy="subsegments")
     */
    private $customerSegment;

    /**
     * @ORM\Column(type="boolean")
     */
    private $active;

    /**
     * @ORM\Column(type="boolean")
     */
    private $used = 0;

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
     * @return SubSegment
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
     * @param null|string $code
     *
     * @return SubSegment
     */
    public function setCode(?string $code): self
    {
        $this->code = $code;

        return $this;
    }

    /**
     * @return Segment|null
     */
    public function getSegment(): ?Segment
    {
        return $this->segment;
    }

    /**
     * @param Segment|null $segment
     *
     * @return SubSegment
     */
    public function setSegment(?Segment $segment): self
    {
        $this->segment = $segment;

        return $this;
    }

    /**
     * @return CustomerSegment|null
     */
    public function getCustomerSegment(): ?CustomerSegment
    {
        return $this->customerSegment;
    }

    /**
     * @param CustomerSegment|null $customerSegment
     *
     * @return SubSegment
     */
    public function setCustomerSegment(?CustomerSegment $customerSegment): self
    {
        $this->customerSegment = $customerSegment;

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
     * @return SubSegment
     */
    public function setActive(bool $active): self
    {
        $this->active = $active;

        return $this;
    }

    /**
     * @return bool|null
     */
    public function isUsed(): ?bool
    {
        return $this->used;
    }

    /**
     * @param bool $used
     *
     * @return SubSegment
     */
    public function setUsed(bool $used): self
    {
        $this->used = $used;

        return $this;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return ''.$this->name;
    }
}
