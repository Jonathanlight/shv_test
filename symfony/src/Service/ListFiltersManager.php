<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\Session\SessionInterface;

class ListFiltersManager
{

    private $session;

    /**
     * ListFiltersManager constructor.
     * @param SessionInterface $session
     */
    public function __construct(SessionInterface $session)
    {
        $this->session = $session;
    }

    /**
     * @param string $route
     * @param array $filters
     */
    public function setFilters(string $route, array $filters)
    {
        $this->session->set($route, $filters);
    }

    /**
     * @param string $route
     * @return null|array
     */
    public function getFilters(string $route): ?array
    {
        return $this->session->get($route);
    }

    /**
     * @param array $filters
     * @param int $page
     * @return array
     */
    public function formatFilters(array $filters, int $page = 1): array
    {
        $formattedFilters = [];
        foreach ($filters as $filter) {
            $formattedFilters[$filter['name']][] = $filter['value'];
        }

        return $filters = ['page' => $page, 'filters' => $formattedFilters];
    }
}