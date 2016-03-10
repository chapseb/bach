<?php
/**
 * Bach Solarium query container
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

namespace Bach\HomeBundle\Entity;

use Bach\HomeBundle\Entity\Filters;

/**
 * Bach Solarium query container
 *
 * PHP version 5
 *
 * @category Search
 * @package  Bach
 * @author   Johan Cwiklinski <johan.cwiklinski@anaphore.eu>
 * @license  BSD 3-Clause http://opensource.org/licenses/BSD-3-Clause
 * @link     http://anaphore.eu
 */
class SolariumQueryContainer
{
    private $_fields = array();
    private $_filters;
    private $_order = ViewParams::ORDER_RELEVANCE;
    private $_search_form;
    private $_no_results;
    private $_weight;

    /**
     * Set field
     *
     * @param string $name  Field name
     * @param string $value Field value
     *
     * @return void
     */
    public function setField($name, $value)
    {
        $this->_fields[$name] = $value;
    }

    /**
     * Set filters
     *
     * @param Filters $filters Filters
     *
     * @return void
     */
    public function setFilters(Filters $filters)
    {
        $this->_filters = $filters;
    }

    /**
     * Set order
     *
     * @param int $order Order
     *
     * @return void
     */
    public function setOrder($order)
    {
        $this->_order = $order;
    }

    /**
     * Get field
     *
     * @param string $name Field name
     *
     * @return string
     */
    public function getField($name)
    {
        return $this->_fields[$name];
    }

    /**
     * Get filter
     *
     * @param string $name Field name
     *
     * @return string
     */
    public function getFilter($name)
    {
        return $this->_filters[$name];
    }

    /**
     * Get weight
     *
     * @return array
     */
    public function getWeight()
    {
        return $this->_weight;
    }

    /**
     * Set Weight
     *
     * @param array $weight parameter weight in query
     *
     * @return array
     */
    public function setWeight($weight)
    {
        $this->_weight = $weight;
    }


    /**
     * Is field known?
     *
     * @param string $name Field name
     *
     * @return boolean
     */
    public function hasField($name)
    {
        return array_key_exists($name, $this->_fields);
    }

    /**
     * Get fields
     *
     * @return array
     */
    public function getFields()
    {
        return $this->_fields;
    }

    /**
     * Get filters
     *
     * @return array
     */
    public function getFilters()
    {
        return $this->_filters;
    }

    /**
     * Get order
     *
     * @return int
     */
    public function getOrderField()
    {
        switch ( $this->_order ) {
        case ViewParams::ORDER_TITLE:
            return 'ocUnittitle';
            break;
        case ViewParams::ORDER_DOC_LOGIC:
            return array('archDescUnitTitle', 'elt_order');
            break;
        case ViewParams::ORDER_CHRONO:
            return 'cDateBegin';
            break;
        case MatriculesViewParams::ORDER_MATRICULE:
            return 'matricule';
            break;
        case MatriculesViewParams::ORDER_NAME:
            return 'oNom';
            break;
        case MatriculesViewParams::ORDER_SURNAME:
            return 'oTxt_prenoms';
            break;
        case MatriculesViewParams::ORDER_BIRTHYEAR:
            return 'annee_naissance';
            break;
        case MatriculesViewParams::ORDER_BIRTHPLACE:
            return 'oLieu_naissance';
            break;
        case MatriculesViewParams::ORDER_CLASS:
            return 'classe';
            break;
        case MatriculesViewParams::ORDER_RECORDPLACE:
            return 'oLieu_enregistrement';
            break;
        case MatriculesViewParams::ORDER_RESIDENCEPLACE:
            return 'oLieu_residence';
            break;
        case MatriculesViewParams::ORDER_RECORDYEAR:
            return 'date_enregistrement';
            break;
        case MatriculesViewParams::ORDER_COTE:
            return 'cote';
            break;
        }
        return $this->_order;
    }

    /**
     * Get order direction
     *
     * @return string
     */
    public function getOrderDirection()
    {
        return ViewParams::ORDER_ASC;
    }

    /**
     * Should query be ordered?
     *
     * @return boolean
     */
    public function isOrdered()
    {
        if ( $this->_order !== ViewParams::ORDER_RELEVANCE ) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Set search form configuration
     *
     * @param string $config Search form configuration
     *
     * @return void
     */
    public function setSearchForm($config)
    {
        $this->_search_form = $config;
    }

    /**
     * Get search form configuration
     *
     * @return array
     */
    public function getSearchForm()
    {
        return $this->_search_form;
    }

    /**
     * Explicitely retrieve no results rows
     *
     * @return void
     */
    public function setNoResults()
    {
        $this->_no_results = true;
    }

    /**
     * Do query should retrieve results rows or not
     *
     * @return boolean
     */
    public function noResults()
    {
        return $this->_no_results;
    }
}
