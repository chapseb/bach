<?php
namespace Anph\AdministrationBundle\Entity\Helpers\FormBuilders;

use Anph\AdministrationBundle\Entity\Helpers\FormObjects\Field;
use Anph\AdministrationBundle\Entity\SolrSchema\XMLProcess;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Anph\AdministrationBundle\Entity\SolrSchema\BachAttribute;
use Anph\AdministrationBundle\Entity\SolrSchema\BachSchemaConfigReader;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\HttpFoundation\Session\Session;


class FieldForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $bachTagType = BachSchemaConfigReader::FIELD_TAG;
        $reader = new BachSchemaConfigReader();
        
        // Form attributes
        $attr = $reader->getAttributeByTag($bachTagType, 'name');
        $builder->add('name', 'text', array(
                'label'    => $attr->getLabel(),
                'required' => true
                ));
        $attr = $reader->getAttributeByTag($bachTagType, 'type');
        $builder->add('type', 'choice', array(
                'label'    => $attr->getLabel(),
                'required' => true,
                'choices'  => $this->retreiveTypeAttributeValues(),
                ));
        $attr = $reader->getAttributeByTag($bachTagType, 'indexed');
        $builder->add('indexed', 'checkbox', array(
                'label'    => $attr->getLabel(),
                'required' => false
                ));
        $attr = $reader->getAttributeByTag($bachTagType, 'stored');
        $builder->add('stored', 'checkbox', array(
                'label'    => $attr->getLabel(),
                'required' => false
                ));
        $attr = $reader->getAttributeByTag($bachTagType, 'multiValued');
        $builder->add('multiValued', 'checkbox', array(
                'label'    => $attr->getLabel(),
                'required' => false
                ));
        $attr = $reader->getAttributeByTag($bachTagType, 'default');
        $builder->add('default', 'text', array(
                'label'    => $attr->getLabel(),
                'required' => false
                ));
        $attr = $reader->getAttributeByTag($bachTagType, 'required');
        $builder->add('required', 'checkbox', array(
                'label'    => $attr->getLabel(),
                'required' => false
                ));
        /*
         * Other Attributes that can be added to the application in the future
         */
        /*$attr = $reader->getAttributeByTag($bachTagType, 'omitNorms');
        $builder->add('omitNorms', 'checkbox', array(
                'label' => $attr->getLabel(),
                'required' => $attr->isRequired()));
        $attr = $reader->getAttributeByTag($bachTagType, 'omitTermFreqAndPositions');
        $builder->add('omitTermFreqAndPositions', 'checkbox', array(
                'label' => $attr->getLabel(),
                'required' => $attr->isRequired()));
        $attr = $reader->getAttributeByTag($bachTagType, 'omitPositions');
        $builder->add('omitPositions', 'checkbox', array(
                'label' => $attr->getLabel(),
                'required' => $attr->isRequired()));
        $attr = $reader->getAttributeByTag($bachTagType, 'termVectors');
        $builder->add('termVectors', 'checkbox', array(
                'label' => $attr->getLabel(),
                'required' => $attr->isRequired()));
        $attr = $reader->getAttributeByTag($bachTagType, 'termPositions');
        $builder->add('termPositions', 'checkbox', array(
                'label' => $attr->getLabel(),
                'required' => $attr->isRequired()));
        $attr = $reader->getAttributeByTag($bachTagType, 'termOffsets');
        $builder->add('termOffsets', 'checkbox', array(
                'label' => $attr->getLabel(),
                'required' => $attr->isRequired()));*/
    }
    
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
                'data_class' => 'Anph\AdministrationBundle\Entity\Helpers\FormObjects\Field',
        ));
    }

    public function getName()
    {
        return 'fieldForm';
    }
    
    /**
     * Get available values for type attribute. Only values from the schema.xml in typeField tags
     * can be used.
     * @return multitype:NULL
     */
    private function retreiveTypeAttributeValues()
    {
        $choices = array();
        /*$session = new Session();
        if ($session->has('schema')) {
            $xmlProcess = $session->get('schema');*/
            $xmlP = new XMLProcess('core0');
            $types = $xmlP->getElementsByName('types');
            $types = $types[0];
            $types = $types->getElementsByName('fieldType');
            foreach ($types as $t) {
                $schemaAttr = $t->getAttribute('name');
                if (!$this->isContains($choices, $schemaAttr->getValue())) {
                    $choices[$schemaAttr->getValue()] = $schemaAttr->getValue();
                }
            }
        //}
        return $choices;
    }
    
    private function isContains($choices, $name)
    {
        foreach ($choices as $c) {
            if ($c == $name) {
                return true;
            }
        }
        return false;
    }
}
