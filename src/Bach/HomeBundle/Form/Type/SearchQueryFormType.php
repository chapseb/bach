<?php
/**
 * Bach search form type
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
 * @category Search
 * @package  Bach
 * @author   Johan Cwiklinski <johan.cwiklinski@anaphore.eu>
 * @license  BSD 3-Clause http://opensource.org/licenses/BSD-3-Clause
 * @link     http://anaphore.eu
 */

namespace Bach\HomeBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Bach search form type
 *
 * PHP version 5
 *
 * @category Search
 * @package  Bach
 * @author   Johan Cwiklinski <johan.cwiklinski@anaphore.eu>
 * @license  BSD 3-Clause http://opensource.org/licenses/BSD-3-Clause
 * @link     http://anaphore.eu
 */
class SearchQueryFormType extends AbstractType
{
    private $_value = "";
    private $_keep;
    private $_pdf;

    /**
     * Instanciate search form
     *
     * @param string  $value Search term (default to empty string)
     * @param boolean $keep  Wheter to display keep filters checkbox
     * @param boolean $pdf   Wheter to display pdf filters checkbox
     */
    public function __construct($value = '', $keep = true, $pdf = true)
    {
        $this->_value = $value;
        $this->_keep = $keep;
        $this->_pdf = $pdf;
    }

    /**
     * Builds form
     *
     * @param FormBuilderInterface $builder Builder
     * @param array                $options Options
     *
     * @return void
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            'query',
            'text',
            array(
                'attr'  => array(
                    'placeholder'  => _('Enter your search'),
                    'value'        => $this->_value,
                    'autocomplete' => 'off',
                    'title'        => _('Enter your search')
                )
            )
        );
        if ($this->_keep === true) {
            $builder->add(
                'keep_filters',
                'checkbox',
                array(
                    'data'      => true,
                    'label'     => _('Keep filters'),
                    'required'  => false
                )
            );
        }
        $builder->add(
            'pdf_filters',
            'checkbox',
            array(
                'data'      => true,
                'label'     => _('Search in pdf'),
                'required'  => false
            )
        );

        $builder->add(
            'perform_search',
            'submit',
            array(
                'label' => _('Search'),
                'attr'  => array(
                    'class' => 'btn btn-primary'
                )
            )
        );
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return 'searchQuery';
    }
}
