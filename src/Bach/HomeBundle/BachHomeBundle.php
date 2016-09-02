<?php
/**
 * Bach HomeBundle
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

namespace Bach\HomeBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Bach\HomeBundle\DependencyInjection\Compiler\AddDependencyCallsCompilerPass;

use Presta\SitemapBundle\Event\SitemapPopulateEvent;
use Presta\SitemapBundle\Sitemap\Url\UrlConcrete;

/**
 * Bach HomeBundle
 *
 * @category Search
 * @package  Bach
 * @author   Johan Cwiklinski <johan.cwiklinski@anaphore.eu>
 * @license  BSD 3-Clause http://opensource.org/licenses/BSD-3-Clause
 * @link     http://anaphore.eu
 */
class BachHomeBundle extends Bundle
{

    /**
     * Builds the bundle.
     *
     * It is only ever called once when the cache is empty.
     *
     * This method can be overridden to register compilation passes,
     * other extensions, ...
     *
     * @param ContainerBuilder $container A ContainerBuilder instance
     *
     * @return void
     */
    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(new AddDependencyCallsCompilerPass());
    }

    /**
     * Boots the bundle
     *
     * @return void
     */
    public function boot()
    {
        $router = $this->container->get('router');
        $event = $this->container->get('event_dispatcher');
        $em = $this->container->get('doctrine.orm.entity_manager');

        //listen presta_sitemap.populate event
        $event->addListener(
            SitemapPopulateEvent::ON_SITEMAP_POPULATE,
            function (SitemapPopulateEvent $event) use ($router, $em) {
                $bach_url = $this->container->getParameter('bach_url');
                //get absolute homepage url
                $url = $bach_url . $router->generate('bach_homepage');

                if ( $this->container->getParameter('feature.archives') === true ) {
                    //main search URL
                    $url = $bach_url . $router->generate('federate_search');

                    //add homepage url to the urlset named default
                    $event->getGenerator()->addUrl(
                        new UrlConcrete(
                            $url,
                            new \DateTime(),
                            UrlConcrete::CHANGEFREQ_WEEKLY,
                            1
                        ),
                        'default'
                    );

                    //if enabled, add classification scheme URL
                    if ( $this->container->getParameter('feature.cdc') === true ) {
                        $url = $bach_url . $router->generate('bach_classification');
                        $event->getGenerator()->addUrl(
                            new UrlConcrete(
                                $url,
                                new \DateTime(),
                                UrlConcrete::CHANGEFREQ_WEEKLY,
                                1
                            ),
                            'default'
                        );
                    }

                    //ead HTML documents
                    $query = $em->createQuery(
                        'SELECT h.headerId, h.updated '.
                        'FROM BachIndexationBundle:EADHeader h'
                    );
                    $elements = $query->getResult();

                    foreach ( $elements as $elt ) {
                        $url = $bach_url . $router->generate(
                            'bach_ead_html',
                            array(
                                'docid' => $elt['headerId']
                            )
                        );

                        $event->getGenerator()->addUrl(
                            new UrlConcrete(
                                $url,
                                new \DateTime(),
                                UrlConcrete::CHANGEFREQ_WEEKLY,
                                1
                            ),
                            'archives'
                        );
                    }

                    //add archives URLs
                    $query = $em->createQuery(
                        'SELECT f.fragmentid, f.updated FROM ' .
                        'BachIndexationBundle:EADFileFormat f'
                    );
                    $elements = $query->getResult();

                    foreach ( $elements as $elt ) {
                        $url = $bach_url . $router->generate(
                            'bach_display_document',
                            array(
                                'docid' => $elt['fragmentid']
                            )
                        );

                        $event->getGenerator()->addUrl(
                            new UrlConcrete(
                                $url,
                                new \DateTime(),
                                UrlConcrete::CHANGEFREQ_WEEKLY,
                                1
                            ),
                            'archives'
                        );
                    }
                }

                //if enabled, add matricules URLs
                if ( $this->container->getParameter('feature.matricules') === true ) {
                    $url = $bach_url . $router->generate('bach_matricules');

                    $event->getGenerator()->addUrl(
                        new UrlConcrete(
                            $url,
                            new \DateTime(),
                            UrlConcrete::CHANGEFREQ_WEEKLY,
                            1
                        ),
                        'matricules'
                    );

                    //add matricules URLs
                    $query = $em->createQuery(
                        'SELECT m.id, m.updated FROM ' .
                        ' BachIndexationBundle:MatriculesFileFormat m'
                    );
                    $elements = $query->getResult();
                    foreach ( $elements as $elt ) {
                        $url = $bach_url . $router->generate(
                            'bach_display_matricules',
                            array(
                                'docid' => $elt['id']
                            )
                        );

                        $event->getGenerator()->addUrl(
                            new UrlConcrete(
                                $url,
                                new \DateTime(),
                                UrlConcrete::CHANGEFREQ_WEEKLY,
                                1
                            ),
                            'matricules'
                        );
                    }
                }

                if ( $this->container->getParameter('feature.expos') === true ) {
                    //TODO: if enabled, add expos URLs
                }

                //add browse URLs, if any
                if ( $this->container->getParameter('feature.archives') === true
                    && $this->container->getParameter('feature.browse') === true
                ) {
                    $query = $em->createQuery(
                        'SELECT b.solr_field_name ' .
                        'FROM BachHomeBundle:BrowseFields b ' .
                        'WHERE b.active=true ORDER BY b.position'
                    );
                    $elements = $query->getResult();
                    if ( count($elements) > 0) {
                        foreach ( $elements as $elt ) {
                            $url = $bach_url . $router->generate(
                                'bach_browse',
                                array(
                                    'part' => $elt['solr_field_name']
                                )
                            );

                            $event->getGenerator()->addUrl(
                                new UrlConcrete(
                                    $url,
                                    new \DateTime(),
                                    UrlConcrete::CHANGEFREQ_WEEKLY,
                                    1
                                ),
                                'descriptors'
                            );
                        }
                    }
                }

                //What else?
            }
        );
    }
}
