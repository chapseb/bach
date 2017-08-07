<?php
/**
 * Twig extension to display numeric documents
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


/**
 * Twig extension to display numeric documents
 *
 * PHP version 5
 *
 * @category Templating
 * @package  Bach
 * @author   Johan Cwiklinski <johan.cwiklinski@anaphore.eu>
 * @license  BSD 3-Clause http://opensource.org/licenses/BSD-3-Clause
 * @link     http://anaphore.eu
 */
class DisplayDao extends \Twig_Extension
{
    private $_viewer;

    private static $_images_extensions = array(
        'jpeg', 'jpg', 'png',
        'gif', 'tif', 'tiff'
    );
    private static $_sounds_extensions = array('ogg', 'wav');
    private static $_videos_extensions = array('ogv', 'mp4', 'webm', 'mov', 'wmv');
    private static $_flash_sounds_extensions = array('mp3');
    private static $_flash_extensions = array('flv');

    const IMAGE = 0;
    const SERIES = 1;
    const SOUND = 2;
    const VIDEO = 3;
    const FLA_SOUND = 4;
    const FLASH = 5;
    const MISC = 6;
    const EXTERNAL = 7;
    const XML=8;
    const SERIESBOUND = 9;

    /**
     * Main constructor
     *
     * @param string $viewer_uri         Viewer URI
     * @param string $covers_dir         Covers directory
     * @param string $bach_default_theme Bach default theme name
     */
    public function __construct($viewer_uri, $covers_dir, $bach_default_theme)
    {
        if (!(substr($viewer_uri, -1) === '/')) {
            $viewer_uri .= '/';
        }
        $this->_viewer = $viewer_uri;
        $this->_covers_dir = $covers_dir;
        $this->_bach_default_theme = $bach_default_theme;
    }

    /**
     * Get provided functions
     *
     * @return array
     */
    public function getFunctions()
    {
        return array(
            'displayDao' => new \Twig_Function_Method($this, 'display')
        );
    }

    /**
     * Displays numeric documents regarding to their type
     *
     * @param string  $daos            Documents
     * @param boolean $all             Displays all documents, default to false
     * @param string  $format          Format to display, defaults to thumb
     * @param boolean $communicability Flag for communicability defaults to false
     * @param string  $testSeries      Test if first is a serie, defaults to null
     * @param boolean $aws             Aws environment or not
     * @param string  $cloudfront      Cloudfront adress for direct link to image
     * @param string  $daotitle        Make title for dao hover
     *
     * @return string
     */
    public function display($daos, $all = false, $format = 'thumb',
        $communicability = false, $testSeries = null,
        $aws = false, $cloudfront = null, $daotitle = null
    ) {
        if ($all === false) {
            if ($testSeries == 'series') {
                $directory = substr($daos[0], 0, strrpos($daos[0], '/'));
                $imageBegin = substr($daos[0], strrpos($daos[0], '/') + 1);
                $imageEnd = substr($daos[1], strrpos($daos[1], '/') + 1);
                $retLink = '<a href="' . $this->_viewer . 'series/' . $directory .
                    '?s=' . $imageBegin . '&e=' . $imageEnd .'" target="_blank">';
                if ($aws == true) {
                    $srcImage = @file_get_contents(
                        $this->_viewer.'ajax/representativeAws/'.
                            rtrim($daos[0], '/') . '/format/' . $format
                    );
                    $retLink .= '<img src="'.$srcImage.'" alt="'.$daos[0].'"/></a>';
                } else {
                    $retLink .= '<img src="' . $this->_viewer . 'ajax/img/' .
                        rtrim($daos[0], '/') .  '/format/' . $format .
                        '" alt="' . $daos[0] . '"/></a>';
                }
                return $retLink;
            } else {
                $daorep = null;
                if (isset($daos[1])) {
                    $daorep = $daos[1];
                }
                return self::proceedDao(
                    $daos[0],
                    $daotitle,
                    $this->_viewer,
                    $format,
                    false,
                    true,
                    $this->_covers_dir,
                    false,
                    false,
                    $this->_bach_default_theme,
                    $communicability,
                    $aws,
                    $cloudfront,
                    $daorep
                );
            }
        } else {
            $res = '';
            foreach ( $daos as $dao ) {
                $res .= self::proceedDao(
                    $dao,
                    null,
                    $this->_viewer,
                    $format,
                    false,
                    true,
                    $this->_covers_dir,
                    true,
                    false,
                    $this->_covers_dir
                );
            }
            return $res;
        }
    }

