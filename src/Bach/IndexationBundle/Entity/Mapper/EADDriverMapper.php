<?php
/**
 * Mapper for EAD data
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
 * @category Indexation
 * @package  Bach
 * @author   Johan Cwiklinski <johan.cwiklinski@anaphore.eu>
 * @license  BSD 3-Clause http://opensource.org/licenses/BSD-3-Clause
 * @link     http://anaphore.eu
 */

namespace Bach\IndexationBundle\Entity\Mapper;

use Bach\IndexationBundle\DriverMapperInterface;
use Bach\IndexationBundle\Entity\EADFileFormat;

/**
 * Mapper for EAD data
 *
 * PHP version 5
 *
 * @category Indexation
 * @package  Bach
 * @author   Johan Cwiklinski <johan.cwiklinski@anaphore.eu>
 * @license  BSD 3-Clause http://opensource.org/licenses/BSD-3-Clause
 * @link     http://anaphore.eu
 */
class EADDriverMapper implements DriverMapperInterface
{
    private $_eadid;

    /**
     * Translate eadheader
     *
     * @param array $data EAD header data
     *
     * @return array
     */
    public function translateHeader($data)
    {
        $mapped_data = array();

         $header_elements = array(
            'headerId' => 'eadid',
            'headerTitle' => 'filedesc/titlestmt/titleproper',
            'headerAuthor' => 'filedesc/titlestmt/author',
            'headerDate'    => 'filedesc/publicationstmt/date',
            'headerPublisher'   => 'filedesc/publicationstmt/publisher',
            'headerAddress'     => 'filedesc/publicationstmt/address/addressline',
            'headerLanguage'    => 'profiledesc/langusage/language'
        );

        foreach ( $header_elements as $map=>$element ) {
            if ( array_key_exists($element, $data)
                && $map !== 'headerLanguage'
                && isset($data[$element][0])
            ) {
                $mapped_data[$map] = $data[$element][0]['value'];
            } else if ( array_key_exists($element, $data)
                && isset($data[$element][0])
                && $map === 'headerLanguage'
                && array_key_exists(
                    'langcode',
                    $data[$element][0]['attributes']
                )
            ) {
                $mapped_data[$map]
                    = $data[$element][0]['attributes']['langcode'];
            }
        }

        return $mapped_data;
    }

    /**
     * Translate archdesc
     *
     * @param array $data Archdesc data
     *
     * @return array
     */
    public function translateArchdesc($data)
    {
        $mapped_data = array();

        $archdesc_elements = array(
            'cUnitId'           => 'did/unitid',
            'cUnitidbegin'      => 'did/unitid/[@type=\'numDebut\']',
            'cUnitidend'        => 'did/unitid/[@type=\"numFin\"]',
            'cUnitTitle'        => 'did/unittitle',
            'cDate'             => 'did/unitdate|did/unittitle/unitdate',
            'cScopeContent'     => 'scopecontent',
            'cControlacces'     => 'controlacces',
            'fragment'          => 'fragment'
        );

        foreach ( $archdesc_elements as $map=>$element ) {
            if ( array_key_exists($element, $data) ) {
                $mapped_data[$map] = $data['c'][$element][0]['value'];
            }
        }

        $mapped_data['fragmentid'] = $this->_eadid . $data['id'];

        return $mapped_data;
    }

    /**
     * Set eadid
     *
     * @param string $id eadid
     *
     * @return void
     */
    public function setEadId($id)
    {
        $this->_eadid = $id;
    }

    /**
     * Translate elements
     *
     * @param array $data Document data
     *
     * @return array
     */
    public function translate($data)
    {
        $mapped_data = array();

        $ead_elements = array(
            'cUnitid'       => 'did/unitid',
            'cUnitidbegin'  => 'did/unitid/[@type="numDebut"]',
            'cUnitidend'    => 'did/unitid/[@type="numFin"]',
            'cUnittitle'    => 'did/unittitle',
            'cScopcontent'  => 'scopecontent',
            'cControlacces' => 'controlacces',
            'cLegalstatus'  => 'accessrestrict//legalstatus[1]',
            'cRepository'   => 'did/repository'
        );

        if ( array_key_exists('parents', $data['c']) ) {
            $mapped_data['parents'] = implode(
                '/',
                array_keys($data['c']['parents'])
            );
            $mapped_data['parents_titles'] = $data['c']['parents'];
        }

        $mapped_data['fragmentid'] = $this->_eadid . '_' . $data['id'];

        foreach ( $ead_elements as $map=>$element ) {
            if ( array_key_exists($element, $data['c'])
                && count($data['c'][$element])
                && $element !== 'parents'
                || array_key_exists($element, $data['c'])
                && $element === 'parents'
            ) {
                $mapped_data[$map] = $data['c'][$element][0]['value'];
            }
            if (isset($data['c']['did/unitid']) and ($map == 'cUnitidbegin' or $map == 'cUnitidend')) {
                foreach ($data['c']['did/unitid'] as $key=>$value) {
                    if ((isset($value['attributes']['type']))
                        && (($value['attributes']['type'] == 'numDebut'
                        && $map == 'cUnitidbegin')
                        || ($value['attributes']['type'] == 'numFin'
                        && $map == 'cUnitidend'))
                    ) {
                        $mapped_data[$map] = $value['value'];
                    }
                }
            }
        }

        $ead_mulitple_elements = array(
            'cCorpname'    => './/controlaccess//corpname',
            'cFamname'     => './/controlaccess//famname',
            'cGenreform'   => './/controlaccess//genreform',
            'cGeogname'    => './/controlaccess//geogname',
            'cName'        => './/controlaccess//name',
            'cPersname'    => './/controlaccess//persname',
            'cSubject'     => './/controlaccess//subject',
            'cDate'        => 'did/unitdate|did/unittitle/unitdate',
            'fragment'     => 'fragment',
            'daolist'      => './/daoloc|.//dao|.//archref[not(contains(@href, \'http://\')) and contains(@href, \'.pdf\')]',
            'cTitle'       => './/controlaccess//title',
            'cFunction'    => './/controlaccess//function',
            'geolocalized' => './/controlaccess//geogname[@latitude and @longitude]'
        );
        $descriptors = EADFileFormat::$descriptors;

        foreach ( $ead_mulitple_elements as $map=>$element ) {
            if ( array_key_exists($element, $data['c'])
                && count($data['c'][$element])
            ) {
                if (in_array($map, $descriptors) ) {
                    $mapped_data['descriptors'][$map] = $data['c'][$element];
                } else {
                    $mapped_data[$map] = $data['c'][$element];
                }
            }
        }

        //c elements order
        if ( isset($data['c']['order']) ) {
            $mapped_data['elt_order'] = $data['c']['order'];
        }

        //previous/next composants
        if ( isset($data['c']['previous']) ) {
            $mapped_data['previous_id'] = $data['c']['previous']['id'];
            $mapped_data['previous_title'] = $data['c']['previous']['title'];
        }
        if ( isset($data['c']['next']) ) {
            $mapped_data['next_id'] = $data['c']['next']['id'];
            $mapped_data['next_title'] = $data['c']['next']['title'];
        }

        return $mapped_data;
    }
}
