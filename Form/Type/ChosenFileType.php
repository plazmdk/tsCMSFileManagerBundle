<?php
/**
 * Created by PhpStorm.
 * User: plazm
 * Date: 8/23/14
 * Time: 9:39 AM
 */

namespace tsCMS\FileManagerBundle\Form\Type;


use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class ChosenFileType extends AbstractType {
    private $dataClass;
    private $entityPathField;
    private $entityPositionField;
    private $entityTitleField;
    private $entityDescriptionField;

    function __construct($dataClass, $entityPathField, $entityPositionField, $entityTitleField, $entityDescriptionField)
    {
        $this->dataClass = $dataClass;
        $this->entityPathField = $entityPathField;
        $this->entityPositionField = $entityPositionField;
        $this->entityTitleField = $entityTitleField;
        $this->entityDescriptionField = $entityDescriptionField;
    }


    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
        ->add('id', 'hidden')
        ->add($this->entityPathField, 'hidden', array(
            'required' => true,
            'attr' => array(
                'class' => 'path'
            )
        ));

        if ($this->entityPositionField) {
            $builder->add($this->entityPositionField, 'hidden', array(
                'required' => true,
                'attr' => array(
                    'class' => 'position'
                )
            ));
        }

        if ($this->entityTitleField) {
            $builder->add($this->entityTitleField, 'text', array(
                'required' => false,
                'attr' => array(
                    'class' => 'title'
                )
            ));
        }

        if ($this->entityDescriptionField) {
            $builder->add($this->entityDescriptionField, 'textarea', array(
                'required' => false,
                'attr' => array(
                    'class' => 'description'
                )
            ));
        }
    }

    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => $this->dataClass
        ));
    }

    /**
     * Returns the name of this type.
     *
     * @return string The name of this type
     */
    public function getName()
    {
        return "chosenfile";
    }
}