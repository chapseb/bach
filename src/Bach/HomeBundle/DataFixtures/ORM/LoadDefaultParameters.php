<?php
/**
 * Bach defaults parameters fixture
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
 * @author   Sebastien Chaptal <sebastien.chaptal@anaphore.eu>
 * @license  BSD 3-Clause http://opensource.org/licenses/BSD-3-Clause
 * @link     http://anaphore.eu
 */

namespace Bach\HomeBundle\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Bach\HomeBundle\Entity\Parameters;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Bach defaults parameters fixture
 *
 * PHP version 5
 *
 * @category Search
 * @package  Bach
 * @author   Sebastien Chaptal <sebastien.chaptal@anaphore.eu>
 * @license  BSD 3-Clause http://opensource.org/licenses/BSD-3-Clause
 * @link     http://anaphore.eu
 */
class LoadDefaultParameters implements FixtureInterface, ContainerAwareInterface
{

    /*
    * @var ContainerInterface
    */
    private $_container;


    /**
     * Sets container
     *
     * @param ContainerInterface $container Container
     *
     * @return void
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->_container = $container;
    }


    /**
     * Get container
     *
     * @return ContainerInterface
     */
    public function getContainer()
    {
        return $this->_container;
    }

    /**
     * Loads fixture
     *
     * @param ObjectManager $manager Object manager
     *
     * @return void
     */
    public function load(ObjectManager $manager)
    {
        $container = $this->getContainer();

        $arrayParam = array();
        $arrayParam['weight.cUnittitle']    = strval($container->getParameter('weight.cUnittitle'));
        $arrayParam['weight.descriptors']   = strval($container->getParameter('weight.descriptors'));
        $arrayParam['weight.parents_titles']= strval($container->getParameter('weight.parents_titles'));
        $arrayParam['weight.fulltext']      = strval($container->getParameter('weight.fulltext'));

        $arrayParam['feature.tagcloud'] = ($container->getParameter('feature.tagcloud')) ? 'true' : 'false';
        $arrayParam['feature.social']   = ($container->getParameter('feature.social')) ? 'true' : 'false';
        $arrayParam['feature.maps']     = ($container->getParameter('feature.maps')) ? 'true' : 'false';
        $arrayParam['feature.comments'] = ($container->getParameter('feature.comments')) ? 'true' : 'false';

        $arrayParam['display.disable_suggestions'] = ($container->getParameter('display.disable_suggestions')) ? 'true' : 'false';
        $arrayParam['display.disable_searchmap']   = ($container->getParameter('display.disable_searchmap')) ? 'true' : 'false';
        $arrayParam['display.show_maps']           = ($container->getParameter('display.show_maps')) ? 'true' : 'false';
        $arrayParam['display.show_daterange']      = ($container->getParameter('display.show_daterange')) ? 'true' : 'false';
        $arrayParam['display.disable_select_daterange'] = ($container->getParameter('display.disable_select_daterange')) ? 'true' : 'false';
        $arrayParam['display.ead.rows']            = strval($container->getParameter('display.ead.rows'));
        $arrayParam['display.matricules.rows']     = strval($container->getParameter('display.matricules.rows'));
        $arrayParam['display.ead.show_param']      = $container->getParameter('display.ead.show_param');
        $arrayParam['display.matricules.show_param'] = $container->getParameter('display.matricules.show_param');

        $arrayParam['matricules_histogram'] = $container->getParameter('matricules_histogram');
        $arrayParam['centerlat'] = strval($container->getParameter('centerlat'));
        $arrayParam['centerlon'] = strval($container->getParameter('centerlon'));
        $arrayParam['zoommap']   = strval($container->getParameter('zoommap'));

        $arrayParam['label.cdc']        = $container->getParameter('label.cdc');
        $arrayParam['label.matricules'] = $container->getParameter('label.matricules');
        $arrayParam['label.archives']   = $container->getParameter('label.archives');
        $arrayParam['label.browse']     = $container->getParameter('label.browse');
        $arrayParam['label.expos']      = $container->getParameter('label.expos');

        foreach ($arrayParam as $key => $param) {
            $fields = new Parameters();
            $fields->setName($key);
            $fields->setValue($param);
            $manager->persist($fields);
        }

        $manager->flush();
    }
}
