<?php
/**
 * Bach search controller
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

namespace Bach\HomeBundle\Controller;

use Bach\HomeBundle\Entity\SolariumQueryContainer;
use Bach\HomeBundle\Entity\ViewParams;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Bach\HomeBundle\Entity\Filters;
use Bach\HomeBundle\Service\SolariumQueryFactory;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Bach search controller
 *
 * PHP version 5
 *
 * @category Search
 * @package  Bach
 * @author   Johan Cwiklinski <johan.cwiklinski@anaphore.eu>
 * @license  BSD 3-Clause http://opensource.org/licenses/BSD-3-Clause
 * @link     http://anaphore.eu
 */
abstract class SearchController extends Controller
{
    private $_geoloc;
    protected $date_field;
    protected $search_form;

    /**
     * Index page, will redirect to default_url
     *
     * @return void
     */
    public function indexAction()
    {
        $url = $this->get('router')->generate(
            $this->container->getParameter('default_url')
        );
        return new RedirectResponse($url);
    }

    /**
     * Search page
     *
     * @param string $query_terms Term(s) we search for
     * @param int    $page        Page
     * @param string $facet_name  Display more terms in suggests
     * @param string $form_name   Search form name
     *
     * @return void
     */
    abstract public function searchAction($query_terms = null, $page = 1,
        $facet_name = null, $form_name = null
    );

    /**
     * Get common template variables
     *
     * @param ViewParams $view_params View parameters
     * @param int        $page        Current requested page
     *
     * @return array
     */
    protected function searchTemplateVariables(ViewParams $view_params, $page = 1)
    {
        $common_vars = $this->commonTemplateVariables();

        $tpl_vars = array(
            'page'              => $page,
            'show_pics'         => $view_params->showPics(),
            'show_map'          => $view_params->showMap(),
            'show_daterange'    => $view_params->showDaterange(),
            'view'              => $view_params->getView(),
            'results_order'     => $view_params->getOrder(),
            'available_orders'  => $this->getOrders(),
            'available_views'   => $this->getViews(),
            'map_facets_name'   => $this->mapFacetsName(),
            'q'                 => ''
        );

        if ( $this->search_form !== null ) {
            $tpl_vars['search_form'] = $this->search_form;
        } else {
            $tpl_vars['search_form'] = 'default';
        }

        return array_merge($common_vars, $tpl_vars);
    }

    /**
     * Get common template variables
     *
     * @return array
     */
    protected function commonTemplateVariables()
    {
        $show_maps              = $this->container->getParameter('feature.maps');
        $viewer_uri             = $this->container->getParameter('viewer_uri');
        $covers_dir             = $this->container->getParameter('covers_dir');
        $viewDisplayParam       = $this->container->getParameter('display.ead.show_param');
        $viewDisplayOrderResult = $this->container->getParameter('display.ead.result_order');


        $tpl_vars = array(
            'viewer_uri'        => $viewer_uri,
            'show_maps'         => $show_maps,
            'covers_dir'        => $covers_dir,
            'cookie_param_name' => $this->getCookieName(),
            'view'              => $viewDisplayParam,
            'result_order'      => $viewDisplayOrderResult
        );

        return $tpl_vars;
    }

    /**
     * Handle geolocalization
     *
     * @param SolariumQueryFactory $factory Query factory
     *
     * @return void
     */
    protected function handleGeoloc(SolariumQueryFactory $factory)
    {
        $show_maps = $this->container->getParameter('feature.maps');
        if ( $show_maps ) {
            $request = $this->getRequest();
            $session = $request->getSession();
            $fields = $this->getGeolocFields();
            $rs = $factory->getResultset();

            foreach ( $fields as $field ) {
                $map_facets[$field] = $rs->getFacetSet()->getFacet($field);
            }

            $session->set(
                $this->mapFacetsName(),
                $map_facets
            );
        }
    }

