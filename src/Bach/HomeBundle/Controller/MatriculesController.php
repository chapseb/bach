<?php
/**
 * Bach matricules controller
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

use Symfony\Component\HttpFoundation\RedirectResponse;
use Bach\HomeBundle\Form\Type\MatriculesType;
use Bach\HomeBundle\Entity\SolariumQueryContainer;
use Bach\HomeBundle\Entity\Filters;
use Bach\HomeBundle\Entity\Facets;
use Bach\HomeBundle\Form\Type\SearchQueryFormType;
use Bach\HomeBundle\Entity\SearchQuery;
use Bach\HomeBundle\Entity\MatriculesViewParams;
use Bach\HomeBundle\Entity\Comment;
use Bach\HomeBundle\Entity\Pdf;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Bach matricules controller
 *
 * PHP version 5
 *
 * @category Search
 * @package  Bach
 * @author   Johan Cwiklinski <johan.cwiklinski@anaphore.eu>
 * @license  BSD 3-Clause http://opensource.org/licenses/BSD-3-Clause
 * @link     http://anaphore.eu
 */
class MatriculesController extends SearchController
{
    protected $dates_fields_mat = array(
        'date_enregistrement',
        'classe',
        'annee_naissance'
    );

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
    public function searchAction($query_terms = null, $page = 1,
        $facet_name = null, $form_name = null
    ) {
        $request = $this->getRequest();
        $session = $request->getSession();

        if ($query_terms !== null) {
            $query_terms = urldecode($query_terms);
            $session->set('query_orig', $query_terms);
        }

        $this->search_form = $form_name;

        /* Manage view parameters */
        $view_params = $this->handleViewParams();

        $tpl_vars = $this->searchTemplateVariables($view_params, $page);
        if ($tpl_vars['view'] == 'txtlist') {
            $tpl_vars['view'] = 'list';
        }
        if (isset($_COOKIE[$this->getCookieName()]) ) {
            $tpl_vars['cookie_param'] = true;
        }

        $filters = $session->get($this->getFiltersName());
        if (!$filters instanceof Filters || $request->get('clear_filters')) {
            $filters = new Filters();
            $session->set($this->getFiltersName(), null);
        }

        $filters->bind($request);
        $session->set($this->getFiltersName(), $filters);

        if (($request->get('filter_field') || $filters->count() > 0)
            && is_null($query_terms)
        ) {
            $query_terms = '*:*';
        }

        $form = $this->createForm(
            new SearchQueryFormType(
                $query_terms,
                !is_null($query_terms)
            ),
            null
        );
        $tpl_vars['search_path'] = 'bach_matricules_do_search';

        $form->handleRequest($request);

        $resultCount = null;
        $searchResults = null;

        if ($query_terms !== null) {
            $conf_facets = $this->getDoctrine()
                ->getRepository('BachHomeBundle:Facets')
                ->findBy(
                    array(
                        'active'    => true,
                        'form'      => 'matricules'
                    ),
                    array('position' => 'ASC')
                );
        } else {
            $conf_facets = $this->getDoctrine()
                ->getRepository('BachHomeBundle:Facets')
                ->findBy(
                    array(
                        'active' => true,
                        'form'   => 'matricules',
                        'on_home'=> true
                    ),
                    array('position' => 'ASC')
                );
        }
        if ($this->container->hasParameter('matricules_histogram')) {
            $current_date = $this->container->getParameter('matricules_histogram');
        } else {
            $current_date = 'date_enregistrement';
        }

        $factory = $this->get($this->factoryName());

        //FIXME: try to avoid those 2 calls
        $factory->setGeolocFields($this->getGeolocFields());
        $factory->setDateField($current_date);
        $dates_fields = array();
        foreach ( $conf_facets as $conf_facet ) {
            if (in_array($conf_facet->getSolrFieldName(), $this->dates_fields_mat)
                && $conf_facet->getSolrFieldName() != $current_date
            ) {
                array_push($dates_fields, $conf_facet->getSolrFieldName());
            }
        }
        $factory->setDatesFields($dates_fields);

        if ($filters->count() > 0) {
            $tpl_vars['filters'] = $filters;
        }

        $container = new SolariumQueryContainer();

        if ($query_terms !== null) {
            $container->setOrder($view_params->getOrder());
            $container->setField($this->getContainerFieldName(), $query_terms);
            $container->setField(
                "pager",
                array(
                    "start"     => ($page - 1) * $view_params->getResultsbyPage(),
                    "offset"    => $view_params->getResultsbyPage()
                )
            );

            $container->setFilters($filters);
        } else {
            $container->setNoResults();
        }

        $factory->prepareQuery($container);

        $searchResults = $factory->performQuery(
            $container,
            $conf_facets
        );

        $this->handleFacets(
            $factory,
            $conf_facets,
            $searchResults,
            $filters,
            $facet_name,
            $tpl_vars
        );

        if ($query_terms !== null) {
            $hlSearchResults = $factory->getHighlighting();
            $scSearchResults = $factory->getSpellcheck();
            $resultCount = $searchResults->getNumFound();

            $tpl_vars['searchResults'] = $searchResults;
            $tpl_vars['hlSearchResults'] = $hlSearchResults;
            $tpl_vars['scSearchResults'] = $scSearchResults;
            $tpl_vars['totalPages'] = ceil(
                $resultCount/$view_params->getResultsbyPage()
            );


            if ($this->container->getParameter('display.disable_suggestions') != true) {
                $suggestions = $factory->getSuggestions($query_terms);
            }

            if (isset($suggestions) && $suggestions->count() > 0) {
                $tpl_vars['suggestions'] = $suggestions;
            }

        }

        $tpl_vars['stats'] = $factory->getStats();
        $this->handleYearlyResults($factory, $tpl_vars);
        $this->handleGeoloc($factory);

        $tpl_vars['form'] = $form->createView();

        $tpl_vars['resultStart'] = ($page - 1)
            * $view_params->getResultsbyPage() + 1;
        $resultEnd = ($page - 1) * $view_params->getResultsbyPage()
            + $view_params->getResultsbyPage();
        if ($resultEnd > $resultCount) {
            $resultEnd = $resultCount;
        }
        $tpl_vars['resultEnd'] = $resultEnd;
        $tpl_vars['current_date'] = $current_date;

        if ($this->container->hasParameter('matricules_listparameters')) {
            $tpl_vars['matricules_listparameters']
                = $this->container->getParameter('matricules_listparameters');
        }
        if ($this->container->hasParameter('matricules_searchparameters')) {
            $tpl_vars['matricules_searchparameters']
                = $this->container->getParameter('matricules_searchparameters');
        }
        $tpl_vars['all_facets'] = $tpl_vars['facet_names'];
        $tpl_vars['disable_select_daterange']
            = $this->container->getParameter('display.disable_select_daterange');

        $this->searchhistoMatAddAction($searchResults->getNumFound());

        $tpl_vars['readingroomIp'] = $this->container->getParameter('readingroom');
        return $this->render(
            'BachHomeBundle:Matricules:search_form.html.twig',
            array_merge(
                $tpl_vars,
                array(
                    'resultCount'   => $resultCount,
                    'q'             => urlencode($query_terms),
                )
            )
        );
    }

