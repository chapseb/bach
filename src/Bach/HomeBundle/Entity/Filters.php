<?php
/**
 * Bach search filters
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

/**
 * Bach search filters
 *
 * @category Search
 * @package  Bach
 * @author   Johan Cwiklinski <johan.cwiklinski@anaphore.eu>
 * @license  BSD 3-Clause http://opensource.org/licenses/BSD-3-Clause
 * @link     http://anaphore.eu
 */
class Filters extends \ArrayObject
{

    /**
     * Bind request
     *
     * @param Request $request Request to bind
     *
     * @return void
     */
    public function bind($request)
    {
        if ( $request->get('rm_filter_field') ) {
            $rm_filter_field = $request->get('rm_filter_field');
            $rm_filter_value = $request->get('rm_filter_value');

            $this->removeFilter($rm_filter_field, $rm_filter_value);
        }

        if ( $request->get('filter_field') ) {
            $filter_fields = $request->get('filter_field');
            $filter_values = $request->get('filter_value');

            if ( !is_array($filter_fields) ) {
                $filter_fields = array($filter_fields);
            }
            if ( !is_array($filter_values) ) {
                $filter_values = array($filter_values);
            }

            if ( count($filter_fields) != count($filter_values) ) {
                throw new \RuntimeException(
                    'Filter fields and values does not match!'
                );
            }

            $count = count($filter_fields);
            for ( $i = 0; $i < $count; $i++ ) {
                $this->addFilter(
                    $filter_fields[$i],
                    $filter_values[$i]
                );
            }
        }

        $qry_params = $request->query->keys();
        foreach ( $qry_params as $qry_p ) {
            if ( preg_match('/(.*)_range_date_(min|max)/', $qry_p, $matches) ) {
                $this->addFilter(
                    'date_' . $matches[1] . '_' . $matches[2],
                    $request->get($qry_p)
                );
            }
        }
    }

    /**
     * Add a filter
     *
     * @param string $field Filter field
     * @param string $value Filter value
     *
     * @return void
     */
    public function addFilter($field, $value, $fromListSearch = false)
    {
        if ( preg_match('/date_.*_(min|max)/', $field, $matches) ) {
            //only one start and end date allowed
            if ($fromListSearch == false) {
                $php_date = \DateTime::createFromFormat('Y', $value);
                if ( $matches[1] === 'min' ) {
                    $value = $php_date->format('Y-01-01');
                } else {
                    $value = $php_date->format('Y-12-31');
                }
            }
            $this->offsetSet($field, $value);
        } else {
            switch ( $field ) {
            case 'dao':
                //avoid mutliple values
                $this->offsetSet($field, $value);
                break;
            default:
                if ( !$this->offsetExists($field) ) {
                    //initialize filter field if it does not exists
                    $this->offsetSet(
                        $field,
                        new \ArrayObject(array($value))
                    );
                } else if ( !$this->hasValue($field, $value) ) {
                    //check if value already exists in current filters
                    $this->offsetGet($field)->append($value);
                }
            }
        }
    }

    /**
     * Remove a filter
     *
     * @param string $field Filter field
     * @param string $value Filter value
     *
     * @return void
     */
    public function removeFilter($field, $value)
    {
        if ( preg_match('/date_.*_(min|max)/', $field) ) {
            $this->offsetUnset($field);
        } else {
            switch ( $field ) {
            case 'dao':
                if ( $this->offsetExists($field) ) {
                    $this->offsetUnset($field);
                }
                break;
            default:
                if ( $this->offsetExists($field)
                    && $this->hasValue($field, $value)
                ) {
                    $offset = $this->offsetGet($field);
                    if ( $offset->count() > 1 ) {
                        $iterator = $offset->getIterator();
                        while ( $iterator->valid() ) {
                            if ( $iterator->current() === $value ) {
                                $offset->offsetUnset($iterator->key());
                                break;
                            }
                            $iterator->next();
                        }
                    } else {
                        //field contains only one value, unset
                        $this->offsetUnset($field);
                    }
                }
            }
        }
    }

    /**
     * Does field filter already contains value?
     *
     * @param string $field Fields name
     * @param string $value Filter value
     *
     * @return boolean
     */
    public function hasValue($field, $value)
    {
        return in_array(
            $value,
            (array)$this->offsetGet($field)
        );
    }
}