    /**
     * Handle facets
     *
     * @param SolariumQueryFactory $factory       Query factory
     * @param array                $conf_facets   Configured facets
     * @param array                $searchResults Search results
     * @param Filters              $filters       Active filters
     * @param string               $facet_name    Facet name
     * @param array                $tpl_vars      Template variables
     *
     * @return void
     */
    protected function handleFacets(SolariumQueryFactory $factory, $conf_facets,
        $searchResults, $filters, $facet_name, &$tpl_vars
    ) {
        $request = $this->getRequest();
        $session = $request->getSession();

        $show_maps = $this->container->getParameter('feature.maps');
        $facets = array();
        $facetset = $searchResults->getFacetSet();

        $facet_names = array(
            'geoloc'                       => _('Map selection'),
            'date_cDateBegin_min'          => _('Start date'),
            'date_cDateBegin_max'          => _('End date'),
            'date_classe_min'              => _('Start class date'),
            'date_classe_max'              => _('End class date'),
            'date_date_enregistrement_min' => _('Start record date'),
            'date_date_enregistrement_max' => _('End record date'),
            'date_annee_naissance_min'     => _('Start birth date'),
            'date_annee_naissance_max'     => _('End birth date'),
            'headerId'                     => _('Document')
        );
        $facet_labels = array();
        $docs_titles = array();

        foreach ( $conf_facets as $facet ) {
            $solr_field = $facet->getSolrFieldName();
            $facet_names[$solr_field] = $facet->getLabel($request->getLocale());
            $field_facets = $facetset->getFacet($solr_field);

            $values = array();

            if ( $solr_field == 'headerId' ) {
                //retrieve documents titles...
                $ids = array();
                foreach ( $field_facets as $key=>$value ) {
                    $ids[] = $key . '_description';
                }

                if ( count($ids) > 0 ) {
                    $query = $this->getDoctrine()->getManager()->createQuery(
                        'SELECT h.headerId, h.headerTitle ' .
                        'FROM BachIndexationBundle:EADFileFormat e ' .
                        'JOIN e.eadheader h WHERE e.fragmentid IN (:ids)'
                    )->setParameter('ids', $ids);
                    $docs_titles = $query->getResult();
                }
            }

            if ( $field_facets !== null ) {
                foreach ( $field_facets as $item=>$count ) {
                    if ( !$filters->offsetExists($solr_field)
                        || !$filters->hasValue($solr_field, $item)
                    ) {
                        if ( $solr_field == 'headerId' && count($docs_titles) > 0 ) {
                            foreach ( $docs_titles as $title ) {
                                if ( $title['headerId'] === $item ) {
                                    $facet_labels[$solr_field][$item]
                                        = $title['headerTitle'];
                                    break;
                                }
                            }
                        }
                        $values[$item] = $count;
                    }
                }
            }

            if ( count($values) > 0
                || in_array($solr_field, $this->getFacetsDateFields())
            ) {
                if ( $solr_field !== 'dao' ) {
                    //facet order
                    $facet_order = $request->get('facet_order');
                    if ( $facet_name !== null ) {
                        $facet_order = 1;
                    }
                    if ( !$facet_order || $facet_order == 0 ) {
                        arsort($values);
                    } else {
                        if ( defined('SORT_FLAG_CASE') ) {
                            //TODO: find a better way!
                            if ( $this->getRequest()->getLocale() == 'fr_FR' ) {
                                setlocale(LC_COLLATE, 'fr_FR.utf8');
                            }
                            ksort($values, SORT_LOCALE_STRING | SORT_FLAG_CASE);
                        } else {
                            //fallback for PHP < 5.4
                            ksort($values, SORT_LOCALE_STRING);
                        }
                    }
                }

                $do = true;
                if ( $facet->getSolrFieldName() === 'dao' ) {
                    foreach ( $values as $v ) {
                        if ( $v == 0 ) {
                            $do = false;
                        }
                    }
                }

                if ( $do ) {
                    //get original URL if any
                    $tpl_vars['orig_href'] = $request->get('orig_href');
                    $tpl_vars['facet_order'] = $request->get('facet_order');

                    $facets[$facet->getSolrFieldName()] = array(
                        'label'         => $facet->getFrLabel(),
                        'content'       => $values,
                        'index_name'    => $facet->getSolrFieldName()
                    );
                }
            }
        }

        $browse_fields = $this->getDoctrine()
            ->getRepository('BachHomeBundle:BrowseFields')
            ->findBy(
                array('active' => true),
                array('position' => 'ASC')
            );
        foreach ( $browse_fields as $field ) {
            if ( !isset($facet_names[$field->getSolrFieldName()]) ) {
                $facet_names[$field->getSolrFieldName()]
                    = $field->getLabel($request->getLocale());
            }
        }

        if ( $show_maps ) {
            $geoloc = $this->getGeolocFields();
            foreach ( $geoloc as $field ) {
                $map_facets[$field] = $facetset->getFacet($field);
            }
        }

        if ( $filters->offsetExists('headerId') ) {
            if ( !isset($facet_labels['headerId']) ) {
                $facet_labels['headerId'] = array();
            }
            $filtered_docs = $filters->offsetGet('headerId');
            if ( count($docs_titles) === 0 ) {
                 //retrieve documents titles...
                $ids = array();
                foreach ( $filtered_docs as $filtered_doc ) {
                    $ids[] = $filtered_doc . '_description';
                }

                $query = $this->getDoctrine()->getManager()->createQuery(
                    'SELECT h.headerId, h.headerTitle ' .
                    'FROM BachIndexationBundle:EADFileFormat e ' .
                    'JOIN e.eadheader h WHERE e.fragmentid IN (:ids)'
                )->setParameter('ids', $ids);
                $docs_titles = $query->getResult();

            }
            foreach ( $filtered_docs as $filtered_doc ) {
                if ( !isset($facet_labels['headerId'][$filtered_doc]) ) {
                    foreach ( $docs_titles as $title ) {
                        if ( $title['headerId'] === $filtered_doc ) {
                            $facet_labels['headerId'][$filtered_doc]
                                = $title['headerTitle'];
                            break;
                        }
                    }
                }
            }
        }

        if ( count($facet_labels) > 0 ) {
            $tpl_vars['facet_labels'] = $facet_labels;
        }
        $tpl_vars['facet_names'] = $facet_names;

        $tpl_vars['facets'] = $facets;

        if ( $facet_name !== null ) {
            $tpl_vars['facet_name'] = $facet_name;
            $active = array_search($facet_name, array_keys($facets));
            if ( false !== $active ) {
                $tpl_vars['active_facet'] = $active;
            }
        }

        if ( $show_maps ) {
            $session->set(
                $this->mapFacetsName(),
                $map_facets
            );
        }
    }

