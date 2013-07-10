<?php
namespace Bach\AdministrationBundle\Entity\Helpers\FormBuilders;

use Bach\AdministrationBundle\Entity\SolrSchema\XMLProcess;

use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Bach\AdministrationBundle\Entity\SolrSchema\BachAttribute;
use Bach\AdministrationBundle\Entity\SolrSchema\BachSchemaConfigReader;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class CopyFieldForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $bachTagType = BachSchemaConfigReader::COPY_FIELD_TAG;
        $reader = new BachSchemaConfigReader();
        
        // Form attributes
        $attr = $reader->getAttributeByTag($bachTagType, 'source');
        $builder->add('source', 'choice', array(
                'label'    => $attr->getLabel(),
                'required' => true,
                'choices'  => $this->retreiveUniqueKeyValues()
                ));
        $attr = $reader->getAttributeByTag($bachTagType, 'dest');
        $builder->add('dest', 'choice', array(
                'label'    => $attr->getLabel(),
                'required' => true,
                'choices'  => $this->retreiveUniqueKeyValues()
                ));
        $attr = $reader->getAttributeByTag($bachTagType, 'maxChars');
        $builder->add('maxChars', 'integer', array(
                'label'    => $attr->getLabel(),
                'required' => false
                ));
    }
    
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
                'data_class' => 'Bach\AdministrationBundle\Entity\Helpers\FormObjects\CopyField',
        ));
    }
    
    public function getName()
    {
        return 'copyFieldForm';
    }
    
    /**
     * Get available values for source and dest attributes. Only values from the schema.xml in field tags
     * can be used.
     * @return multitype:NULL
     */
    private function retreiveUniqueKeyValues()
    {
        $choices = array();
        /*$session = new Session();
         if ($session->has('schema')) {
        $xmlProcess = $session->get('schema');*/
        $xmlP = new XMLProcess($_SESSION['_sf2_attributes']['coreName']);
        $fieldsTag = $xmlP->getElementsByName('fields');
        $fieldsTag= $fieldsTag[0];
        $fields = $fieldsTag->getElementsByName('field');
        foreach ($fields as $t) {
            $schemaAttr = $t->getAttribute('name');
            if (!$this->isContains($choices, $schemaAttr->getValue())) {
                $choices[$schemaAttr->getValue()] = $schemaAttr->getValue();
            }
        }
        $fields = $fieldsTag->getElementsByName('dynamicField');
        foreach ($fields as $t) {
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
