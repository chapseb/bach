<?php
/**
 * Bach geolocalization fields adminstration (for SonataAdminBundle)
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
namespace Bach\HomeBundle\Admin;

use Sonata\AdminBundle\Admin\Admin;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Bach\IndexationBundle\Entity\EADFileFormat;
use Bach\AdministrationBundle\Entity\SolrCore\Fields;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Sonata\AdminBundle\Route\RouteCollection;

/**
 * Bach geolocalization fields management
 *
 * @category Search
 * @package  Bach
 * @author   Johan Cwiklinski <johan.cwiklinski@anaphore.eu>
 * @license  BSD 3-Clause http://opensource.org/licenses/BSD-3-Clause
 * @link     http://anaphore.eu
 */
class GeolocFieldsAdmin extends Admin
{
    private $_reader;
    private $_search_core;
    private $_data_class;

    /**
     * Constructor
     *
     * @param string                    $code               ?
     * @param string                    $class              ?
     * @param string                    $baseControllerName ?
     * @param BachCoreAdminConfigReader $reader             Config reader.
     * @param string                    $search_core        Search core name
     * @param string                    $data_class         Data class name
     */
    public function __construct($code, $class, $baseControllerName, $reader,
        $search_core, $data_class
    ) {
        $this->_reader = $reader;
        $this->_search_core = $search_core;
        $this->_data_class = $data_class;
        parent::__construct($code, $class, $baseControllerName);
    }

    /**
     * Fields to be shown on create/edit forms
     *
     * @param FormMapper $formMapper Mapper
     *
     * @return void
     */
    protected function configureFormFields(FormMapper $formMapper)
    {
        $formMapper
            ->add(
                'solr_fields_names',
                'choice',
                array(
                    'choices'   => $this->getFields(),
                    'label'     => _('Fields'),
                    'multiple'  => true
                )
            );
    }

    /**
     * Return fields list
     *
     * @return array
     */
    protected function getFields()
    {
        $fields = new Fields($this->_reader);
        $solr_fields = $fields->getFacetFields(
            $this->_search_core,
            $this->getExcludedFields()
        );
        return $solr_fields;
    }

    /**
     * Get excluded fields
     *
     * @return array
     */
    protected function getExcludedFields()
    {
        $class = $this->_data_class;
        return $class::$facet_excluded;
    }

    /**
     * Configure routes
     *
     * @param RouteCollection $collection Collection
     *
     * @return void
     */
    protected function configureRoutes(RouteCollection $collection)
    {
        $collection->clearExcept(array('edit'));
    }

    /**
     * Container injenction
     *
     * @param ContainerInterface $container Container
     *
     * @return void
     */
    public function setContainer(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * Retrieve localized label for field
     *
     * @param string $name Field name
     *
     * @return string
     */
    public function getFieldLabel($name)
    {
        $fields = new Fields();
        return $fields->getFieldLabel($name);
    }
}
