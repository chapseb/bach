<?php
/**
 * Bach geoloc admin controller (for Sonata Admin)
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

use Sonata\AdminBundle\Controller\CRUDController as Controller;
use Bach\IndexationBundle\Entity\Toponym;
use Solarium\Exception\HttpException;

/**
 * Bach geoloc admin controller
 *
 * PHP version 5
 *
 * @category Security
 * @package  Bach
 * @author   Johan Cwiklinski <johan.cwiklinski@anaphore.eu>
 * @license  BSD 3-Clause http://opensource.org/licenses/BSD-3-Clause
 * @link     http://anaphore.eu
 */
class GeolocAdminController extends Controller
{
    /**
     * Handle missing geolocalized entries
     *
     * @return void
     */
    public function geolocMissingAction()
    {

        $doctrine = $this->container->get('doctrine');

        $repo = $doctrine->getRepository('BachIndexationBundle:EADIndexes');
        $qb = $repo->createQueryBuilder('a')
            ->select('DISTINCT a.name')
            ->leftJoin(
                'BachIndexationBundle:Geoloc',
                'g',
                'WITH',
                'a.name = g.indexed_name'
            )
            ->where('a.type = :type')
            ->andWhere('g.indexed_name IS NULL')
            ->orWhere('g.found = false')
            ->setParameter('type', 'cGeogname');

        $query = $qb->getQuery();
        $bdd_places = $query->getResult();

        $repo = $doctrine->getRepository(
            'BachIndexationBundle:MatriculesFileFormat'
        );
        $qb = $repo->createQueryBuilder('a')
            ->select('DISTINCT a.lieu_naissance AS name')
            ->leftJoin(
                'BachIndexationBundle:Geoloc',
                'g',
                'WITH',
                'a.lieu_naissance = g.indexed_name'
            )
            ->where('g.indexed_name IS NULL')
            ->orWhere('g.found = false');

        $query = $qb->getQuery();
        $bdd_places = array_merge($bdd_places, $query->getResult());

        $repo = $doctrine->getRepository(
            'BachIndexationBundle:MatriculesFileFormat'
        );
        $qb = $repo->createQueryBuilder('a')
            ->select('DISTINCT a.lieu_enregistrement as name')
            ->leftJoin(
                'BachIndexationBundle:Geoloc',
                'g',
                'WITH',
                'a.lieu_enregistrement = g.indexed_name'
            )
            ->where('g.indexed_name IS NULL')
            ->orWhere('g.found = false');

        $query = $qb->getQuery();
        $bdd_places = array_merge($bdd_places, $query->getResult());

        $repo = $doctrine->getRepository(
            'BachIndexationBundle:MatriculesFileFormat'
        );
        $qb = $repo->createQueryBuilder('a')
            ->select('DISTINCT a.lieu_residence as name')
            ->leftJoin(
                'BachIndexationBundle:Geoloc',
                'g',
                'WITH',
                'a.lieu_residence = g.indexed_name'
            )
            ->where('g.indexed_name IS NULL')
            ->orWhere('g.found = false');

        $query = $qb->getQuery();
        $bdd_places = array_merge($bdd_places, $query->getResult());

        $places = array();
        foreach ( $bdd_places as $p ) {
            //create toponyms & dedup
            if ( !isset($places[$p['name']]) ) {
                try {
                    $places[$p['name']]= new Toponym($p['name']);
                } catch ( \RuntimeException $e ) {
                    //pass
                }
            }
        }

        return $this->render(
            'BachHomeBundle:Admin:geoloc_missing.html.twig',
            array(
                'missing'   => $places
            )
        );
    }

    /**
     * Visualize on map to edit or delete entries
     *
     * @return void
     */
    public function geolocVisualizeAction()
    {
        $known_types = $this->container->getParameter('bach.types');
        foreach ( $known_types as $type ) {
            $geoloc = null;

            switch ( $type ) {
            case 'ead':
                $gf = new \Bach\HomeBundle\Entity\GeolocMainFields;
                $gf = $gf->loadDefaults(
                    $this->getDoctrine()->getManager()
                );
                $geoloc = $gf->getSolrFieldsNames();
                break;
            case 'matricules':
                $gf = new \Bach\HomeBundle\Entity\GeolocMatriculesFields;
                $gf = $gf->loadDefaults(
                    $this->getDoctrine()->getManager()
                );
                $geoloc = $gf->getSolrFieldsNames();
                break;
            default:
                continue;
                break;
            }

            if ( $geoloc !== null ) {
                $client = $this->get('solarium.client.' . $type);
                $query = $client->createSelect();
                $query->setQuery('*:*');
                $query->setStart(0)->setRows(0);

                $facetSet = $query->getFacetSet();
                $facetSet->setLimit(-1);
                $facetSet->setMinCount(1);

                foreach ( $geoloc as $field ) {
                    $facetSet->createFacetField($field)
                        ->setField($field);
                }

                try {
                    $rs = $client->select($query);

                    $facetset = $rs->getFacetSet();
                    foreach ( $geoloc as $field ) {
                        if ( !isset($map_facets[$field]) ) {
                            $map_facets[$field] = $facetset->getFacet($field);
                        } else {
                            $map_facets[$field] = array_merge(
                                $map_facets[$field],
                                $facetset->getFacet($field)
                            );
                        }
                    }
                } catch ( HttpException $ex ) {
                    //empty catch
                }
            }
        }

        return $this->render(
            'BachHomeBundle:Admin:geoloc_visualize.html.twig'
        );
    }
}