    /**
     * Document display
     *
     * @param int     $docid Document unique identifier
     * @param int     $page  Page
     * @param boolean $ajax  Called from ajax
     * @param boolean $print Flag to add print in template
     *
     * @return void
     */
    public function displayDocumentAction(
        $docid, $page = 1,
        $ajax = false, $print = false
    ) {
        $client = $this->get($this->entryPoint());
        $query = $client->createSelect();
        $query->setQuery('id:"' . $docid . '"');
        $query->setStart(0)->setRows(1);

        $rs = $client->select($query);

        if ($rs->getNumFound() !== 1) {
            throw new \RuntimeException(
                str_replace(
                    '%count%',
                    $rs->getNumFound(),
                    _('%count% results found, 1 expected.')
                )
            );
        }

        $docs  = $rs->getDocuments();
        $doc = $docs[0];

        $tpl = '';

        $tplParams = $this->commonTemplateVariables();
        $tplParams = array_merge(
            $tplParams,
            array(
                'docid'         => $docid,
                'document'      => $doc
            )
        );

        //retrieve comments
        $show_comments = $this->container->getParameter('feature.comments');
        if ($show_comments) {
            $query = $this->getDoctrine()->getManager()
                ->createQuery(
                    'SELECT c FROM BachHomeBundle:MatriculesComment c
                    WHERE c.state = :state
                    AND c.docid = :docid
                    ORDER BY c.creation_date DESC'
                )->setParameters(
                    array(
                        'state'     => Comment::PUBLISHED,
                        'docid'     => $docid
                    )
                );
            $comments = $query->getResult();
            if (count($comments) > 0) {
                $tplParams['comments'] = $comments;
            }
        }

        // get additional Informations about this record
        $zdb = $this->container->get('zend_db');
        $select = $zdb->select('matricules_file_format')
            ->where(array('id' => $docid));
        $stmt = $zdb->sql->prepareStatementForSqlObject(
            $select
        );
        $result = $stmt->execute();
        if ($result->current()['additional_informations'] != null) {
            $tplParams['additionalInformations']
                = nl2br($result->current()['additional_informations']);
        }

        if ($ajax === 'ajax') {
            $tpl = 'BachHomeBundle:Matricules:content_display.html.twig';
            $tplParams['ajax'] = true;
        } else {
            $tpl = 'BachHomeBundle:Matricules:display.html.twig';
            $tplParams['ajax'] = false;
        }

        /* not display warning about cookies */
        if (isset($_COOKIE[$this->getCookieName()])) {
            $tplParams['cookie_param'] = true;
        }

        if ($this->container->hasParameter('matricules_listparameters')) {
            $tplParams['matricules_listparameters']
                = $this->container->getParameter('matricules_listparameters');
        }
        if ($this->container->hasParameter('matricules_searchparameters')) {
            $tplParams['matricules_searchparameters']
                = $this->container->getParameter('matricules_searchparameters');
        }
        if ($print == true) {
            $tplParams['print'] = $print;
        }

        $current_date = new \DateTime();
        $tplParams['communicability'] = false;
        if (strtotime($doc->communicability_general) <= $current_date->getTimestamp()) {
            $tplParams['communicability'] = true;
        }
        if ($tplParams['communicability'] == false) {
            $incomeIp = $this->container->get(
                'request'
            )->getClientIp();
            $testIp = $this->container->getParameter('readingroom');
            if ($testIp == $incomeIp
                && strtotime($doc->communicability_sallelecture) <= $current_date->getTimestamp()
            ) {
                $tplParams['communicability'] = true;
            }
        }
        return $this->render(
            $tpl,
            $tplParams
        );
    }
    /**
     * Get Solarium EntryPoint
     *
     * @return string
     */
    protected function entryPoint()
    {
        return 'solarium.client.matricules';
    }

