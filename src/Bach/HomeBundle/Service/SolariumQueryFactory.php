<?php
/**
 * Bach Solarium query factory
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

namespace Bach\HomeBundle\Service;

use Symfony\Component\Finder\Finder;
use Bach\HomeBundle\Entity\ViewParams;
use Bach\HomeBundle\Entity\SolariumQueryContainer;
use Bach\HomeBundle\Entity\SolariumQueryDecoratorAbstract;
use Bach\HomeBundle\Entity\Filters;
use Doctrine\ORM\EntityRepository;
use Bach\HomeBundle\Entity\TagCloud;
use Bach\AdministrationBundle\Entity\SolrCore\Fields;
use Solarium\QueryType\Select\Query\Component\FacetSet;

/**
 * Bach Solarium query factory
 *
 * PHP version 5
 *
 * @category Search
 * @package  Bach
 * @author   Johan Cwiklinski <johan.cwiklinski@anaphore.eu>
 * @author   Sebastien Chaptal <sebastien.chaptal@anaphore.eu>
 * @license  BSD 3-Clause http://opensource.org/licenses/BSD-3-Clause
 * @link     http://anaphore.eu
 */
class SolariumQueryFactory
{
    private $_client;
    private $_decorators = array();
    private $_request;
    private $_highlitght;
    private $_spellcheck;
    private $_query;
    private $_rs;
    private $_stats;
    private $_qry_facets_fields = array(
        'dao',
        'cDateBegin',
        'date_enregistrement',
        'classe',
        'annee_naissance'
    );
    private $_query_fields;

    private $_low_date;
    private $_up_date;
    private $_date_gap;

    private $_geoloc;

    private $_date_field;
    private $_dates_fields;
    private $_authorized;

    /**
     * Factory constructor
     *
     * @param \Solarium\Client $client Solarium client
     * @param string           $qf     Query fields
     */
    public function __construct(\Solarium\Client $client, $qf = null, $authorized = null)
    {
        $this->_client = $client;
        if ( $qf !== null ) {
            $this->_query_fields = $qf;
        }
        $this->_searchQueryDecorators();
        $this->_authorized = $authorized;
    }

    /**
     * Prepare solr Query
     *
     * @param SolariumQueryContainer $container Solarium container
     *
     * @return void
     */
    public function prepareQuery(SolariumQueryContainer $container)
    {
        $this->_buildQuery($container);
    }

    /**
     * Perform a query into Solr
     *
     * @param SolariumQueryContainer $container Solarium container
     * @param array                  $facets    Facets
     *
     * @return \Solarium\QueryType\Select\Result\Result
     */
    public function performQuery(SolariumQueryContainer $container, $facets)
    {
        //create query
        if ( !$this->_query ) {
            $this->_buildQuery($container);
        }

        if ( isset($this->_date_field) ) {
            $this->setDatesBounds();
        }

        $facetSet = $this->_query->getFacetSet();
        // Take care of facet date range
        if ( isset($this->_low_date)
            && isset($this->_up_date)
            && isset($this->_date_field)
        ) {
            $fr = $facetSet->createFacetRange(
                'years' .
                ' f.' . $this->_date_field . '.facet.range.start=' .
                $this->_low_date .
                ' f.' . $this->_date_field . '.facet.range.end=' . $this->_up_date .
                ' f.' . $this->_date_field . '.facet.range.gap=+1YEARS'
            );
            $fr->setField($this->_date_field);

            $stats = $this->_query->getStats();
            $stats->createField($this->_date_field);
            if ( $this->_dates_fields != null ) {
                foreach ( $this->_dates_fields as $date_field ) {
                    $stats->createField($date_field);
                }
            }
        }

        //dynamically create facets
        $this->_addFacets($facets, $facetSet);

        $this->_request = $this->_client->createRequest($this->_query);
        $rs = $this->_client->select($this->_query);

        $rsStats = $rs->getStats();
        if ( $rsStats ) {
            $this->_stats = $rsStats->getResults();
        }

        $this->_highlitght = $rs->getHighlighting();
        $this->_spellcheck = $rs->getSpellcheck();
        $this->_rs = $rs;
        return $this->_rs;
    }