    /**
     * Loads Geojson data
     *
     * @param string $form_name Search form name
     *
     * @return void
     */
    public function getGeoJsonAction($form_name = null)
    {
        if ( $form_name !== 'default' ) {
            $this->search_form = $form_name;
        }
        $request = $this->getRequest();
        $session = $request->getSession();

        $factory = $this->get($this->factoryName());
        $map_facets = $session->get($this->mapFacetsName());

        $geojson = $factory->getGeoJson(
            $map_facets,
            $this->getDoctrine()
                ->getRepository('BachIndexationBundle:Geoloc')
        );

        return new JsonResponse($geojson);
    }

    /**
     * Get map facets session name
     *
     * @return string
     */
    abstract protected function mapFacetsName();

    /**
     * Get date fields
     *
     * @return array
     */
    abstract protected function getFacetsDateFields();

    /**
     * Get Solarium EntryPoint
     *
     * @return string
     */
    abstract protected function entryPoint();

    /**
     * Get factory name
     *
     * @return string
     */
    abstract protected function factoryName();

    /**
     * Get available ordering options
     *
     * @return array
     */
    abstract protected function getOrders();

    /**
     * Get available views
     *
     * @return array
     */
    abstract protected function getViews();

    /**
     * Get session name for view parameters
     *
     * @return string
     */
    abstract protected function getParamSessionName();

