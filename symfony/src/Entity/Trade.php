<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 */
class Trade
{
    const IMPORT_NB_COLS = 18;

    const STATUS_ACCEPTED = 'Accepted';
    const STATUS_VERIFIED = 'Verified';
    const STATUS_VOID = 'Void';

    public static $statusesLabels = [
        self::STATUS_ACCEPTED => 'trade.status.accepted',
        self::STATUS_VERIFIED => 'trade.status.verified',
        self::STATUS_VOID => 'trade.status.void'
    ];

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="float")
     */
    private $quantity;

    /**
     * @ORM\Column(type="datetime")
     */
    private $tradingDate;

    /**
     * @ORM\Column(type="integer")
     */
    private $cxlTradeNumber;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\HedgeLine", inversedBy="trades")
     * @ORM\JoinColumn(nullable=false)
     */
    private $hedgeLine;

    /**
     * @ORM\Column(type="float")
     */
    private $callStrike = 0;

    /**
     * @ORM\Column(type="float")
     */
    private $putStrike = 0;

    /**
     * @ORM\Column(type="float")
     */
    private $callPremium = 0;

    /**
     * @ORM\Column(type="float")
     */
    private $putPremium = 0;

    /**
     * @ORM\Column(type="float")
     */
    private $swapPrice = 0;

    /**
     * @ORM\Column(type="integer")
     */
    private $operationType;

    /**
     * @ORM\Column(type="integer")
     */
    private $instrument;

    /**
     * @ORM\Column(type="string")
     */
    private $status;

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return float
     */
    public function getQuantity(): ?float
    {
        return $this->quantity;
    }

    /**
     * @param float $quantity
     * @return Trade
     */
    public function setQuantity(float $quantity): self
    {
        $this->quantity = $quantity;

        return $this;
    }

    /**
     * @return \DateTimeInterface|null
     */
    public function getTradingDate(): ?\DateTimeInterface
    {
        return $this->tradingDate;
    }

    /**
     * @param \DateTimeInterface $tradingDate
     * @return Trade
     */
    public function setTradingDate(\DateTimeInterface $tradingDate): self
    {
        $this->tradingDate = $tradingDate;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getCxlTradeNumber(): ?int
    {
        return $this->cxlTradeNumber;
    }

    /**
     * @param int $cxlTradeNumber
     * @return Trade
     */
    public function setCxlTradeNumber(int $cxlTradeNumber): self
    {
        $this->cxlTradeNumber = $cxlTradeNumber;

        return $this;
    }

    /**
     * @return HedgeLine|null
     */
    public function getHedgeLine(): ?HedgeLine
    {
        return $this->hedgeLine;
    }

    /**
     * @param HedgeLine|null $hedgeLine
     * @return Trade
     */
    public function setHedgeLine(?HedgeLine $hedgeLine): self
    {
        $this->hedgeLine = $hedgeLine;

        return $this;
    }

    /**
     * @return float
     */
    public function getCallStrike(): float
    {
        return $this->callStrike;
    }

    /**
     * @param float $callStrike
     * @return Trade
     */
    public function setCallStrike(float $callStrike): self
    {
        $this->callStrike = $callStrike;

        return $this;
    }

    /**
     * @return float
     */
    public function getCallPremium(): float
    {
        return $this->callPremium;
    }

    /**
     * @param float $callPremium
     * @return Trade
     */
    public function setCallPremium(float $callPremium): self
    {
        $this->callPremium = $callPremium;

        return $this;
    }

    /**
     * @return float
     */
    public function getPutStrike(): float
    {
        return $this->putStrike;
    }

    /**
     * @param float $putStrike
     * @return Trade
     */
    public function setPutStrike(float $putStrike): self
    {
        $this->putStrike = $putStrike;

        return $this;
    }

    /**
     * @return float
     */
    public function getPutPremium(): float
    {
        return $this->putPremium;
    }

    /**
     * @param float $putPremium
     * @return Trade
     */
    public function setPutPremium(float $putPremium): self
    {
        $this->putPremium = $putPremium;

        return $this;
    }

    /**
     * @return float
     */
    public function getSwapPrice(): float
    {
        return $this->swapPrice;
    }

    /**
     * @param float $swapPrice
     * @return Trade
     */
    public function setSwapPrice(float $swapPrice): self
    {
        $this->swapPrice = $swapPrice;

        return $this;
    }

    /**
     * @return integer
     */
    public function getOperationType(): ?int
    {
        return $this->operationType;
    }

    /**
     * @param integer $operationType
     * @return Trade
     */
    public function setOperationType(int $operationType): self
    {
        $this->operationType = $operationType;

        return $this;
    }

    /**
     * @return integer
     */
    public function getInstrument(): ?int
    {
        return $this->instrument;
    }

    /**
     * @param integer $instrument
     * @return Trade
     */
    public function setInstrument(int $instrument): self
    {
        $this->instrument = $instrument;

        return $this;
    }

    /**
     * @return string
     */
    public function getStatus(): ?string
    {
        return $this->status;
    }

    /**
     * @param integer $status
     * @return Trade
     */
    public function setStatus(string $status): self
    {
        $this->status = $status;

        return $this;
    }
}
