<?php

namespace App\Admin;

use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Route\RouteCollection;

final class ImportHedgesViewAdmin extends AbstractAdmin
{
    protected $baseRoutePattern = 'import_hedges';
    protected $baseRouteName = 'import_hedges';

    protected function configureRoutes(RouteCollection $collection)
    {
        $collection->clearExcept(['list']);
    }
}
