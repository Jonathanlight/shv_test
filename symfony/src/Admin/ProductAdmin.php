<?php

namespace App\Admin;

use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Route\RouteCollection;
use Sonata\CoreBundle\Form\Type\BooleanType;
use Symfony\Component\Form\Extension\Core\Type\TextType;


class ProductAdmin extends AbstractAdmin
{
    protected function configureFormFields(FormMapper $formMapper)
    {
        $formMapper->add('name', TextType::class)
            ->add('longName', TextType::class)
            ->add('code', TextType::class)
            ->add('uom', null)
            ->add('currency', null)
            ->add('commodity', null)
            ->add('fxQuote', null)
            ->add('pricingType', null)
            ->add('active', null);
    }

    protected function configureDatagridFilters(DatagridMapper $datagridMapper)
    {
        $datagridMapper->add('name')
            ->add('longName')
            ->add('code')
            ->add('uom', null)
            ->add('currency', null)
            ->add('commodity', null)
            ->add('fxQuote', null)
            ->add('pricingType', null)
            ->add('active', null);
    }

    protected function configureListFields(ListMapper $listMapper)
    {
        unset($this->listModes['mosaic']);
        $listMapper->addIdentifier('name')
            ->add('longName', null)
            ->add('code', null)
            ->add('pricingType', null)
            ->add('fxQuote', null)
            ->add('uom', null)
            ->add('currency', null)
            ->add('commodity', null)
            ->add('active', null, ['editable' => true]);
    }

    protected function configureRoutes(RouteCollection $collection): void
    {
        $collection->clearExcept('list');
        $collection->add('import');
    }

    public function getDashboardActions()
    {
        $actions = parent::getDashboardActions();

        $actions['import'] = [
            'label' => 'import',
            'translation_domain' => 'SonataAdminBundle',
            'url' => $this->generateUrl('import'),
            'icon' => 'level-up',
        ];

        return $actions;
    }

    public function configureActionButtons($action, $object = null)
    {
        $list = parent::configureActionButtons($action,$object);

        $list['import'] = array(
            'template' =>  'admin/import_product_action.html.twig',
        );

        return $list;
    }
}