    /**
     * Build query
     *
     * @param SolariumQueryContainer $container Solarium container
     *
     * @return void
     */
    private function _buildQuery(SolariumQueryContainer $container)
    {
        $this->_query = $this->_client->createSelect();

        if ($this->_authorized != null
            && !$this->_authorized->archivesRight()
            && !$this->_authorized->warehouseRight()
        ) {
            $this->_query->createFilterQuery("audience")->setQuery(
                '-cAudience:internal'
            );
        }

        $hl = $this->_query->getHighlighting();
        $hl_fields = '';
        $this->_query->getSpellcheck();

        $filters = $container->getFilters();

        if ( count($filters) > 0 ) {
            $begin = '';
            $end = '';
            foreach ( $container->getFilters() as $name=>$value ) {
                // build query with date range to catch result with date range
                if ( preg_match('/(date_(.*)_)(min|max)/', $name, $matches) ) {
                    switch ( $matches[3] ) {
                    case 'min':
                        $end = '*';
                        $e_offset = $matches[1] . 'max';
                        if ( $container->getFilters()->offsetExists($e_offset) ) {
                            $end = $container->getFilters()->offsetGet($e_offset) .
                                'T23:59:59Z';
                        }
                        $this->_query->createFilterQuery($name)
                            ->setQuery(
                                '+' . $matches[2] . ':[' . $value .
                                'T00:00:00Z TO ' . $end . ']'
                            );
                        if ($this->getQuery()->getFilterQuery('date_cDateBegin_min') != null) {
                            $begin = $value . 'T23:59:59Z';
                            $queryBegin = $this->getQuery()->getFilterQuery('date_cDateBegin_min')->getQuery();
                            $queryBegin = explode('+', $queryBegin);
                            $queryBegin = $queryBegin[1];
                            $queryEnd   = str_replace('Begin', 'End', $queryBegin);
                            $finalQuery = '+(' . $queryBegin . ' OR ' . $queryEnd . ')';
                            $this->getQuery()->getFilterQuery('date_cDateBegin_min')->setQuery($finalQuery);
                        }
                        break;
                    case 'max':
                        $b_offset = $matches[1] . 'min';
                        if ( !$container->getFilters()->offsetExists($b_offset) ) {
                            $this->_query->createFilterQuery($name)
                                ->setQuery(
                                    '+' . $matches[2] . ':[* TO ' . $value .
                                    'T23:59:59Z]'
                                );
                        }

                        // dates intervals search
                        if ($this->getQuery()->getFilterQuery('date_cDateBegin_min') != null) {
                            $existingQuery = $this->getQuery()->getFilterQuery('date_cDateBegin_min')->getQuery();
                            $existingQuery = explode('+', $existingQuery);
                            $existingQuery = $existingQuery[1];
                            $AndQuery = '(cDateBegin:[* TO '. $begin .'] AND cDateEnd:[' . $end . ' TO *])';
                            $finalQuery = '+(' . $existingQuery . ' OR ' . $AndQuery.')';
                            $this->getQuery()->getFilterQuery('date_cDateBegin_min')->setQuery($finalQuery);
                        }
                        if ($this->getQuery()->getFilterQuery('date_cDateBegin_max') != null ) {
                            $queryBegin = $this->getQuery()->getFilterQuery('date_cDateBegin_max')->getQuery();
                            $queryBegin = explode('+', $queryBegin);
                            $queryBegin = $queryBegin[1];
                            $queryEnd   = str_replace('Begin', 'End', $queryBegin);
                            $finalQuery = '+(' . $queryBegin . ' OR ' . $queryEnd . ')';
                            $this->getQuery()->getFilterQuery('date_cDateBegin_max')->setQuery($finalQuery);
                        }
                        break;
                    }
                } else {
                    switch ( $name ) {
                    case 'dao':
                        $query = null;
                        if ( $value === _('Yes') ) {
                            $query = '+' . $name . ':*';
                        } else {
                            $query = '-' . $name . ':*';
                        }
                        $this->_query->createFilterQuery($name)
                            ->setQuery($query);
                        break;
                    case 'geoloc':
                        $query = '';
                        foreach ( $value as $v ) {
                            $query .= '+(';
                            foreach ( $this->_geoloc as $field ) {
                                $query .= ' ' . $field . ':"' .
                                    str_replace('"', '\"', $v) . '"';
                            }
                            $query .= ')';
                        }

                        $this->_query->createFilterQuery('geoloc')
                            ->setQuery($query);
                        break;
                    // query for range unitid when exists
                    case 'cUnitidbegin':
                        foreach ( $value as $v ) {
                            $query = '(cUnitidbegin:[* TO ' . $v . '] AND cUnitidend:[' . $v . ' TO *])';
                            $this->getQuery()->createFilterQuery($name)->setQuery($query);
                        }
                        break;
                    default:
                        $i = 0;
                        foreach ( $value as $v ) {
                            $this->_query->createFilterQuery($name . $i)
                                ->setQuery(
                                    '+' . $name . ':"' .
                                    str_replace('"', '\"', $v) . '"'
                                );
                            $i++;
                        }
                        break;
                    }
                }
            }
        }

        if ( $container->isOrdered() ) {
            $qry = $this->_query;
            $order = $container->getOrderField();
            $direction = $qry::SORT_DESC;

            if ($container->getOrderDirection() === ViewParams::ORDER_ASC) {
                $direction = $qry::SORT_ASC;
            }

            if ( is_array($order) ) {
                foreach ( $order as $field) {
                    $this->_query->addSort(
                        $field,
                        $direction
                    );
                }
            } else {
                $this->_query->addSort(
                    $container->getOrderField(),
                    $direction
                );
            }
        }

        $search_form = $container->getSearchForm();
        if ( $search_form !== null ) {
            $filter = $search_form['filter'];
            $this->_query->createFilterQuery('search_form')
                ->setQuery('+(' . $filter  . ')');
        }

        foreach ( $container->getFields() as $name=>$value ) {
            if ( array_key_exists($name, $this->_decorators) ) {
                //Decorate the query
                if ( $search_form !== null && isset($search_form['query_fields']) ) {
                    $this->_decorators[$name]->setQueryFields(
                        $search_form['query_fields']
                    );
                }
                $this->_decorators[$name]->setWeight($container->getWeight());
                $this->_decorators[$name]->decorate($this->_query, $value);
                if ( method_exists($this->_decorators[$name], 'getHlFields') ) {
                    if ( trim($hl_fields) !== '' ) {
                        $hl_fields .=',';
                    }
                    $hl_fields .= $this->_decorators[$name]->getHlFields();
                }
            }
        }

        $hl->setSnippets(10);
        $hl->setFields($hl_fields);
        /** TODO: find a better way to do */
        if ( strpos($hl_fields, 'cUnittitle') !== false ) {
            //on highlithed unititles, we always want the full string
            $hl->getField('cUnittitle')->setFragSize(0);
        }
        if ( strpos($hl_fields, 'parents_titles') !== false ) {
            //on highlithed parents_titles, we always want the full string
            $hl->getField('parents_titles')->setFragSize(0);
        }
        if ( strpos($hl_fields, 'subject_w_expanded') !== false ) {
            //on highlithed parents_titles, we always want the full string
            $hl->getField('subject_w_expanded')->setFragSize(0);
        }
        if ( strpos($hl_fields, 'cSubject') !== false ) {
            //on highlithed cSubject, we always want the full string
            $hl->getField('cSubject')->setFragSize(0);
        }
        if ( strpos($hl_fields, 'cGeogname') !== false ) {
            //on highlithed cSubject, we always want the full string
            $hl->getField('cGeogname')->setFragSize(0);
        }

        if ( $container->noResults() ) {
            $this->_query->setRows(0);
        }
    }

