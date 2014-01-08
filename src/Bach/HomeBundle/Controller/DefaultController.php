<?php
/**
 * Bach home controller
 *
 * PHP version 5
 *
 * @category Search
 * @package  Bach
 * @author   Johan Cwiklinski <johan.cwiklinski@anaphore.eu>
 * @license  Unknown http://unknown.com
 * @link     http://anaphore.eu
 */

namespace Bach\HomeBundle\Controller;

use Bach\HomeBundle\Entity\SolariumQueryContainer;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Bach\HomeBundle\Entity\ViewParams;
use Bach\HomeBundle\Entity\SearchQueryFormType;
use Bach\HomeBundle\Entity\SearchQuery;
use Bach\HomeBundle\Entity\Comment;
use Bach\HomeBundle\Entity\BrowseFields;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Bach\HomeBundle\Entity\Filters;
use Bach\HomeBundle\Entity\GeolocFields;

/**
 * Bach home controller
 *
 * PHP version 5
 *
 * @category Search
 * @package  Bach
 * @author   Johan Cwiklinski <johan.cwiklinski@anaphore.eu>
 * @license  Unknown http://unknown.com
 * @link     http://anaphore.eu
 */
class DefaultController extends SearchController
{
    /**
     * Serve default page
     *
     * @param string  $query_terms Term(s) we search for
     * @param int     $page        Page
     * @param string  $facet_name  Display more terms in suggests
     * @param boolean $ajax        Fomr ajax call
     *
     * @return void
     */
    public function indexAction($query_terms = null, $page = 1,
        $facet_name = null, $ajax = false
    ) {
        $request = $this->getRequest();
        $session = $request->getSession();

        if ( $query_terms !== null ) {
            $query_terms = urldecode($query_terms);
        }

        /** Manage view parameters */
        $view_params = $session->get('view_params');
        if ( !$view_params ) {
            $view_params = new ViewParams();
        }
        //take care of user view params
        if ( isset($_COOKIE['bach_view_params']) ) {
            $view_params->bindCookie('bach_view_params');
        }

        //set current view parameters according to request
        $view_params->bind($request);

        //store new view parameters
        $session->set('view_params', $view_params);

        $viewer_uri = $this->container->getParameter('viewer_uri');
        $show_maps = $this->container->getParameter('show_maps');

        $geoloc = array();
        if ( $show_maps ) {
            $gf = new GeolocFields();
            $gf = $gf->loadCloud(
                $this->getDoctrine()->getManager()
            );
            $geoloc = $gf->getSolrFieldsNames();
        }

        $filters = $session->get('filters');
        if ( !$filters instanceof Filters || $request->get('clear_filters') ) {
            $filters = new Filters();
            $session->set('filters', null);
        }

        $filters->bind($request);
        $session->set('filters', $filters);

        if ( ($request->get('filter_field') || $filters->count() > 0)
            && is_null($query_terms)
        ) {
            $query_terms = '*:*';
        }

        $templateVars = array(
            'q'             => urlencode($query_terms),
            'page'          => $page,
            'show_pics'     => $view_params->showPics(),
            'show_map'      => $view_params->showMap(),
            'show_daterange'=> $view_params->showDaterange(),
            'viewer_uri'    => $viewer_uri,
            'view'          => $view_params->getView(),
            'results_order' => $view_params->getOrder(),
            'show_maps'     => $show_maps
        );

        if ( $facet_name !== null ) {
            $templateVars['facet_name'] = $facet_name;
        }

        $factory = $this->get("bach.home.solarium_query_factory");
        $factory->setGeolocFields($geoloc);

        $map_facets = array();
        if ( !is_null($query_terms) ) {
            // On effectue une recherche
            $form = $this->createForm(
                new SearchQueryFormType($query_terms),
                new SearchQuery()
            );

            $container = new SolariumQueryContainer();
            $container->setOrder($view_params->getOrder());

            $container->setField(
                'show_pics',
                $view_params->showPics()
            );
            $container->setField("main", $query_terms);

            $container->setField(
                "pager",
                array(
                    "start"     => ($page - 1) * $view_params->getResultsbyPage(),
                    "offset"    => $view_params->getResultsbyPage()
                )
            );

            //Add filters to container
            $container->setFilters($filters);
            if ( $filters->count() > 0 ) {
                $templateVars['filters'] = $filters;
            }

            $factory->prepareQuery($container);

            $conf_facets = $this->getDoctrine()
                ->getRepository('BachHomeBundle:Facets')
                ->findBy(
                    array('active' => true),
                    array('position' => 'ASC')
                );

            if ( $ajax === false ) {
                $factory->setDatesBounds($filters);
            }

            $searchResults = $factory->performQuery(
                $container,
                $conf_facets,
                $geoloc
            );

            $hlSearchResults = $factory->getHighlighting();
            $scSearchResults = $factory->getSpellcheck();
            $resultCount = $searchResults->getNumFound();

            $facets = array();
            $facetset = $searchResults->getFacetSet();

            if ( $ajax !== false && $facet_name !== false ) {
                foreach ( $conf_facets as $facet ) {
                    if ( $facet->getSolrFieldName() === $facet_name ) {
                        $conf_facets = array($facet);
                        break;
                    }
                }
            }

            $facet_names = array(
                'geoloc'        => _('Map selection'),
                'cDateBegin'    => _('Start date'),
                'cDateEnd'      => _('End date')
            );
            $facet_labels = array();

            foreach ( $conf_facets as $facet ) {
                $solr_field = $facet->getSolrFieldName();
                $facet_names[$solr_field] = $facet->getLabel($request->getLocale());
                $field_facets = $facetset->getFacet($solr_field);

                $values = array();
                foreach ( $field_facets as $item=>$count ) {
                    if ( !$filters->offsetExists($solr_field)
                        || !$filters->hasValue($solr_field, $item)
                    ) {
                        if ( $solr_field === 'cDate' ) {
                            $start = null;
                            $end = null;

                            if ( strpos('|', $item) !== false ) {
                                list($start, $end) = explode('|', $item);
                            } else {
                                $start = $item;
                            }
                            $bdate = new \DateTime($start);

                            $edate = null;
                            if ( !$end ) {
                                $edate = new \DateTime($start);
                                $edate->add(
                                    new \DateInterval(
                                        'P' . $factory->getDateGap()  . 'Y'
                                    )
                                );
                                $edate->sub(new \DateInterval('PT1S'));
                            } else {
                                $edate = new \DateTime($end);
                            }
                            if ( !isset($facet_labels[$solr_field]) ) {
                                $facet_labels[$solr_field] = array();
                            }

                            $item = $bdate->format('Y-m-d\TH:i:s\Z') . '|' .
                                $edate->format('Y-m-d\TH:i:s\Z');

                            $ys = $bdate->format('Y');
                            $ye = $edate->format('Y');

                            if ( $ys != $ye ) {
                                $facet_labels[$solr_field][$item] = $ys . '-' . $ye;
                            } else {
                                $facet_labels[$solr_field][$item] = $ys;
                            }
                        }
                        $values[$item] = $count;
                    }
                }

                if ( count($values) > 0 ) {
                    if ( $facet->getSolrFieldName() !== 'dao' ) {
                        //facet order
                        $facet_order = $request->get('facet_order');
                        if ( !$facet_order || $facet_order == 0 ) {
                            arsort($values);
                        } else {
                            if ( defined('SORT_FLAG_CASE') ) {
                                ksort($values, SORT_FLAG_CASE | SORT_NATURAL);
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

                    if ( $facet->getSolrFieldName() === 'cDate' ) {
                        if ( count($values) == 1
                            && (in_array(1, $values)
                            || strpos('|', array_keys($values)[0]) === false)
                        ) {
                            $do = false;
                        }
                    }

                    if ( $do ) {
                        //get original URL if any
                        $templateVars['orig_href'] = $request->get('orig_href');
                        $templateVars['facet_order'] = $request->get('facet_order');

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
                foreach ( $geoloc as $field ) {
                    $map_facets[$field] = $facetset->getFacet($field);
                }
            }

            if ( $filters->offsetExists('cDate') ) {
                //set label for current date range filter
                if ( !isset($facet_labels['cDate'])) {
                    $facet_labels['cDate'] = array();
                }

                $cdate = $filters->offsetGet('cDate');
                list($start, $end) = explode('|', $cdate);
                $bdate = new \DateTime($start);
                $edate = new \DateTime($end);

                $ys = $bdate->format('Y');
                $ye = $edate->format('Y');

                if ( $ys != $ye ) {
                    $facet_labels['cDate'][$cdate] = $ys . '-' . $ye;
                } else {
                    $facet_labels['cDate'][$cdate] = $ys;
                }
            }

            if ( count($facet_labels) > 0 ) {
                $templateVars['facet_labels'] = $facet_labels;
            }
            $templateVars['facet_names'] = $facet_names;

            if ( $ajax === false ) {
                $suggestions = $factory->getSuggestions($query_terms);

                $templateVars['resultCount'] = $resultCount;
                $templateVars['resultByPage'] = $view_params->getResultsbyPage();
                $templateVars['totalPages'] = ceil($resultCount/$view_params->getResultsbyPage());
                $templateVars['searchResults'] = $searchResults;
                $templateVars['hlSearchResults'] = $hlSearchResults;
                $templateVars['scSearchResults'] = $scSearchResults;
            }
            $templateVars['facets'] = $facets;

            if ( $ajax === false ) {
                $templateVars['resultStart'] = ($page - 1) * $view_params->getResultsbyPage() + 1;
                $resultEnd = ($page - 1) * $view_params->getResultsbyPage() + $view_params->getResultsbyPage();
                if ( $resultEnd > $resultCount ) {
                    $resultEnd = $resultCount;
                }
                $templateVars['resultEnd'] = $resultEnd;
            }
        } else {
            $form = $this->createForm(new SearchQueryFormType(), new SearchQuery());

            $show_tagcloud = $this->container->getParameter('show_tagcloud');
            if ( $show_tagcloud ) {
                $tagcloud = $factory->getTagCloud($this->getDoctrine()->getManager());

                if ( $tagcloud ) {
                    $templateVars['tagcloud'] = $tagcloud;
                }
            }

            if ( $show_maps ) {
                $query = $this->get("solarium.client")->createSelect();
                $query->setQuery('*:*');
                $query->setStart(0)->setRows(0);

                $facetSet = $query->getFacetSet();
                $facetSet->setLimit(-1);
                $facetSet->setMinCount(1);
                foreach ( $geoloc as $field ) {
                    $facetSet->createFacetField($field)->setField($field);
                }

                $rs = $this->get('solarium.client')->select($query);

                foreach ( $geoloc as $field ) {
                    $map_facets[$field] = $rs->getFacetSet()->getFacet($field);
                }
            }
        }

        if ( $ajax === false ) {
            $slider_dates = $factory->getSliderDates($filters);
            if ( is_array($slider_dates) ) {
                $templateVars = array_merge($templateVars, $slider_dates);
            }
            $by_year = $factory->getResultsByYear();
            $templateVars['by_year'] = $by_year;

            $templateVars['form'] = $form->createView();
            if ( $this->container->get('kernel')->getEnvironment() == 'dev'
                && isset($factory) && $factory->getRequest() !== null
            ) {
                //let's pass Solr raw query to template
                $templateVars['solr_qry'] = $factory->getRequest()->getUri();
            }

            if ( isset($suggestions) && $suggestions->count() > 0 ) {
                $templateVars['suggestions'] = $suggestions;
            }

            if ( $show_maps ) {
                $session->set('map_facets', $map_facets);
                $geojson = $factory->getGeoJson(
                    $map_facets,
                    $this->getDoctrine()
                        ->getRepository('BachIndexationBundle:Geoloc')
                );
                $templateVars['geojson'] = $geojson;
            }

            return $this->render(
                'BachHomeBundle:Default:index.html.twig',
                $templateVars
            );
        } else {
            return $this->render(
                'BachHomeBundle:Default:facet.html.twig',
                $templateVars
            );
        }
    }

    /**
     * Get geographical zones
     *
     * @param stirng $bbox Bounding box
     *
     * @return json
     */
    /*public function getZonesAction($bbox)
    {
        $request = $this->getRequest();
        $session = $request->getSession();
        $factory = $this->get("bach.home.solarium_query_factory");

        $facets_name = $request->get('facets_name');

        if ( !$facets_name ) {
            $facets_name = 'map_facets';
        }

        $geojson = $factory->getGeoJson(
            $session->get($facets_name),
            $this->getDoctrine()
                ->getRepository('BachIndexationBundle:Geoloc'),
            $bbox
        );

        $response = new Response($geojson);
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }*/

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
        $redirectUrl = $this->get('router')->generate('bach_homepage');

        if ($this->getRequest()->isMethod('POST')) {
            $form->bind($this->getRequest());
            if ($form->isValid()) {
                $q = $query->getQuery();
                $redirectUrl = $this->get('router')->generate(
                    'bach_search',
                    array('query_terms' => $q)
                );

                $session = $this->getRequest()->getSession();
                $session->set('filters', null);
            }
        }
        return new RedirectResponse($redirectUrl);
    }

    /**
     * Browse contents
     *
     * @param string  $part     Part to browse
     * @param boolean $show_all Show all results
     * @param boolean $ajax     If we were called from ajax
     *
     * @return void
     */
    public function browseAction($part = '', $show_all = false, $ajax = false)
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

        $templateVars = array(
            'fields'        => $fields,
            'current_field' => $field,
            'part'          => $part
        );

        $lists = array();

        $limit = 20;
        if ( $show_all === 'show_all' ) {
            $limit = -1;
            $templateVars['show_all'] = true;
        } else {
            $templateVars['show_all'] = 'false';
        }

        if ( $part !== '' ) {
            $client = $this->get("solarium.client");
            // get a terms query instance
            $query = $client->createTerms();

            $query->setLimit($limit);

            $query->setFields($part);

            $found_terms = $client->terms($query);
            foreach ( $found_terms as $field=>$terms ) {
                $lists[$field] = array();
                $current_values = array();
                foreach ( $terms as $term=>$count ) {
                    $current_values[$term] = array(
                        'term'  => $term,
                        'count' => $count
                    );
                }
                if ( $show_all === 'show_all' ) {
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
                }
                $lists[$field] = $current_values;
            }
        }

        $templateVars['lists'] = $lists;

        if ( $ajax === false ) {
            $tpl_name = 'browse';
        } else {
            $tpl_name = 'browse_tab_contents';
        }

        return $this->render(
            'BachHomeBundle:Default:' . $tpl_name  . '.html.twig',
            $templateVars
        );
    }

    /**
     * Suggests
     *
     * @return void
     */
    /*public function doSuggestAction()
    {
        $query = $this->get("solarium.client")->createSuggester();
        $query->setQuery(strtolower($this->getRequest()->get('q')));
        $query->setDictionary('suggest');
        $query->setOnlyMorePopular(true);
        $query->setCount(10);
        //$query->setCollate(true);
        $terms = $this->get("solarium.client")->suggester($query)->getResults();

        $suggestions = array();
        foreach ( $terms as $term ) {
            $suggestions = array_merge(
                $suggestions,
                $term->getSuggestions()
            );
        }

        return new JsonResponse($suggestions);
    }*/

    /**
     * Document display
     *
     * @param int     $docid Document unique identifier
     * @param int     $page  Page
     * @param boolean $ajax  Called from ajax
     *
     * @return void
     */
    public function displayDocumentAction($docid, $page = 1, $ajax = false)
    {
        $client = $this->get("solarium.client");
        $query = $client->createSelect();
        $query->setQuery('fragmentid:"' . $docid . '"');
        $query->setFields('headerId, fragment, parents, archDescUnitTitle');
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

        $viewer_uri = $this->container->getParameter('viewer_uri');
        $covers_dir = $this->container->getParameter('covers_dir');

        $docs  = $rs->getDocuments();
        $doc = $docs[0];

        $tpl = '';

        $tplParams = array(
            'docid'         => $docid,
            'document'      => $doc,
            'viewer_uri'    => $viewer_uri,
            'archdesc'      => $doc['archDescUnitTitle']
        );

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
                $tplParams['ariane'] = $ariane;
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

        if ( count($children) > 0 ) {
            $tplParams['count_children'] = $count_children;
            $tplParams['children'] = $children;
            if ( count($children) < $count_children ) {
                $tplParams['totalPages'] = ceil($count_children/$max_results);
                $tplParams['page'] = $page;
            }
        } else {
            $tplParams['children'] = false;
        }

        if ( $ajax === 'ajax' ) {
            $tpl = 'BachHomeBundle:Default:content_display.html.twig';
            $tplParams['ajax'] = true;
        } else {
            $tpl = 'BachHomeBundle:Default:display.html.twig';
            $tplParams['ajax'] = false;
        }

        //retrieve comments
        $query = $this->getDoctrine()->getManager()
            ->createQuery(
                'SELECT c, d FROM BachHomeBundle:Comment c
                JOIN c.eadfile d
                WHERE c.state = :state
                AND d.fragmentid = :docid
                ORDER BY c.creation_date DESC, c.id DESC'
            )->setParameters(
                array(
                    'state' => Comment::PUBLISHED,
                    'docid' => $docid
                )
            );
        $comments = $query->getResult();
        if ( count($comments) > 0 ) {
            $tplParams['comments'] = $comments;
        }

        /** FIXME: find a suitable comportement for the stuff to avoid loops
        $referer = $this->getRequest()->headers->get('referer');
        if ( $referer !== null ) {
            $tplParams['referer'] = $referer;
        }*/

        return $this->render(
            $tpl,
            $tplParams
        );
    }

    /**
     * Display classification scheme
     *
     * @return void
     */
    public function cdcAction()
    {
        $tplParams = array();

        $client = $this->get("solarium.client");
        $query = $client->createSelect();
        $query->setQuery('fragmentid:*_description');
        $query->setFields('cUnittitle, headerId, fragmentid');
        $query->setStart(0)->setRows(1000);

        $rs = $client->select($query);

        $published = new \SimpleXMLElement(
            '<docs></docs>'
        );

        foreach ( $rs as $doc ) {
            $published->addChild($doc->headerId, $doc->cUnittitle);
        }

        $tplParams['docs'] = $published;

        return $this->render(
            'BachHomeBundle:Default:cdc.html.twig',
            $tplParams
        );
    }
}
