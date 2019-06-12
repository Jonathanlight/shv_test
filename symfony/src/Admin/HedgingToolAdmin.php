<?php

namespace App\Admin;

use App\Entity\MasterData\HedgingTool;
use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Sonata\AdminBundle\Route\RouteCollection;

/**
 * Class HedgingToolAdmin
 * @package App\Admin
 */
class HedgingToolAdmin extends AbstractAdmin
{

    /**
     * @param FormMapper $formMapper
     */
    protected function configureFormFields(FormMapper $formMapper)
    {
        // get the current Image instance
        $hedgingTool = $this->getSubject();

        // use $fileFieldOptions so we can add other options to the field
        $fileFieldOptions = ['required' => false];
        if ($hedgingTool && ($webPath = $hedgingTool->getChartImagePath())) {
            // get the container so the full path to the image can be set
            $container = $this->getConfigurationPool()->getContainer();
            $fullPath = $container->get('request_stack')->getCurrentRequest()->getBasePath() . '/' . $webPath;

            // add a 'help' option containing the preview's img tag
            $fileFieldOptions['help'] = '<img style="max-height: 200px; max-width: 200px;" src="' . $fullPath . '" class="admin-preview"/>';
        }

        $formMapper->add('name', TextType::class, [
            'disabled' => $this->isCurrentRoute('create') ? false : true
        ]);

        if ($this->isCurrentRoute('create')) {
            $formMapper->add('operationType', IntegerType::class, [
                'disabled' => false
            ]);
        } else {
            $formMapper->add('operationTypeLabel', TextType::class, [
                'disabled' => true
            ]);
        }

        $formMapper->add('chartImage', FileType::class, $fileFieldOptions);

    }

    /**
     * @param ListMapper $listMapper
     */
    protected function configureListFields(ListMapper $listMapper)
    {
        unset($this->listModes['mosaic']);
        $listMapper->addIdentifier('name')
            ->add('operationTypeLabel', 'string');
    }

    /**
     * @param DatagridMapper $datagridMapper
     */
    protected function configureDatagridFilters(DatagridMapper $datagridMapper)
    {
        $datagridMapper->add('operationType')
            ->add('name');
    }

    /**
     * @param RouteCollection $collection
     */
    protected function configureRoutes(RouteCollection $collection): void
    {
        $collection->remove('delete');
        $collection->remove('create');
    }

    /**
     * @param $hedgingTool
     */
    public function prePersist($hedgingTool)
    {
        $this->manageFileUpload($hedgingTool);
    }

    /**
     * @param $hedgingTool
     */
    public function preUpdate($hedgingTool)
    {
        $this->manageFileUpload($hedgingTool);
    }

    /**
     * Manages the copying of the file to the relevant place on the server
     */
    private function manageFileUpload(HedgingTool $hedgingTool)
    {
        if ($hedgingTool->getChartImage()) {

            $container = $this->getConfigurationPool()->getContainer();

            // upload dir
            $uploadDir = sprintf('%s', $container->getParameter('hedging_tool_files_dir'));

            if (!file_exists($uploadDir) && !is_dir($uploadDir)) {
                mkdir($uploadDir, 0775, true);
            }

            // filenames
            $filename = uniqid() . '.' . $hedgingTool->getChartImage()->guessExtension();
            $originalFilePath = $uploadDir . '/' . $filename;

            // move takes the target directory and target filename as params
            $hedgingTool->getChartImage()->move(
                $uploadDir,
                $filename
            );

            // and delete old file if it exists
            if (file_exists($hedgingTool->getChartImagePath())) {
                if (!unlink($hedgingTool->getChartImagePath())) {
                    echo "Success";
                }
            }

            // set the path property to the filename where we've saved the file
            $hedgingTool->setChartImagePath($originalFilePath);

            if (!file_exists($uploadDir) && !is_dir($uploadDir)) {
                mkdir($uploadDir, 0775, true);
            }

            // clean up the file property as we won't need it anymore
            $hedgingTool->setChartImage(null);
        }
    }
}