    /**
     * Get DAOs as XML nodes (XSLT will display them as string otherwise)
     *
     * @param NodeSet $daogrps         Groups list
     * @param NodeSet $daos            Documents list
     * @param string  $viewer          Viewer URL
     * @param string  $format          Format to display, defaults to thumb
     * @param boolean $ajax            Is call came from ajax
     * @param string  $covers_dir      Covers directory
     * @param boolean $communicability Flag for communicability defaults to false
     *
     * @return DOMElement
     */
    public static function displayDaos($daogrps, $daos, $viewer, $format = 'thumb',
        $ajax = false, $covers_dir = null, $communicability = false,
        $aws = false, $cloudfront = null
    ) {
        //start root element
        $res = '<div>';

        if (count($daogrps) > 0) {
            $groups_results = array();
            foreach ( $daogrps as $dg ) {
                $xml_dg = simplexml_import_dom($dg);

                $gres = array(
                    'title'     => null,
                    'content'   => array()
                );

                if ($xml_dg['title']) {
                    $gres['title'] = (string)$xml_dg['title'];
                }

                $results = array(
                    self::IMAGE       => array(),
                    self::SERIES      => array(),
                    self::SERIESBOUND => array(),
                    self::VIDEO       => array(),
                    self::FLASH       => array(),
                    self::SOUND       => array(),
                    self::FLA_SOUND   => array(),
                    self::MISC        => array(),
                    self::XML         => array()
                );

                if ($xml_dg->attributes()['role'] == 'series') {
                    $retLink = '';
                    $retImg = '';
                    if (!$communicability) {
                        $results[self::SERIESBOUND][] = '<img src="/img/thumb_comm.png" title="'.
                            _('This picture is not communicable') . '"/>';
                    } else {
                        foreach ($xml_dg->children() as $node_name => $xml_dao) {
                            if ($xml_dao['role'] == 'image:first') {
                                $dao = (string)$xml_dao['href'];
                                $daotitle = null;
                                if ($xml_dao['title']) {
                                    $daotitle = $xml_dao['title'];
                                }
                                $directory = substr($dao, 0, strrpos($dao, '/'));
                                $imageBegin = substr($dao, strrpos($dao, '/') + 1);
                                $retLink = '<a href="' . $viewer . 'series/' .
                                    $directory . '?s=' . $imageBegin;
                                if ($aws == true) {
                                    $srcImage = @file_get_contents(
                                        $viewer.'ajax/representativeAws/'.
                                        rtrim($dao, '/') . '/format/' . $format
                                    );
                                    $retImg .= '<img src="'.$srcImage.'" alt="' . $dao . '"/>';
                                } else {
                                    $retImg .= '<img src="' . $viewer . 'ajax/img/' .
                                        rtrim($dao, '/') . '/format/' . $format .
                                        '" alt="' . $dao . '"/>';
                                }
                            } else if ($xml_dao['role'] == 'image:last') {
                                $dao = (string)$xml_dao['href'];
                                $daotitle = null;
                                if ($xml_dao['title']) {
                                    $daotitle = $xml_dao['title'];
                                }
                                $directory = substr($dao, 0, strrpos($dao, '/'));

                                $imageEnd = substr($dao, strrpos($dao, '/') + 1);
                                $retLink .= '&e='. $imageEnd . '" target="_blank">';
                                $ret = $retLink. $retImg . '</a>';
                                $results[self::SERIESBOUND][] = $ret;
                            } else {
                                break;
                            }
                        }
                    }
                }

                foreach ($xml_dg->children() as $node_name => $xml_dao) {
                    if ($node_name === 'dao' || $node_name === 'daoloc') {
                        if (isset($xml_dao['role'])
                            && ((string)$xml_dao['role'] == 'thumbnails'
                            ||(string)$xml_dao['role'] == 'thumbnail'
                            ||(string)$xml_dao['role'] == 'image:first'
                            ||(string)$xml_dao['role'] == 'image:last')
                        ) {
                            break;
                        }
                        $dao = (string)$xml_dao['href'];
                        $daotitle = null;
                        if ($xml_dao['title']) {
                            $daotitle = $xml_dao['title'];
                        }

                        $daorepHref = null;
                        foreach ($xml_dg->children() as $node_name => $daorep) {
                            if (($node_name === 'dao' || $node_name === 'daoloc')
                                && isset($daorep['role'])
                                && ((string)$daorep['role'] == 'thumbnail'
                                || (string)$daorep['role'] == 'thumbnails')
                            ) {
                                $daorepHref = $daorep['href'];
                            }
                        }

                        $results[self::_getType($dao)][] = self::proceedDao(
                            $dao,
                            $daotitle,
                            $viewer,
                            $format,
                            $ajax,
                            true,
                            $covers_dir,
                            true,
                            false,
                            'web',
                            $communicability,
                            $aws,
                            $cloudfront,
                            $daorepHref
                        );
                    }
                }
                $gres['content'] = $results;
                $groups_results[] = $gres;
            }

            foreach ( $groups_results as $group ) {
                $res .= '<section>';
                if ($group['title'] !== null) {
                    $res .= '<header><h4>' . $group['title'] . '</h4></header>';
                }

                $results = $group['content'];

                if (count($results[self::IMAGE]) > 0) {
                    $res .= '<div>';
                    foreach ( $results[self::IMAGE] as $image ) {
                        $res .= $image;
                    }
                    $res .= '</div>';
                }

                if (count($results[self::SERIES]) > 0) {
                    $res .= '<div>';
                    foreach ( $results[self::SERIES] as $series ) {
                        $res .= $series;
                    }
                    $res .= '</div>';
                }

                if (count($results[self::SERIESBOUND]) > 0) {
                    $res .= '<div>';
                    foreach ( $results[self::SERIESBOUND] as $series ) {
                        $res .= $series;
                    }
                    $res .= '</div>';
                }

                if (count($results[self::SOUND]) > 0) {
                    $res .= '<div>';
                    foreach ( $results[self::SOUND] as $sound ) {
                        $res .= $sound;
                    }
                    $res .= '</div>';
                }

                if (count($results[self::FLA_SOUND]) > 0) {
                    $res .= '<div>';
                    $res .= '<ul class="playlist">';
                    foreach ( $results[self::FLA_SOUND] as $sound ) {
                        $res .= $sound;
                    }
                    $res .= '</ul>';
                    $res .= '</div>';
                }

                if (count($results[self::VIDEO]) > 0) {
                    $res .= '<div>';
                    foreach ( $results[self::VIDEO] as $video ) {
                        $res .= $video;
                    }
                    $res .= '</div>';
                }

                if (count($results[self::FLASH]) > 0) {
                    $res .= '<div>';
                    foreach ( $results[self::FLASH] as $flash ) {
                        $res .= $flash;
                    }
                    $res .= '</div>';
                }

                if (count($results[self::MISC]) > 0) {
                    $res .= '<div>';
                    foreach ( $results[self::MISC] as $other ) {
                        $res .= $other;
                    }
                    $res .= '</div>';
                }
                if (count($results[self::XML]) > 0) {
                    $res .= '<div>';
                    foreach ( $results[self::XML] as $other ) {
                        $res .= $other;
                    }
                    $res .= '</div>';
                }


                $res .= '</section>';
            }
        }

        if (count($daos) > 0) {
            $results = array(
                self::IMAGE     => array(),
                self::SERIES    => array(),
                self::VIDEO     => array(),
                self::FLASH     => array(),
                self::SOUND     => array(),
                self::FLA_SOUND => array(),
                self::MISC      => array(),
                self::XML       => array()
            );

            foreach ( $daos as $d ) {
                $xml_dao = simplexml_import_dom($d);
                $dao = (string)$xml_dao['href'];
                $daotitle = null;
                if ($xml_dao['title']) {
                    $daotitle = $xml_dao['title'];
                }
                $results[self::_getType($dao)][] = self::proceedDao(
                    $dao,
                    $daotitle,
                    $viewer,
                    $format,
                    $ajax,
                    true,
                    $covers_dir,
                    true,
                    false,
                    'web',
                    $communicability,
                    $aws,
                    $cloudfront
                );
            }

            if (count($results[self::IMAGE]) > 0) {
                $res .= '<section id="images">';
                $res .= '<header><h4>' . _('Images') . '</h4></header>';
                foreach ( $results[self::IMAGE] as $image ) {
                    $res .= $image;
                }
                $res .= '</section>';
            }

            if (count($results[self::SERIES]) > 0) {
                $res .= '<section id="series">';
                $res .= '<header><h4>' . _('Series') . '</h4></header>';
                foreach ( $results[self::SERIES] as $series ) {
                    $res .= $series;
                }
                $res .= '</section>';
            }

            if (count($results[self::SOUND]) > 0) {
                $res .= '<section id="sounds">';
                $res .= '<header><h4>' . _('Sounds') . '</h4></header>';
                foreach ( $results[self::SOUND] as $sound ) {
                    $res .= $sound;
                }
                $res .= '</section>';
            }

            if (count($results[self::FLA_SOUND]) > 0) {
                $res .= '<section id="sounds">';
                $res .= '<header><h4>' . _('Flash sounds') . '</h4></header>';
                $res .= '<ul>';
                foreach ( $results[self::FLA_SOUND] as $sound ) {
                    $res .= $sound;
                }
                $res .= '</ul>';
                $res .= '</section>';
            }

            if (count($results[self::VIDEO]) > 0) {
                $res .= '<section id="videos">';
                $res .= '<header><h4>' . _('Videos') . '</h4></header>';
                foreach ( $results[self::VIDEO] as $video ) {
                    $res .= $video;
                }
                $res .= '</section>';
            }

            if (count($results[self::FLASH]) > 0) {
                $res .= '<section id="flashvideos">';
                $res .= '<header><h4>' . _('Flash videos') . '</h4></header>';
                foreach ( $results[self::FLASH] as $flash ) {
                    $res .= $flash;
                }
                $res .= '</section>';
            }

            if (count($results[self::MISC]) > 0) {
                $res .= '<section id="other">';
                $res .= '<header><h4>' . _('Miscellaneous documents') .
                    '</h4></header>';
                foreach ( $results[self::MISC] as $other ) {
                    $res .= $other;
                }
                $res .= '</section>';
            }
            if (count($results[self::XML]) > 0) {
                $res .= '<section id="other">';
                $res .= '<header><h4>' . _('XML documents') .
                    '</h4></header>';
                foreach ( $results[self::XML] as $other ) {
                    $res .= $other;
                }
                $res .= '</section>';
            }

        }

        //end root element
        $res .= '</div>';
        $res = preg_replace('#&(?=[a-z_0-9]+=)#', '&amp;', $res);
        $sxml = simplexml_load_string($res);
        $doc = dom_import_simplexml($sxml);
        return $doc;
    }

