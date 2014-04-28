<?php
/**
 * Field form
 *
 * PHP version 5
 *
 * Copyright (c) 2014, Anaphore
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are
 * met:
 *
 *     (1) Redistributions of source code must retain the above copyright
 *     notice, this list of conditions and the following disclaimer.
 *
 *     (2) Redistributions in binary form must reproduce the above copyright
 *     notice, this list of conditions and the following disclaimer in
 *     the documentation and/or other materials provided with the
 *     distribution.
 *
 *     (3)The name of the author may not be used to
 *    endorse or promote products derived from this software without
 *    specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE AUTHOR ``AS IS'' AND ANY EXPRESS OR
 * IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
 * WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL THE AUTHOR BE LIABLE FOR ANY DIRECT,
 * INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR
 * SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION)
 * HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT,
 * STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING
 * IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 *
 * @category Administration
 * @package  Bach
 * @author   Johan Cwiklinski <johan.cwiklinski@anaphore.eu>
 * @license  BSD 3-Clause http://opensource.org/licenses/BSD-3-Clause
 * @link     http://anaphore.eu
 */

namespace Bach\AdministrationBundle\Entity\Helpers\FormBuilders;

use Bach\AdministrationBundle\Entity\SolrSchema\XMLProcess;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Bach\AdministrationBundle\Entity\SolrSchema\BachSchemaConfigReader;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Field form entry
 *
 * @category Administration
 * @package  Bach
 * @author   Johan Cwiklinski <johan.cwiklinski@anaphore.eu>
 * @license  BSD 3-Clause http://opensource.org/licenses/BSD-3-Clause
 * @link     http://anaphore.eu
 */
class FieldForm extends AbstractType
{
    private $_xmlp;

    /**
     * Main constructor
     *
     * @param XMLProcess $xmlp XMLProcess instance
     */
    public function __construct(XMLProcess $xmlp)
    {
        $this->_xmlp = $xmlp;
    }

    /**
     * Builds the form
     *
     * @param FormBuilderInterface $builder Form builder
     * @param array                $options Form options
     *
     * @return void
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $bachTagType = BachSchemaConfigReader::FIELD_TAG;
        $reader = new BachSchemaConfigReader();

        // Form attributes
        $attr = $reader->getAttributeByTag($bachTagType, 'name');
        $builder->add(
            'name',
            'text',
            array(
                'label'    => $attr->getLabel(),
                'required' => true,
                'read_only' => true
            )
        );
        $attr = $reader->getAttributeByTag($bachTagType, 'type');
        $builder->add(
            'type',
            'choice',
            array(
                'label'    => $attr->getLabel(),
                'required' => true,
                'choices'  => $this->_retrieveTypeAttributeValues(),
            )
        );
        $attr = $reader->getAttributeByTag($bachTagType, 'indexed');
        $builder->add(
            'indexed',
            'checkbox',
            array(
                'label'    => $attr->getLabel(),
                'required' => false
            )
        );
        $attr = $reader->getAttributeByTag($bachTagType, 'stored');
        $builder->add(
            'stored',
            'checkbox', array(
                'label'    => $attr->getLabel(),
                'required' => false
            )
        );
        $attr = $reader->getAttributeByTag($bachTagType, 'multiValued');
        $builder->add(
            'multiValued',
            'checkbox', array(
                'label'    => $attr->getLabel(),
                'required' => false
            )
        );
        $attr = $reader->getAttributeByTag($bachTagType, 'default');
        $builder->add(
            'default',
            'text',
            array(
                'label'    => $attr->getLabel(),
                'required' => false
            )
        );
        $attr = $reader->getAttributeByTag($bachTagType, 'required');
        $builder->add(
            'required',
            'checkbox',
            array(
                'label'    => $attr->getLabel(),
                'required' => false
            )
        );
    }

    /**
     * Sets default options
     *
     * @param OptionsResolverInterface $resolver Resolver
     *
     * @return void
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(
            array(
                'data_class' => 'Bach\AdministrationBundle\Entity' .
                '\Helpers\FormObjects\Field',
            )
        );
    }

    /**
     * Get form name
     *
     * @return string
     */
    public function getName()
    {
        return 'fieldForm';
    }

    /**
     * Get available values for type attribute. Only values from the
     * schema.xml in typeField tags can be used.
     *
     * @return multitype:NULL
     */
    private function _retrieveTypeAttributeValues()
    {
        $choices = array();
        $types = $this->_xmlp->getElementsByName('types');
        $types = $types[0];
        $types = $types->getElementsByName('fieldType');
        foreach ($types as $t) {
            $schemaAttr = $t->getAttribute('name');
            if ( !in_array($schemaAttr->getValue(), $choices) ) {
                $choices[$schemaAttr->getValue()] = $schemaAttr->getValue();
            }
        }
        return $choices;
    }
}
