<?php
/**
 * Search view parameters
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
 * @category Parameters
 * @package  Bach
 * @author   Johan Cwiklinski <johan.cwiklinski@anaphore.eu>
 * @license  BSD 3-Clause http://opensource.org/licenses/BSD-3-Clause
 * @link     http://anaphore.eu
 */

namespace Bach\HomeBundle\Entity;

use Symfony\Component\HttpFoundation\Request;

/**
 * Search view parameters
 *
 * @category Parameters
 * @package  Bach
 * @author   Johan Cwiklinski <johan.cwiklinski@anaphore.eu>
 * @license  BSD 3-Clause http://opensource.org/licenses/BSD-3-Clause
 * @link     http://anaphore.eu
 */
class ViewParams
{
    const ORDER_RELEVANCE = 0;
    const ORDER_TITLE = 1;
    const ORDER_DOC_LOGIC = 2;
    const ORDER_CHRONO = 3;

    const ORDER_ASC = 0;
    const ORDER_DESC = 1;

    const VIEW_LIST = 'list';
    const VIEW_TEXT_LIST = 'txtlist';
    const VIEW_THUMBS = 'thumbs';

    private $_show_pics = true;
    private $_results_by_page = 10;
    private $_view = null;
    protected $order = self::ORDER_RELEVANCE;
    private $_show_daterange = null;
    private $_show_map = null;

    private $_request;

    /**
     * Should pictures be displayed
     *
     * @return boolean
     */
    public function showPics()
    {
        return $this->_show_pics;
    }

    /**
     * Set pictures display
     *
     * @param boolean $show Display pictures or not
     *
     * @return void
     */
    public function setShowPics($show)
    {
        $this->_show_pics = $show;
    }

    /**
     * Should map be displayed
     *
     * @return boolean
     */
    public function showMap()
    {
        return $this->_show_map;
    }

    /**
     * Set map display
     *
     * @param boolean $show Display map or not
     *
     * @return void
     */
    public function setShowMap($show)
    {
        $this->_show_map = $show;
    }

    /**
     * Should date range be displayed
     *
     * @return boolean
     */
    public function showDaterange()
    {
        return $this->_show_daterange;
    }

    /**
     * Set date range display
     *
     * @param boolean $show Display date range or not
     *
     * @return void
     */
    public function setShowDaterange($show)
    {
        $this->_show_daterange = $show;
    }

    /**
     * Set number of results to display on each page
     *
     * @return int
     */
    public function getResultsbyPage()
    {
        return $this->_results_by_page;
    }

    /**
     * Set number of results to display on each page
     *
     * @param int $count Number of results to display
     *
     * @return void
     */
    public function setResultsByPage($count)
    {
        $this->_results_by_page = $count;
    }

    /**
     * Get current view
     *
     * @return string
     */
    public function getView()
    {
        return $this->_view;
    }

    /**
     * Set view
     *
     * @param string $view New view
     *
     * @return void
     */
    public function setView($view)
    {
        if ( $view === self::VIEW_LIST
            || $view === self::VIEW_TEXT_LIST
            || $view === self::VIEW_THUMBS
        ) {
            if ( $view === self::VIEW_TEXT_LIST ) {
                $this->setShowPics(false);
            } else {
                $this->setShowPics(true);
            }
            $this->_view = $view;
        } else {
            throw new \RuntimeException(
                str_replace(
                    '%s',
                    $view,
                    _('View %s is not known!')
                )
            );
        }
    }

    /**
     * Get order
     *
     * @return int
     */
    public function getOrder()
    {
        return $this->order;
    }

    /**
     * Set order
     *
     * @param int $order New order
     *
     * @return void
     */
    public function setOrder($order)
    {
        if ( $order === self::ORDER_RELEVANCE
            || $order === self::ORDER_TITLE
            || $order === self::ORDER_DOC_LOGIC
            || $order === self::ORDER_CHRONO
        ) {
            $this->order = $order;
        } else {
            throw new \RuntimeException(
                str_replace(
                    '%s',
                    $order,
                    _('Order %s is not known!')
                )
            );
        }
    }

    /**
     * Bind request
     *
     * @param Request $request     Request to bind to
     * @param string  $cookie_name Name for the cookie
     *
     * @return void
     */
    public function bind(Request $request, $cookie_name)
    {
        $this->_request = $request;

        if ( $request->get('view') ) {
            $this->setView($request->get('view'));
        }

        if ( $request->get('results_by_page') ) {
            $this->setResultsByPage($request->get('results_by_page'));
        }

        if ( $request->get('results_order')
            || $request->get('results_order') === '0'
        ) {
            $this->setOrder((int)$request->get('results_order'));
        }

        $set_cookie = false;
        if ( $request->get('show_map') ) {
            $set_cookie = true;
            switch( $request->get('show_map') ) {
            case 'on':
                $this->setShowMap(true);
                break;
            case 'off':
                $this->setShowMap(false);
                break;
            }
        }

        if ( $request->get('show_daterange') ) {
            $set_cookie = true;
            switch( $request->get('show_daterange') ) {
            case 'on':
                $this->setShowDaterange(true);
                break;
            case 'off':
                $this->setShowDaterange(false);
                break;
            }
        }

        if ( $set_cookie === true) {
            $_cook = new \stdClass();
            $_cook->map = $this->showMap();
            $_cook->daterange = $this->showDaterange();
            $expire = 365*24*3600;
            setcookie($cookie_name, json_encode($_cook), time()+$expire, '/');
        }
    }

    /**
     * Bind cookie
     *
     * @param string $name Cookie name
     *
     * @return void
     */
    public function bindCookie($name)
    {
        if ( isset($_COOKIE[$name]) ) {
            $_cook = json_decode($_COOKIE[$name]);
            $this->setShowMap($_cook->map);
            $this->setShowDaterange($_cook->daterange);
        }
    }
}
