<?php

namespace App\Admin;

use App\Entity\User;
use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Sonata\CoreBundle\Form\Type\BooleanType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class UserAdmin extends AbstractAdmin
{
    protected function configureFormFields(FormMapper $formMapper)
    {
        $formMapper->add('firstName', TextType::class)
            ->add('lastName', TextType::class)
            ->add('email', TextType::class)
            ->add('function', TextType::class)
            ->add('roles', ChoiceType::class, [
                'choices' => array_flip(User::$adminRolesChoices),
                'expanded' => false,
                'multiple' => true,
                'label' => 'app.admin.label.admin_roles',
                'required' => false,
            ])
            ->add('role', ChoiceType::class, [
                'choices' => array_flip(User::$rolesChoices),
                'expanded' => false,
                'multiple' => false,
                'label' => 'app.admin.label.role',
                'required' => false,
            ])
            ->add('businessUnits')
            ->add('enabled', null);
    }

    protected function configureDatagridFilters(DatagridMapper $datagridMapper)
    {
        $datagridMapper->add('firstName')
            ->add('lastName')
            ->add('email')
            ->add('function')
            ->add('roles')
            ->add('businessUnits');
    }

    protected function configureListFields(ListMapper $listMapper)
    {
        unset($this->listModes['mosaic']);
        $listMapper->addIdentifier('firstName')
            ->addIdentifier('lastName')
            ->addIdentifier('email')
            ->add('function')
            ->add('businessUnits');
    }
}