    /**
     * Get cookie name for view parameters
     *
     * @return string
     */
    protected function getCookieName()
    {
        $host = str_replace('.', '_', $this->getRequest()->getHost());
        return $host . '_bach_cookie';
    }

    /**
     * POST search destination for main form.
     *
     * Will take care of search terms, and reroute with proper URI
     *
     * @return void
     */
    abstract public function doSearchAction();

    /**
     * Suggests
     *
     * @return void
     */
    public function doSuggestAction()
    {
        $query = $this->get($this->entryPoint())->createSuggester();
        $query->setQuery(strtolower($this->getRequest()->get('q')));
        $query->setDictionary('suggest');
        $query->setOnlyMorePopular(true);
        $query->setCount(10);
        $terms = $this->get($this->entryPoint())->suggester($query)->getResults();

        $suggestions = array();
        foreach ( $terms as $term ) {
            $suggestions = array_merge(
                $suggestions,
                $term->getSuggestions()
            );
        }

        return new JsonResponse($suggestions);
    }

    /**
     * List all entries for a specific facet
     *
     * @param string $form_name   Search form name
     * @param string $query_terms Term(s) we search for
     * @param string $name        Facet name
     *
     * @return void
     */
    public function fullFacetAction($form_name, $query_terms, $name)
    {
        if ( $form_name !== 'default' ) {
            $this->search_form = $form_name;
        }

        $request = $this->getRequest();
        $session = $request->getSession();

        $query_terms = urldecode($query_terms);

        $view_params = $session->get($this->getParamSessionName());
        if ( !$view_params ) {
            $view_params = $this->get($this->getViewParamsServicename());
        }
        $view_params->bind($request, $this->getCookieName());


        $factory = $this->get($this->factoryName());

        $geoloc = $this->getGeolocFields();

        //FIXME: try to avoid those 2 calls
        $factory->setGeolocFields($this->getGeolocFields());
        $factory->setDateField($this->date_field);

        $filters = $session->get($this->getFiltersName());
        if ( !$filters instanceof Filters ) {
            $filters = new Filters();
        }

        $conf_facets = $this->getUniqueFacet($name);

        $container = new SolariumQueryContainer();
        $container->setOrder($view_params->getOrder());
        $container->setField($this->getContainerFieldName(), $query_terms);

        //Add filters to container
        $container->setFilters($filters);

        $container->setNoResults();
        $factory->prepareQuery($container);

        $searchResults = $factory->performQuery(
            $container,
            $conf_facets
        );

        $facets = array();
        $facetset = $searchResults->getFacetSet();
        $current_facet = $facetset->getFacet($name);
        $facet = $conf_facets[0];
        $results = $current_facet->getValues();

        if ( $name == 'headerId' ) {
            //retrieve documents titles...
            $query = $this->getDoctrine()->getManager()->createQuery(
                'SELECT h.headerId, h.headerTitle ' .
                'FROM BachIndexationBundle:EADHeader h INDEX BY h.headerId ' .
                'WHERE h.headerId IN (:ids)'
            )->setParameter('ids', array_keys($results));
            $titles = $query->getArrayResult();
        }

        $values = array();
        foreach ( $results as $key=>$count ) {
            $value = array(
                'key'   => $key,
                'count' => $count
            );

            if ( isset($titles[$key]) ) {
                $values[$titles[$key]['headerTitle']] = $value;
            } else {
                $values[$key] = $value;
            }
        }

        //facet order
        $facet_order = $request->get('facet_order');
        if ( $facet_order === null ) {
            $facet_order = 1;
        }
        if ( $facet_order == 1 ) {
            if ( defined('SORT_FLAG_CASE') ) {
                //TODO: find a better way!
                if ( $this->getRequest()->getLocale() == 'fr_FR' ) {
                    setlocale(LC_COLLATE, 'fr_FR.utf8');
                }
                ksort($values, SORT_FLAG_CASE | SORT_LOCALE_STRING);
            } else {
                //fallback for PHP < 5.4
                ksort($values, SORT_LOCALE_STRING);
            }
        } else {
            uasort($values, array($this, '_orderFacetsByOccurences'));
        }

        $facets[$facet->getSolrFieldName()] = array(
            'label'         => $facet->getFrLabel(),
            'content'       => $values,
            'index_name'    => $facet->getSolrFieldName()
        );

        $tpl_vars = array(
            'q'             => $query_terms,
            'facets'        => $facets,
            'orig_href'     => $request->get('orig_href'),
            'facet_order'   => $facet_order,
            'search_uri'    => $this->getSearchUri()
        );

        if ( $this->search_form !== null ) {
            $tpl_vars['search_form'] = $this->search_form;
        } else {
            $tpl_vars['search_form'] = 'default';
        }

        return $this->render(
            'BachHomeBundle:Default:facet.html.twig',
            $tpl_vars
        );
    }

