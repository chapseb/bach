<?php
/**
 * Bach geolocalisation adminstration (for SonataAdminBundle)
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
use Sonata\AdminBundle\Route\RouteCollection;

/**
 * Bach geolocalisation management
 *
 * @category Search
 * @package  Bach
 * @author   Johan Cwiklinski <johan.cwiklinski@anaphore.eu>
 * @license  BSD 3-Clause http://opensource.org/licenses/BSD-3-Clause
 * @link     http://anaphore.eu
 */
class GeolocAdmin extends Admin
{
    protected $baseRouteName = 'admin_bach_homebundle_geolocadmin';
    protected $baseRoutePattern = 'geoloc';

    /**
     * Fields to be shown on create/edit forms
     *
     * @param FormMapper $formMapper Mapper
     *
     * @return void
     */
    protected function configureFormFields(FormMapper $formMapper)
    {
        $iname_params = array(
            'required'  => true,
            'disabled'  => false,
            'label'     => _('Indexed name')
        );

        if ( $this->getSubject()->getId() !== null ) {
            $iname_params = array(
                'required'  => false,
                'disabled'  => true,
                'label'     => _('Indexed name')
            );
        }

        $formMapper
            ->add(
                'indexed_name',
                null,
                $iname_params
            )->add(
                'name',
                null,
                array(
                    'label' => _('Name')
                )
            )->add(
                'place_id'
            )->add(
                'type'
            )->add(
                'osm_id'
            )->add(
                'bbox',
                null,
                array(
                    'label' => _('Bounding box')
                )
            )->add(
                'lat',
                null,
                array(
                    'label' => _('Latitude')
                )
            )->add(
                'lon',
                null,
                array(
                    'label' => _('Longitude')
                )
            )->add(
                'geojson'
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
        $collection->add('geoloc_missing');
        $collection->add('geoloc_visualize');
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
                'indexed_name',
                null,
                array(
                    'label' => _('Indexed name')
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
        $listMapper
            ->addIdentifier(
                'indexed_name',
                null,
                array(
                    'label' => _('Indexed name')
                )
            )->add(
                'lat',
                null,
                array(
                    'label' => _('Latitude')
                )
            )->add(
                'lon',
                null,
                array(
                    'label' => _('Longitude')
                )
            );
    }
}
