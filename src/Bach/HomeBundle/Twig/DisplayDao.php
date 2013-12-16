<?php
/**
 * Twig extension to display numeric documents
 *
 * PHP version 5
 *
 * @category Templating
 * @package  Bach
 * @author   Johan Cwiklinski <johan.cwiklinski@anaphore.eu>
 * @license  Unknown http://unknown.com
 * @link     http://anaphore.eu
 */

namespace Bach\HomeBundle\Twig;

use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\HttpFoundation\Request;

/**
 * Twig extension to display numeric documents
 *
 * PHP version 5
 *
 * @category Templating
 * @package  Bach
 * @author   Johan Cwiklinski <johan.cwiklinski@anaphore.eu>
 * @license  Unknown http://unknown.com
 * @link     http://anaphore.eu
 */
class DisplayDao extends \Twig_Extension
{
    private $_router;
    private $_viewer;

    private static $_images_extensions = array('jpeg', 'jpg', 'png', 'gif');
    private static $_sounds_extensions = array('ogg', 'wav');
    private static $_videos_extensions = array('ogv', 'mp4', 'webm', 'mov');
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

    /**
     * Main constructor
     *
     * @param UrlGeneratorInterface $router     Router
     * @param string                $viewer_uri Viewer URI
     * @param string                $covers_dir Covers directory
     */
    public function __construct(Router $router, $viewer_uri, $covers_dir)
    {
        $this->_router = $router;
        if ( !(substr($viewer_uri, -1) === '/') ) {
            $viewer_uri .= '/';
        }
        $this->_viewer = $viewer_uri;
        $this->_covers_dir = $covers_dir;
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
     * @param string  $daos   Documents
     * @param boolean $all    Displays all documents, default to false
     * @param string  $format Format to display, defaults to thumb
     *
     * @return string
     */
    public function display($daos, $all = false, $format = 'thumb')
    {
        if ( $all === false ) {
            return self::proceedDao(
                $daos[0],
                null,
                $this->_viewer,
                $format,
                false,
                true,
                $this->_covers_dir,
                false
            );
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
                    $this->_covers_dir
                );
            }
            return $res;
        }
    }

    /**
     * Get DAOs as XML nodes (XSLT will display them as string otherwise)
     *
     * @param NodeSet $daogrps    Groups list
     * @param NodeSet $daos       Documents list
     * @param string  $viewer     Viewer URL
     * @param string  $format     Format to display, defaults to thumb
     * @param boolean $ajax       Is call came from ajax
     * @param string  $covers_dir Covers directory
     *
     * @return DOMElement
     */
    public static function displayDaos($daogrps, $daos, $viewer, $format = 'thumb',
        $ajax = false, $covers_dir = null
    ) {
        //start root element
        $res = '<div>';

        if ( count($daogrps) > 0 ) {
            $groups_results = array();
            foreach ( $daogrps as $dg ) {
                $xml_dg = simplexml_import_dom($dg);

                $gres = array(
                    'title'     => null,
                    'content'   => array()
                );

                if ( $xml_dg['title'] ) {
                    $gres['title'] = (string)$xml_dg['title'];
                }

                $results = array(
                    self::IMAGE     => array(),
                    self::SERIES    => array(),
                    self::VIDEO     => array(),
                    self::FLASH     => array(),
                    self::SOUND     => array(),
                    self::FLA_SOUND => array(),
                    self::MISC     => array()
                );

                foreach ( $xml_dg->children() as $node_name => $xml_dao ) {
                    if ( $node_name === 'dao' || $node_name === 'daoloc' ) {
                        $dao = (string)$xml_dao['href'];
                        $daotitle = null;
                        if ( $xml_dao['title'] ) {
                            $daotitle = $xml_dao['title'];
                        }

                        $results[self::_getType($dao)][] = self::proceedDao(
                            $dao,
                            $daotitle,
                            $viewer,
                            $format,
                            $ajax,
                            true,
                            $covers_dir
                        );
                    }
                }
                $gres['content'] = $results;
                $groups_results[] = $gres;
            }

            foreach ( $groups_results as $group ) {
                $res .= '<section>';
                if ( $group['title'] !== null ) {
                    $res .= '<header><h4>' . $group['title'] . '</h4></header>';
                }

                $results = $group['content'];

                if ( count($results[self::IMAGE]) > 0 ) {
                    $res .= '<div>';
                    foreach ( $results[self::IMAGE] as $image ) {
                        $res .= $image;
                    }
                    $res .= '</div>';
                }

                if ( count($results[self::SERIES]) > 0 ) {
                    $res .= '<div>';
                    foreach ( $results[self::SERIES] as $series ) {
                        $res .= $series;
                    }
                    $res .= '</div>';
                }

                if ( count($results[self::SOUND]) > 0 ) {
                    $res .= '<div>';
                    foreach ( $results[self::SOUND] as $sound ) {
                        $res .= $sound;
                    }
                    $res .= '</div>';
                }

                if ( count($results[self::FLA_SOUND]) > 0 ) {
                    $res .= '<div>';
                    $res .= '<ul class="playlist">';
                    foreach ( $results[self::FLA_SOUND] as $sound ) {
                        $res .= $sound;
                    }
                    $res .= '</ul>';
                    $res .= '</div>';
                }

                if ( count($results[self::VIDEO]) > 0 ) {
                    $res .= '<div>';
                    foreach ( $results[self::VIDEO] as $video ) {
                        $res .= $video;
                    }
                    $res .= '</div>';
                }

                if ( count($results[self::FLASH]) > 0 ) {
                    $res .= '<div>';
                    foreach ( $results[self::FLASH] as $flash ) {
                        $res .= $flash;
                    }
                    $res .= '</div>';
                }

                if ( count($results[self::MISC]) > 0 ) {
                    $res .= '<div>';
                    foreach ( $results[self::MISC] as $other ) {
                        $res .= $other;
                    }
                    $res .= '</div>';
                }

                $res .= '</section>';
            }
        }

        if ( count($daos) > 0 ) {
            $results = array(
                self::IMAGE     => array(),
                self::SERIES    => array(),
                self::VIDEO     => array(),
                self::FLASH     => array(),
                self::SOUND     => array(),
                self::FLA_SOUND => array(),
                self::MISC     => array()
            );

            foreach ( $daos as $d ) {
                $xml_dao = simplexml_import_dom($d);
                $dao = (string)$xml_dao['href'];
                $daotitle = null;
                if ( $xml_dao['title'] ) {
                    $daotitle = $xml_dao['title'];
                }
                $results[self::_getType($dao)][] = self::proceedDao(
                    $dao,
                    $daotitle,
                    $viewer,
                    $format
                );
            }

            if ( count($results[self::IMAGE]) > 0 ) {
                $res .= '<section id="images">';
                $res .= '<header><h4>' . _('Images') . '</h4></header>';
                foreach ( $results[self::IMAGE] as $image ) {
                    $res .= $image;
                }
                $res .= '</section>';
            }

            if ( count($results[self::SERIES]) > 0 ) {
                $res .= '<section id="series">';
                $res .= '<header><h4>' . _('Series') . '</h4></header>';
                foreach ( $results[self::SERIES] as $series ) {
                    $res .= $series;
                }
                $res .= '</section>';
            }

            if ( count($results[self::SOUND]) > 0 ) {
                $res .= '<section id="sounds">';
                $res .= '<header><h4>' . _('Sounds') . '</h4></header>';
                foreach ( $results[self::SOUND] as $sound ) {
                    $res .= $sound;
                }
                $res .= '</section>';
            }

            if ( count($results[self::FLA_SOUND]) > 0 ) {
                $res .= '<section id="sounds">';
                $res .= '<header><h4>' . _('Flash sounds') . '</h4></header>';
                $res .= '<ul>';
                foreach ( $results[self::FLA_SOUND] as $sound ) {
                    $res .= $sound;
                }
                $res .= '</ul>';
                $res .= '</section>';
            }

            if ( count($results[self::VIDEO]) > 0 ) {
                $res .= '<section id="videos">';
                $res .= '<header><h4>' . _('Videos') . '</h4></header>';
                foreach ( $results[self::VIDEO] as $video ) {
                    $res .= $video;
                }
                $res .= '</section>';
            }

            if ( count($results[self::FLASH]) > 0 ) {
                $res .= '<section id="flashvideos">';
                $res .= '<header><h4>' . _('Flash videos') . '</h4></header>';
                foreach ( $results[self::FLASH] as $flash ) {
                    $res .= $flash;
                }
                $res .= '</section>';
            }

            if ( count($results[self::MISC]) > 0 ) {
                $res .= '<section id="other">';
                $res .= '<header><h4>' . _('Miscellaneous documents') .
                    '</h4></header>';
                foreach ( $results[self::MISC] as $other ) {
                    $res .= $other;
                }
                $res .= '</section>';
            }
        }

        //end root element
        $res .= '</div>';

        $sxml = simplexml_load_string($res);
        $doc = dom_import_simplexml($sxml);
        return $doc;
    }

    /**
     * Get a DAO as XML nodes (XSLT will display them as string otherwise)
     *
     * @param string $dao        Document name
     * @param string $title      Document title
     * @param string $viewer     Viewer URL
     * @param string $format     Format to display, defaults to thumb
     * @param string $covers_dir Covers directory
     *
     * @return DOMElement
     */
    public static function getDao($dao, $title, $viewer, $format = 'thumb',
        $covers_dir = null
    ) {
        $str = self::proceedDao(
            $dao,
            $title,
            $viewer,
            $format,
            false,
            true,
            $covers_dir
        );
        $sxml = simplexml_load_string($str);
        $doc = dom_import_simplexml($sxml);
        return $doc;
    }

    /**
     * Proceed daos
     *
     * @param string  $dao        Document name
     * @param string  $daotitle   Document title, if any
     * @param string  $viewer     Viewer URL
     * @param string  $format     Format to display
     * @param boolean $ajax       Does call came from ajax
     * @param boolean $standalone Is a standalone document, defaults to true
     * @param boolean $covers_dir Covers directory
     * @param boolean $all        Proceed all daos
     *
     * @return string
     */
    public static function proceedDao($dao, $daotitle, $viewer, $format,
        $ajax = false, $standalone = true, $covers_dir = null, $all = true
    ) {
        $ret = null;

        if ( !(substr($viewer, -1) === '/') ) {
            $viewer .= '/';
        }

        $title = str_replace(
            '%name%',
            ($daotitle) ? $daotitle : $dao,
            _("Play '%name%'")
        );
        switch ( self::_getType($dao) ) {
        case self::SERIES:
            $ret = '<a href="' . $viewer . 'series/' . $dao . '">';
            $ret .= '<img src="' . $viewer . 'ajax/representative/' .
                rtrim($dao, '/') .  '/format/' . $format  . '" alt="' . $dao . '"/>';
            if ( $daotitle !== null ) {
                $ret .= '<span class="title">' . $daotitle . '</span>';
            }
            $ret .= '</a>';
            break;
        case self::IMAGE:
            $ret = '<a href="' . $viewer . 'viewer/' . $dao . '">';
            $ret .= '<img src="' . $viewer . 'ajax/img/' . $dao .
                '/format/' . $format . '" alt="' . $dao .'"/>';
            if ( $daotitle !== null ) {
                $ret .= '<span class="title">' . $daotitle . '</span>';
            }
            $ret .= '</a>';
            break;
        case self::SOUND:
            $href = '/file/music/' . $dao;
            $ret .= '<audio controls="controls" width="300" height="30">';
            $ret .= '<source src="' . $href  . '"/>';
            $ret .= '</audio>';
            break;
        case self::VIDEO:
            $href = '/file/video/' . $dao;
            $ret = '<div class="htmlplayer standalone">';
            $ret .= '<video controls="controls" width="300" height="300">';
            $ret .= '<source src="' . $href  . '"/>';
            $ret .= '<a href="' . $href . '">' . _('Your browser does not support this video format, you may want to download file and watch it offline') . '</a>';
            $ret .= '</video>';
            if ( $daotitle !== null ) {
                $ret .= '<span class="title">' . $daotitle . '</span>';
            }
            $ret .= '</div>';
            break;
        case self::FLASH:
            $href = '/file/video/' . $dao;
            $class = '';
            if ( $standalone === true ) {
                $class = ' class="';
                if ( $ajax !== false ) {
                    $class .= 'ajaxflashplayer ';
                }
                $class .= 'flashplayer"';
            }
            $ret = '<a' . $class . ' href="' . $href . '" title="' .
                $title  . '">';
            if ( $standalone === true ) {
                if ( $covers_dir !== null ) {
                    //check if a cover exists
                    $name_wo_ext = str_replace(
                        ltrim(strstr($dao, '.'), '.'),
                        '',
                        $dao
                    );

                    $cover_name = $name_wo_ext .= 'jpg';
                    $src = '';
                    $alt = '';

                    if ( file_exists($covers_dir . '/' . $cover_name) ) {
                        $src = '/file/covers/';
                        if ( $format === 'thumbs' ) {
                            $src = 'thumb/';
                        }
                        $src .= $cover_name;
                    } else {
                        $filetype = self::_guessFileType($dao);
                        $src = '/img/' . $filetype . 'nocover.png';
                    }

                    if ( $daotitle ) {
                        $alt = $daotitle;
                    } else {
                        $alt = $dao;
                    }

                    $ret .='<img src="' . $src . '" alt="' . $alt . '"/>';
                } else {
                    $ret .= '<img src="/img/play_large.png" alt="' . $dao . '"/>';
                }
            }
            if ( $daotitle !== null ) {
                $ret .= '<span class="title">' . $daotitle . '</span>';
            }
            $ret .= '</a>';
            break;
        case self::FLA_SOUND;
            $class = '';
            $ret = '';
            if ( $standalone === true ) {
                $class = ' class="';
                if ( $ajax !== false ) {
                    $class .= 'ajaxflashmusicplayer ';
                }
                $class .= 'flashmusicplayer"';
            }
            if ( $all === true ) {
                $ret .= '<li>';
            }
            $href = '/file/music/' . $dao;
            $ret .= '<a' . $class . ' href="' . $href . '" title="' .
                $title  . '">';
            if ( $all === false ) {
                if ( $covers_dir !== null ) {
                    //check if a cover exists
                    $name_wo_ext = str_replace(
                        ltrim(strstr($dao, '.'), '.'),
                        '',
                        $dao
                    );

                    $cover_name = $name_wo_ext .= 'jpg';
                    $src = '';
                    $alt = '';

                    if ( file_exists($covers_dir . '/' . $cover_name) ) {
                        $src = '/file/covers/';
                        if ( $format === 'thumbs' ) {
                            $src = 'thumb/';
                        }
                        $src .= $cover_name;
                    } else {
                        $filetype = self::_guessFileType($dao);
                        $src = '/img/' . $filetype . 'nocover.png';
                    }

                    if ( $daotitle ) {
                        $alt = $daotitle;
                    } else {
                        $alt = $dao;
                    }

                    $ret .='<img src="' . $src . '" alt="' . $alt . '"/>';
                } else {
                    $ret .= '<img src="/img/sound_nocover.png" alt="' . $dao . '"/>';
                }
            }
            if ( $daotitle !== null ) {
                $ret .= '<span class="title">' . $daotitle . '</span>';
            }
            $ret .= '</a>';
            if ( $all === true ) {
                $ret .= '</li>';
            }
            break;
        case self::MISC:
            $title = str_replace(
                '%name%',
                $dao,
                _("Display '%name%'")
            );

            $href = '/file/misc/' . $dao;
            $ret = '<a href="' . $href . '" title="' . $title  . '">';
            if ( $covers_dir !== null ) {
                //check if a cover exists
                $name_wo_ext = str_replace(
                    ltrim(strstr($dao, '.'), '.'),
                    '',
                    $dao
                );

                $cover_name = $name_wo_ext .= 'jpg';
                $src = '';
                $alt = '';

                if ( file_exists($covers_dir . '/' . $cover_name) ) {
                    $src = '/file/covers/';
                    if ( $format === 'thumbs' ) {
                        $src = 'thumb/';
                    }
                    $src .= $cover_name;
                } else {
                    $filetype = self::_guessFileType($dao);
                    $src = '/img/' . $filetype . 'nocover.png';
                }

                if ( $daotitle ) {
                    $alt = $daotitle;
                } else {
                    $alt = $dao;
                }

                $ret .='<img src="' . $src . '" alt="' . $alt . '"/>';
            } else if ( $daotitle ) {
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

            $ret = '<a href="' . $dao . '" title="' . $title  . '">';
            if ( $daotitle ) {
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
        if ( isset($matches[2]) ) {
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

        if (strpos($dao, 'http://') === 0) {
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
        if ( preg_match($fla_reg, $dao, $matches) ) {
            //document is a flahs video
            $type = self::FLASH;
        } else if ( preg_match($vid_reg, $dao, $matches) ) {
            //document is a video
            $type = self::VIDEO;
        } else if ( preg_match($snd_reg, $dao, $matches) ) {
            //document is a HTML5 sound
            $type = self::SOUND;
        } else if ( preg_match($fla_snd_reg, $dao, $matches) ) {
            //document is a flash sound
            $type = self::FLA_SOUND;
        } else if ( preg_match($img_reg, $dao, $matches) ) {
            //document is an image
            $type = self::IMAGE;
        } else if ( preg_match($all_reg, $dao, $matches) ) {
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