    /**
     * Order facets by number of ocurences
     *
     * @param array $a First entry
     * @param array $b Second entry
     *
     * @return int
     */
    private function _orderFacetsByOccurences($a, $b)
    {
        if ( $a['count'] == $b['count'] ) {
            return 0;
        }
        return ($a['count'] > $b['count']) ? -1 : 1;
    }

    /**
     * Get unique conf facet
     *
     * @param string $name Facet name
     *
     * @return array
     */
    abstract protected function getUniqueFacet($name);

    /**
     * Get container field name
     *
     * @return string
     */
    abstract protected function getContainerFieldName();

    /**
     * Get filters session name
     *
     * @return string
     */
    abstract protected function getFiltersName();

    /**
     * Get search URI
     *
     * @return string
     */
    abstract protected function getSearchUri();

    /**
     * Get view params service name
     *
     * @return string
     */
    abstract protected function getViewParamsServicename();

    /**
     * Get configured geolocalization fields
     *
     * @return array
     */
    protected function getGeolocFields()
    {
        $show_maps = $this->container->getParameter('feature.maps');
        if ( $show_maps && !isset($this->_geoloc) ) {
            $class = $this->getGeolocClass();
            $gf = new $class;
            $gf = $gf->loadDefaults(
                $this->getDoctrine()->getManager()
            );
            $this->_geoloc = $gf->getSolrFieldsNames();
        }
        return $this->_geoloc;
    }

    /**
     * Get golocalization fields class name
     *
     * @return string
     */
    abstract protected function getGeolocClass();

    /**
     * Handle yearly results
     *
     * @param SolariumQueryFactory $factory  Factory instance
     * @param array                $tpl_vars Template variables
     *
     * @return void
     */
    protected function handleYearlyResults($factory, &$tpl_vars)
    {
        $by_year = $factory->getResultsByYear();
        if ( count($by_year) > 0 ) {
            $tpl_vars['by_year'] = $by_year;
            $date_min = new \DateTime($by_year[0][0] . '-01-01');
            $date_max = new \DateTime($by_year[count($by_year)-1][0] . '-01-01');
            $tpl_vars['by_year_min'] = (int)$date_min->format('Y');
            $tpl_vars['by_year_max'] = (int)$date_max->format('Y');
        }
    }

    /**
     * Handle view parameters
     *
     * @return ViewParams
     */
    protected function handleViewParams()
    {
        $request = $this->getRequest();
        $session = $request->getSession();

        /* Manage view parameters */
        $view_params = $session->get($this->getParamSessionName());
        if ( !$view_params ) {
            $view_params = $this->get($this->getViewParamsServicename());
        }
        //take care of user view params
        $view_params->bindCookie($this->getCookieName());

        //set current view parameters according to request
        $view_params->bind($request, $this->getCookieName());

        //store new view parameters
        $session->set($this->getParamSessionName(), $view_params);

        return $view_params;
    }

