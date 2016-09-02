<?php
/**
 * Twig extension to display a classification shceme from EAD
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
 * @category Templating
 * @package  Bach
 * @author   Johan Cwiklinski <johan.cwiklinski@anaphore.eu>
 * @license  BSD 3-Clause http://opensource.org/licenses/BSD-3-Clause
 * @link     http://anaphore.eu
 */

namespace Bach\HomeBundle\Twig;

use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\HttpKernel\Kernel;

/**
 * Twig extension to display an EAD fragment
 *
 * PHP version 5
 *
 * @category Templating
 * @package  Bach
 * @author   Johan Cwiklinski <johan.cwiklinski@anaphore.eu>
 * @license  BSD 3-Clause http://opensource.org/licenses/BSD-3-Clause
 * @link     http://anaphore.eu
 */
class DisplayCdc extends DisplayHtml
{
    private $_cdc_uri;
    private $_docs;
    protected $cache_key_prefix = 'cdc';

    /**
     * Main constructor
     *
     * @param UrlGeneratorInterface $router   Router
     * @param Kernel                $kernel   App kernel
     * @param string                $cote_loc Cote location
     * @param string                $path     Classification scheme URL
     */
    public function __construct(Router $router, Kernel $kernel, $cote_loc, $path)
    {
        parent::__construct($router, $kernel, $cote_loc);
        $this->_cdc_uri = $path;
    }

    /**
     * Get provided functions
     *
     * @return array
     */
    public function getFunctions()
    {
        return array(
            'displayCdc' => new \Twig_Function_Method($this, 'displayCdc'),
            'displayCdcScheme' => new \Twig_Function_Method($this, 'cdcScheme')
        );
    }

    /**
     * Displays classification scheme from EAD in HTML with XSLT
     *
     * @param SimpleXMLElement $docs Published documents
     *
     * @return string
     */
    public function displayCdc(\SimpleXMLElement $docs)
    {
        $this->_docs = $docs;
        return $this->display('', $this->_cdc_uri);
    }

    /**
     * Render contents
     *
     * @param simple_xml $xml_doc XML document
     * @param string     $docid   Document id
     *
     * @return string
     */
    protected function renderContents($xml_doc, $docid, $audience = false)
    {
        $proc = new \XsltProcessor();

        $proc->importStylesheet(
            simplexml_load_file(__DIR__ . '/display_cdc.xsl')
        );

        $proc->setParameter('', 'docid', $docid);
        $proc->registerPHPFunctions();

        $dadocs = $xml_doc->addChild('dadocs');
        foreach ( $this->_docs as $doc ) {
            $dadocs->addChild($doc->getName(), $doc);
        }

        //find not published documents
        $this->_setNotMatched($xml_doc);

        $contents = $proc->transformToXml($xml_doc);

        $router = $this->router;
        $request = $this->request;
        $callback = function ($matches) use ($router, $request) {
            $href = '';
            if ( count($matches) > 2 ) {
                $href = $router->generate(
                    'federate_search',
                    array(
                        'query_terms'   => $request->get('query_terms'),
                        'filter_field'  => 'c' . ucwords($matches[1]),
                        'filter_value'  => $matches[2]
                    )
                );
            } else {
                $href = $router->generate(
                    'bach_ead_html',
                    array(
                        'docid' => $matches[1]
                    )
                );
            }
            return 'href="' . str_replace('&', '&amp;', $href) . '"';
        };

        $contents = preg_replace_callback(
            '/link="%%%(.[^:]+)::(.[^%]*)%%%"/',
            $callback,
            $contents
        );

        $contents = preg_replace_callback(
            '/link="%%%(.[^%]+)%%%"/',
            $callback,
            $contents
        );


        return $contents;
    }

    /**
     * Displays an EAD document scheme as HTML with XSLT
     *
     * @param string           $docid    Document id
     * @param string           $xml_file Document
     * @param SimpleXMLElement $docs     Published documents
     *
     * @return string
     */
    public function cdcScheme($docid, $xml_file, $docs)
    {
        $contents = parent::scheme($docid, $xml_file, false);

        $this->_docs = $docs;
        if (count($this->_getNotMatched(simplexml_load_file($xml_file))) > 0 ) {
            $contents .= '<h3 class="no-accordion"><a href="#not-classified">' .
                _('Not classified') . '</a></h3>';
        }

        return $contents;
    }

    /**
     * Set documents not matched in classification shceme
     *
     * @param SimpleXMLElement $xml XML classification shceme
     *
     * @return void
     */
    private function _setNotMatched(\SimpleXMLElement $xml)
    {
        $docs = $this->_getNotMatched($xml);

        if ( count($docs) > 0 ) {
            $not_matched = $xml->addChild('not_matched');
            foreach ( $docs as $doc_id=>$doc_name ) {
                $not_matched->addChild($doc_id, $doc_name);
            }
        }
    }

    /**
     * Get documents not matched in classification shceme
     *
     * @param SimpleXMLElement $xml XML classification shceme
     *
     * @return array
     */
    private function _getNotMatched(\SimpleXMLElement $xml)
    {
        $alllinks = $xml->xpath('//*[@href]');
        $docs = (array)$this->_docs;
        $docs_id = array_keys($docs);

        foreach ( $alllinks as $link ) {
            $href = preg_replace('/\\.[^.\\s]{3,4}$/', '', $link['href']);
            if ( in_array($href, $docs_id) ) {
                unset($docs[$href]);
            }
        }

        return $docs;
    }

    /**
     * Get translations from XSL stylesheet.
     * It would be possible to directly call _(),
     * but those strings would not be found with
     * standard gettext capabilities.
     *
     * @param string $ref String reference
     *
     * @return string
     */
    public static function i18nFromXsl($ref)
    {
        switch ( $ref ) {
        case 'Not classified':
            return _('Not classified');
            break;
        default:
            //Should we really throw an exception here?
            //return _($ref);
            throw new \RuntimeException(
                'Translation from XSL reference "' . $ref . '" is not known!'
            );
        }
    }

    /**
     * Does current node contains published docs?
     *
     * @param DOMElement[] $node Current node
     * @param DOMElement[] $docs Published documents
     *
     * @return boolean
     */
    public static function hasPublished($node, $docs)
    {
        $docs = array_keys((array)simplexml_import_dom($docs[0]));

        $simple_node = simplexml_import_dom($node[0]);
        $links = $simple_node->xpath('descendant::*[@href]');

        foreach ( $links as $link ) {
            $href = preg_replace('/\\.[^.\\s]{3,4}$/', '', $link['href']);
            if ( in_array($href, $docs) ) {
                return true;
            }
        }
        return false;
    }

    /**
     * Extension name
     *
     * @return string
     */
    public function getName()
    {
        return 'display_cdc';
    }
}
