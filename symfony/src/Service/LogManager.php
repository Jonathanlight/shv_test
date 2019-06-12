<?php

namespace App\Service;

use App\Entity\CommonLog;
use App\Entity\Hedge;
use App\Entity\HedgeLog;
use App\Entity\RMP;
use App\Entity\RMPLog;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Csrf\TokenStorage\TokenStorageInterface;

class LogManager
{
    private $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    /**
     * @param Hedge|RMP|null $parent
     * @param User $user
     * @param int $type
     * @param string $additionalMessage
     *
     * @return HedgeLog|RMPLog|CommonLog
     */
    public function createLog($parent, ?User $user = null, int $type, string $additionalMessage = null)
    {
        $log = null;

        if ($parent) {
            $parentClass = get_class($parent);
        } else {
            $parentClass = CommonLog::BASE_CLASS;
        }

        if (class_exists($parentClass) || !$parent) {
            $logClass = $parentClass.'Log';
            $log = new $logClass();

            $currentDateTime = new \DateTime();
            $log->setTimestamp($currentDateTime);
            $log->setUser($user);
            $log->setType($type);
            $log->setAdditionalMessage($additionalMessage);

            if ($parent) {
                $log->setParent($parent);
            }

            $this->em->persist($log);
            $this->em->flush();
        }

        return $log;
    }
}