    /**
     * Redirect trailing slash
     *
     * @param Request $request Request
     *
     * @return void
     */
    public function removeTrailingSlashAction(Request $request)
    {
        $pathInfo = $request->getPathInfo();
        $requestUri = $request->getRequestUri();

        $url = str_replace($pathInfo, rtrim($pathInfo, ' /'), $requestUri);

        return $this->redirect($url, 301);
    }

    /**
     * Retrieve fragment informations from image
     *
     * @param string $path Image path
     * @param string $img  Image name
     * @param string $ext  Image extension
     *
     * @return void
     */
    abstract public function infosImageAction($path, $img, $ext);

    /**
     *  Basket list action
     *
     * @return void
     */
    public function basketListAction()
    {
        $typeDocuments = array();
        $typeDocuments['ead'] = $this->container->getParameter(
            'feature.archives'
        );
        $typeDocuments['matricules'] = $this->container->getParameter(
            'feature.matricules'
        );

        $resultAction = $this->getRequest()->getSession()->get('resultAction');
        $this->getRequest()->getSession()->remove('resultAction');
        $session = $this->getRequest()->getSession();
        if ($typeDocuments['ead']
            && isset($session->get('documents')['ead'])
            && !empty($session->get('documents')['ead'])
        ) {
            $docs = $this->getDoctrine()->getManager()->createQuery(
                'SELECT e.cUnittitle, e.fragmentid, e.cUnitid, d.date ' .
                'FROM BachIndexationBundle:EADFileFormat e ' .
                'JOIN e.dates d WHERE e.fragmentid IN (:ids)'
            )->setParameter('ids', $session->get('documents')['ead'])->getResult();
        } else {
            $docs = array();
        }

        if ($typeDocuments['matricules'] 
            && isset($session->get('documents')['matricules'])
            && !empty($session->get('documents')['matricules'])
        ) {
            $docsMat = $this->getDoctrine()->getManager()->createQuery(
                'SELECT m.id, m.cote, m.nom, m.prenoms ' .
                'FROM BachIndexationBundle:MatriculesFileFormat m ' .
                'WHERE m.id IN (:ids)'
            )->setParameter(
                'ids',
                $session->get('documents')['matricules']
            )->getResult();
        } else {
            $docsMat = array();
        }

        $arraytpl = array(
            'documents'     => $docs,
            'documentsMat'  => $docsMat,
            'typeDocuments' => $typeDocuments,
            'resultAction'  => $resultAction
        );
        if (isset($_COOKIE[$this->getCookieName()]) ) {
            $arraytpl['cookie_param'] = true;
        }

        return $this->render(
            'BachHomeBundle:Default:listbracket.html.twig',
            $arraytpl
        );
    }

    /**
     *  Basket deleteAll action
     *
     * @return void
     */
    public function basketDeleteAllAction()
    {
        $session = $this->getRequest()->getSession();
        $session->remove('documents');
        if ($session->has('documents')) {
            $deleteFlag = false;
        } else {
            $deleteFlag = true;
        }

        $session->set('resultAction', _('Documents have sucessfully been removed.'));
        $resultAction = $this->getRequest()->getSession()->get('resultAction');
        return $this->redirect(
            $this->generateUrl(
                'bach_display_list_basket'
            )
        );
    }

    /**
     *  Basket export action
     *
     *  @return JsonResponse
     */
    public function basketExportAction()
    {
        $session = $this->getRequest()->getSession();
        $export = $session->get('documents');

        header('Content-disposition: attachment; filename=basket.json');
        header('Content-type: application/json');

        return new JsonResponse($export);
    }

