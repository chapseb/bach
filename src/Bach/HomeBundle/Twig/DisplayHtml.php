<?php
/**
 * Twig extension to display an EAD document as HTML
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
use Symfony\Component\HttpFoundation\Request;
Use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpKernel\Kernel;

/**
 * Twig extension to display an EAD document as HTML
 *
 * PHP version 5
 *
 * @category Templating
 * @package  Bach
 * @author   Johan Cwiklinski <johan.cwiklinski@anaphore.eu>
 * @license  BSD 3-Clause http://opensource.org/licenses/BSD-3-Clause
 * @link     http://anaphore.eu
 */
class DisplayHtml extends \Twig_Extension
{
    protected $router;
    protected $request;
    protected $cote_location;
    protected $viewer_uri;
    protected $prod;
    protected $readingroomip;
    protected $kernel_root_dir;
    protected $cache_key_prefix = 'html';

    /**
     * Main constructor
     *
     * @param UrlGeneratorInterface $router     Router
     * @param Kernel                $kernel     App kernel
     * @param string                $cote_loc   Cote location
     * @param string                $viewer_uri Viewer Url
     */
    public function __construct(Router $router, Kernel $kernel, $cote_loc, $viewer_uri)
    {
        $this->router = $router;
        $this->kernel_root_dir = $kernel->getRootDir();
        $this->readingroomip = $kernel->getContainer()->getParameter('readingroom');
        $this->kernel = $kernel;
        if ( $kernel->getEnvironment() !== 'dev' ) {
            $this->prod = true;
        } else {
            $this->prod = false;
        }
        $this->cote_location = $cote_loc;
        $this->viewer_uri = $viewer_uri;
    }

    /**
     * Set Request
     *
     * @param Request $request The Request
     *
     * @return void
     */
    public function setRequest(Request $request = null)
    {
        $this->request = $request;
    }

    /**
     * Get provided functions
     *
     * @return array
     */
    public function getFunctions()
    {
        return array(
            'displayHtml'       => new \Twig_Function_Method($this, 'display'),
            'displayHtmlScheme' => new \Twig_Function_Method($this, 'scheme')
        );
    }

    /**
     * Displays an EAD document as HTML with XSLT
     *
     * @param string  $docid    Document id
     * @param string  $xml_file Document
     * @param boolean $audience Authorization audience
     *
     * @return string
     */
    public function display($docid, $xml_file, $audience = false,
        $daodetector = null
    ) {
        $cached_doc = null;
        //do not use cache when not in prod
        if ( $this->prod === true ) {
            $cache = new \Doctrine\Common\Cache\ApcCache();
            $cached_doc = null;
            $cached_doc_date = $cache->fetch(
                $this->getCacheKeyName('date_' . $docid)
            );

            $redo = true;
            if ( $cached_doc_date ) {
                //check if document is newer than cache
                $f = new File($xml_file);

                $change_date = new \DateTime();
                $last_file_change = $f->getMTime();
                $change_date->setTimestamp($last_file_change);

                if ( $cached_doc_date > $change_date ) {
                    $redo = false;
                }
            }

            if ( !$redo ) {
                $cached_doc = $cache->fetch(
                    $this->getCacheKeyName($docid)
                );
            }
        }

        if ( !$cached_doc ) {
            $html = '';

            $xml_doc = simplexml_load_file($xml_file);

            $archdesc_html = $this->_renderArchdesc($xml_doc, $docid);
            $contents = $this->renderContents($xml_doc, $docid, $audience, $daodetector);

            $proc = new \XsltProcessor();
            $proc->importStylesheet(
                simplexml_load_file(__DIR__ . '/display_html.xsl')
            );

            $proc->setParameter('', 'docid', $docid);
            $proc->registerPHPFunctions();

            unset($xml_doc->archdesc->dsc);
            $html .= $proc->transformToXml($xml_doc);

            $html = preg_replace(
                array(
                    '/%archdesc%/',
                    '/%contents%/'
                ),
                array(
                    $archdesc_html,
                    $contents
                ),
                $html
            );

            $router = $this->router;
            $request = $this->request;
            $callback = function ($matches) use ($router, $request) {
                $href = '';
                if ( count($matches) > 2 ) {
                    $fieldTested = $matches[1];
                    if (substr($fieldTested, 0, 8) !== 'dyndescr') {
                        $fieldTested = 'c' . ucwords($matches[1]);
                    }

                    $href = $router->generate(
                        'bach_archives',
                        array(
                            'query_terms'   => $request->get('query_terms'),
                            'filter_field'  => $fieldTested,
                            'filter_value'  => $matches[2]
                        )
                    );
                } else {
                    $href = $router->generate(
                        'bach_display_document',
                        array(
                            'docid' => $matches[1]
                        )
                    );
                }
                return 'href="' . str_replace('&', '&amp;', $href) . '"';
            };

            $html = preg_replace_callback(
                '/link="%%%(.[^:]+)::(.[^%]*)%%%"/',
                $callback,
                $html
            );

            $html = preg_replace_callback(
                '/link="%%%(.[^%]+)%%%"/',
                $callback,
                $html
            );

            if ( $this->prod === true ) {
                $cache->save(
                    $this->getCacheKeyName($docid),
                    $html
                );
                $cache->save(
                    $this->getCacheKeyName('date_' . $docid),
                    new \DateTime()
                );
            }
        } else {
            $html = $cached_doc;
        }

        return $html;
    }