    /**
     * Get a DAO as XML nodes (XSLT will display them as string otherwise)
     *
     * @param string  $dao        Document name
     * @param string  $title      Document title
     * @param string  $viewer     Viewer URL
     * @param string  $format     Format to display, defaults to thumb
     * @param string  $covers_dir Covers directory
     * @param boolean $linkDesc   If in description
     *
     * @return DOMElement
     */
    public static function getDao($dao, $title, $viewer, $format = 'thumb',
        $covers_dir = null, $linkDesc = false, $aws = false, $cloudfront = null
    ) {
        $str = self::proceedDao(
            $dao,
            str_replace('"', '&quot;', $title),
            $viewer,
            $format,
            false,
            true,
            $covers_dir,
            true,
            $linkDesc,
            'web',
            $aws,
            $cloudfront
        );
        $sxml = simplexml_load_string(
            str_replace('&', '&amp;', $str)
        );
        $doc = dom_import_simplexml($sxml);
        return $doc;
    }

    /**
     * Proceed daos
     *
     * @param string  $dao                Document name
     * @param string  $daotitle           Document title, if any
     * @param string  $viewer             Viewer URL
     * @param string  $format             Format to display
     * @param boolean $ajax               Does call came from ajax
     * @param boolean $standalone         Is a standalone document, defaults to true
     * @param boolean $covers_dir         Covers directory
     * @param boolean $all                Proceed all daos
     * @param boolean $linkDesc           If in description
     * @param string  $bach_default_theme Bach default theme name
     * @param boolean $communicability    Flag for communicability defaults to false
     * @param boolean $aws                Aws environment or not
     * @param string  $cloudfront         Cloudfront adress for direct link to image
     * @param string  $daorep             Link to representative image for series
     *
     * @return string
     */
    public static function proceedDao($dao, $daotitle, $viewer, $format,
        $ajax = false, $standalone = true, $covers_dir = null, $all = true,
        $linkDesc = false, $bach_default_theme = 'web', $communicability = false,
        $aws = false, $cloudfront = null, $daorep = null
    ) {
        $ret = null;

        $daotitle = str_replace('"', '&quot;', $daotitle);
        if ($linkDesc == true) {
            $ret = '<span/>';
            switch ( self::_getType($dao) ) {
            case self::SERIES:
                $ret = '<a href="' . $viewer . 'series/' .
                    $dao . '" target="_blank">';
                $ret .=  $daotitle;
                $ret .= '</a>';
                break;
            case self::IMAGE:
                $ret = '<a href="' . $viewer . 'viewer/' .
                    $dao . '" target="_blank">';
                $ret .=  $daotitle;
                $ret .= '</a>';
                break;
            }
            return $ret;
        }
        if (!(substr($viewer, -1) === '/')) {
            $viewer .= '/';
        }

        $linkCommunicability = '<img src="/img/thumb_comm.png" title="'.
            _('This picture is not communicable') . '"/>';
        $title = str_replace(
            '%name%',
            ($daotitle) ? $daotitle : $dao,
            _("Play '%name%'")
        );
        switch ( self::_getType($dao) ) {
        case self::SERIES:
            if ($communicability == true ) {
                if ($aws == true) {
                    if (substr($dao, -1) != '/') {
                        $dao .= '/';
                    }
                    $ret = '<a href="' . $viewer . 'series/' .
                    $dao . '" target="_blank" property="image">';
                    $srcImage = @file_get_contents(
                        $viewer.'ajax/representativeAws/'.
                        rtrim($dao, '/') . '/format/' . $format
                    );
                    if ($daorep != null) {
                        $srcImage = @file_get_contents(
                            $viewer.'ajax/representativeAws/'.
                            rtrim($daorep, '/') . '/format/' . $format
                        );
                    }
                    $ret .= '<img src="' . $srcImage
                        . '" alt="' . $dao . '" title="'. $daotitle .'" />';
                } else {
                    $ret = '<a href="' . $viewer . 'series/' .
                    $dao . '" target="_blank" property="image">';
                    if ($daorep != null) {
                        $ret .= '<img src="' . $viewer . 'ajax/img/' .
                        rtrim($daorep, '/') .  '/format/' . $format
                        . '" alt="' . $dao . '" title="'. $daotitle .'" />';
                    } else {
                        $ret .= '<img src="' . $viewer . 'ajax/representative/' .
                        rtrim($dao, '/') .  '/format/' . $format
                        . '" alt="' . $dao . '" title="'. $daotitle .'" />';
                    }
                }
            } else {
                $ret .= $linkCommunicability;
            }
            if ($daotitle !== null && $all == true) {
                $ret .= '<span class="title">' . $daotitle . '</span>';
            }
            if ($communicability == true ) {
                $ret .= '</a>';
            }
            break;
        case self::IMAGE:
            if ($communicability == true ) {
                $ret = '<a href="' . $viewer . 'viewer/' .
                    $dao . '" target="_blank" property="image">';
                if ($aws == true) {
                    $srcImage = @file_get_contents(
                        $viewer.'ajax/representativeAws/'.
                        rtrim($dao, '/') . '/format/' . $format
                    );
                    $ret .= '<img src="' . $srcImage
                        . '" alt="' . $dao . '" title="'. $daotitle .'" />';
                } else {
                    $ret .= '<img src="' . $viewer . 'ajax/img/' . $dao .
                        '/format/' . $format . '" alt="' . $dao .'"  title="'. $daotitle .'" />';
                }
            } else {
                $ret .= $linkCommunicability;
            }
            if ( $daotitle !== null && $all == true) {
                $ret .= '<span class="title">' . $daotitle . '</span>';
            }
            if ($communicability == true ) {
                $ret .= '</a>';
            }
            break;
        case self::XML:
            $ret = '<a href="/document/' . str_replace('.xml', '', $dao) .
                '" target="_blank">';
            if ($daotitle !== null) {
                $ret .= '<span class="title">' .
                    str_replace('.xml', '', ($daotitle) ? $daotitle : $dao)
                    . '</span>';
            }
            $ret .= '</a>';
            break;
        case self::SOUND:
            $href = '/file/music/' . $dao;
            $ret .= '<audio controls="controls" width="300" height="30" property="audio">';
            $ret .= '<source src="' . $href  . '"/>';
            $ret .= '</audio>';
            break;
        case self::VIDEO:
            $href = '/file/video/' . $dao;
            $ret = '<div class="htmlplayer standalone">';
            $ret .= '<video poster="/img/play_large.png" preload="none" controls="controls" width="300" height="300" property="video">';
            $ret .= '<source src="' . $href  . '"/>';
            $ret .= '<a href="' . $href . '" target="_blank">' .
                _('Your browser does not support this video format, you may want to download file and watch it offline') . '</a>';
            $ret .= '</video>';
            $ret .= '<div><a href="' . $href . '" target="_blank">' .
                _('If your browser does not support this video format, you may want to download this video and watch it offline') . '</a></div>';
            if ($daotitle !== null) {
                $ret .= '<span class="title">' . $daotitle . '</span>';
            }
            $ret .= '</div>';
            break;
        case self::FLASH:
            $href = '/file/video/' . $dao;
            $class = '';
            if ($standalone === true) {
                $class = ' class="';
                if ($ajax !== false) {
                    $class .= 'ajaxflashplayer ';
                }
                $class .= 'flashplayer"';
            }
            $ret = '<div' . $class . ' id ="' .  self::_getRandomId() . '">';
            $ret .= '<a href="' . $href . '" title="' .
                $title  . '" target="_blank">';
            if ($standalone === true) {
                if ($covers_dir !== null) {
                    //check if a cover exists
                    $name_wo_ext = str_replace(
                        ltrim(strstr($dao, '.'), '.'),
                        '',
                        $dao
                    );

                    $cover_name = $name_wo_ext .= 'jpg';
                    $src = '';
                    $alt = '';

                    if (file_exists($covers_dir . '/' . $cover_name)) {
                        $src = '/file/covers/';
                        if ($format === 'thumbs') {
                            $src = 'thumb/';
                        }
                        $src .= $cover_name;
                    } else {
                        $filetype = self::_guessFileType($dao);
                        $src = '/img/' . $filetype . 'nocover.png';
                    }

                    if ($daotitle) {
                        $alt = $daotitle;
                    } else {
                        $alt = $dao;
                    }

                    $ret .='<img src="' . $src . '" alt="' . $alt . '"/>';
                } else {
                    $ret .= '<img src="/img/play_large.png" alt="' . $dao . '"/>';
                }
            }
            $ret .= '</a>';
            if ($daotitle !== null) {
                $ret .= '<span class="title">' . $daotitle . '</span>';
            }
            $ret .= '</div>';
            break;
        case self::FLA_SOUND;
            $class = '';
            $ret = '';
            if ($standalone === true) {
                $class = ' class="';
                if ($ajax !== false) {
                    $class .= 'ajaxflashmusicplayer ';
                }
                $class .= 'flashmusicplayer"';
            }
            if ($all === true) {
                $ret .= '<li>';
            }
            $href = '/file/music/' . $dao;
            $ret .= '<a' . $class . ' href="' . $href . '" title="' .
                $title  . '" target="_blank">';
            if ($all === false) {
                if ($covers_dir !== null) {
                    //check if a cover exists
                    $name_wo_ext = str_replace(
                        ltrim(strstr($dao, '.'), '.'),
                        '',
                        $dao
                    );

                    $cover_name = $name_wo_ext .= 'jpg';
                    $src = '';
                    $alt = '';

                    if (file_exists($covers_dir . '/' . $cover_name)) {
                        $src = '/file/covers/';
                        if ($format === 'thumbs') {
                            $src = 'thumb/';
                        }
                        $src .= $cover_name;
                    } else {
                        $filetype = self::_guessFileType($dao);
                        if (($bach_default_theme != 'web'
                            || $bach_default_theme != 'phone')
                            && (file_exists('assetic/img/'.$bach_default_theme.'_'.$filetype.'nocover.png'))
                        ) {
                            $src = '/assetic/img/' . $bach_default_theme .
                                '_' . $filetype . 'nocover.png';
                        } else {
                            $src = '/img/' . $filetype . 'nocover.png';
                        }
                    }

                    if ($daotitle) {
                        $alt = $daotitle;
                    } else {
                        $alt = $dao;
                    }

                    $ret .='<img src="' . $src . '" alt="' . $alt . '"/>';
                } else {
                    $filetype = self::_guessFileType($dao);
                    if (($bach_default_theme != 'web'
                        || $bach_default_theme != 'phone')
                        && (file_exists('assetic/img/'.$bach_default_theme.'_'.$filetype.'nocover.png'))
                    ) {
                        $src = '/assetic/img/' . $bach_default_theme .
                            '_' . $filetype . 'nocover.png';
                    } else {
                        $src = '/img/' . $filetype . 'nocover.png';
                    }


                    $ret .= '<img src="/img/sound_nocover.png" alt="' . $dao . '"/>';
                }
            }
            if ($daotitle !== null) {
                $ret .= '<span class="title">' . $daotitle . '</span>';
            }
            if ($all === true && $daotitle === null) {
                $ret .= $dao;
            }
            $ret .= '</a>';
            if ($all === true) {
                $ret .= '</li>';
            }
            break;
        case self::MISC:
            $title = str_replace(
                '%name%',
                $daotitle,
                _("Display '%name%'")
            );

            $href = '/file/misc/' . $dao;
            $ret = '<a href="' . $href . '" title="' . $title  .
                '" target="_blank">';
            if ($covers_dir !== null) {
                //check if a cover exists
                $name_wo_ext = str_replace(
                    ltrim(strstr($dao, '.'), '.'),
                    '',
                    $dao
                );

                $cover_name = $name_wo_ext .= 'jpg';
                $src = '';
                $alt = '';

                if (file_exists($covers_dir . '/' . $cover_name)) {
                    $src = '/file/covers/';
                    if ($format === 'thumbs') {
                        $src = 'thumb/';
                    }
                    $src .= $cover_name;
                } else {
                    $filetype = self::_guessFileType($dao);
                    if (($bach_default_theme != 'web'
                        || $bach_default_theme != 'phone')
                        && (file_exists('assetic/img/'.$bach_default_theme.'_'.$filetype.'nocover.png'))
                    ) {
                        $src = '/assetic/img/' . $bach_default_theme .
                            '_' . $filetype . 'nocover.png';
                    } else {
                        $src = '/img/' . $filetype . 'nocover.png';
                    }
                }

                if ($daotitle) {
                    $alt = $daotitle;
                } else {
                    $alt = $dao;
                }

                $ret .='<img src="' . $src . '" alt="' . $alt . '"/>';
            } else if ($daotitle) {
                $ret .= '<span class="title">' . $daotitle . '</span>';
            } else {
                $ret .= $dao;
            }
            $ret .= '</a>';
            break;
        case self::EXTERNAL:
            $title = str_replace(
                '%name%',
                $dao,
                _("Display '%name%'")
            );

            $ret = '<a href="' . $dao . '" title="' . $title  . '" target="_blank">';
            if ($daotitle) {
                $ret .= $daotitle;
            } else {
                $ret .= $dao;
            }
            $ret .= '</a>';
            break;
        }

        return $ret;
    }