    /**
     *  Basket import action
     *
     *  @return void
     */
    public function basketImportAction()
    {
        $files = $this->getRequest()->files;
        $flagExtension = null;
        foreach ($files as $file) {
            if ($file != null) {
                $basket = file_get_contents($file->getPathName());
                $flagExtension = (
                    $file->getClientOriginalExtension() == 'json'
                ) ? true : false;
            } else {
                $basket = null;
            }
        }
        if ($flagExtension == true) {
            $basket = get_object_vars(json_decode($basket));
            $this->getRequest()->getSession()->set('documents', $basket);

            $this->getRequest()->getSession()->set(
                'resultAction',
                _('Your basket is now uploaded')
            );
        } else if ($basket == null) {
            $this->getRequest()->getSession()->set(
                'resultAction',
                _('Yout forget to put a file')
            );
        }
        return $this->redirect(
            $this->generateUrl(
                'bach_display_list_basket'
            )
        );
    }
    
    /**
     *  Search historic list action
     *
     * @return void
     */
    public function searchhistoListAction()
    {
        $typeDocuments = array();
        $typeDocuments['ead'] = $this->container->getParameter(
            'feature.archives'
        );
        $typeDocuments['matricules'] = $this->container->getParameter(
            'feature.matricules'
        );

        $resultAction = $this->getRequest()->getSession()->get('resultAction');
        $this->getRequest()->getSession()->remove('resultAction');

        $session = $this->getRequest()->getSession();
        if ($typeDocuments['ead']
            && isset($session->get('histosave')['ead'])
            && !empty($session->get('histosave')['ead'])
        ) {
            $searchEad = $session->get('histosave')['ead'];
        } else {
            $searchEad = array();
        }

        if ($typeDocuments['matricules']
            && isset($session->get('histosave')['matricules'])
            && !empty($session->get('histosave')['matricules'])
        ) {
            $searchMat = $session->get('histosave')['matricules'];
        } else {
            $searchMat = array();
        }

        $all_facets_table = $this->getDoctrine()
            ->getRepository('BachHomeBundle:Facets')
            ->findAll();

        $all_facets = array(
            'geoloc'                       => _('Map selection'),
            'date_cDateBegin_min'          => _('Start date'),
            'date_cDateBegin_max'          => _('End date'),
            'date_classe_min'              => _('Start class date'),
            'date_classe_max'              => _('End class date'),
            'date_date_enregistrement_min' => _('Start record date'),
            'date_date_enregistrement_max' => _('End record date'),
            'date_annee_naissance_min'     => _('Start birth date'),
            'date_annee_naissance_max'     => _('End birth date'),
            'headerId'                     => _('Document')
        );
        foreach ( $all_facets_table as $field ) {
            $all_facets[$field->getSolrFieldName()]
                = $field->getLabel($this->getRequest()->getLocale());
        }

        $browse_fields = $this->getDoctrine()
            ->getRepository('BachHomeBundle:BrowseFields')
            ->findAll();
        foreach ( $browse_fields as $field ) {
            $all_facets[$field->getSolrFieldName()]
                = $field->getLabel($this->getRequest()->getLocale());
        }

        $arraytpl = array(
            'searchEad'         => $searchEad,
            'searchMatricules'  => $searchMat,
            'typeDocuments'     => $typeDocuments,
            'resultAction'      => $resultAction,
            'facet_names'       => $all_facets,
            'ead_dates'         => $session->get('histosaveDate')
        );
        if (isset($_COOKIE[$this->getCookieName()])) {
            $arraytpl['cookie_param'] = true;
        }

        return $this->render(
            'BachHomeBundle:Default:listsearch.html.twig',
            $arraytpl
        );
    }

    /**
     *  Search historic deleteAll action
     *
     * @return void
     */
    public function searchHistoDeleteAllAction()
    {
        $session = $this->getRequest()->getSession();
        $session->remove('histosave');
        if ($session->has('histosave')) {
            $deleteFlag = false;
        } else {
            $deleteFlag = true;
        }

        $session->set(
            'resultAction',
            _('All searches have sucessfully been removed.')
        );
        $resultAction = $this->getRequest()->getSession()->get('resultAction');
        return $this->redirect(
            $this->generateUrl(
                'bach_display_searchhisto'
            )
        );
    }
}
