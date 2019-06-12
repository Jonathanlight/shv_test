<?php

namespace App\Admin;

use App\Entity\RMPLog;
use App\Entity\User;
use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Route\RouteCollection;
use Sonata\CoreBundle\Form\Type\DatePickerType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

final class RmpLogAdmin extends AbstractAdmin
{
    protected $datagridValues = [
        '_page' => 1,
        '_sort_order' => 'DESC',
        '_sort_by' => 'timestamp',
    ];

    protected function configureListFields(ListMapper $listMapper)
    {
        unset($this->listModes['mosaic']);
        $listMapper->add('type', null, ['template' => 'admin/rmp_log_column_field_type.html.twig', 'row_align' => 'left']);
    }

    protected function configureDatagridFilters(DatagridMapper $datagridMapper)
    {
        $datagridMapper->add('type', null, [], ChoiceType::class, [
            'choices' => array_flip(RMPLog::$typeActionsLabels),
        ])->add('user', 'doctrine_orm_model_autocomplete', [], null, [
            'property' => 'email',
        ])->add('timestamp', 'doctrine_orm_date', [
            'callback' => function ($queryBuilder, $alias, $field, $value) {

                if (!$value['value']) {
                    return;
                }

                $queryBuilder->andWhere($alias . '.timestamp BETWEEN :date_start AND :date_end')
                    ->setParameter('date_start', $value['value'] . ' 00:00:00')
                    ->setParameter('date_end', strtolower($value['value'], strtotime("+1 day")) . ' 00:00:00');

                return true;
            }
        ], DatePickerType::class, [
            'format' => 'yyyy-MM-dd'
        ]);
    }

    protected function configureRoutes(RouteCollection $collection): void
    {
        $collection->clearExcept('list');
    }
}
