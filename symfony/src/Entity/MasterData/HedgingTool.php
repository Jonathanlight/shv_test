<?php

namespace App\Entity\MasterData;

use Doctrine\ORM\Mapping as ORM;
use mysql_xdevapi\Exception;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Vich\UploaderBundle\Mapping\Annotation as Vich;
use App\Entity\Traits\TimestampableTrait;

/**
 * @ORM\Entity(repositoryClass="App\Repository\HedgingToolRepository")
 * @Vich\Uploadable
 * @ORM\HasLifecycleCallbacks()
 */
class HedgingTool
{

    use TimestampableTrait;

    const OPERATION_TYPE_BUY = 1;
    const OPERATION_TYPE_SELL = 2;

    public static $operationTypesLabels = [
        self::OPERATION_TYPE_BUY => 'Buy',
        self::OPERATION_TYPE_SELL => 'Sell',
    ];

    const OPERATION_TYPE_BUY_LABEL = 'Buy';
    const OPERATION_TYPE_SELL_LABEL = 'Sell';

    const RISK_LEVEL_1 = 1;
    const RISK_LEVEL_2 = 2;
    const RISK_LEVEL_3 = 3;
    const RISK_LEVEL_4 = 4;
    const RISK_LEVEL_0 = 0;

    public static $riskLevels = [
        self::RISK_LEVEL_0,
        self::RISK_LEVEL_1,
        self::RISK_LEVEL_2,
        self::RISK_LEVEL_3,
        self::RISK_LEVEL_4
    ];

    public static $riskLevelsLabels = [
        self::RISK_LEVEL_1 => 'N°1',
        self::RISK_LEVEL_2 => 'N°2',
        self::RISK_LEVEL_3 => 'N°3',
        self::RISK_LEVEL_4 => 'N°4',
        self::RISK_LEVEL_0 => 'ST',
    ];

    public static $riskLevelsBlotterLabels = [
        self::RISK_LEVEL_1 => '1',
        self::RISK_LEVEL_2 => '2',
        self::RISK_LEVEL_3 => '3',
        self::RISK_LEVEL_4 => '4',
        self::RISK_LEVEL_0 => 'ST',
    ];

    // Heding tools code
    const HEDGING_TOOL_SPREAD_BUY = 'SPREADBuy';
    const HEDGING_TOOL_SWAP_BUY = 'SwapBuy';

    const HEDGING_TOOL_SPREAD_SELL = 'SPREADSell';
    const HEDGING_TOOL_SWAP_SELL = 'SwapSell';
    const HEDGING_TOOL_CALL_SELL = 'CALLSell';
    const HEDGING_TOOL_COLLAR_SELL = 'COLLARSell';

    const NOT_PREMIUM_CLASS = 'not-premium';

    public static $notPremiumHedgingTool = [
        self::HEDGING_TOOL_SPREAD_BUY,
        self::HEDGING_TOOL_SPREAD_SELL,
        self::HEDGING_TOOL_SWAP_BUY,
        self::HEDGING_TOOL_SWAP_SELL,
    ];

    public static $storageToolsHedgingTool = [
        self::HEDGING_TOOL_CALL_SELL,
        self::HEDGING_TOOL_SWAP_SELL,
        self::HEDGING_TOOL_COLLAR_SELL,
    ];

    const REF_DH = 'DH';
    const REF_SH = 'SH';

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
     * @ORM\Column(type="integer")
     */
    private $riskLevel;

    /**
     * @ORM\Column(type="integer")
     */
    private $operationType;

    /**
     * @ORM\Column(type="array")
     */
    private $operations;

    /**
     * @ORM\Column(type="boolean")
     */
    private $active;

    /**
     * @ORM\Column(type="array")
     */
    private $columns;

    /**
     * @ORM\Column(type="boolean")
     */
    private $specialStorageTools = false;


    /**
     * @Vich\UploadableField(mapping="hedging_tools_files", fileNameProperty="chartImage")
     * @var File
     */
    private $chartImage;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $chartImagePath;

    /**
     * @return int|null
     */
    public function getId(): ? int
    {
        return $this->id;
    }

