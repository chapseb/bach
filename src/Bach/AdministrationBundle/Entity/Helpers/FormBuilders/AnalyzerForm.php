<?php
/**
 * Analyser form
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

use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Bach\AdministrationBundle\Entity\SolrSchema\BachSchemaConfigReader;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Analyser form
 *
 * @category Administration
 * @package  Bach
 * @author   Johan Cwiklinski <johan.cwiklinski@anaphore.eu>
 * @license  BSD 3-Clause http://opensource.org/licenses/BSD-3-Clause
 * @link     http://anaphore.eu
 */
class AnalyzerForm extends AbstractType
{

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
        $bachTagType = BachSchemaConfigReader::ANALYZER_TAG;
        $reader = new BachSchemaConfigReader();

        // Form attributes
        $builder->add(
            'name',
            'text',
            array(
                'label'     => 'Field type name',
                'read_only' => true
            )
        );
        $attr = $reader->getAttributeByTag($bachTagType, 'class');
        $builder->add(
            'class',
            'choice',
            array(
                'label'         => $attr->getLabel(),
                'empty_value'   => _('(none)'),
                'required'      => false,
                'choices'       => $this->_retrieveClassAttributeValues($reader)
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
                '\Helpers\FormObjects\Analyzer',
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
        return 'analyzerTypeForm';
    }

    /**
     * Get available values for type attribute.
     *
     * @param BachSchemaConfigReader $reader Config reader
     *
     * @return multitype:NULL
     */
    private function _retrieveClassAttributeValues(BachSchemaConfigReader $reader)
    {
        $attr = $reader->getAttributeByTag(
            BachSchemaConfigReader::ANALYZER_TAG,
            'class'
        );
        return $attr->getValues();
    }
}