    /**
     * Get cache key name, unique for this app
     *
     * @param string $key Key name
     *
     * @return string
     */
    protected function getCacheKeyName($key)
    {
        return $this->kernel_root_dir . '_' . $this->cache_key_prefix
            . '_' . $key;
    }

    /**
     * Render archdesc
     *
     * @param simple_xml $xml_doc XML document
     * @param string     $docid   Document id
     *
     * @return string
     */
    private function _renderArchdesc($xml_doc, $docid)
    {
        $archdesc_doc = clone $xml_doc;
        unset($archdesc_doc->archdesc->eadheader);
        unset($archdesc_doc->archdesc->dsc);

        $display = new DisplayEADFragment(
            $this->router,
            false,
            $this->cote_location
        );
        $display->setRequest($this->request);
        $archdesc_xml = $display->display(
            $archdesc_doc->archdesc->asXML(),
            $docid,
            true
        );
        $archdesc_xml = simplexml_load_string(
            '<root>' . str_replace('<br>', '<br/>', $archdesc_xml) . '</root>'
        );
        $archdesc_html = $archdesc_xml->div->asXML();
        return $archdesc_html;
    }

    /**
     * Render contents
     *
     * @param simple_xml $xml_doc  XML document
     * @param string     $docid    Document id
     * @param boolean    $audience Authorization audience
     *
     * @return string
     */
    protected function renderContents($xml_doc, $docid, $audience = false, $daodetector = null)
    {
        $proc = new \XsltProcessor();
        $proc->importStylesheet(
            simplexml_load_file(__DIR__ . '/display_html_contents.xsl')
        );

        $current_date = new \DateTime();
        $current_year = $current_date->format("Y");
        $outputip = 'bjoedjoerq';
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $outputip
                = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
        } else {
            $outputip = $this->kernel->getContainer()->get(
                'request'
            )->getClientIp();
        }
        $flagReadroom = false;
        if (strpos($this->readingroomip, $outputip) !== false) {
            $flagReadroom = true;
        }

        $host = str_replace('.', '_', $this->kernel->getContainer()->get('request')->getHost());

        if (!isset($_COOKIE[$host.'_bach_cookie_reader'])) {
            $flagReadroom = true;
        }

        //$authorizedArchives = $this->get('bach.home.authorization')->archivesRight();
        $proc->setParameter('', 'docid', $docid);
        $audience = ($audience) ? 'true' : 'false';
        $readingroom = ($flagReadroom) ? 'true' : 'false';

        $proc->setParameter('', 'audience', $audience);
        $proc->setParameter('', 'readingroom', $readingroom);
        $proc->setParameter('', 'current_year', $current_year);
        $proc->setParameter('', 'daodetector', $daodetector);
        $proc->setParameter('', 'viewer_uri', $this->viewer_uri);
        $proc->registerPHPFunctions();

        $up_nodes = $xml_doc->xpath('/ead/archdesc/dsc/c');

