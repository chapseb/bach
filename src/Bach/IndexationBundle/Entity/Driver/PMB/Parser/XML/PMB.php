<?php
/**
 * PMB processing
 *
 * PHP version 5
 *
 * @category Indexation
 * @package  Bach
 * @author   Vincent Fleurette <vincent.fleurette@anaphore.eu>
 * @license  Unknown http://unknown.com
 * @link     http://anaphore.eu
 */

namespace Bach\IndexationBundle\Entity\Driver\PMB\Parser\XML;

/**
 * PMB processing
 *
 * PHP version 5
 *
 * @category Indexation
 * @package  Bach
 * @author   Vincent Fleurette <vincent.fleurette@anaphore.eu>
 * @license  Unknown http://unknown.com
 * @link     http://anaphore.eu
 */
class PMB
{
    private $_xpath;
    private $_values = array();

    /**
     * Constructor
     *
     * @param DOMXPath $xpath  XPath
     * @param DOMNode  $node   XML node
     * @param array    $fields Known fields
     */
    public function __construct(\DOMXPath $xpath, \DOMNode $node, $fields)
    {
        $this->_xpath = $xpath;
        $this->_parse($node, $fields);
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
     * @param DOMNode $headerNode XML archdesc node
     * @param array   $fields     Known fields
     *
     * @return void
     */
    private function _parse(\DOMNode $headerNode, $fields)
    {
        foreach ( $fields as $field ) {
            $nodes = $this->_xpath->query($field, $headerNode);
            $this->_values[$field] = array();
            if ( $nodes->length > 0 ) {
                foreach ( $nodes as $key=>$node ) {
                    $value = null;
                    $attributes = null;
                    //var_dump($field);
                    if ( $field === 'zoneAuteursAutres|zoneAuteursSecondaires|zoneAuteurPrincipal' ) {
                        $name = null;
                        $surname = null;
                        $function = null;
                        $type = null;
                        foreach ($node->childNodes as $child) {
                            switch($child->localName){
                            case 'nom':
                                $name = $child->nodeValue;
                                break;
                            case 'prenom':
                                $surname = $child->nodeValue;
                                break;
                            case 'codeFonction':
                                $function = $child->nodeValue;
                                break;
                            }
                        }
                        switch ($node->localName){
                        case "zoneAuteursAutres" :
                            $type = 'other';
                            break;
                        case "zoneAuteursSecondaires" :
                            $type = 'secondary';
                            break;

                        case "zoneAuteurPrincipal" :
                            $type = 'primary';
                            break;
                        }
                        //var_dump($node->nodeValue);
                        //nom //prenom
                        $value = $name . ' ' . $surname;
                        $attributes['function'] = $function;
                        $attributes['type'] = $type;
                    } else {
                        $value = $node->nodeValue;
                        $attributes = $this->_parseAttributes($node->attributes);
                    }
                    $this->_values[$field][] = array(
                        'value'         => $value,
                        'attributes'    => $attributes
                    );
                }
            }
        }
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