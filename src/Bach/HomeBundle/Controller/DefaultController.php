<?php
/**
 * Bach home controller
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
use Symfony\Component\HttpFoundation\RedirectResponse;
use Bach\HomeBundle\Form\Type\SearchQueryFormType;
use Bach\HomeBundle\Entity\SearchQuery;
use Bach\HomeBundle\Entity\Comment;
use Bach\HomeBundle\Entity\Filters;
use Bach\HomeBundle\Entity\ViewParams;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Bach\HomeBundle\Entity\Pdf;

/**
 * Bach home controller
 *
 * PHP version 5
 *
 * @category Search
 * @package  Bach
 * @author   Johan Cwiklinski <johan.cwiklinski@anaphore.eu>
 * @license  BSD 3-Clause http://opensource.org/licenses/BSD-3-Clause
 * @link     http://anaphore.eu
 */
class DefaultController extends SearchController
{
    protected $date_field = 'cDateBegin';

    /**
     * Get Solarium EntryPoint
     *
     * @return string
     */
    protected function entryPoint()
    {
        return 'solarium.client';
    }

    /**
     * Get factory name
     *
     * @return string
     */
    protected function factoryName()
    {
        return 'bach.home.solarium_query_factory';
    }

    /**
     * Get map facets session name
     *
     * @return string
     */
    protected function mapFacetsName()
    {
        $name = 'map_facets';
        if ( $this->search_form !== null ) {
            $name .= '_form_' . $this->search_form;
        }
        return $name;
    }

    /**
     * Get date fields
     *
     * @return array
     */
    protected function getFacetsDateFields()
    {
        return array('cDateBegin');
    }

