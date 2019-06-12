<?php
namespace App\Form;

use App\Form\DataTransformer\XssTransformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use FOS\CKEditorBundle\Form\Type\CKEditorType as CKEditorType;

class CommentType extends AbstractType
{
    private $xssTransformer;

    /**
     * CommentType constructor.
     * @param XssTransformer $xssTransformer
     */
    public function __construct(XssTransformer $xssTransformer)
    {
        $this->xssTransformer = $xssTransformer;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('message', CKEditorType::class, [
                'label' => false,
                'required' => false
            ]);

        $builder->get('message')
            ->addModelTransformer($this->xssTransformer);
    }
}