    /**
     * Generate a ramdon html id
     *
     * @param int $length Id lenght
     *
     * @return string
     */
    private static function _getRandomId($length = 15)
    {
        $characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, strlen($characters) - 1)];
        }
        return $randomString;
    }

    /**
     * Guess a file type from its name
     *
     * @param string $name File name
     *
     * @return string
     */
    private static function _guessFileType($name)
    {
        $ext_reg = "/^(.+)\.(.+)$/i";
        preg_match($ext_reg, $name, $matches);
        if (isset($matches[2])) {
            switch ( strtolower($matches[2]) ) {
            case 'pdf':
                return 'pdf_';
                break;
            case 'doc':
            case 'docx':
            case 'odt':
                return 'doc_';
                break;
            case 'xlsx':
            case 'xls':
            case 'ods':
                return 'sheet_';
                break;
            case 'xml':
                return 'xml_';
                break;
            case 'txt':
                return 'txt_';
                break;
            case 'jpg':
            case 'jpeg':
            case 'gif':
            case 'tif':
            case 'bmp':
                return 'img_';
                break;
            case 'webm':
            case 'flv':
            case 'avi':
            case 'mpg':
            case 'ogv':
            case 'mp4':
            case 'mov':
                return 'movie_';
                break;
            case 'mp3':
                return 'sound_';
                break;
            default:
                return '';
                break;
            }
        } else {
            return '';
        }
    }

    /**
     * Retrieve Dao type
     *
     * @param string $dao Document name
     *
     * @return int
     */
    private static function _getType($dao)
    {

        if (strpos($dao, 'http://') === 0 || strpos($dao, 'https://') === 0) {
            return self::EXTERNAL;
        }

        $all_reg = "/^(.+)\.(.+)$/i";
        $img_reg = "/^(.+)\.(" . implode('|', self::$_images_extensions) . ")$/i";
        $vid_reg = "/^(.+)\.(" . implode('|', self::$_videos_extensions) . ")$/i";
        $fla_reg = "/^(.+)\.(" . implode('|', self::$_flash_extensions) . ")$/i";
        $snd_reg = "/^(.+)\.(" . implode('|', self::$_sounds_extensions) . ")$/i";
        $fla_snd_reg = "/^(.+)\.(" . implode(
            '|',
            self::$_flash_sounds_extensions
        ) . ")$/i";

        $type = null;
        if (preg_match($fla_reg, $dao, $matches)) {
            //document is a flahs video
            $type = self::FLASH;
        } else if (preg_match($vid_reg, $dao, $matches)) {
            //document is a video
            $type = self::VIDEO;
        } else if (preg_match($snd_reg, $dao, $matches)) {
            //document is a HTML5 sound
            $type = self::SOUND;
        } else if (preg_match($fla_snd_reg, $dao, $matches)) {
            //document is a flash sound
            $type = self::FLA_SOUND;
        } else if (preg_match($img_reg, $dao, $matches)) {
            //document is an image
            $type = self::IMAGE;
        } else if (strpos($dao, '.xml') !== false) {
            //document is a xml file
            $type = self::XML;
        } else if (preg_match($all_reg, $dao, $matches)) {
            //document is a unkonwn file
            $type = self::MISC;
        } else {
            //document should be a series
            $type = self::SERIES;
        }
        return $type;
    }

    /**
     * Get link to document
     *
     * @param string $href Link
     *
     * @return string
     */
    public static function getDocumentLink($href)
    {
        $href = '/file/misc/' . $href;
        return $href;
    }

    /**
     * Extension name
     *
     * @return string
     */
    public function getName()
    {
        return 'display_dao';
    }
}
