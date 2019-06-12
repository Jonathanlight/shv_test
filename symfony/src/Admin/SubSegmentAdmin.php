<?php

namespace App\Admin;

use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Route\RouteCollection;
use Sonata\CoreBundle\Form\Type\BooleanType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class SubSegmentAdmin extends AbstractAdmin
{
    protected function configureFormFields(FormMapper $formMapper)
    {
        $formMapper->add('name', TextType::class)
            ->add('code', TextType::class)
            ->add('segment', null)
            ->add('customerSegment', null)
            ->add('active', null);
    }

    protected function configureDatagridFilters(DatagridMapper $datagridMapper)
    {
        $datagridMapper->add('name')
            ->add('code')
            ->add('segment')
            ->add('customerSegment')
            ->add('active');
    }

    protected function configureListFields(ListMapper $listMapper)
    {
        unset($this->listModes['mosaic']);
        $listMapper->addIdentifier('name')
            ->addIdentifier('code')
            ->add('segment')
            ->add('customerSegment')
            ->addIdentifier('active');
    }

    protected function configureRoutes(RouteCollection $collection): void
    {
        $collection->remove('delete');
    }
}