    /**
     * Dynamically add facets to query
     *
     * @param array    $facets   Facets list
     * @param FacetSet $facetSet FacetSet
     *
     * @return void
     */
    private function _addFacets($facets, FacetSet $facetSet)
    {
        $facetSet->setLimit(-1);
        $facetSet->setMinCount(1);
        $map_facet = array();

        if ( count($this->_geoloc) > 0 ) {
            foreach ( $this->_geoloc as $field ) {
                $map_facet[$field] = false;
            }
        }

        foreach ( $facets as $facet ) {
            if ( !in_array($facet->getSolrFieldName(), $this->_qry_facets_fields) ) {
                $facetSet->createFacetField($facet->getSolrFieldName())
                    ->setField($facet->getSolrFieldName());
                if ( isset($map_facet[$facet->getSolrFieldName()]) ) {
                    $map_facet[$facet->getSolrFieldName()] = true;
                }
            } else {
                switch($facet->getSolrFieldName()) {
                case 'dao':
                    $fmq = $facetSet->createFacetMultiQuery('dao');
                    $fmq->createQuery(_('Yes'), 'dao:*');
                    $fmq->createQuery(_('No'), '-dao:*');
                    break;
                case 'cDateBegin':
                case 'classe':
                case 'annee_naissance':
                case 'date_enregistrement':
                    $stats = $this->_query->getStats();
                    $exists  = $stats->getField($facet->getSolrFieldName());
                    if ( !$exists ) {
                        $stats->createField($facet->getSolrFieldName());
                    }
                    break;
                default:
                    throw new \RuntimeException(
                        'Unknown facet query field ' .
                        $facet->getSolrFieldName()  . '!'
                    );
                    break;
                }
            }
        }

        //check if map facet is present
        if ( count($map_facet) > 0 ) {
            foreach ( $map_facet as $key=>$value ) {
                if ( $value === false ) {
                    //add missing geoloc facet
                    $facetSet->createFacetField($key)
                        ->setField($key);
                }
            }
        }
    }