    /**
     * Get golocalization fields class name
     *
     * @return string
     */
    protected function getGeolocClass()
    {
        return 'Bach\HomeBundle\Entity\GeolocMainFields';
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
    public function searchAction($query_terms = null, $page = 1,
        $facet_name = null, $form_name = null
    ) {
        $request = $this->getRequest();
        $session = $request->getSession();

        if ( $query_terms !== null && $query_terms != '*:*') {
            $query_terms = urldecode(str_replace("*:*", "", $query_terms));
        }

        if ( $form_name !== 'default' ) {
            $this->search_form = $form_name;
        }

        /** Manage view parameters */
        $view_params = $this->handleViewParams();

        $filters = $session->get($this->getFiltersName());
        if ( !$filters instanceof Filters || $request->get('clear_filters') ) {
            $filters = new Filters();
            $session->set($this->getFiltersName(), null);
        }

        $filters->bind($request);
        $session->set($this->getFiltersName(), $filters);

        if ( ($request->get('filter_field') || $filters->count() > 0)
            && is_null($query_terms)
        ) {
            $query_terms = '*:*';
        }

        $tpl_vars = $this->searchTemplateVariables($view_params, $page);
        $tpl_vars['q'] = urlencode($query_terms);

        /* not display warning about cookies */
        if ( isset($_COOKIE[$this->getCookieName()]) ) {
            $tpl_vars['cookie_param'] = true;
        }

        $factory = $this->get($this->factoryName());

        //FIXME: try to avoid those 2 calls
        $factory->setGeolocFields($this->getGeolocFields());
        $factory->setDateField($this->date_field);

        // On effectue une recherche
        $form = $this->createForm(
            new SearchQueryFormType(
                $query_terms,
                !is_null($query_terms),
                $session->get('pdf_filters')
            ),
            new SearchQuery()
        );

        $search_forms = null;
        if ( $this->search_form !== null ) {
            $search_forms = $this->container->getParameter('search_forms');
        }

        $search_form_params = null;
        if ( $search_forms !== null ) {
            $search_form_params = $search_forms[$this->search_form];
        }

        $current_form = 'main';
        if ( $search_form_params !== null ) {
            $current_form = $this->search_form;
        }

        $container = new SolariumQueryContainer();

        if ( !is_null($query_terms) ) {
            if ($request->get('results_order') !== null) {
                $container->setOrder($request->get('results_order'));//$view_params->getOrder();
            } else if ( $this->container->getParameter('display.ead.result_order') != null) {
                $container->setOrder($this->container->getParameter('display.ead.result_order'));//$view_params->getOrder();
            } else {
                $container->setOrder($view_params->getOrder());
            }

            if ( $this->search_form !== null ) {
                $container->setSearchForm($search_forms[$this->search_form]);
            }

            $container->setField(
                'show_pics',
                $view_params->showPics()
            );
            $container->setField($this->getContainerFieldName(), $query_terms);

            $container->setField(
                "pager",
                array(
                    "start"     => ($page - 1) * $view_params->getResultsbyPage(),
                    "offset"    => $view_params->getResultsbyPage()
                )
            );

            //Add filters to container
            $container->setFilters($filters);

            $weight = array(
                "descriptors" => $this->container->getParameter('weight.descriptors'),
                "cUnittitle" => $this->container->getParameter('weight.cUnittitle'),
                "parents_titles" => $this->container->getParameter('weight.parents_titles'),
                "fulltext" => $this->container->getParameter('weight.fulltext')
            );
            $cMediaContentQuery = array();
            if ($session->get('pdf_filters') == true) {
                $cMediaContentQuery = array(
                    "cMediaContent" => "0.1"
                );
            }
            $weight = array_merge($weight, $cMediaContentQuery);
            $container->setWeight($weight);
            if ( $filters->count() > 0 ) {
                $tpl_vars['filters'] = $filters;
            }
        } else {
            $container->setNoResults();
        }

        $factory->prepareQuery($container);

        if ( !is_null($query_terms) ) {
            $conf_facets = $this->getDoctrine()
                ->getRepository('BachHomeBundle:Facets')
                ->findBy(
                    array(
                        'active'    => true,
                        'form'      => $current_form
                    ),
                    array('position' => 'ASC')
                );
        } else {
            $conf_facets = array();
            $conf_facets = $this->getDoctrine()
                ->getRepository('BachHomeBundle:Facets')
                ->findBy(
                    array(
                        'active' => true,
                        'form'   => $current_form,
                        'on_home'=> true
                    ),
                    array('position' => 'ASC')
                );
        }

        $searchResults = $factory->performQuery(
            $container,
            $conf_facets
        );

        if ($session->get('pdf_filters') == true) {
            $query_words = explode(" ", $query_terms);
            $cMediaContentResult = array();
            foreach ($searchResults as $res) {
                if (isset($res->getFields()['cMediaContent'])) {
                    foreach ($query_words as $word) {
                        $test = preg_match(
                            "/".$word."/i",
                            $res->getFields()['cMediaContent']
                        );
                        if ($test == 1) {
                            break;
                        }
                    }
                    array_push($cMediaContentResult, $test);
                } else {
                    $test = 0;
                    array_push($cMediaContentResult, $test);
                }
            }
            $tpl_vars['cMediaContent']   = $cMediaContentResult;
        }
        $this->handleFacets(
            $factory,
            $conf_facets,
            $searchResults,
            $filters,
            $facet_name,
            $tpl_vars
        );

        $all_facets_table = $this->getDoctrine()
            ->getRepository('BachHomeBundle:Facets')
            ->findBy(
                array(
                    'form'   => $current_form,
                ),
                array('position' => 'ASC')
            );

        $all_facets = $tpl_vars['facet_names'];
        foreach ( $all_facets_table as $field ) {
            if ( !isset($all_facets_table[$field->getSolrFieldName()]) ) {
                $all_facets[$field->getSolrFieldName()]
                    = $field->getLabel($request->getLocale());
            }
        }

        $browse_fields = $this->getDoctrine()
            ->getRepository('BachHomeBundle:BrowseFields')
            ->findAll();
        foreach ( $browse_fields as $field ) {
            if ( !isset($browse_fields[$field->getSolrFieldName()]) ) {
                $all_facets[$field->getSolrFieldName()]
                    = $field->getLabel($request->getLocale());
            }
        }

        $tpl_vars['all_facets'] = $all_facets;

        $daoSeries = array();
        foreach ($searchResults as $searchResult) {
            if (count($searchResult['dao']) > 1) {
                $query = $this->getDoctrine()->getManager()
                    ->createQuery(
                        'SELECT e.role FROM BachIndexationBundle:EADDaos e
                        WHERE e.href = :href'
                    )->setParameters(
                        array(
                            'href' => $searchResult['dao'][0],
                        )
                    );
                if ($query->getResult()[0]['role'] == 'image:first') {
                    $daoSeries[$searchResult['fragmentid']] = 'series';
                }
            }
            $tpl_vars['daoSeries'] = $daoSeries;
        }

        if ( !is_null($query_terms) ) {
            $hlSearchResults = $factory->getHighlighting();
            $scSearchResults = $factory->getSpellcheck();
            $resultCount = $searchResults->getNumFound();

            $session->set('highlight', $hlSearchResults);
            $session->set('query_orig', $query_terms);
            $query_session = str_replace("AND", " ", $query_terms);
            $query_session = str_replace("OR", " ", $query_session);
            $query_session = str_replace("NOT", " ", $query_session);
            $session->set('query_terms', $query_session);

            if ($this->container->getParameter('display.disable_suggestions') != true) {
                $suggestions = $factory->getSuggestions($query_session);
            }

            $tpl_vars['resultCount'] = $resultCount;
            $tpl_vars['resultByPage'] = $view_params->getResultsbyPage();
            $tpl_vars['totalPages'] = ceil(
                $resultCount/$view_params->getResultsbyPage()
            );
            $tpl_vars['searchResults']   = $searchResults;
            $tpl_vars['hlSearchResults'] = $hlSearchResults;
            $tpl_vars['scSearchResults'] = $scSearchResults;
            $tpl_vars['resultStart'] = ($page - 1)
                * $view_params->getResultsbyPage() + 1;
            $resultEnd = ($page - 1) * $view_params->getResultsbyPage()
                + $view_params->getResultsbyPage();
            if ( $resultEnd > $resultCount ) {
                $resultEnd = $resultCount;
            }
            $tpl_vars['resultEnd'] = $resultEnd;
        } else {
            $show_tagcloud = $this->container->getParameter('feature.tagcloud');
            if ( $show_tagcloud ) {
                $tagcloud = $factory->getTagCloud(
                    $this->getDoctrine()->getManager(),
                    $search_form_params
                );

                if ( $tagcloud ) {
                    $tpl_vars['tagcloud'] = $tagcloud;
                }
            }
        }

        $tpl_vars['stats'] = $factory->getStats();
        $this->handleYearlyResults($factory, $tpl_vars);
        $this->handleGeoloc($factory);

        $tpl_vars['form'] = $form->createView();

        $tpl_vars['view'] = $view_params->getView();
        $tpl_vars['pdf_search'] = $this->container->getParameter('pdf_search');
        if ($session) {
        } else {
            $tpl_vars['results_order'] = $this->container->getParameter('display.ead.result_order');//$view_params->getOrder();
        }
        if ( isset($suggestions) && $suggestions->count() > 0 ) {
            $tpl_vars['suggestions'] = $suggestions;
        }
        $tpl_vars['disable_select_daterange']
            = $this->container->getParameter('display.disable_select_daterange');
        $tpl_vars['current_date'] = 'cDateBegin';

        $this->searchhistoAddAction($searchResults->getNumFound(), $all_facets);

        return $this->render(
            'BachHomeBundle:Default:index.html.twig',
            $tpl_vars
        );
    }

    /**
     * POST search destination for main form.
     *
     * Will take care of search terms, and reroute with proper URI
     *
     * @param string $form_name Search form name
     *
     * @return void
     */
    public function doSearchAction($form_name = null)
    {
        if ( $form_name !== 'default' ) {
            $this->search_form = $form_name;
        }
        $query = new SearchQuery();
        $form = $this->createForm(new SearchQueryFormType(), $query);

        $redirectUrl = null;
        if ( $this->search_form !== null ) {
            $redirectUrl = $this->get('router')->generate(
                'bach_search_form_homepage',
                array(
                    'form_name' => $this->search_form
                )
            );
        } else {
            $redirectUrl = $this->get('router')->generate('bach_archives');
        }

        $request = $this->getRequest();

        if ( $request->isMethod('POST') ) {
            $form->bind($request);
            if ($form->isValid()) {
                $q = $query->getQuery();
                $url_vars = array('query_terms' => $q);

                $session = $request->getSession();
                $view_params = $session->get($this->getParamSessionName());
                if ($request->get('results_order') !== null) {
                    $view_params->setOrder((int)$request->get('results_order'));//$view_params->getOrder();
                } else {
                    $view_params->setOrder((int)$this->container->getParameter('display.ead.result_order'));//$view_params->getOrder();
                }
                $session->set($this->getParamSessionName(), $view_params);
                $url_vars['view'] = $view_params->getView();
                //check for filtering informations
                if ( $request->get('filter_field')
                    && $request->get('filter_value')
                ) {
                    $url_vars['filter_field'] = $request->get('filter_field');
                    $url_vars['filter_value'] = $request->get('filter_value');
                }
                if ( $form->getData()->keep_filters != 1 ) {
                    $session->set($this->getFiltersName(), null);
                }
                if ($form->getData()->pdf_filters == 1) {
                    $session->set('pdf_filters', true);
                } else {
                    $session->set('pdf_filters', false);
                }
                $route = 'bach_archives';
                if ( $this->search_form !== null ) {
                    $url_vars['form_name'] = $this->search_form;
                }
                $redirectUrl = $this->get('router')->generate(
                    $route,
                    $url_vars
                );
            }
        }
        return new RedirectResponse($redirectUrl);
    }

    /**
     * Browse contents
     *
     * @param string  $part Part to browse
     * @param boolean $ajax If we were called from ajax
     *
     * @return void
     */
    public function browseAction($part = '', $ajax = false)
    {
        $fields = $this->getDoctrine()
            ->getRepository('BachHomeBundle:BrowseFields')
            ->findBy(
                array('active' => true),
                array('position' => 'ASC')
            );

        $field = null;
        if ( $part === '' && count($fields) >0 ) {
            $field = $fields[0];
            $part = $field->getSolrFieldName();
        } else if ( count($fields) > 0 ) {
            foreach ( $fields as $f ) {
                if ( $f->getSolrFieldName() === $part ) {
                    $field = $f;
                    break;
                }
            }
        }

        $tpl_vars = array(
            'fields'        => $fields,
            'current_field' => $field,
            'part'          => $part
        );

        $lists = array();

        if ( $part !== '' ) {
            $client = $this->get($this->entryPoint());

            $query = $client->createSelect();
            $query->setQuery('*:*');
            $query->setRows(0);
            $facetSet = $query->getFacetSet();
            $facetSet->setLimit(-1);
            $facetSet->setMinCount(1);

            $facetSet->createFacetField($part)
                ->setField($part);

            $rs = $client->select($query);
            $facetSet = $rs->getFacetSet();
            $facets = $facetSet->getFacet($part);

            $lists[$part] = array();
            $current_values = array();
            foreach ( $facets as $term=>$count ) {
                $current_values[$term] = array(
                    'term'  => $term,
                    'count' => $count
                );
            }

            if ( defined('SORT_FLAG_CASE') ) {
                //TODO: find a better way!
                if ( $this->getRequest()->getLocale() == 'fr_FR' ) {
                    setlocale(LC_COLLATE, 'fr_FR.utf8');
                }
                ksort($current_values, SORT_LOCALE_STRING | SORT_FLAG_CASE);
            } else {
                //fallback for PHP < 5.4
                ksort($current_values, SORT_LOCALE_STRING);
            }

            if ( $part == 'headerId' ) {
                //retrieve documents titles...
                $ids = array();
                foreach ( $current_values as $v ) {
                    $ids[] = $v['term'] . '_description';
                }

                $query = $this->getDoctrine()->getManager()->createQuery(
                    'SELECT h.headerId, h.headerTitle ' .
                    'FROM BachIndexationBundle:EADFileFormat e ' .
                    'JOIN e.eadheader h WHERE e.fragmentid IN (:ids)'
                )->setParameter('ids', $ids);
                $lists[$part] = $query->getResult();
            } else {
                $lists[$part] = $current_values;
            }
        }

        /* not display warning about cookies */
        if ( isset($_COOKIE[$this->getCookieName()]) ) {
            $tpl_vars['cookie_param'] = true;
        }

        $tpl_vars['lists'] = $lists;

        if ( $ajax === false ) {
            $tpl_name = 'browse';
        } else {
            $tpl_name = 'browse_tab_contents';
        }

        return $this->render(
            'BachHomeBundle:Default:' . $tpl_name  . '.html.twig',
            $tpl_vars
        );
    }

    /**
     * Document display
     *
     * @param int     $docid Document unique identifier
     * @param int     $page  Page
     * @param boolean $ajax  Called from ajax
     * @param boolean $print Know if print
     *
     * @return void
     */
    public function displayDocumentAction($docid, $page = 1, $ajax = false, $print = false)
    {
        $with_context = true;

        if ( $this->getRequest()->get('nocontext') ) {
            $with_context = false;
        }

        $request = $this->getRequest();
        $session = $request->getSession();
        if ($session->get('highlight')) {
            $highlight = $session->get('highlight')->getResult($docid);
        } else {
            $highlight = null;
        }

        $client = $this->get($this->entryPoint());
        $query = $client->createSelect();
        $query->setQuery('fragmentid:"' . $docid . '"');
        $query->setFields(
            'headerId, fragmentid, fragment, parents, ' .
            'archDescUnitTitle, cUnittitle, cDate, ' .
            'previous_id, previous_title, next_id, next_title'
        );
        $query->setStart(0)->setRows(1);

        $rs = $client->select($query);

        if ( $rs->getNumFound() !== 1 ) {
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
        $children = array();

        $tpl = '';

        $form_name = 'default';
        if ( $this->getRequest()->get('search_form') ) {
            $form_name = $this->getRequest()->get('search_form');
        }

        $tpl_vars = $this->commonTemplateVariables();
        $tpl_vars = array_merge(
            $tpl_vars,
            array(
                'docid'         => $docid,
                'document'      => $doc,
                'context'       => $with_context,
                'search_form'   => $form_name
            )
        );

        if ( isset($doc['archDescUnitTitle']) ) {
            $tpl_vars['archdesc'] = $doc['archDescUnitTitle'];
        }
        $parents = explode('/', $doc['parents']);
        if ( count($parents) > 0 ) {
            $pquery = $client->createSelect();
            $query = null;
            foreach ( $parents as $p ) {
                if ( $query !== null ) {
                    $query .= ' | ';
                }
                $query .= 'fragmentid:"' . $doc['headerId'] . '_' . $p . '"';
            }
            $pquery->setQuery($query);
            $pquery->setFields('fragmentid, cUnittitle');
            $rs = $client->select($pquery);
            $ariane  = $rs->getDocuments();
            if ( count($ariane) > 0 ) {
                $tpl_vars['ariane'] = $ariane;
            }
        }

        $max_results = 20;
        $cquery = $client->createSelect();
        $pid = substr($docid, strlen($doc['headerId']) + 1);

        $query = '+headerId:"' . $doc['headerId'] . '" +parents: ';
        if ( $pid === 'description' ) {
            $query .= '""';
        } else {
            if ( isset($doc['parents']) && trim($doc['parents'] !== '') ) {
                $pid = $doc['parents'] . '/' . $pid;
            }
            $query .= $pid;
        }
        $cquery->setQuery($query);
        $cquery->setStart(($page - 1) * $max_results);
        $cquery->setRows($max_results);
        $cquery->setFields('fragmentid, cUnittitle');
        $rs = $client->select($cquery);
        $children  = $rs->getDocuments();
        $count_children = $rs->getNumFound();

        $tpl_vars['count_children'] = $count_children;

        if ( count($children) > 0 ) {
            $tpl_vars['children'] = $children;
            if ( count($children) < $count_children ) {
                $tpl_vars['totalPages'] = ceil($count_children/$max_results);
                $tpl_vars['page'] = $page;
            }
        } else {
            $tpl_vars['children'] = false;
        }

        if ( $ajax === 'ajax' ) {
            $tpl = 'BachHomeBundle:Default:content_display.html.twig';
            $tpl_vars['ajax'] = true;
        } else {
            $tpl = 'BachHomeBundle:Default:display.html.twig';
            $tpl_vars['ajax'] = false;
        }

        //retrieve comments
        $show_comments = $this->container->getParameter('feature.comments');
        if ( $show_comments ) {
            $query = $this->getDoctrine()->getManager()
                ->createQuery(
                    'SELECT c FROM BachHomeBundle:Comment c
                    WHERE c.state = :state
                    AND c.docid = :docid
                    ORDER BY c.creation_date DESC, c.id DESC'
                )->setParameters(
                    array(
                        'state' => Comment::PUBLISHED,
                        'docid' => $docid
                    )
                );
            $comments = $query->getResult();
            if ( count($comments) > 0 ) {
                $tpl_vars['comments'] = $comments;
            }
        }

        /* not display warning about cookies */
        if ( isset($_COOKIE[$this->getCookieName()]) ) {
            $tpl_vars['cookie_param'] = true;
        }

        $tpl_vars['print'] = $print;
        $tpl_vars['highlight']= $highlight;
        return $this->render(
            $tpl,
            $tpl_vars
        );
    }

    /**
     * Display classification scheme
     *
     * @return void
     */
    public function cdcAction()
    {
        $cdc_path = $this->container->getParameter('cdc_path');

        $tpl_vars = $this->commonTemplateVariables();

        $client = $this->get($this->entryPoint());
        $query = $client->createSelect();
        $query->setQuery('fragmentid:*_description');
        $query->setFields('cUnittitle, headerId, fragmentid');
        $query->setStart(0)->setRows(1000);

        $results = $client->select($query);

        $published = new \SimpleXMLElement(
            '<docs></docs>'
        );

        foreach ( $results as $doc ) {
            $published->addChild($doc->headerId, $doc->cUnittitle);
        }

        $tpl_vars['docs'] = $published;
        $tpl_vars['docid'] = '';
        $tpl_vars['xml_file'] = $cdc_path;
        $tpl_vars['cdc'] = true;

        /* not display warning about cookies */
        if ( isset($_COOKIE[$this->getCookieName()]) ) {
            $tpl_vars['cookie_param'] = true;
        }

        return $this->render(
            'BachHomeBundle:Default:html.html.twig',
            $tpl_vars
        );
    }

    /**
     * Displays an EAD document as HTML
     *
     * @param string $docid Document id
     *
     * @return void
     */
    public function eadHtmlAction($docid)
    {
        $tpl_vars = $this->commonTemplateVariables();

        $repo = $this->getDoctrine()
            ->getRepository('BachIndexationBundle:Document');
        $document = $repo->findOneByDocid($docid);

        if ( $document === null ) {
            throw new NotFoundHttpException(
                str_replace(
                    '%docid',
                    $docid,
                    _('Document "%docid" does not exists.')
                )
            );
        } else {
            if ( $document->isUploaded() ) {
                $document->setUploadDir(
                    $this->container->getParameter('upload_dir')
                );
            } else {
                $document->setStoreDir(
                    $this->container->getParameter('bach.typespaths')['ead']
                );
            }
            $xml_file = $document->getAbsolutePath();

            if ( !file_exists($xml_file) ) {
                throw new NotFoundHttpException(
                    str_replace(
                        '%docid',
                        $docid,
                        _('File for %docid document no longer exists on disk.')
                    )
                );
            } else {
                $tpl_vars['docid'] = $docid;
                $tpl_vars['xml_file'] = $xml_file;

                $form = $this->createForm(
                    new SearchQueryFormType(),
                    new SearchQuery()
                );
                $tpl_vars['form'] = $form->createView();

                /* not display warning about cookies */
                if ( isset($_COOKIE[$this->getCookieName()]) ) {
                    $tpl_vars['cookie_param'] = true;
                }

                return $this->render(
                    'BachHomeBundle:Default:html.html.twig',
                    $tpl_vars
                );
            }
        }
    }

    /**
     * Get available ordering options
     *
     * @return array
     */
    protected function getOrders()
    {
        $orders = array(
            ViewParams::ORDER_DOC_LOGIC => _('Inventory logic'),
            ViewParams::ORDER_CHRONO    => _('Chronological'),
            ViewParams::ORDER_TITLE     => _('Alphabetical')
        );
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
                'title' => _('View search results as a list, with images')
            ),
            'txtlist'   => array(
                'text'  => _('Text only list'),
                'title' => _('View search results as a list, without images')
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
        $form_name = 'main';
        if ( $this->search_form !== null ) {
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
        return 'main';
    }

    /**
     * Get filters session name
     *
     * @return string
     */
    protected function getFiltersName()
    {
        $name = 'filters';
        if ( $this->search_form !== null ) {
            $name .= '_form_' . $this->search_form;
        }
        return $name;
    }


    /**
     * Get search URI
     *
     * @return string
     */
    protected function getSearchUri()
    {
        return 'bach_archives';
    }

    /**
     * Get view params service name
     *
     * @return string
     */
    protected function getViewParamsServicename()
    {
        return ('bach.home.ead_view_params');
    }

    /**
     * Serve twig generated CSS
     *
     * @param string $name CSS name
     *
     * @return void
     */
    public function dynamicCssAction($name)
    {
        return $this->render(
            '::' . $name . '.css.twig'
        );
    }

    /**
     * Serve twig generated JS
     *
     * @param string $name JS name
     *
     * @return void
     */
    public function dynamicJsAction($name)
    {
        return $this->render(
            'BachHomeBundle:js:' . $name . '.js.twig'
        );
    }


    /**
     * Get session name for view parameters
     *
     * @return string
     */
    protected function getParamSessionName()
    {
        $name = 'view_params';
        if ( $this->search_form !== null ) {
            $name .= '_form_' . $this->search_form;
        }
        return $name;
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
        if ( $img !== null && $ext !== null ) {
            $qry_string = $img . '.' . $ext;
        }
        if ( $path !== null ) {
            $qry_string = $path . '/' . $qry_string;
        }

        if ($this->container->getParameter('feature.archives')) {
            $client = $this->get($this->entryPoint());
            $query = $client->createSelect();
            $query->setQuery('dao:' . $qry_string);
            $query->setFields(
                'headerId, fragmentid, parents, archDescUnitTitle, cUnittitle, cUnitid, cLegalstatus, cRepository'
            );
            $query->setStart(0)->setRows(1);

            $rs = $client->select($query);
            $docs = $rs->getDocuments();
            $parents_docs = null;
        }

        $response = array();
        $response_mat = array();
        if ($this->container->getParameter('feature.archives') && count($docs) > 0 ) {
            $doc = $docs[0];
            $parents = explode('/', $doc['parents']);
            if ( count($parents) > 0 ) {
                $pquery = $client->createSelect();
                $query = null;
                foreach ( $parents as $p ) {
                    if ( $query !== null ) {
                        $query .= ' | ';
                    }
                    $query .= 'fragmentid:"' . $doc['headerId'] . '_' . $p . '"';
                }
                $pquery->setQuery($query);
                $pquery->setFields('fragmentid, cUnittitle');
                $rs = $client->select($pquery);
                $parents_docs = $rs->getDocuments();
            }

            $response['ead'] = array();
            //link to main document
            $doc_url = $this->get('router')->generate(
                'bach_ead_html',
                array(
                    'docid' => $doc['headerId']
                )
            );
            $response['ead']['unitid'] = $docs[0]['cUnitid'];
            $response['ead']['cUnittitle'] = $docs[0]['cUnittitle'];
            $response['ead']['cLegalstatus'] = $docs[0]['cLegalstatus'];
            $response['ead']['link'] = '<a href="' . $doc_url . '">' .
                $doc['archDescUnitTitle'] . '</a>';

            //links to parents
            foreach ( $parents_docs as $pdoc ) {
                $doc_url = $this->get('router')->generate(
                    'bach_display_document',
                    array(
                        'docid' => $pdoc['fragmentid']
                    )
                );
                $response['ead']['link'] .= ' » <a href="' . $doc_url . '">' .
                    $pdoc['cUnittitle'] . '</a>';
            }

            //link to document itself
            $doc_url = $this->get('router')->generate(
                'bach_display_document',
                array(
                    'docid' => $doc['fragmentid']
                )
            );
            $response['ead']['link'] .= ' » <a href="' . $doc_url . '">' .
                $doc['cUnittitle'] . '</a>';
            $response['ead']['doclink'] = '<a href="' . $doc_url .'">' .
                $doc['cUnittitle'] . '</a>';
        }
        if ( $this->container->getParameter('feature.matricules') ) {
            //we did not find any restuls in archives, try with matricules.
            $route = 'remote_matimage_infos';
            $params = array(
                'path'  => $path,
                'img'   => $img,
                'ext'   => $ext
            );
            if ( $path === null ) {
                $route = 'remote_matimage_infos_nopath';
                unset($params['path']);
            }
            if ( $img === null ) {
                $route = 'remote_matimage_infos_noimg';
                unset($params['img']);
                unset($params['ext']);
            }

            $redirectUrl = $this->get('router')->generate(
                $route,
                $params
            );
            $urlBase = $this->container->get('router')->getContext()->getHost();
            $response_mat = @file_get_contents('http://'.$urlBase.$redirectUrl);
            $response_mat = (array)json_decode($response_mat);
        }
        $total_response = array_merge($response, $response_mat);
        $jsonResponse = new JsonResponse();
        $jsonResponse->setData($total_response);
        return $jsonResponse;
    }

    /**
     * Display page of credits or general conditions
     *
     * @param string $type type of document to render
     *
     * @return void
     */
    public function footerLinkAction($type)
    {
        if (isset($_COOKIE[$this->getCookieName()])) {
             $tpl_vars['cookie_param'] = true;
        }
        $tpl_vars['type'] = $type;
        return $this->render(
            '::credits.html.twig',
            $tpl_vars
        );
    }

    /**
     * Create a cookie
     *
     * @return void
     */
    public function authorizedCookieAction()
    {
        $view_params = $this->get($this->getViewParamsServicename());
        $_cook = new \stdClass();
        $_cook->map = $this->container->getParameter('display.show_maps');
        $_cook->daterange = $this->container->getParameter('display.show_daterange');
        $expire = 365 * 24 * 3600;
        setcookie($this->getCookieName(), json_encode($_cook), time()+$expire, '/');
        return new Response();
    }

    /**
     * Display page about cookies
     *
     * @return void
     */
    public function cookieLinkAction()
    {
        return $this->render(
            '::cookies.html.twig'
        );
    }

    /**
     * Print a pdf with a document
     *
     * @param string $docid id of document
     *
     * @return void
     */
    public function printPdfDocAction($docid)
    {
        $params = $this->container->getParameter('print');
        $tpl_vars['docid'] = $docid;
        $content = '<style>' . file_get_contents('css/bach_print.css'). '</style>';
        $content .= $this->displayDocumentAction(
            $docid,
            1,
            'ajax',
            true
        )->getContent();
        $pdf = new Pdf($params);
        $pdf->addPage();
        $pdf->writeHTML($content);
        $pdf->download();
    }

    /**
     * Print a pdf with a list of result
     *
     * @param string $docid id of document
     *
     * @return void
     */
    public function printPdfResultsPageAction(
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
        $pdf->addPage();
        $pdf->writeHTML($content);
        $pdf->download();
    }


    /**
     * Print search page
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

        if ( $query_terms !== null ) {
            $query_terms = urldecode($query_terms);
        }

        if ( $form_name !== 'default' ) {
            $this->search_form = $form_name;
        }

        /** Manage view parameters */
        $view_params = $this->handleViewParams();

        $filters = $session->get($this->getFiltersName());
        if ( !$filters instanceof Filters || $request->get('clear_filters') ) {
            $filters = new Filters();
            $session->set($this->getFiltersName(), null);
        }

        $filters->bind($request);
        $session->set($this->getFiltersName(), $filters);
        if ( ($request->get('filter_field') || $filters->count() > 0)
            && is_null($query_terms)
        ) {
            $query_terms = '*:*';
        }

        $tpl_vars = $this->searchTemplateVariables($view_params, $page);

        if ($query_terms != '*:*') {
            $tpl_vars['q'] = preg_replace('/[^A-Za-z0-9ÀÁÂÃÄÅàáâãäåÒÓÔÕÖØòóôõöøÈÉÊËèéêëÇçÌÍÎÏìíîïÙÚÛÜùúûüÿÑñ\-]/', ' ', $query_terms);
        } else {
            $tpl_vars['q'] = "*:*";
        }

        $factory = $this->get($this->factoryName());
        $factory->setGeolocFields($this->getGeolocFields());

        // On effectue une recherche
        $form = $this->createForm(
            new SearchQueryFormType(
                $query_terms,
                !is_null($query_terms)
            ),
            new SearchQuery()
        );

        $search_forms = null;
        if ( $this->search_form !== null ) {
            $search_forms = $this->container->getParameter('search_forms');
        }

        $search_form_params = null;
        if ( $search_forms !== null ) {
            $search_form_params = $search_forms[$this->search_form];
        }

        $current_form = 'main';
        if ( $search_form_params !== null ) {
            $current_form = $this->search_form;
        }

        $container = new SolariumQueryContainer();

        if ( !is_null($query_terms) ) {
            $container->setOrder($view_params->getOrder());

            if ( $this->search_form !== null ) {
                $container->setSearchForm($search_forms[$this->search_form]);
            }

            $container->setField(
                'show_pics',
                $view_params->showPics()
            );
            $container->setField($this->getContainerFieldName(), $query_terms);

            $container->setField(
                "pager",
                array(
                    "start"     => ($page - 1) * $view_params->getResultsbyPage(),
                    "offset"    => $view_params->getResultsbyPage()*2
                )
            );

            //Add filters to container
            $container->setFilters($filters);

            $weight = array(
                "descriptors" => $this->container->getParameter('weight.descriptors'),
                "cUnittitle" => $this->container->getParameter('weight.cUnittitle'),
                "parents_titles" => $this->container->getParameter('weight.parents_titles'),
                "fulltext" => $this->container->getParameter('weight.fulltext')
            );
            $container->setWeight($weight);
            if ( $filters->count() > 0 ) {
                $tpl_vars['filters'] = $filters;
            }
        } else {
            $container->setNoResults();
        }

        $factory->prepareQuery($container);

        if ( !is_null($query_terms) ) {
            $conf_facets = $this->getDoctrine()
                ->getRepository('BachHomeBundle:Facets')
                ->findBy(
                    array(
                        'active'    => true,
                        'form'      => $current_form
                    ),
                    array('position' => 'ASC')
                );
        } else {
            $conf_facets = array();
            $conf_facets = $this->getDoctrine()
                ->getRepository('BachHomeBundle:Facets')
                ->findBy(
                    array(
                        'active' => true,
                        'form'   => $current_form,
                        'on_home'=> true
                    ),
                    array('position' => 'ASC')
                );
        }

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

        if ( !is_null($query_terms) ) {
            $resultCount = $searchResults->getNumFound();

            $tpl_vars['resultCount'] = $resultCount;
            $tpl_vars['resultByPage'] = $view_params->getResultsbyPage()*2;
            $tpl_vars['totalPages'] = ceil(
                $resultCount/$view_params->getResultsbyPage()*2
            );
            $tpl_vars['searchResults'] = $searchResults;
            $tpl_vars['resultStart'] = ($page - 1)
                * $view_params->getResultsbyPage() + 1;
            $resultEnd = ($page - 1) * $view_params->getResultsbyPage() +
                $view_params->getResultsbyPage() * 2;
            if ( $resultEnd > $resultCount ) {
                $resultEnd = $resultCount;
            }
            $tpl_vars['resultEnd'] = $resultEnd;
        }
        $tpl_vars['form'] = $form->createView();

        $tpl_vars['view'] = $view_params->getView();
        $tpl_vars['current_date'] = 'cDateBegin';
        return $this->render(
            'BachHomeBundle:Commons:searchPrint.html.twig',
            $tpl_vars
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
        if (!isset($basketArray['ead'])) {
            $basketArray['ead'] = array();
        }
        if (!in_array($docid, $basketArray['ead'])) {
            array_push($basketArray['ead'], $docid);
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
        $filesToDelete = json_decode($this->getRequest()->get('deleteFiles'));
        $session = $this->getRequest()->getSession();
        $basketArray = $session->get('documents');
        foreach ($filesToDelete as $file) {
            unset($basketArray['ead'][array_search($file, $basketArray['ead'])]);
        }
        $docs = $session->set('documents', $basketArray);

        $session->set('resultAction', _('Ead have successfully been removed.'));
        return $this->redirect(
            $this->generateUrl(
                'bach_display_list_basket'
            )
        );
    }

    /**
     *  Basket delete all ead action
     *
     * @return void
     */
    public function basketDeleteAllEadAction()
    {
        $session = $this->getRequest()->getSession();

        $basketArray = $session->get('documents');
        unset($basketArray['ead']);
        $docs = $session->set('documents', $basketArray);

        $session->set('resultAction', _('Ead have successfully been removed.'));
        return $this->redirect(
            $this->generateUrl(
                'bach_display_list_basket'
            )
        );
    }

    /**
     *  Search historic add action
     *
     * @return void
     */
    public function searchhistoAddAction($nbResults = null)
    {
        $time = time();
        $session = $this->getRequest()->getSession();

        $query = $session->get('query_orig');
        $filters = $session->get($this->getFiltersName());

        $histoArray = $session->get('histosave');
        if ($histoArray == null) {
            $histoArray = array();
        }
        if (!isset($histoArray['ead'])) {
            $histoArray['ead'] = array();
        }
        $filtersClone = unserialize(serialize($filters));
        $arraySave = array(
            'query'     => $query,
            'filters'   => $filtersClone,
            'nbResults' => $nbResults
        );

        if ($query != null
            && $filters != null
            && !in_array($arraySave, $histoArray['ead'])
        ) {
            $histoArray['ead'][$time] = $arraySave;
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
    public function searchHistoDeleteOneAction($timedelete)
    {
        $session = $this->getRequest()->getSession();
        $searchhistoArray = $session->get('histosave');

        if (isset($session->get('histosave')['ead'][$timedelete])) {
            unset($searchhistoArray['ead'][$timedelete]);
        }
        $session->set('histosave', $searchhistoArray);
        if (isset($session->get('histosave')['ead'][$timedelete])) {
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
    public function searchHistoExecuteAction()
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
                'bach_archives',
                array(
                    'query_terms' => $query_terms
                )
            )
        );
    }

    /**
     * Delete ead search in historic
     *
     * @return void
     */
    public function searchhistoDeleteEadAction()
    {
        $searchToDelete = json_decode($this->getRequest()->get('deleteSearch'));
        $session = $this->getRequest()->getSession();
        $searchArray = $session->get('histosave');
        foreach ($searchToDelete as $search) {
            unset($searchArray['ead'][$search]);
        }
        $search = $session->set('histosave', $searchArray);

        $session->set(
            'resultAction',
            _('Ead search have successfully been removed.')
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
    public function searchhistoDeleteAllEadAction()
    {
        $session = $this->getRequest()->getSession();

        $searchArray = $session->get('histosave');
        unset($searchArray['ead']);
        $docs = $session->set('histosave', $searchArray);

        $session->set('resultAction', _('Ead search have successfully been removed.'));
        return $this->redirect(
            $this->generateUrl(
                'bach_display_searchhisto'
            )
        );
    }

}
