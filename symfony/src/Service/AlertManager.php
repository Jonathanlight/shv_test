<?php

namespace App\Service;

use App\Entity\Hedge;
use App\Entity\HedgeAlert;
use App\Entity\RMP;
use App\Entity\RmpAlert;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Csrf\TokenStorage\TokenStorageInterface;

class AlertManager
{
    private $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    /**
     * @param Hedge|RMP $parent
     * @param array $recipients
     * @param int $type
     * @param string $additionalMessage
     *
     * @return HedgeAlert|RmpAlert
     */
    public function createAlert($parent, array $recipients, int $type, string $additionalMessage = null)
    {
        $alert = null;
        $parentClass = $this->em->getClassMetadata(get_class($parent))->getName();

        if (class_exists($parentClass)) {
            $parentClass = explode('\\', $parentClass);
            $parentClass = ucfirst(strtolower($parentClass[0])) . '\\' . ucfirst(strtolower($parentClass[1])) . '\\' . ucfirst(strtolower($parentClass[2]));
            $alertClass = $parentClass.'Alert';
            $alert = new $alertClass();

            $currentDateTime = new \DateTime();
            $alert->setParent($parent);
            $alert->setTimestamp($currentDateTime);
            $alert->setType($type);
            $alert->setAdditionalMessage($additionalMessage);

            $this->em->persist($alert);

            foreach ($recipients as $recipient) {
                $alertUserClass = $alertClass.'User';
                $alertUser = new $alertUserClass();
                $alertUser->setAlert($alert);
                $alertUser->setUser($recipient);
                $alertUser->setIsRead(false);
                $alertUser->setViewed(false);
                $alertUser->setDeleted(false);

                $this->em->persist($alertUser);
            }

            $this->em->flush();
        }

        return $alert;
    }


}