    /**
     * Search existing query decorators
     *
     * @return void
     */
    private function _searchQueryDecorators()
    {
        $finder = new Finder();
        $finder->files()
            ->in(__DIR__.'/../Entity/SolariumQueryDecorator')
            ->depth('== 0')
            ->name('*.php');

        foreach ($finder as $file) {
            try {
                $reflection = new \ReflectionClass(
                    'Bach\HomeBundle\Entity\SolariumQueryDecorator\\'.
                    $file->getBasename(".php")
                );

                $expectedClass = 'Bach\HomeBundle\Entity' .
                    '\SolariumQueryDecoratorAbstract';

                $class = $reflection->getParentClass()->getName();
                $pclass = null;
                if ( $reflection->getParentClass()->getParentClass() !== false ) {
                    $pclass = $reflection->getParentClass()
                        ->getParentClass()->getName();
                }
                if ( $expectedClass == $class || $expectedClass == $pclass ) {
                    $this->_registerQueryDecorator(
                        $reflection->newInstance($this->_query_fields)
                    );
                }
            } catch(\RuntimeException $e) {
            }
        }
    }

    /**
     * Get number of results per year, to draw plot
     *
     * @return array
     */
    public function getResultsByYear()
    {
        if ( !isset($this->_rs) ) {
            throw new \RuntimeException(
                'A query must have been performed already.'
            );
        }

        $facetSet = $this->_rs->getFacetSet();
        $dates = $facetSet->getFacet('years');

        $results = array();
        foreach ( $dates as $d=>$count ) {
            $_date = new \DateTime($d);
            $results[] = array(
                (string)$_date->format('Y'),
                $count
            );
        }

        return $results;
    }