    /**
     * Get factory name
     *
     * @return string
     */
    protected function factoryName()
    {
        return 'bach.matricules.solarium_query_factory';
    }

    /**
     * Get map facets session name
     *
     * @return string
     */
    protected function mapFacetsName()
    {
        return 'matricules_map_facets';
    }

    /**
     * Get date fields
     *
     * @return array
     */
    protected function getFacetsDateFields()
    {
        return array(
            'date_enregistrement',
            'annee_naissance',
            'classe'
        );
    }

    /**
     * Get golocalization fields class name
     *
     * @return string
     */
    protected function getGeolocClass()
    {
        return 'Bach\HomeBundle\Entity\GeolocMatriculesFields';
    }

    /**
     * POST search destination for main form.
     *
     * Will take care of search terms, and reroute with proper URI
     *
     * @return void
     */
    public function doSearchAction()
    {
        $query = new SearchQuery();
        $form = $this->createForm(new SearchQueryFormType(), $query);
        $redirectUrl = $this->get('router')->generate('bach_matricules');

        if ($this->getRequest()->isMethod('POST')) {
            $form->bind($this->getRequest());
            if ($form->isValid()) {
                $q = $query->getQuery();
                $redirectUrl = $this->get('router')->generate(
                    'bach_matricules',
                    array('query_terms' => $q)
                );

                $session = $this->getRequest()->getSession();
                if ($form->getData()->keep_filters != 1) {
                    $session->set($this->getFiltersName(), null);
                }
                $view_params = $session->get($this->getParamSessionName());
                $view_params->setOrder(
                    (int)$this->getRequest()->get('results_order')
                );
                $session->set($this->getParamSessionName(), $view_params);

            }
        }
        return new RedirectResponse($redirectUrl);
    }