    /**
     * @return null|string
     */
    public function getName(): ? string
    {
        return $this->name;
    }

    /**
     * @param string $name
     *
     * @return HedgingTool
     */
    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return null|string
     */
    public function getCode(): ? string
    {
        return $this->code;
    }

    /**
     * @param string $code
     *
     * @return HedgingTool
     */
    public function setCode(string $code): self
    {
        $this->code = $code;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getRiskLevel(): ? int
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
     * @return int|null
     */
    public function getOperationType(): ? int
    {
        return $this->operationType;
    }

    /**
     * @param int $operationType
     *
     * @return HedgingTool
     */
    public function setOperationType(int $operationType): self
    {
        $this->operationType = $operationType;

        return $this;
    }

    /**
     * @return array|null
     */
    public function getOperations(): ? array
    {
        return $this->operations;
    }

    /**
     * @param array $operations
     *
     * @return HedgingTool
     */
    public function setOperations(array $operations): self
    {
        $this->operations = $operations;

        return $this;
    }

    /**
     * @return array|null
     */
    public function getColumns(): ? array
    {
        return $this->columns;
    }

    /**
     * @param array $columns
     *
     * @return HedgingTool
     */
    public function setColumns(array $columns): self
    {
        $this->columns = $columns;

        return $this;
    }

    /**
     * @return bool|null
     */
    public function isActive(): ? bool
    {
        return $this->active;
    }

    /**
     * @param bool $active
     * @return HedgingTool
     */
    public function setActive(bool $active): self
    {
        $this->active = $active;

        return $this;
    }

    /**
     * @return bool|null
     */
    public function isSpecialStorageTools(): ? bool
    {
        return $this->specialStorageTools;
    }

    /**
     * @param bool $specialStorageTools
     * @return HedgingTool
     */
    public function setSpecialStorageTools(bool $specialStorageTools): self
    {
        $this->specialStorageTools = $specialStorageTools;

        return $this;
    }

    /**
     * @param null  $operations
     * @param array $operationsAsArray
     *
     * @return array
     */
    public function getOperationsAsArray($operations = null, $operationsAsArray = []): array
    {
        $hedgingToolOperations = $operations ?: $this->operations;

        foreach ($hedgingToolOperations as $k => $operation) {
            if (is_array($operation)) {
                $operationsAsArray = $this->getOperationsAsArray($operation, $operationsAsArray);
            } else {
                if ('' != $operation) {
                    $operationsAsArray[] = $operation . ' ' . $k;
                }
            }
        }

        return $operationsAsArray;
    }

    /**
     * @param null  $operations
     * @param array $operationsAsArray
     *
     * @return array
     */
    public function getOperationsAsArrayKey($operations = null, $operationsAsArray = []): array
    {
        $hedgingToolOperations = $operations ?: $this->operations;

        foreach ($hedgingToolOperations as $k => $operation) {
            if (is_array($operation)) {
                $operationsAsArray = $this->getOperationsAsArrayKey($operation, $operationsAsArray);
            } else {
                if ('' != $operation) {
                    $operationsAsArray[][$operation] = $k;
                }
            }
        }

        return $operationsAsArray;
    }

    public function getOperationTypeLabel()
    {
        return self::OPERATION_TYPE_BUY == $this->operationType ? "BUY" : "SELL";
    }

    /**
     * @param UploadedFile $file
     */
    public function setChartImage(UploadedFile $chartImage = null)
    {
        $this->chartImage = $chartImage;
    }

    /**
     * @return UploadedFile
     */
    public function getChartImage()
    {
        return $this->chartImage;
    }

    /**
     * @return string|null
     */
    public function getChartImagePath(): ? string
    {
        return $this->chartImagePath;
    }

    /**
     * @param string $chartImagePath
     * @return HedgingTool
     */
    public function setChartImagePath(string $chartImagePath): self
    {
        $this->chartImagePath = $chartImagePath;

        return $this;
    }

    public function __toString()
    {
        return $this->name . ' - ' . (($this->riskLevel || $this->riskLevel === 0)  ? self::$riskLevelsLabels[$this->riskLevel] : "No risk level assigned yet");
    }
}