    /**
     * Retrieve GeoJSON informations
     *
     * @param array            $map_facets Map facets
     * @param EntityRepository $repo       Entity repository
     *
     * @return array
     */
    public function getGeoJson($map_facets, EntityRepository $repo)
    {
        $result = array();
        $labels = array();
        $values = array();
        $all_values = array();
        $parameters = array();

        if ( count($map_facets) > 0 ) {
            $solr_fields = new Fields();
            foreach ( $map_facets as $field=>$facet ) {
                $values[$field] = null;
                $labels[$field] = $solr_fields->getFieldLabel($field);
                foreach ( $facet as $item=>$count ) {
                    if ( !isset($values[$field][$item]) ) {
                        $values[$field][$item] = $count;
                    } else {
                        $values[$field][$item] += $count;
                    }
                }
            }

            foreach ( array_keys($values) as $key ) {
                if ( is_array($values[$key]) ) {
                    $all_values = array_merge($all_values, $values[$key]);
                }
            }

            if ( count($all_values) > 0 ) {
                $qb = $repo->createQueryBuilder('g');
                $qb->where('g.indexed_name IN (:names)')
                    ->andWhere('g.found = true');
                $parameters['names'] = array_keys($all_values);

                $qb->setParameters($parameters);

                $query = $qb->getQuery();
                $polygons = $query->getResult();

                //prepare json
                if ( count($polygons) > 0 ) {
                    foreach ( $values as $field=>$value ) {
                        $results = array();

                        foreach ( $polygons as $polygon ) {
                            $id = $polygon->getId();
                            $name = $polygon->getIndexedName();

                            if ( isset($value[$name]) ) {
                                if ( !isset($value[$name]) ) {
                                    //for MySQL, Vénéjan is the same as Venejan...
                                    continue;
                                }

                                $lon = $polygon->getLon();
                                $lat = $polygon->getLat();
                                $geometry = array(
                                    'type'          => 'Point',
                                    'coordinates'   => array(
                                        (float)$lon,
                                        (float)$lat
                                    )
                                );

                                $count = $value[$name];
                                $results[] = array(
                                    'type'          => 'Feature',
                                    'id'            => (string)$id,
                                    'properties'    => array(
                                        'name'      => $name,
                                        'results'   => $count
                                    ),
                                    'geometry'  => $geometry
                                );
                            }
                        }
                        if ( count($results) > 0 ) {
                            $result[$field] = array(
                                'type'      => 'FeatureCollection',
                                'features'  => $results
                            );
                        }
                    }
                }
            }
        }

        return array(
            'labels'    => $labels,
            'data'      => $result
        );
    }

    /**
     * Load dates bounds from index stats
     *
     * @return array
     */
    private function _loadDatesFromStats()
    {
        $query = clone $this->_query;
        $query->setRows(0);
        $stats = $query->getStats();

        $stats->createField($this->_date_field);

        if ( $this->_dates_fields != null ) {
            foreach ( $this->_dates_fields as $date_field ) {
                $stats->createField($date_field);
            }
        }
        $rs = $this->_client->select($query);
        $rsStats = $rs->getStats();
        $statsResults = $rsStats->getResults();
        $dates_min_max = array();
        foreach ( $statsResults as $date_field => $statResult ) {
            $min_date = null;
            $max_date = null;
            if ( isset($statsResults[$this->_date_field]) ) {
                $min_date = $statsResults[$date_field]->getMin();
                $max_date = $statsResults[$date_field]->getMax();
            }
            $dates_min_max[$date_field]['min_date'] = new \DateTime($min_date);
            $dates_min_max[$date_field]['max_date'] = new \DateTime($max_date);
        }

        return $dates_min_max;
    }

    /**
     * Register query decorator
     *
     * @param SolariumQueryDecoratorAbstract $decorator Decorator
     *
     * @return void
     */
    private function _registerQueryDecorator(
        SolariumQueryDecoratorAbstract $decorator
    ) {
        $this->_decorators[$decorator->getTargetField()] = $decorator;
    }

    /**
     * Get executed solr query
     *
     * @return mixed
     */
    public function getRequest()
    {
        return $this->_request;
    }

    /**
     * Get highlighted results
     *
     * @return Solarium\QueryType\Select\Result\Highlighting\Highlighting
     */
    public function getHighlighting()
    {
        return $this->_highlitght;
    }

    /**
     * Get spellcheck results
     *
     * @return Solarium\QueryType\Select\Result\Highlighting\Highlighting
     */
    public function getSpellcheck()
    {
        return $this->_spellcheck;
    }

    /**
     * Get query
     *
     * @return Solarium\Query\Select
     */
    public function getQuery()
    {
        return $this->_query;
    }

    /**
     * Get resultset
     *
     * @return ResultSet
     */
    public function getResultset()
    {
        return $this->_rs;
    }

    /**
     * Get stats
     *
     * @return Solarium\QueryType\Select\Result\Stats\Result[]
     */
    public function getStats()
    {
        return $this->_stats;
    }