    /**
     * Get available ordering options
     *
     * @return array
     */
    protected function getOrders()
    {
        $orders = array();
        $listSearchParamaters
            = $this->container->getParameter('matricules_searchparameters');
        foreach ($listSearchParamaters as $searchParameter) {
            switch ($searchParameter) {
            case 'cote':
                $orders[MatriculesViewParams::ORDER_COTE]
                    = _('Classification');
                break;
            case 'date_enregistrement':
                $orders[MatriculesViewParams::ORDER_RECORDYEAR]
                    = _('Year of recording');
                break;
            case 'lieu_enregistrement':
                $orders[MatriculesViewParams::ORDER_RECORDPLACE]
                    = _('Place of recording');
                break;
            case 'classe':
                $orders[MatriculesViewParams::ORDER_CLASS]
                    = _('Class');
                break;
            case 'nom':
                $orders[MatriculesViewParams::ORDER_NAME]
                    = _('Name');
                break;
            case 'prenoms':
                $orders[MatriculesViewParams::ORDER_SURNAME]
                    = _('Surname');
                break;
            case 'matricule':
                $orders[MatriculesViewParams::ORDER_MATRICULE]
                    = _('Matricule');
                break;
            case 'annee_naissance':
                $orders[MatriculesViewParams::ORDER_BIRTHYEAR]
                    = _('Year of birth');
                break;
            case 'lieu_naissance':
                $orders[MatriculesViewParams::ORDER_BIRTHPLACE]
                    = _('Place of birth');
                break;
            case 'lieu_residence':
                $orders[MatriculesViewParams::ORDER_RESIDENCEPLACE]
                    = _('Place of residence');
                break;
            }
        }
        return $orders;
    }

    /**
     * Get available views
     *
     * @return array
     */
    protected function getViews()
    {
        $views = array(
            'list'      => array(
                'text'  => _('List'),
                'title' => _('View search results as a list')
            ),
            'thumbs'    => array(
                'text'  => _('Thumbnails'),
                'title' => _('View search results as thumbnails')
            )
        );
        return $views;
    }

    /**
     * Get unique conf facet
     *
     * @param string $name Facet name
     *
     * @return array
     */
    protected function getUniqueFacet($name)
    {
        $form_name = 'matricules';
        if ($this->search_form !== null) {
            $form_name = $this->search_form;
        }

        return $this->getDoctrine()
            ->getRepository('BachHomeBundle:Facets')
            ->findBy(
                array(
                    'active'            => true,
                    'solr_field_name'   => $name,
                    'form'              => $form_name
                )
            );
    }

    /**
     * Get container field name
     *
     * @return string
     */
    protected function getContainerFieldName()
    {
        return 'matricules';
    }

    /**
     * Get filters session name
     *
     * @return string
     */
    protected function getFiltersName()
    {
        return 'matricules_filters';
    }

