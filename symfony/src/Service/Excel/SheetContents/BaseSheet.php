<?php

namespace App\Service\Excel\SheetContents;

use App\Entity\Hedge;
use App\Entity\HedgeLine;
use App\Entity\MasterData\HedgingTool;
use App\Entity\MasterData\PriceRiskClassification;
use App\Entity\RmpSubSegment;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class BaseSheet
{
    protected $hedge;

    protected $em;

    protected $user;

    protected $currency;

    protected $uom;

    protected $hedgingTool;

    protected $rmpSubSegment;

    protected $type;

    protected $operation;

    protected $hedgeLine;

    protected $header;

    protected $allowedOperations;

    protected $title;

    protected $depth;

    // Default values
    const DEFAULT_PREM_PAYMENT = '2 B US BANK';
    const DEFAULT_PAYMENT = '5 B US BANK';
    const DEFAULT_OPTION_STYLE = 'Asian';
    const DEFAULT_MONTHLY = 'Monthly';
    const DEFAULT_WHAT_IF = '\'FALSE';
    const DEFAULT_PERIODICITY = 'per Month';
    const DEFAULT_INTERNAL_COMPANY = 'SHV GAS TRADING';

    const DEPTH = 2;

    /**
     * ApoSheet constructor.
     * @param EntityManagerInterface $em
     * @param Hedge $hedge
     * @param User $user
     * @param string $title
     * @param array $header
     */
    public function __construct(EntityManagerInterface $em, Hedge $hedge, User $user, string $title, array $header)
    {
        $this->em = $em;
        $this->hedge = $hedge;
        $this->user = $user;
        $this->currency = $hedge->getCurrency()->getCode();
        $this->hedgingTool = $hedge->getHedgingTool();
        $this->title = $title;
        $this->header = $header;
        $this->depth = self::DEPTH;

        $rmpSubSegment = $this->em->getRepository(RmpSubSegment::class)->findOneBy(['subSegment' => $this->hedge->getSubSegment(), 'rmp' => $this->hedge->getRmp()]);
        $this->rmpSubSegment = $rmpSubSegment;
        $this->uom = $rmpSubSegment->getUom()->getCode();
    }


    /**
     * @return array
     */
    public function getData()
    {
        $results = [];
        foreach ($this->hedge->getHedgeLines() as $hedgeLine) {
            if ($hedgeLine->getQuantity()) {
                $results[] = $this->getLines($hedgeLine);
            }
        }

        return $results;
    }

    /**
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * @return array
     */
    public function getHeader(): array
    {
        return $this->header;
    }

    /**
     * @return int
     */
    public function getDepth(): int
    {
        return $this->depth;
    }

    /**
     * @return array
     */
    protected function getLine(): array
    {
        $line = [];
        foreach ($this->header as $item) {
            $headerFormatted = str_replace(' ', '', ucwords(strtolower(str_replace(array('/', '-', '_'), ' ', $item))));
            $line[] = call_user_func(array($this, 'get'.$headerFormatted));
        }

        return $line;
    }

    /**
     * @param HedgeLine $hedgeLine
     * @return array
     */
    protected function getLines(HedgeLine $hedgeLine): array
    {
        $results = [];
        $this->hedgeLine = $hedgeLine;

        foreach ($this->hedge->getHedgingTool()->getOperationsAsArrayKey() as $operations) {
            foreach ($operations as $type => $operation) {
                if (ucfirst($type) == HedgingTool::OPERATION_TYPE_BUY_LABEL) {
                    $this->type = HedgingTool::OPERATION_TYPE_SELL_LABEL;
                } else {
                    $this->type = HedgingTool::OPERATION_TYPE_BUY_LABEL;
                }
                $this->operation = $operation;
                if (in_array($operation, $this->allowedOperations) && !empty($operation)) {
                    $results[$hedgeLine->getId().$operation] = $this->getLine();
                }
            }
        }

        return $results;
    }

    /**
     * @return string
     */
    protected function getTradeDate(): string
    {
        return '';
    }

    /**
     * @return string
     */
    protected function getCounterpart(): string
    {
        return $this->hedge->getRmp()->getBusinessUnit()->getCounterpartCode();
    }

    /**
     * @return string
     */
    protected function getFloat(): string
    {
        // Exception for hedgingTool SPREAD for the 2nd line
        if ($this->operation == 'swap2'
         && $this->rmpSubSegment->getPriceRiskClassification()->getCode() == PriceRiskClassification::CODE_DIFFERENTIAL_PRICE
         && ($this->hedgingTool->getCode() == HedgingTool::HEDGING_TOOL_SPREAD_BUY || $this->hedgingTool->getCode() == HedgingTool::HEDGING_TOOL_SPREAD_SELL)) {
            $float = $this->hedge->getProduct2()->getCode();
        } else {
            $float = $this->hedge->getProduct1()->getCode();
        }

        return $float;
    }

    /**
     * @return string
     */
    protected function getBuySell(): string
    {
        return ucfirst($this->type);
    }

    /**
     * @return string
     */
    protected function getUnderlyingQuote(): string
    {
        return $this->hedge->getProduct1()->getCode();
    }

    /**
     * @return string
     */
    protected function getPutCall(): string
    {
        $operation = preg_replace('/[0-9]/', '', $this->operation);
        return ucfirst($operation);
    }

    /**
     * @return float
     */
    protected function getQty(): float
    {
        return $this->hedgeLine->getQuantity() - $this->hedgeLine->getQuantityRealized();
    }

    /**
     * @return string
     */
    protected function getQtyUOM(): string
    {
        return $this->hedgeLine->getHedge()->getUom();
    }

    /**
     * @return float
     */
    protected function getStrike(): float
    {
        return $this->hedgeLine->getStrikeByOperation($this->operation);
    }

    /**
     * @return string
     */
    protected function getStrikePriceCcy(): string
    {
        return $this->currency;
    }

    /**
     * @return string
     */
    protected function getStrikePriceUOM(): string
    {
        return $this->hedge->getCurrency()->getUom()->getCode();
    }

    /**
     * @return float
     */
    protected function getPremium(): float
    {
        return $this->hedgeLine->getPremiumByOperation($this->operation);
    }

    /**
     * @return string
     */
    protected function getPremiumCcy(): string
    {
        return $this->currency;
    }

    /**
     * @return string
     */
    protected function getPremiumUOM(): string
    {
        return $this->hedge->getCurrency()->getUom()->getCode();
    }

    /**
     * @return string
     */
    protected function getStartDate(): string
    {
        return $this->hedgeLine->getMaturity()->getDate()->format('d/m/Y');
    }

    /**
     * @return string
     */
    protected function getEndDate(): string
    {
        return date("Y-m-t", strtotime($this->hedgeLine->getMaturity()->getDate()->format('Y-m-d')));
    }

    /**
     * @return string
     */
    protected function getVenture(): string
    {
        return $this->hedge->getHedgingTool()->getName();
    }

    /**
     * @return string
     */
    protected function getInternalCompany(): string
    {
        return self::DEFAULT_INTERNAL_COMPANY;
    }

    /**
     * @return string
     */
    protected function getStrategy(): string
    {
        return $this->hedgeLine->getStrategy()->getName();
    }

    /**
     * @return string
     */
    protected function getRef(): string
    {
        return $this->hedgingTool->getRiskLevel() == HedgingTool::RISK_LEVEL_0 ? HedgingTool::REF_SH : HedgingTool::REF_DH;
    }

    /**
     * @return string
     */
    protected function getSettlementCcy(): string
    {
        return $this->currency;
    }

    /**
     * @return string
     */
    protected function getPremPayment(): string
    {
        return self::DEFAULT_PREM_PAYMENT;
    }

    /**
     * @return string
     */
    protected function getPayment(): string
    {
        return self::DEFAULT_PAYMENT;
    }

    /**
     * @return string
     */
    protected function getTrader(): string
    {
        return $this->user->getFirstName() . ' ' . $this->user->getLastName();
    }

    /**
     * @return string
     */
    protected function getOptionStyle(): string
    {
        return self::DEFAULT_OPTION_STYLE;
    }

    /**
     * @return string
     */
    protected function getExerciseFreq(): string
    {
        return self::DEFAULT_MONTHLY;
    }

    /**
     * @return string
     */
    protected function getRef2(): string
    {
        return $this->hedge->getId() . '-' . $this->hedgeLine->getId();
    }

    /**
     * @return string
     */
    protected function getWhatIf(): string
    {
        return self::DEFAULT_WHAT_IF;
    }

    /**
     * @return string
     */
    protected function getQtyPeriodicity(): string
    {
        return self::DEFAULT_PERIODICITY;
    }

    /**
     * @return string
     */
    protected function getSchedule(): string
    {
        return self::DEFAULT_MONTHLY;
    }

    /**
     * @return string
     */
    protected function getMarketSegmentation(): string
    {
        return strtoupper($this->hedge->getSubSegment()->getSegment()->getName());
    }

    /**
     * @return string
     */
    protected function getWaiver(): string
    {
        return  $this->hedgeLine->getWaiversCodes();
    }

    /**
     * @return bool
     */
    protected function getWaiverClassRisk(): string
    {
        return  $this->hedge->isWaiverClassRiskLevel() ?  HedgingTool::$riskLevelsBlotterLabels[$this->rmpSubSegment->getMaximumRiskLevel()] : '';
    }

    /**
     * @return string
     */
    protected function getDiffPriceLeg(): string
    {
        // Exception for hedgingTool SPREAD for the 2nd line
        if ($this->operation == 'swap2'
         && $this->rmpSubSegment->getPriceRiskClassification()->getCode() == PriceRiskClassification::CODE_DIFFERENTIAL_PRICE
         && ($this->hedgingTool->getCode() == HedgingTool::HEDGING_TOOL_SPREAD_BUY || $this->hedgingTool->getCode() == HedgingTool::HEDGING_TOOL_SPREAD_SELL)) {
            $diffPriceLeg = 'Yes';
        } else {
            $diffPriceLeg = '';
        }

        return $diffPriceLeg;
    }

    /**
     * @return string
     */
    protected function getSubSegment(): string
    {
        return  ucfirst(strtolower($this->hedge->getSubSegment()->getName()));
    }

    /**
     * @return int
     */
    protected function getHedgingToolClassRisk(): string
    {
        return  HedgingTool::$riskLevelsBlotterLabels[$this->hedge->getHedgingTool()->getRiskLevel()];
    }

    /**
     * @return string
     */
    protected function getMarketPriceRisk(): string
    {
        return strtoupper($this->rmpSubSegment->getPriceRiskClassification()->getCode());
    }

    /**
     * @return float
     */
    protected function getFixedPrice(): float
    {
        return $this->hedgeLine->getSwapByOperation($this->operation);
    }

    /**
     * @return string
     */
    protected function getPriceUOM(): string
    {
        return $this->hedge->getCurrency()->getUom()->getCode();
    }

    /**
     * @return string
     */
    protected function getPriceCcy(): string
    {
        return $this->currency;
    }

    /**
     * @return string
     */
    protected function getFxQuote(): string
    {
        return '';
    }

    /**
     * @return string
     */
    protected function getFxEvent(): string
    {
        return '';
    }
}
