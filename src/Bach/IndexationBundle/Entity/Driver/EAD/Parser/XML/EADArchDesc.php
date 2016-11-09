<?php
/**
 * EAD archdesc processing
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
 * @author   Anaphore PI Team <uknown@unknown.com>
 * @author   Johan Cwiklinski <johan.cwiklinski@anaphore.eu>
 * @license  BSD 3-Clause http://opensource.org/licenses/BSD-3-Clause
 * @link     http://anaphore.eu
 */

namespace Bach\IndexationBundle\Entity\Driver\EAD\Parser\XML;

use Bach\IndexationBundle\Entity\EADFileFormat;
/**
 * EAD archdesc processing
 *
 * PHP version 5
 *
 * @category Indexation
 * @package  Bach
 * @author   Anaphore PI Team <uknown@unknown.com>
 * @author   Johan Cwiklinski <johan.cwiklinski@anaphore.eu>
 * @license  BSD 3-Clause http://opensource.org/licenses/BSD-3-Clause
 * @link     http://anaphore.eu
 */
class EADArchDesc
{
    private $_xpath;
    private $_values = array();
    private $_cnodes = 'c|c01|c02|c03|c04|c05|c06|c07|c08|c09|c10|c11|c12|dsc';
    private $_heritage;

    /**
     * Constructor
     *
     * @param DOMXPath $xpath        XPath
     * @param DOMNode  $archDescNode XML archdesc node
     * @param array    $fields       Known fields
     * @param boolean  $heritage     Heritage status
     */
    public function __construct(\DOMXPath $xpath, \DOMNode $archDescNode, $fields,
        $heritage
    ) {
        $this->_xpath = $xpath;
        $this->_heritage = $heritage;
        $this->_parse($archDescNode, $fields);
    }

    /**
     * Getter
     *
     * @param string $name Property name to retrieve
     *
     * @return mixed
     */
    public function __get($name)
    {
        if ( array_key_exists(strtolower($name), $this->_values) ) {
            return $this->_values[strtolower($name)];
        } else {
            return null;
        }
    }

    /**
     * Get values
     *
     * @return array
     */
    public function getValues()
    {
        return $this->_values;
    }

    /**
     * Parse XML
     *
     * @param DOMNode $archDescNode XML archdesc node
     * @param array   $fields       Known fields
     *
     * @return void
     */
    private function _parse(\DOMNode $archDescNode, $fields)
    {
        $this->_values = array();
        $result = $this->_parseNode(
            $archDescNode,
            array_merge($fields['root'], $fields['c'])
        );
        unset($result['frag']);
        $this->_values['root'] = $result;

        // Let's go parsing C node recursively
        $this->_values['c'] = array();
        $this->_recursiveCNodeSearch(
            $archDescNode->getElementsByTagName('dsc')->item(0),
            $fields['c']
        );
    }

    /**
     * Search c nodes recursively
     *
     * @param DOMNode $rootNode XML root node
     * @param array   $fields   Known fields
     * @param array   $parents  Node parents
     *
     * @return void
     */
    private function _recursiveCNodeSearch(\DOMNode $rootNode,
        $fields, $parents = array()
    ) {
        $cNodes = $this->_xpath->query(
            $this->_cnodes,
            $rootNode
        );

        $i = 0;
        $descriptors = EADFileFormat::$descriptors;
        foreach ($descriptors as &$descriptor) {
            $descriptor = './/controlaccess//' . strtolower(ltrim($descriptor, 'c'));
        }

        foreach ( $cNodes as $cNode ) {
            if ( !$cNode->hasAttribute('id') ) {
                throw new \RuntimeException(
                    'c nodes *must* have a *unique* id to be published.'
                );
            }
            $nodeid = $cNode->getAttribute('id');

            $result = $this->_parseNode($cNode, $fields, $parents);

            $frag = $result['frag'];
            if ( $i === $cNodes->length ) {
                unset($result['frag']);
            }

            $current_title = $this->_getTitle($rootNode, $frag);

            // inherit from parents
            if ( $this->_heritage === true && count($parents) > 0 ) {
                $parentid = array_keys($parents)[count($parents) - 1];

                if ( isset($this->_values['c'][$parentid]) ) {
                    $parent = $this->_values['c'][$parentid];
                    foreach ($descriptors as $descriptor) {
                        if ( isset($parent[$descriptor]) ) {
                            if ( !isset($result[$descriptor]) ) {
                                $result[$descriptor] = array();
                            }
                            $topcopy = array();
                            foreach ( $parent[$descriptor] as $pdesc ) {
                                if (!in_array($pdesc, $result[$descriptor])
                                    && isset($pdesc['attributes']['source'])
                                    && $pdesc['attributes']['source']!='liste-niveau'
                                ) {
                                    $topcopy[] = $pdesc;
                                    $xpath_qry = $descriptor;
                                    foreach ( $pdesc['attributes'] as
                                        $key => $attribute
                                    ) {
                                        $xpath_qry .= '[@' . $key . '="' .
                                            $attribute .'"]';
                                    }
                                    $xpath_qry .= '[text() = "' .
                                        $pdesc['value'] . '"][1]';

                                    $pfrag = clone $parent['frag'];
                                    $desc_xpath = $this->_xpath->query(
                                        $xpath_qry,
                                        $pfrag
                                    )->item(0);

                                    $ca_xpath = $this->_xpath->query(
                                        './/controlaccess[1]',
                                        $frag
                                    );
                                    $ca = null;
                                    $dom = $frag->ownerDocument;
                                    if ( $ca_xpath->length === 0 ) {
                                        $ca = $dom->createElement(
                                            'controlaccess'
                                        );
                                        $frag->appendChild($ca);
                                    } else {
                                        $ca = $ca_xpath->item(0);
                                    }
                                    $ca->appendChild(
                                        $dom->importNode(
                                            $desc_xpath,
                                            true
                                        )
                                    );
                                    $result['frag'] = $frag;
                                    $result['fragment'] = $dom->saveXML($frag);
                                }
                            }

                            $result[$descriptor] = array_merge(
                                $result[$descriptor],
                                $topcopy
                            );
                        }
                    }
                }
            }

            //previous and next components
            if ( $i > 0 ) {
                $previous = $cNodes->item($i - 1);
                $previous_nodeid = $previous->getAttribute('id');
                if ( isset($this->_values['c'][$previous_nodeid]) ) {
                    $previous_frag = $this->_values['c'][$previous_nodeid]['frag'];
                    unset($this->_values['c'][$previous_nodeid]['frag']);
                    $previous_title = $this->_getTitle($rootNode, $previous_frag);
                    $result['previous'] = array(
                        'id'    => $previous_nodeid,
                        'title' => $previous_title
                    );
                    $this->_values['c'][$previous_nodeid]['next'] = array(
                        'id'    => $nodeid,
                        'title' => $current_title
                    );
                }
            }

            $this->_values['c'][$nodeid] = $result;
            if ( $this->_xpath->query($this->_cnodes, $cNode)->length > 0 ) {
                $this->_recursiveCNodeSearch(
                    $cNode,
                    $fields,
                    array_merge(
                        $parents,
                        array(
                            $nodeid => array(
                                'value' => $current_title
                            )
                        )
                    )
                );
            }
            $i++;
        }
    }