    /**
     * Get search URI
     *
     * @return string
     */
    protected function getSearchUri()
    {
        return 'bach_matricules';
    }

    /**
     * Get view params service name
     *
     * @return string
     */
    protected function getViewParamsServicename()
    {
        return ('bach.home.matricules_view_params');
    }

    /**
     * Get session name for view parameters
     *
     * @return string
     */
    protected function getParamSessionName()
    {
        return 'matricules_view_params';
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
    public function infosImageAction($path, $img, $ext)
    {
        $qry_string = null;
        if ($img !== null && $ext !== null) {
            $qry_string = $img . '.' . $ext;
        }
        if ($path !== null) {
            $qry_string = $path . '/' . $qry_string;
        }
        $docs = [];

        $client = $this->get($this->entryPoint());
        $query = $client->createSelect();
        $query->setQuery(
            'start_dao:' . $qry_string . ' OR end_dao:' . $qry_string . ' OR ' .
            ' (+start_dao:[* TO ' . $qry_string . '] +end_dao:[' .
            $qry_string . ' TO *])'
        );

        $query->setFields(
            'id, nom, txt_prenoms, classe, cote, date_enregistrement,
            lieu_enregistrement, prenoms, matricule, annee_naissance, lieu_naissance,
            lieu_residence, communicability_general, communicability_sallelecture'
        );
        $query->setStart(0)->setRows(1);

        $rs = $client->select($query);
        $docs = $rs->getDocuments();
        $response = null;

        if (count($docs) > 0) {
            $doc = $docs[0];
            //link to document
            $doc_url = $this->get('router')->generate(
                'bach_display_matricules',
                array(
                    'docid' => $doc['id']
                )
            );

            if (isset($doc['classe'])) {
                $annee = new \DateTime($doc['classe']);
            } else if (isset($doc['date_enregistrement'])) {
                $annee = new \DateTime($doc['date_enregistrement']);
            } else {
                $annee = new \DateTime($doc['annee_naissance']);
            }
            $response['mat']['link_mat'] = '<a href="' . $doc_url . '">' .
                $doc['nom'] . ' ' . $doc['txt_prenoms'] .
                ' (' . $annee->format('Y') . ')' . '</a>';
            $response['mat']['record'] = $doc->getFields();
        }

        $jsonResponse = new JsonResponse();
        $jsonResponse->setData($response);
        return $jsonResponse;
    }

    /**
     * Print a pdf with a matricule document
     *
     * @param string $docid id of document
     *
     * @return void
     */
    public function printPdfMatdocAction($docid)
    {
        $params = $this->container->getParameter('print');
        $tpl_vars['docid'] = $docid;
        $content = '<style>' . file_get_contents('css/bach_print.css'). '</style>';
        $content .= $this->displayDocumentAction(
            $docid,
            1,
            'ajax',
            true,
            true
        )->getContent();
        $pdf = new Pdf($params);
        $pdf->setFont('helvetica', '', 12);
        $pdf->addPage();
        $pdf->setTopMargin(20);
        $pdf->writeHTML($content);
        $pdf->download();
    }
    /**
     * Print a pdf with a list of result
     *
     * @param string $query_terms Term(s) we search for
     * @param int    $page        Page
     * @param string $facet_name  Display more terms in suggests
     * @param string $form_name   Search form name
     *
     * @return void
     */
    public function printPdfMatResultsPageAction(
        $query_terms = null, $page = 1,
        $facet_name = null, $form_name = null
    ) {
        $params = $this->container->getParameter('print');
        $content = '<style>' . file_get_contents('css/bach_print.css'). '</style>';
        $content .= $this->printSearch(
            $query_terms,
            $page,
            $facet_name,
            $form_name
        )->getContent();
        $pdf = new Pdf($params);
        $pdf->setFont('helvetica', '', 12);

        $pdf->addPage();
        $pdf->setTopMargin(20);
        $pdf->writeHTML($content);
        $pdf->download();
    }

    /**
     * Print results
     *
     * @param string $query_terms Term(s) we search for
     * @param int    $page        Page
     * @param string $facet_name  Display more terms in suggests
     * @param string $form_name   Search form name
     *
     * @return void
     */
    public function printSearch($query_terms = null, $page = 1,
        $facet_name = null, $form_name = null
    ) {
        $request = $this->getRequest();
        $session = $request->getSession();

        if ($query_terms !== null) {
            $query_terms = urldecode($query_terms);
        }

        $this->search_form = $form_name;

        /* Manage view parameters */
        $view_params = $this->handleViewParams();

        $tpl_vars = $this->searchTemplateVariables($view_params, $page);
        if ($tpl_vars['view'] == 'txtlist') {
            $tpl_vars['view'] = 'list';
        }
        if (isset($_COOKIE[$this->getCookieName()]) ) {
            $tpl_vars['cookie_param'] = true;
        }

        $filters = $session->get($this->getFiltersName());
        if (!$filters instanceof Filters || $request->get('clear_filters')) {
            $filters = new Filters();
            $session->set($this->getFiltersName(), null);
        }

        $filters->bind($request);
        $session->set($this->getFiltersName(), $filters);

        if (($request->get('filter_field') || $filters->count() > 0)
            && is_null($query_terms)
        ) {
            $query_terms = '*:*';
        }

        $form = $this->createForm(
            new SearchQueryFormType(
                $query_terms,
                !is_null($query_terms)
            ),
            null
        );
        $tpl_vars['search_path'] = 'bach_matricules_do_search';

        $form->handleRequest($request);

        $resultCount = null;
        $searchResults = null;

        if ($query_terms !== null) {
            $conf_facets = $this->getDoctrine()
                ->getRepository('BachHomeBundle:Facets')
                ->findBy(
                    array(
                        'active'    => true,
                        'form'      => 'matricules'
                    ),
                    array('position' => 'ASC')
                );
        } else {
            $conf_facets = $this->getDoctrine()
                ->getRepository('BachHomeBundle:Facets')
                ->findBy(
                    array(
                        'active' => true,
                        'form'   => 'matricules',
                        'on_home'=> true
                    ),
                    array('position' => 'ASC')
                );
        }
        if ($this->container->hasParameter('matricules_histogram')) {
            $current_date = $this->container->getParameter('matricules_histogram');
        } else {
            $current_date = 'date_enregistrement';
        }

        $factory = $this->get($this->factoryName());

        //FIXME: try to avoid those 2 calls
        $factory->setGeolocFields($this->getGeolocFields());
        $factory->setDateField($current_date);
        $dates_fields = array();
        foreach ( $conf_facets as $conf_facet ) {
            if (in_array($conf_facet->getSolrFieldName(), $this->dates_fields_mat)
                && $conf_facet->getSolrFieldName() != $current_date
            ) {
                array_push($dates_fields, $conf_facet->getSolrFieldName());
            }
        }
        $factory->setDatesFields($dates_fields);

        if ($filters->count() > 0) {
            $tpl_vars['filters'] = $filters;
        }

        $container = new SolariumQueryContainer();

        if ($query_terms !== null) {
            $container->setOrder($view_params->getOrder());
            $container->setField($this->getContainerFieldName(), $query_terms);
            $container->setField(
                "pager",
                array(
                    "start"     => ($page - 1) * $view_params->getResultsbyPage(),
                    "offset"    => $view_params->getResultsbyPage()*2
                )
            );

            $container->setFilters($filters);
        } else {
            $container->setNoResults();
        }

        $factory->prepareQuery($container);

        $searchResults = $factory->performQuery(
            $container,
            $conf_facets
        );

        $this->handleFacets(
            $factory,
            $conf_facets,
            $searchResults,
            $filters,
            $facet_name,
            $tpl_vars
        );

        if ($query_terms !== null) {
            $hlSearchResults = $factory->getHighlighting();
            $scSearchResults = $factory->getSpellcheck();
            $resultCount = $searchResults->getNumFound();

            $tpl_vars['searchResults'] = $searchResults;
            $tpl_vars['hlSearchResults'] = $hlSearchResults;
            $tpl_vars['scSearchResults'] = $scSearchResults;
            $tpl_vars['totalPages'] = ceil(
                $resultCount/$view_params->getResultsbyPage()
            );

            $suggestions = $factory->getSuggestions($query_terms);

            if (isset($suggestions) && $suggestions->count() > 0) {
                $tpl_vars['suggestions'] = $suggestions;
            }

        }

        $tpl_vars['stats'] = $factory->getStats();
        $this->handleYearlyResults($factory, $tpl_vars);
        $this->handleGeoloc($factory);

        $tpl_vars['form'] = $form->createView();

        $tpl_vars['resultStart'] = ($page - 1)
            * $view_params->getResultsbyPage() + 1;
        $resultEnd = ($page - 1) * $view_params->getResultsbyPage()
            + $view_params->getResultsbyPage()*2;
        if ($resultEnd > $resultCount) {
            $resultEnd = $resultCount;
        }
        $tpl_vars['resultEnd'] = $resultEnd;
        $tpl_vars['current_date'] = $current_date;

        if ($this->container->hasParameter('matricules_listparameters')) {
            $tpl_vars['matricules_listparameters']
                = $this->container->getParameter('matricules_listparameters');
        }
        if ($this->container->hasParameter('matricules_searchparameters')) {
            $tpl_vars['matricules_searchparameters']
                = $this->container->getParameter('matricules_searchparameters');
        }

        if ($query_terms != '*:*') {
            $tpl_vars['q'] = preg_replace('/[^A-Za-z0-9ÀÁÂÃÄÅàáâãäåÒÓÔÕÖØòóôõöøÈÉÊËèéêëÇçÌÍÎÏìíîïÙÚÛÜùúûüÿÑñ\-]/', ' ', $query_terms);
        } else {
            $tpl_vars['q'] = "*:*";
        }
        return $this->render(
            'BachHomeBundle:Matricules:print_matresults.html.twig',
            array_merge(
                $tpl_vars,
                array(
                    'resultCount'   => $resultCount
                )
            )
        );
    }

    /**
     *  Basket add action
     *
     * @param string $docid Document id
     *
     * @return void
     */
    public function basketAddAction($docid)
    {
        $session = $this->getRequest()->getSession();
        $basketArray = $session->get('documents');
        if ($basketArray == null) {
            $basketArray = array();
        }
        if (!isset($basketArray['matricules'])) {
            $basketArray['matricules'] = array();
        }
        if (!in_array($docid, $basketArray['matricules'])) {
            array_push($basketArray['matricules'], $docid);
            $session->set('documents', $basketArray);
            $AddFlag = true;
        } else {
            $AddFlag = false;
        }
        return new JsonResponse(
            array(
                'addFlag' => $AddFlag
            )
        );
    }

    /**
     * Basket delete action
     *
     * @return void
     */
    public function basketDeleteAction()
    {
        $filesToDelete = json_decode($this->getRequest()->get('deleteFilesMat'));
        $session = $this->getRequest()->getSession();
        $basketArray = $session->get('documents');
        foreach ($filesToDelete as $file) {
            unset(
                $basketArray['matricules'][array_search(
                    $file,
                    $basketArray['matricules']
                )]
            );
        }
        $docs = $session->set('documents', $basketArray);

        $session->set(
            'resultAction',
            _('Matricules have successfully been removed.')
        );
        return $this->redirect(
            $this->generateUrl(
                'bach_display_list_basket'
            )
        );
    }

    /**
     *  Basket delete all matricules action
     *
     * @return void
     */
    public function basketDeleteAllMatAction()
    {
        $session = $this->getRequest()->getSession();

        $basketArray = $session->get('documents');
        unset($basketArray['matricules']);
        $docs = $session->set('documents', $basketArray);

        $session->set(
            'resultAction',
            _('Matricules have successfully been removed.')
        );
        return $this->redirect(
            $this->generateUrl(
                'bach_display_list_basket'
            )
        );

    }

    /**
     *  Search historic add action
     *
     * @param string $nbResults Results search number
     *
     * @return void
     */
    public function searchhistoMatAddAction($nbResults = null)
    {
        $time = time();
        $session = $this->getRequest()->getSession();
        $query = $session->get('query_orig');
        $filters = $session->get($this->getFiltersName());

        $histoArray = $session->get('histosave');
        if ($histoArray == null) {
            $histoArray = array();
        }
        if (!isset($histoArray['matricules'])) {
            $histoArray['matricules'] = array();
        }
        $filtersClone = unserialize(serialize($filters));
        $arraySave = array(
            'query'     => $query,
            'filters'   => $filtersClone,
            'nbResults' => $nbResults
        );

        if ($query != null
            && $filters != null
            && !in_array($arraySave, $histoArray['matricules'])
        ) {
            $histoArray['matricules'][$time] = $arraySave;
            $session->set('histosave', $histoArray);
            $AddFlag = true;
        } else {
            $AddFlag = false;
        }
    }

    /**
     * Delete one search historic action
     *
     * @param string $timedelete Search time to delete
     *
     * @return JsonResponse
     */
    public function searchHistoMatDeleteOneAction($timedelete)
    {
        $session = $this->getRequest()->getSession();
        $searchhistoArray = $session->get('histosave');

        if (isset($session->get('histosave')['matricules'][$timedelete])) {
            unset($searchhistoArray['matricules'][$timedelete]);
        }
        $session->set('histosave', $searchhistoArray);
        if (isset($session->get('histosave')['matricules'][$timedelete])) {
            $deleteFlag = false;
        } else {
            $deleteFlag = true;
        }
        return new JsonResponse(
            array(
                'deleteFlag' => $deleteFlag
            )
        );
    }

    /**
     * Execute query
     *
     * @return void
     */
    public function searchHistomatExecuteAction()
    {
        $request = $this->getRequest();
        $newFilters = $request->get('filtersListSearchhisto');
        $query_terms = $request->get('query_terms');
        $filters = new Filters();
        if (is_array($newFilters) || is_object($newFilters)) {
            foreach ( $newFilters as $key => $values) {
                if (is_array($values)) {
                    foreach ($values as $value) {
                        $filters->addFilter($key, $value, true);
                    }
                } else {
                    $filters->addFilter($key, $values, true);
                }
            }
        }
        $request->getSession()->set($this->getFiltersName(), $filters);
        return $this->redirect(
            $this->generateUrl(
                'bach_matricules',
                array(
                    'query_terms' => $query_terms
                )
            )
        );
    }

    /**
     * Delete matricules search in historic
     *
     * @return void
     */
    public function searchhistoDeleteMatAction()
    {
        $searchToDelete = json_decode($this->getRequest()->get('deleteSearchMat'));
        $session = $this->getRequest()->getSession();
        $searchArray = $session->get('histosave');
        foreach ($searchToDelete as $search) {
            unset($searchArray['matricules'][$search]);
        }
        $search = $session->set('histosave', $searchArray);

        $session->set(
            'resultAction',
            _('Matricules search have successfully been removed.')
        );
        return $this->redirect(
            $this->generateUrl(
                'bach_display_searchhisto'
            )
        );
    }

    /**
     * Delete ead search in historic
     *
     * @return void
     */
    public function searchhistoDeleteAllMatAction()
    {
        $session = $this->getRequest()->getSession();

        $searchArray = $session->get('histosave');
        unset($searchArray['matricules']);
        $docs = $session->set('histosave', $searchArray);

        $session->set(
            'resultAction',
            _('Matricules search have successfully been removed.')
        );
        return $this->redirect(
            $this->generateUrl(
                'bach_display_searchhisto'
            )
        );
    }
}
