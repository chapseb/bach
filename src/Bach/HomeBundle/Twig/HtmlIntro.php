<?php
/**
 * Does asset exists
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

/**
 * Handle HTML intorduction files
 *
 * PHP version 5
 *
 * @category Templating
 * @package  Bach
 * @author   Johan Cwiklinski <johan.cwiklinski@anaphore.eu>
 * @license  BSD 3-Clause http://opensource.org/licenses/BSD-3-Clause
 * @link     http://anaphore.eu
 */
class HtmlIntro extends \Twig_Extension
{
    private $_router;
    private $_request;
    private $_html_intros_path;

    /**
     * Main constructor
     *
     * @param UrlGeneratorInterface $router Router
     */
    public function __construct(Router $router)
    {
        $this->_router = $router;
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
            'html_intro_exists' => new \Twig_Function_Method(
                $this,
                'htmlIntroExists'
            ),
            'get_html_intro' => new \Twig_Function_Method(
                $this,
                'getHtmlIntro'
            )
        );
    }

    /**
     * Set HTML contents path
     *
     * @param string $path Path to html contents files
     *
     * @return void
     */
    public function setHtmlContentsPath($path)
    {
        $this->_html_intros_path = $path;
    }

    /**
     * Checks if HTML intro file exists
     *
     * @param string $name File name
     *
     * @return boolean
     */
    public function htmlIntroExists($name)
    {
        $file = realpath($this->_html_intros_path . '/' . $name);

        // check if the file exists
        if (!is_file($file)) {
            return false;
        }

        return true;
    }

    /**
     * Get file contents
     *
     * @param string $name File name
     *
     * @return string
     */
    public function getHtmlIntro($name)
    {
        $contents = file_get_contents(
            $this->_html_intros_path. $name
        );

        $router = $this->_router;
        $request = $this->_request;
        $img_callback = function ($matches) use ($router) {
            $img = '<img';
            $img .= $matches[1];
            $img .= ' src="';

            $img .= $router->generate(
                'bach_get_html_intro_file',
                array(
                    'name' => $matches[2]
                )
            );

            $img .= '"' . $matches[3] . '/>';
            return $img;
        };

        $doclink_callback = function ($matches) use ($router) {
            if ( strpos($matches[1], 'http://') === 0
                || strpos($matches[1], 'https://') === 0
            ) {
                return $matches[0];
            } else if ( $matches[1] === 'cdc' ) {
                $href = $router->generate('bach_classification');
                return 'href="' . $href . '"';
            } else if ( substr($matches[1], -strlen('.html')) === '.html' ) {
                $href = $router->generate(
                    'bach_htmldoc',
                    array(
                        'docid' => str_replace('.html', '', $matches[1])
                    )
                );
                return 'href="' . str_replace('&', '&amp;', $href) . '"';
            } else {
                $href = $router->generate(
                    'bach_ead_html',
                    array(
                        'docid' => $matches[1]
                    )
                );
                return 'href="' . str_replace('&', '&amp;', $href) . '"';
            }
        };

        $filters_callback = function ($matches) use ($router, $request) {
            $href = $router->generate(
                'federate_search',
                array(
                    'query_terms'   => $request->get('query_terms'),
                    'filter_field'  => $matches[1],
                    'filter_value'  => $matches[2]
                )
            );
            return 'href="' . str_replace('&', '&amp;', $href) . '"';
        };

        $contents = preg_replace_callback(
            '@<img(.*) src="(.[^"]+)"(.*)/>@',
            $img_callback,
            $contents
        );

        $contents = preg_replace_callback(
            '/href="(.[^"]+)"/',
            $doclink_callback,
            $contents
        );

        $contents = preg_replace_callback(
            '/link="%%%(.[^:]+)::(.[^%]*)%%%"/',
            $filters_callback,
            $contents
        );

        return $contents;
    }

    /**
     * Extension name
     *
     * @return string
     */
    public function getName()
    {
        return 'asset_exists';
    }
}
