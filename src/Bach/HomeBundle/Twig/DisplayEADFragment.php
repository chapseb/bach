<?php
/**
 * Twig extension to display an EAD fragment
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
 * @author   Sebastien Chaptal <sebastien.chaptal@anaphore.eu>
 * @license  BSD 3-Clause http://opensource.org/licenses/BSD-3-Clause
 * @link     http://anaphore.eu
 */

namespace Bach\HomeBundle\Twig;

use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\HttpFoundation\Request;

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
class DisplayEADFragment extends \Twig_Extension
{
    private $_router;
    private $_request;
    private $_viewer_uri;
    private $_covers_dir;
    private $_comms;
    private $_cote_location;

    /**
     * Main constructor
     *
     * @param UrlGeneratorInterface $router   Router
     * @param boolean               $comms    Comments feature enabled
     * @param string                $cote_loc Cote location
     */
    public function __construct(Router $router, $comms, $cote_loc)
    {
        $this->_router = $router;
        $this->_comms = $comms;
        $this->_cote_location = $cote_loc;
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
        $this->_request = $request;
    }

    /**
     * Get stopword array
     *
     * @return array
     */
    public function getStopWords()
    {
        $stopwordArray = array(
            'au', 'aux', 'avec', 'ce', 'ces' ,'dans' ,'de' ,'des',
            'du', 'elle', 'en', 'et', 'eux', 'il', 'je', 'la', 'le',
            'leur', 'lui', 'ma', 'mais', 'me', 'même', 'mes', 'moi',
            'mon', 'ne', 'nos', 'notre', 'nous', 'on', 'ou', 'par', 'pas',
            'pour', 'qu', 'que', 'qui', 'sa', 'se', 'ses', 'son', 'sur',
            'ta', 'te', 'tes', 'toi', 'ton', 'tu', 'un', 'une', 'vos',
            'votre','vous'
        );
        return $stopwordArray;
    }

    /**
     * Set viewer URI
     *
     * @param string $viewer_uri Viewer URL
     *
     * @return void
     */
    public function setViewer($viewer_uri)
    {
        $this->_viewer_uri = $viewer_uri;
    }

    /**
     * Set covers directory
     *
     * @param string $dir Covers directory
     *
     * @return void
     */
    public function setCoversDir($dir)
    {
        $this->_covers_dir = $dir;
    }

    /**
     * Get provided functions
     *
     * @return array
     */
    public function getFunctions()
    {
        return array(
            'displayFragment' => new \Twig_Function_Method($this, 'display')
        );
    }

