<?php

namespace App\Admin;

use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Route\RouteCollection;
use Sonata\CoreBundle\Form\Type\BooleanType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class BusinessUnitAdmin extends AbstractAdmin
{
    protected function configureFormFields(FormMapper $formMapper)
    {
        $formMapper->add('fullName', TextType::class)
            ->add('listName', TextType::class)
            ->add('counterpartCode', TextType::class)
            ->add('groupName', TextType::class, ['required' => false])
            ->add('active', null);
    }

    protected function configureDatagridFilters(DatagridMapper $datagridMapper)
    {
        $datagridMapper->add('fullName')
            ->add('listName')
            ->add('counterpartCode')
            ->add('groupName')
            ->add('active');
    }

    protected function configureListFields(ListMapper $listMapper)
    {
        unset($this->listModes['mosaic']);
        $listMapper->addIdentifier('fullName')
            ->addIdentifier('listName')
            ->addIdentifier('counterpartCode')
            ->addIdentifier('groupName')
            ->addIdentifier('active');
    }

    protected function configureRoutes(RouteCollection $collection): void
    {
        $collection->remove('delete');
    }
}