    /**
     * Set dates bounds
     *
     * @return void
     */
    public function setDatesBounds()
    {
        $stats = $this->_loadDatesFromStats();
        $low = $stats[$this->_date_field]['min_date'];
        $up = $stats[$this->_date_field]['max_date'];

        if ( !isset($this->_low_date) || !isset($this->_up_date) ) {
            $this->_low_date = $low->format('Y-01-01') . 'T00:00:00Z';
            $this->_up_date = $up->format('Y-12-31') . 'T23:59:59Z';
        }
    }

    /**
     * Calculate date gap
     *
     * @param DateTime $start   Start date
     * @param DateTime $end     End date
     * @param int      $maxdiff Maxium diff years
     *
     * @return int
     */
    private function _getGap(\DateTime $start, \DateTime $end, $maxdiff = 10)
    {
        $diff = $start->diff($end);
        $gap = 1;
        if ( $diff->y > $maxdiff ) {
            $gap = ceil($diff->y / $maxdiff);
        }
        return $gap;
    }

    /**
     * Set geolocalization fields
     *
     * @param array $fields Geoloc fields
     *
     * @return void
     */
    public function setGeolocFields($fields)
    {
        $this->_geoloc = $fields;
    }

    /**
     * Set date field
     *
     * @param string $field Field name
     *
     * @return void
     */
    public function setDateField($field)
    {
        $this->_date_field = $field;
    }

    /**
     * Set dates fields
     *
     * @param array $fields Fields name
     *
     * @return void
     */
    public function setDatesFields($fields)
    {
        $this->_dates_fields = $fields;
    }


    /**
     * Get tag cloud
     *
     * @param EntityManager $em          Doctrine entity manager
     * @param array         $search_form Search form parameters
     *
     * @return array
     */
    public function getTagCloud($em, $search_form = null)
    {
        $tagcloud = new TagCloud();
        $tagcloud = $tagcloud->loadCloud($em);

        $tag_max = $tagcloud->getNumber();

        $query = $this->_client->createSelect();
        $query->setQuery('*:*');
        $query->setStart(0)->setRows(0);

        if ( $search_form !== null ) {
            $query->createFilterQuery('search_form')
                ->setQuery('+(' . $search_form['filter'] . ')');
        }

        $facetSet = $query->getFacetSet();
        $facetSet->setLimit($tag_max);
        $facetSet->setMinCount(1);

        $fields = $tagcloud->getSolrFieldsNames();
        foreach ( $fields as $field ) {
            $facetSet->createFacetField($field)->setField($field);
        }
        $rs = $this->_client->select($query);

        $tags = array();
        foreach ( $fields as $field ) {
            $facet = $rs->getFacetSet()->getFacet($field);
            $tags = array_merge($tags, $facet->getValues());
        }

        if ( count($tags) > 0 ) {
            arsort($tags, SORT_NUMERIC);

            $values = array_values($tags);
            $max = $values[0];
            $min = null;
            if ( count($values) < $tag_max ) {
                $min = $values[count($values)-1];
            } else {
                $min = $values[$tag_max-1];
            }

            //5 levels
            $range = ($max - $min) / 5;
            if ( $range === 0 ) {
                $range = 5;
            }

            $cloud = array();
            $i = 0;
            //loop through returned result and normalize keyword hit counts
            foreach ( $tags as $keyword=>$weight ) {
                if ( $i === $tag_max ) {
                    break;
                }

                $cloud[$keyword] = floor($weight/$range);
                $i++;
            }

            if ( defined('SORT_FLAG_CASE') ) {
                //TODO: find locale!
                setlocale(LC_COLLATE, 'fr_FR.utf8');
                ksort($cloud, SORT_LOCALE_STRING | SORT_FLAG_CASE);
            } else {
                //fallback for PHP < 5.4
                ksort($cloud, SORT_LOCALE_STRING);
            }
            return $cloud;
        }
    }

    /**
     * Get suggestions
     *
     * @param string $terms Query terms
     *
     * @return array
     */
    public function getSuggestions($terms)
    {
        $query = $this->_client->createSuggester();
        $query->setQuery(strtolower($terms));
        $query->setDictionary('suggest');
        $query->setOnlyMorePopular(true);
        $query->setCount(10);
        $suggestions = $this->_client->suggester($query);
        return $suggestions;
    }
}
