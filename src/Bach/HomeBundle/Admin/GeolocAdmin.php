<?php
/**
 * Bach geolocalisation adminstration (for SonataAdminBundle)
 *
 * PHP version 5
 *
 * @category Search
 * @package  Bach
 * @author   Johan Cwiklinski <johan.cwiklinski@anaphore.eu>
 * @license  Unknown http://unknown.com
 * @link     http://anaphore.eu
 */
namespace Bach\HomeBundle\Admin;

use Sonata\AdminBundle\Admin\Admin;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Form\FormMapper;

/**
 * Bach geolocalisation management
 *
 * @category Search
 * @package  Bach
 * @author   Johan Cwiklinski <johan.cwiklinski@anaphore.eu>
 * @license  Unknown http://unknown.com
 * @link     http://anaphore.eu
 */
class GeolocAdmin extends Admin
{
    protected $baseRouteName = 'admin_vendor_bundlename_adminclassname';
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
        );

        if ( $this->getSubject()->getId() !== null ) {
            $iname_params = array(
                'required'  => false,
                'disabled'  => true,
            );
        }

        $formMapper
            ->add(
                'indexed_name',
                null,
                $iname_params
            )->add(
                'name'
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
     * Fields to be shown on filter forms
     *
     * @param DatagridMapper $datagridMapper Grid mapper
     *
     * @return void
     */
    protected function configureDatagridFilters(DatagridMapper $datagridMapper)
    {
        $datagridMapper
            ->add('indexed_name');
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