        $contents = '';
        foreach ( $up_nodes as $up_node ) {
            $contents .= $proc->transformToXml(
                simplexml_load_string($up_node->asXML())
            );
        }
        return $contents;
    }

    /**
     * Displays an EAD document scheme as HTML with XSLT
     *
     * @param string  $docid    Document id
     * @param string  $xml_file Document
     * @param boolean $audience Authorization audience
     *
     * @return string
     */
    public function scheme($docid, $xml_file, $audience = false)
    {
        $xml_doc = simplexml_load_file($xml_file);

        $proc = new \XsltProcessor();
        $proc->importStylesheet(
            simplexml_load_file(__DIR__ . '/display_html_scheme.xsl')
        );

        $proc->setParameter('', 'docid', $docid);
        $audience = ($audience) ? 'true' : 'false';
        $proc->setParameter('', 'audience', $audience);

        $proc->registerPHPFunctions();

        $up_nodes = $xml_doc->xpath('/ead/archdesc/dsc/c');

        $contents = '';
        foreach ( $up_nodes as $up_node ) {
            $contents .= $proc->transformToXml(
                simplexml_load_string($up_node->asXML())
            );
        }
        return $contents;
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
        case 'Publication informations':
            return _('Publication informations');
            break;
        case 'Title statement':
            return _('Title statement');
            break;
        case 'Title proper:':
            return _('Title proper:');
            break;
        case 'Author:':
            return _('Author:');
            break;
        case 'Subtitle:':
            return _('Subtitle:');
            break;
        case 'Sponsor:':
            return _('Sponsor:');
            break;
        case 'Publication statement':
            return _('Publication statement');
            break;
        case 'Publisher:':
            return _('Publisher:');
            break;
        case 'Date:':
            return _('Date:');
            break;
        case 'Address:':
            return _('Address:');
            break;
        case 'Edition statement':
            return _('Edition statement');
            break;
        case 'Profile':
            return _('Profile');
            break;
        case 'Creation:':
            return _('Creation:');
            break;
        case 'Language:':
            return _('Language:');
            break;
        case 'Description rules:':
            return _('Description rules:');
            break;
        case 'Number:':
            return _('Number:');
            break;
        case 'Series statement':
            return _('Series statement');
            break;
        case 'Note statement':
            return _('Note statement');
            break;
        case 'Revision description':
            return _('Revision description');
            break;
        case 'Title':
            return _('Title');
            break;
        case 'Date':
            return _('Date');
            break;
        case 'Class number':
            return _('Class number');
            break;
        case 'Presentation':
            return _('Presentation');
            break;
        case 'Contents':
            return _('Contents');
            break;
        case 'Physical description':
            return _('Physical description');
            break;
        case 'Extent':
            return _('Extent');
            break;
        case 'Custodial history':
            return _('Custodial history');
            break;
        case 'Acquisition information':
            return _('Acquisition information');
            break;
        case 'Biography or history':
            return _('Biography or history');
            break;
        case 'Legal status':
            return _('Legal status');
            break;
        case 'Language':
            return _('Language');
            break;
        case 'Scope and content':
            return _('Scope and content');
            break;
        case 'Processing information':
            return _('Processing information');
            break;
        case 'Other finding aid':
            return _('Other finding aid');
            break;
        case 'Condition of use':
            return _('Condition of use');
            break;
        case 'Related material':
            return _('Related material');
            break;
        case 'Bibliography':
            return _('Bibliography');
            break;
        case 'Repository':
            return _('Repository');
            break;
        case 'Physfacet':
            return _('Physical support');
            break;
        case 'Genreform':
            return _('Type');
            break;
        case 'Odd':
            return _('Odd');
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
     * Display grouped descriptors
     *
     * @param DOMElement $nodes Nodes
     * @param string     $docid Document id
     *
     * @return string
     */
    public static function showDescriptors($nodes, $docid)
    {
        $output = array();

        foreach ( $nodes as $node ) {
            $n = simplexml_import_dom($node);

            $name = null;
            if ( isset($n['source']) ) {
                $name = 'dyndescr_c' . ucwords($n->getName()) . '_' . $n['source'];
            } else if ( isset($n['role']) ) {
                $name = 'dyndescr_c' . ucwords($n->getName()) . '_' . $n['role'];
            } else {
                $name = $n->getName();
            }

            if ( isset($n['rules']) ) {
                $output[$name]['label'] = $n['rules'] . ' :';
            } else {
                $output[$name]['label'] = DisplayEADFragment::i18nFromXsl($name . ':');
            }

            switch ( $n->getName() ) {
            case 'subject':
                $output[$name]['property'] = 'dc:subject';
                break;
            case 'geogname':
                $output[$name]['property'] = 'gn:name';
                break;
            case 'name':
            case 'persname':
            case 'corpname':
                $output[$name]['property'] = 'foaf:name';
                break;
            }

            $output[$name]['values'][] = (string)$n;
        }

        $ret = '<descriptors>';
        foreach ( $output as $elt=>$out) {
            $ret .= '<tr>';
            $ret .= '<th>' . $out['label'] . '</th> ';
            $ret .= '<td>';
            $count = 0;
            foreach ( $out['values'] as $value ) {
                $count++;
                $ret .= '<a link="%%%' . $elt . '::' . str_replace('"', '|quot|', $value) . '%%%"';
                $ret .= ' about="' . $docid . '"';

                if ( isset($out['property']) ) {
                    $ret .= ' property="' . $out['property'] .
                        '" content="' . htmlspecialchars($value) . '"';
                }
                $ret .= '>' . $value . '</a>';

                if ( $count < count($out['values']) ) {
                    $ret .= ', ';
                }
            }
            $ret .= '</td>';
            $ret .='</tr>';
        }
        $ret .='</descriptors>';

        $doc = new \DOMDocument();
        $ret = preg_replace('/&(?!#?[a-z0-9]+;)/', '&amp;', $ret);
        $doc->loadXML($ret);
        return $doc;
    }
    /**
     * Extension name
     *
     * @return string
     */
    public function getName()
    {
        return 'display_html';
    }
}
