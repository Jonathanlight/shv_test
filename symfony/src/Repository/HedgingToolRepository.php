<?php

namespace App\Repository;

use App\Entity\MasterData\HedgingTool;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;

class HedgingToolRepository extends EntityRepository
{
    /**
     * @param int $operationType
     * @return QueryBuilder
     */
    public function queryByOperationType(int $operationType): QueryBuilder
    {
        $qb = $this->createQueryBuilder('ht')
            ->where('ht.operationType = :operationType')
            ->setParameter('operationType', $operationType)
            ->orderBy('ht.name', 'ASC');

        return $qb;
    }

    /**
     * @param int $operationType
     * @param bool $formatted
     * @return array
     */
    public function findByOperationType(int $operationType, bool $formatted = true): array
    {
        if ($formatted) {
            return $this->formatResultByIdAndName($this->queryByOperationType($operationType)->getQuery()->getResult());
        } else {
            return $this->queryByOperationType($operationType)->getQuery()->getResult();
        }
    }

    /**
     * @param array $results
     * @return array
     */
    private function formatResultByIdAndName(array $results): array
    {
        $formattedResults = [];
        foreach ($results as $result) {
            $class = '';

            if (in_array($result->getCode(), HedgingTool::$notPremiumHedgingTool)) {
                $class = 'not-premium';
            }

            if ($result->getRiskLevel() == 1) {
                $class .= ' risk-level-1';
            }

            if ($result->getCode() == HedgingTool::HEDGING_TOOL_SPREAD_SELL || $result->getCode() == HedgingTool::HEDGING_TOOL_SPREAD_BUY) {
                $class .= ' spread';
            }

            $formattedResults[] = ['id' => $result->getId(), 'name' => $result->__toString(), 'class' => $class];
        }

        return $formattedResults;
    }
}
