<?php
/**
 * Bach facets adminstration (for SonataAdminBundle)
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
use Bach\AdministrationBundle\Entity\SolrCore\Fields;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Pix\SortableBehaviorBundle\Services\PositionHandler;
use Sonata\AdminBundle\Route\RouteCollection;

/**
 * Bach facets management
 *
 * @category Search
 * @package  Bach
 * @author   Johan Cwiklinski <johan.cwiklinski@anaphore.eu>
 * @license  BSD 3-Clause http://opensource.org/licenses/BSD-3-Clause
 * @link     http://anaphore.eu
 */
class FacetsAdmin extends Admin
{
    private $_reader;
    private $_search_core;
    private $_current_form;
    private $_fields_class;

    protected $datagridValues = array(
        '_page'         => 1,
        '_sort_order'   => 'ASC',
        '_sort_by'      => 'position'
    );

    public $last_position = 0;

    /**
     * Constructor
     *
     * @param string                    $code               ?
     * @param string                    $class              ?
     * @param string                    $baseControllerName ?
     * @param BachCoreAdminConfigReader $reader             Config reader.
     * @param string                    $search_core        Search core name
     * @param string                    $current_form       Current form name
     * @param string                    $fields_class       Fields class name
     */
    public function __construct($code, $class, $baseControllerName, $reader,
        $search_core, $current_form = 'main', $fields_class = 'EAD'
    ) {
        $this->_reader = $reader;
        $this->_search_core = $search_core;
        $this->_current_form = $current_form;
        $this->_fields_class = $fields_class;
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
        //retrieve already used solr fields to exclude them
        $qb = $this->modelManager
            ->getEntityManager('Bach\HomeBundle\Entity\facets')
            ->createQueryBuilder()
            ->add('select', 'f.solr_field_name')
            ->add('from', 'Bach\HomeBundle\Entity\Facets f')
            ->add('where', 'f.form = :current_form')
            ->setParameter('current_form', $this->_current_form);

        if ( $this->getSubject()->getId() !== null ) {
            $qb
                ->andWhere('f.solr_field_name != :curfield')
                ->setParameter('curfield', $this->getSubject()->getSolrFieldName());
        }

        $query = $qb->getQuery();
        $results = $query->getResult();
        $used = array_map(
            function ($value) {
                return $value['solr_field_name'];
            },
            $results
        );

        $fields = new Fields($this->_reader);
        $fields_class = 'Bach\IndexationBundle\Entity\\' .
            $this->_fields_class . 'FileFormat';
        $facet_fields = $fields->getFacetFields(
            $this->_search_core,
            array_merge(
                $fields_class::$facet_excluded,
                $used
            )
        );

        $formMapper
            ->add(
                'solr_field_name',
                'choice',
                array(
                    'choices' => $facet_fields,
                    'label' => _('Solr field name')
                )
            )->add(
                'active',
                null,
                array(
                    'required'  => false,
                    'label'     => _('Activate facet')
                )
            )->add(
                'on_home',
                null,
                array(
                    'required'  => false,
                    'label'     => _('Show on homepage')
                )
            )->add(
                'fr_label',
                null,
                array(
                    'label' => _('French text')
                )
            )->add(
                'en_label',
                null,
                array(
                    'label' => _('English text')
                )
            );
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
        $collection->add('move', $this->getRouterIdParameter() . '/move/{position}');
    }

    /**
     * Fields to be shown on filter forms
     *
     * @param DatagridMapper $datagridMapper Grid mapper
     *
     * @return void
     */
    protected function configureDatagridFilters(DatagridMapper $datagridMapper)
    {
        $datagridMapper
            ->add(
                'active',
                null,
                array(
                    'label' => _('Active')
                )
            )
            ->add(
                'on_home',
                null,
                array(
                    'label' => _('Home')
                )
            )
            ->add(
                'fr_label',
                null,
                array(
                    'label' => _('French label')
                )
            )
            ->add(
                'en_label',
                null,
                array(
                    'label' => _('English label')
                )
            );
    }

    /**
     * Fields to be shown on lists
     *
     * @param ListMapper $listMapper List mapper
     *
     * @return void
     */
    protected function configureListFields(ListMapper $listMapper)
    {
        $qb = $this->modelManager
            ->getEntityManager('Bach\HomeBundle\Entity\facets')
            ->createQueryBuilder()
            ->add('select', 'MAX(f.position)')
            ->add('from', 'Bach\HomeBundle\Entity\Facets f')
            ->add('where', 'f.form = :current_form')
            ->setParameter('current_form', $this->_current_form);

        $query = $qb->getQuery();
        $result = $query->getResult();

        if (array_key_exists(0, $result)) {
            $this->last_position = intval($result[0][1]);
        } else {
            $this->last_position = 0;
        }

        $listMapper
            ->addIdentifier(
                'solr_field_name',
                null,
                array(
                    'template'  => 'AdministrationBundle:Admin:show_field.html.twig',
                    'label'     => _('Solr field name')
                )
            )->add(
                'active',
                null,
                array(
                    'editable'  => true,
                    'label'     => _('Active')
                )
            )->add(
                'on_home',
                null,
                array(
                    'editable'  => true,
                    'label'     => _('Home')
                )
            )->add(
                'fr_label',
                null,
                array(
                    'label' => _('French text')
                )
            )->add(
                'en_label',
                null,
                array(
                    'label' => _('English text')
                )
            )->add(
                '_action',
                'actions',
                array(
                    'actions' => array(
                        'move' => array(
                            'template' => 'PixSortableBehaviorBundle:Default:_sort.html.twig'
                        ),
                    )
                )
            );
    }

    /**
     * Create query
     *
     * @param string $context Context
     *
     * @return \Sonata\AdminBundle\Datagrid\ProxyQueryInterface
     */
    public function createQuery($context = 'list')
    {
        $query = parent::createQuery($context);

        $query->andWhere(
            $query->expr()->eq($query->getRootAlias() . '.form', ':current_form')
        );
        $query->setParameter(
            'current_form',
            $this->_current_form
        );
        return $query;
    }

    /**
     * Create object instance
     *
     * @return Mixed
     */
    public function getNewInstance()
    {
        $object = $this->getModelManager()->getModelInstance($this->getClass());
        $object->setForm($this->_current_form);
        foreach ($this->getExtensions() as $extension) {
            $extension->alterNewInstance($this, $object);
        }

        return $object;
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
     * Position handler injection
     *
     * @param PositionHandler $positionHandler Position handler
     *
     * @return void
     */
    public function setPositionService(PositionHandler $positionHandler)
    {
        $this->positionService = $positionHandler;
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

    /**
     * Set baseRoutePattern used to generate the routing information
     *
     * @param string $pattern Route pattern
     *
     * @return void
     */
    public function setBaseRoutePattern($pattern)
    {
        $this->baseRoutePattern = $pattern;
    }

    /**
     * Set baseRouteName used to generate the routing information
     *
     * @param string $name Route name
     *
     * @return void
     */
    public function setBaseRouteName($name)
    {
        $this->baseRouteName = $name;
    }

    /**
     * Set class name label (for menu entries)
     *
     * @param string $label Label
     *
     * @return void
     */
    public function setClassnameLabel($label)
    {
        $this->classnameLabel = $label;
    }
}
