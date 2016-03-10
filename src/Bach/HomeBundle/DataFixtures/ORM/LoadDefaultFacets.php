<?php
/**
 * Bach defaults facets fixture
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

namespace Bach\HomeBundle\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Bach\HomeBundle\Entity\Facets;

/**
 * Bach defaults facets fixture
 *
 * PHP version 5
 *
 * @category Search
 * @package  Bach
 * @author   Johan Cwiklinski <johan.cwiklinski@anaphore.eu>
 * @license  BSD 3-Clause http://opensource.org/licenses/BSD-3-Clause
 * @link     http://anaphore.eu
 */
class LoadDefaultFacets implements FixtureInterface
{
    /**
     * Loads fixture
     *
     * @param ObjectManager $manager Object manager
     *
     * @return void
     */
    public function load(ObjectManager $manager)
    {
        $defaults = array(
            array(
                'field'     => 'archDescUnitTitle',
                'fr_label'  => 'Document',
                'en_label'  => 'Document',
                'form'      => 'main'
            ),
            array(
                'field'     => 'cSubject',
                'fr_label'  => 'Sujet',
                'en_label'  => 'Subject',
                'form'      => 'main'
            ),
            array(
                'field'     => 'cPersname',
                'fr_label'  => 'Personne',
                'en_label'  => 'People',
                'form'      => 'main'
            ),
            array(
                'field'     => 'cGeogname',
                'fr_label'  => 'Lieu',
                'en_label'  => 'Place',
                'form'      => 'main'
            ),
            array(
                'field'     => 'dyndescr_cGenreform_liste-niveau',
                'fr_label'  => 'Niveau',
                'en_label'  => 'Level',
                'form'      => 'main'
            ),
            array(
                'field'     => 'nom',
                'fr_label'  => 'Nom',
                'en_label'  => 'Name',
                'form'      => 'matricules'
            ),
            array(
                'field'     => 'prenoms',
                'fr_label'  => 'Prénom',
                'en_label'  => 'Surname',
                'form'      => 'matricules'
            ),
            array(
                'field'     => 'classe',
                'fr_label'  => 'Classe',
                'en_label'  => 'Class',
                'form'      => 'matricules'
            ),
            array(
                'field'     => 'lieu_naissance',
                'fr_label'  => 'Lieu de naissance',
                'en_label'  => 'Place of birth',
                'form'      => 'matricules'
            ),
            array(
                'field'     => 'lieu_enregistrement',
                'fr_label'  => 'Lieu d\'enregistrement',
                'en_label'  => 'Place of recording',
                'form'      => 'matricules'
            ),
            array(
                'field'     => 'lieu_residence',
                'fr_label'  => 'Lieu de résidence',
                'en_label'  => 'Place of residence',
                'form'      => 'matricules'
            )
        );

        for ( $i = 0; $i < count($defaults); $i++ ) {
            $facet = new Facets();
            $data = $defaults[$i];
            $facet->setSolrFieldName($data['field']);
            $facet->setFrLabel($data['fr_label']);
            $facet->setEnLabel($data['en_label']);
            $facet->setActive(true);
            $facet->setOnHome(false);
            $facet->setForm($data['form']);
            $facet->setPosition($i);
            $manager->persist($facet);
        }

        $manager->flush();
    }
}
