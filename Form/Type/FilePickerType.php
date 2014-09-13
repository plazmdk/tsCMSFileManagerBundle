<?php
/**
 * Created by PhpStorm.
 * User: plazm
 * Date: 8/21/14
 * Time: 8:59 PM
 */

namespace tsCMS\FileManagerBundle\Form\Type;


use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\EventListener\ResizeFormListener;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class FilePickerType extends AbstractType
{
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'multiple' => true,
            'image' => false,
            'class' => null,
            'buttontext' => 'filepicker.selectfiles',
            'pathField' => 'path',
            'positionField' => null,
            'titleField' => null,
            'descriptionField' => null,
            'by_reference' => false
        ));

    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $type = new ChosenFileType($options['class'], $options['pathField'], $options['positionField'], $options['titleField'], $options['descriptionField']);

        $prototype = $builder->create('__file__', $type, array('label' => '__file__label__'),array("block_name" => "entry"));
        $builder->setAttribute('prototype', $prototype->getForm());

        $resizeListener = new ResizeFormListener(
            $type,
            array("block_name" => "entry"),
            true,
            true,
            false
        );

        $builder->addEventSubscriber($resizeListener);
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['prototype'] = $form->getConfig()->getAttribute('prototype')->createView($view);
        $view->vars['image'] = $options['image'];
        $view->vars['sortable'] = isset($options['positionField']);
        $view->vars['extraInfo'] = $options['titleField'] || $options['descriptionField'];
        $view->vars['buttontext'] = $options['buttontext'] == "filepicker.selectfiles" && $options['image'] ? "filepicker.selectimages" : $options['buttontext'];
    }

    /**
     * Returns the name of this type.
     *
     * @return string The name of this type
     */
    public function getName()
    {
        return "tscms_filepicker_multiple";
    }
}