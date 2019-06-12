<?php

namespace App\EventListener;

use App\Entity\User;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\Security\Core\Security;

class KernelListener
{
    private $security;

    private $twig;

    public function __construct(Security $security, \Twig_Environment $twig)
    {
        $this->security = $security;
        $this->twig = $twig;
    }

    public function onKernelRequest(GetResponseEvent $event)
    {
        $request = $event->getRequest();
        $user = $this->security->getUser();
        $session = $request->getSession();

        if ($user instanceof User) {
            $selectedBusinessUnit =  $session->get('selectedBusinessUnit') ?: $user->getBusinessUnits()->first();
            $request->attributes->set('selectedBusinessUnit', $selectedBusinessUnit);
            $this->twig->addGlobal('selected_business_unit', $selectedBusinessUnit);
        }
    }

}