    /**
     * Displays EAD fragment in HTML with XSLT
     *
     * @param string  $fragment    EAD fragment as XML
     * @param string  $docid       Document unique identifier
     * @param string  $form_name   Search form name
     * @param boolean $full        Displays full fragment, default to false
     * @param boolean $hasChildren Document has children
     * @param boolean $hasComments Document has comments
     * @param int     $countSub    Sub units count
     * @param boolean $ajax        Called from ajax
     *
     * @return string
     */
    public function display($fragment, $docid, $form_name = 'default', $full = false,
        $hasChildren = false, $hasComments = false, $countSub = 0, $ajax = false,
        $print = false, $highlight = false, $communicability = false, $audience = false,
        $aws = false, $cloudfront = null
    ) {
        $proc = new \XsltProcessor();
        $proc->importStylesheet(
            simplexml_load_file(__DIR__ . '/display_fragment.xsl')
        );
        $router = $this->_router;
        $request = $this->_request;
        $callback = function ($matches) use ($router, $request, $form_name) {
            $filter_field = null;
            if ( strpos($matches[1], 'dyndescr_') === 0 ) {
                $filter_field = $matches[1];
            } else {
                $filter_field = 'c' . ucwords($matches[1]);
            }
            $href = $router->generate(
                'bach_archives',
                array(
                    'query_terms'   => $request->get('query_terms'),
                    'filter_field'  => $filter_field,
                    'filter_value'  => str_replace('|quot|', '"', $matches[2]),
                    'form_name'     => $form_name
                )
            );
            return 'href="' . str_replace('&', '&amp;', $href) . '"';
        };

        $xml = simplexml_load_string($fragment);
        $proc->setParameter('', 'full', $full);
        $proc->setParameter('', 'docid', $docid);
        $proc->setParameter('', 'viewer_uri', $this->_viewer_uri);
        $proc->setParameter('', 'covers_dir', $this->_covers_dir);
        $proc->setParameter('', 'cote_location', $this->_cote_location);
        $comments_enabled = $this->_comms ? 'true' : 'false';
        $proc->setParameter('', 'comments_enabled', $comments_enabled);
        $proc->setParameter('', 'print', $print);
        $proc->setParameter('', 'communicability', $communicability);
        $audience = ($audience) ? 'true' : 'false';
        $proc->setParameter('', 'audience', $audience);
        $proc->setParameter('', 'aws', $aws);
        $proc->setParameter('', 'cloudfront', $cloudfront);

        if ( $hasChildren === true ) {
            $proc->setParameter('', 'children', 'true');
        }
        if ( $hasComments === true ) {
            $proc->setParameter('', 'comments', 'true');
        }
        if ( $countSub > 0 ) {
            $proc->setParameter('', 'count_subs', $countSub);
        }
        if ( $ajax === true ) {
            $proc->setParameter('', 'ajax', 'true');
        }
        $proc->registerPHPFunctions();
        $text = $proc->transformToXml($xml);
        //it is not possible to build routes from the XSL, so we'll build them here
        $text = preg_replace_callback(
            '/link="%%%(.[^:]+)::(.[^%]*)%%%"/',
            $callback,
            $text
        );
        // delete link and images for printing
        if ($print === true) {
            $text = preg_replace('/<a href=\"(.*?)\">(.*?)<\/a>/', "\\2", $text);
            $text = preg_replace(
                '@<section class="otherfindaid"[^>]*?>.*?</section>@si',
                '',
                $text
            );
        }

        //FIXME: Find a better way to handle highlight
        // highlight in descriptors
        if ($highlight != false && $request->getSession()->get('query_terms') != '*:*') {
            $wordHighArray = array();
            foreach ($highlight->getFields() as $fieldArray) {
                foreach ( $fieldArray as $field) {
                    preg_match_all('/<em class="hl">(.*?)<\/em>/', $field, $matches);
                    if (!in_array($matches[1][0], $wordHighArray, true)) {
                        array_push($wordHighArray, $matches[1][0]);
                    }
                }
            }
            // delete one character word and word whiche are in stoplist
            $wordHighArray = array_filter(
                $wordHighArray,
                function ($v) {
                    return strlen($v) > 1;
                }
            );
            $wordHighArray = array_diff($wordHighArray, $this->getStopWords());

            //do the same thing with query terms
            $wordQuery = explode(" ", $request->getSession()->get('query_terms'));
            $wordQuery = array_filter(
                $wordQuery,
                function ($v) {
                    return strlen($v) > 1;
                }
            );
            $wordQuery = array_diff($wordQuery, $this->getStopWords());

            $allWord = array_unique(
                array_merge(
                    $wordHighArray,
                    $wordQuery
                )
            );
            $getLink = preg_match_all(
                '/<a href=\"(.*?)\>(.*?)<\/a>/',
                $text,
                $matches
            );

            $getSectionImage = preg_match(
                '@<section id="images"[^>]*?>.*?</section>@si',
                $text,
                $sectionImage
            );

            // need to rebuild the link because link has the search word
            foreach ($allWord as $wordHigh) {
                foreach ($matches[1] as $key => $link) {
                    if (preg_match("/\b".$wordHigh."\b/i", $matches[2][$key])
                        && strpos($link, $wordHigh) != false
                        && !strpos('<em class="hl">', $wordHigh)
                    ) {
                        $result = str_replace(
                            $wordHigh,
                            '<em class="hl">'.$wordHigh.'</em>',
                            $matches[2][$key]
                        );
                        $finalLink = '<a href="'.$matches[1][$key].'>'.$result.'</a>';
                        $text = str_replace(
                            $matches[0][$key],
                            $finalLink,
                            $text
                        );
                        for ($key2 = $key; $key2 < count($matches[0]); $key2++) {
                            if ($matches[0][$key2] === $matches[0][$key]) {
                                $matches[0][$key2] = $finalLink;
                                $matches[2][$key2] = $result;
                            }
                        }
                    }
                }
            }

            $getSectionBadImage = preg_match(
                '@<section id="images"[^>]*?>.*?</section>@si',
                $text,
                $sectionBadImage
            );

            if ($sectionBadImage != null && $sectionImage != null) {
                $text = str_replace(
                    $sectionBadImage[0],
                    $sectionImage[0],
                    $text
                );
            }

            $text = htmlspecialchars_decode($text);
            if ($full == true) {
                $result = preg_match_all(
                    '@<section class="scopecontent"[^>]*?>.*?</section>@si',
                    $text,
                    $matches
                );

                if (!empty($matches[0][0])) {
                    $result = $matches[0][0];
                    foreach ($allWord as $wordHigh) {
                        if(!strpos('<em class="hl">', $wordHigh)) {
                            $result = str_replace(
                                $wordHigh,
                                '<em class="hl">'.$wordHigh.'</em>',
                                $result
                            );
                        }
                    }
                    $text = str_replace($matches[0][0], $result, $text);
                }
            }
        }

        if ( $docid !== '' ) {
            $add_comment_path = $router->generate(
                'bach_add_comment',
                array(
                    'docid' => $docid,
                    'type'  => 'archives'
                )
            );

            $text = str_replace(
                '__path_add_comment__',
                $add_comment_path,
                $text
            );
        }

        if ($full == false) {
            return $text;
        }
        return html_entity_decode($text);
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
        case 'Physical description':
            return _('Physical description');
            break;
        case 'Descriptors':
            return _('Descriptors');
            break;
        case 'Gender:':
            return _('Gender:');
            break;
        case 'Extent:':
            return _('Extent:');
            break;
        case 'Dimensions:':
            return _('Dimensions:');
            break;
        case 'Appearance:':
            return _('Appearance:');
            break;
        case 'Title:':
            return _('Title:');
            break;
        case 'corpname:':
            return _('Corporate name:');
            break;
        case 'geogname:':
            return _('Geographical name:');
            break;
        case 'subject:':
            return _('Subject:');
            break;
        case 'persname:':
            return _('Personal name:');
            break;
        case 'function:':
            return _('Function:');
            break;
        case 'name:':
            return _('Name:');
            break;
        case 'genreform:':
            return _('Genre:');
            break;
        case 'Relative documents':
            return _('Relative documents');
            break;
        case 'Description:':
            return _('Description:');
            break;
        case 'Conservation history:':
            return _('Conservation history:');
            break;
        case 'Arrangement:':
            return _('Arrangement:');
            break;
        case 'Related material:':
            return _('Related material:');
            break;
        case 'Bibliography:':
            return _('Bibliography:');
            break;
        case 'Userestrict:':
            return _('Userestrict:');
            break;
        case 'Biography or history:':
            return _('Biography or history:');
            break;
        case 'Acquisition information:':
            return _('Acquisition information:');
            break;
        case 'Separated material:':
            return _('Separated material:');
            break;
        case 'Untitled unit':
            return _('Untitled unit');
            break;
        case 'Description':
            return _('Description');
            break;
        case 'Documents':
            return _('Documents');
            break;
        case 'Sub-units':
            return _('Sub-units');
            break;
        case 'Add comment':
            return _('Add comment');
            break;
        case 'Repository:':
            return _('Repository:');
            break;
        case 'Language:':
            return _('Language:');
            break;
        case 'Comments':
            return _('Comments');
            break;
        case 'Bibliographic informations':
            return _('Bibliographic informations');
            break;
        case 'processinfo':
            return _('Information process');
            break;
        case 'Original localisation':
            return _('Original localisation');
            break;
        case 'Abstract':
            return _('Abstract');
            break;

        default:
            if ( strpos($ref, 'dyndescr_') === 0 ) {
                return self::guessDynamicFieldLabel($ref);
            }
            //Should we really throw an exception here?
            //return _($ref);
            throw new \RuntimeException(
                'Translation from XSL reference "' . $ref . '" is not known!'
            );
        }
    }

    /**
     * Guess dynamic field label
     *
     * @param string $name Field name
     *
     * @return string
     */
    public static function guessDynamicFieldLabel($name)
    {
        $exploded = explode(
            '_',
            str_replace('dyndescr_', '', $name)
        );
        $field_label = self::i18nFromXsl(strtolower(substr($exploded[0], 1)) . ':');
        $dynamic_name = str_replace(
            array(
                'dyndescr_' . $exploded[0] . '_',
                ':'
            ),
            '',
            $name
        );
        if ( $dynamic_name === 'none' ) {
            $dynamic_name = _('without specific');
        }

        if ( strpos($field_label, ':') !== 0 ) {
            return preg_replace(
                '/(\s:)/',
                ' (' . $dynamic_name . ')$1',
                $field_label
            );
        } else {
            return $field_label . ' (' . $dynamic_name . ')';
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

            if ( isset($n['label']) ) {
                $output[$name]['label'] = $n['label'] . ' :';
            } else {
                $output[$name]['label'] = self::i18nFromXsl($name . ':');
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

        $ret = '<div>';
        $excludeFacets = array(
            'dyndescr_cSubject_liste-grandDomaine',
            'dyndescr_cSubject_liste-domaine',
            'dyndescr_cSubject_liste-sousDomaine'
        );

        foreach ( $output as $elt=>$out) {
            if (!in_array($elt, $excludeFacets)) {
                $ret .= '<div>';
                $ret .= '<strong>' . $out['label'] . '</strong> ';
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
                        $ret .= ' • ';
                    }
                }
                $ret .='</div>';
            }
        }
        $ret .='</div>';

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
        return 'display_ead_fragment';
    }
}