    /**
     * Get title
     *
     * @param DOMNode $rootNode XML root node
     * @param DOMNode $frag     XML Fragment
     *
     * @return string
     */
    private function _getTitle(\DOMNode $rootNode, \DOMNode $frag)
    {
        $title = '';
        $title_xpath = $this->_xpath->query('./did/unittitle', $frag);
        if ( $title_xpath->length == 1) {
            $value = strip_tags(
                str_replace(
                    '<lb/>',
                    ' ',
                    $rootNode->ownerDocument->saveXML($title_xpath->item(0))
                )
            );
            $title = $value;
        }
        return $title;
    }

    /**
     * Parse a node
     *
     * @param DOMNode $cNode   DOM node
     * @param array   $fields  Known fields
     * @param array   $parents Node parents
     *
     * @return array
     */
    private function _parseNode(\DOMNode $cNode, $fields, $parents = null)
    {
        $result = array();

        if ( $parents !== null && count($parents) > 0 ) {
            $result['parents'] = $parents;
        }

        //keep original fragment, without children
        $frag = clone $cNode;
        $child = $this->_xpath->query($this->_cnodes, $frag);
        if ( count($child) > 0 ) {
            foreach ( $child as $oldc ) {
                $frag->removeChild($oldc);
            }
        }
        //remove ordering field as well

        $audience = $this->_xpath->query('./@audience', $frag);
        if ($audience->length > 0) {
            $result['audience'] = $audience->item(0)->nodeValue;
        }
        $order = $this->_xpath->query('did/unitid[@type="ordre_c"]', $frag);
        if ( $order->length > 0 ) {
            $result['order'] = $order->item(0)->nodeValue;
            $did = $frag->getElementsByTagName('did')->item(0);
            foreach ( $order as $oldorder ) {
                $did->removeChild($oldorder);
            }
        }
        $result['fragment'] = $frag->ownerDocument->saveXML($frag);
        $result['frag'] = $frag;

        foreach ( $fields as $field ) {
            $nodes = $this->_xpath->query($field, $frag);

            if ( $nodes->length > 0 ) {
                $result[$field] = array();
                foreach ( $nodes as $node ) {
                    $value = strip_tags(
                        str_replace(
                            '<lb/>',
                            ' ',
                            $node->ownerDocument->saveXML($node)
                        )
                    );
                    if ( $field !== './/controlaccess//geogname[@latitude and @longitude]' ) {
                        $result[$field][] = array(
                            'value'         => $value,
                            'attributes'    => $this->_parseAttributes(
                                $node->attributes
                            )
                        );
                    } else {
                        $result[$field][$value] = array(
                            'value'         => $value,
                            'attributes'    => $this->_parseAttributes(
                                $node->attributes
                            )
                        );
                    }
                }
            }
        }
        return $result;
    }

    /**
     * Parse attributes
     *
     * @param DOMNamedNodeMap $attributes DOM node attributes
     *
     * @return array
     */
    private function _parseAttributes(\DOMNamedNodeMap $attributes)
    {
        $return = array();
        foreach ( $attributes as $key=>$attribute ) {
            $return[$key] = $attribute->value;
        }
        return $return;
    }
}
