<?php
/**
 * Twig extension to display social buttons
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
 * Twig extension to display social buttons
 *
 * PHP version 5
 *
 * @category Templating
 * @package  Bach
 * @author   Johan Cwiklinski <johan.cwiklinski@anaphore.eu>
 * @license  BSD 3-Clause http://opensource.org/licenses/BSD-3-Clause
 * @link     http://anaphore.eu
 */
class BachSocialBar extends \Twig_Extension
{

    protected $container;

    /**
     * Constructor.
     *
     * @param ContainerInterface $container Container
     */
    public function __construct($container)
    {
        $this->container = $container;
    }

    /**
     * Add Filter for twig template
     *
     * @return array
     */
    public function getFilters()
    {
        return array(
            new \Twig_SimpleFilter(
                'minify',
                array(
                    $this,
                    'getMinifyUrl'
                )
            )
        );
    }

    /**
     * Extension name
     *
     * @return string
     */
    public function getName()
    {
        return 'bach_social_bar';
    }

    /**
     * Get provided functions
     *
     * @return array
     */
    public function getFunctions()
    {
        return array(
            'socialButtons' => new \Twig_Function_Method(
                $this,
                'getSocialButtons',
                array('is_safe' => array('html'))
            ),
            'facebookButton' => new \Twig_Function_Method(
                $this,
                'getFacebookLikeButton',
                array('is_safe' => array('html'))
            ),
            'twitterButton' => new \Twig_Function_Method(
                $this,
                'getTwitterButton',
                array('is_safe' => array('html'))
            ),
            'googlePlusButton' => new \Twig_Function_Method(
                $this,
                'getGooglePlusButton',
                array('is_safe' => array('html'))
            ),
            'scoopitButton' => new \Twig_Function_Method(
                $this,
                'getScoopitButton',
                array('is_safe' => array('html'))
            )
        );
    }

    /**
     * Get social buttons
     *
     * @param array $parameters Buttons parameters
     *
     * @return string
     */
    public function getSocialButtons($parameters = array())
    {
        // no parameters were defined, keeps default values
        if ( !array_key_exists('facebook', $parameters) ) {
            $render_parameters['facebook'] = array();
            // parameters are defined, overrides default values
        } else if ( is_array($parameters['facebook']) ) {
            $render_parameters['facebook'] = $parameters['facebook'];
            // the button is not displayed 
        } else {
            $render_parameters['facebook'] = false;
        }

        if ( !array_key_exists('twitter', $parameters) ) {
            $render_parameters['twitter'] = array();
        } else if ( is_array($parameters['twitter']) ) {
            $render_parameters['twitter'] = $parameters['twitter'];
        } else {
            $render_parameters['twitter'] = false;
        }

        if ( !array_key_exists('googleplus', $parameters) ) {
            $render_parameters['googleplus'] = array();
        } else if ( is_array($parameters['googleplus']) ) {
            $render_parameters['googleplus'] = $parameters['googleplus'];
        } else {
            $render_parameters['googleplus'] = false;
        }

        if ( !array_key_exists('scoopit', $parameters) ) {
            $render_parameters['scoopit'] = array();
        } else if ( is_array($parameters['scoopit']) ) {
            $render_parameters['scoopit'] = $parameters['scoopit'];
        } else {
            $render_parameters['scoopit'] = false;
        }

        // get the helper service and display the template
        return $this->container->get('bach.socialBarHelper')
            ->socialButtons($render_parameters);
    }

    /**
     * Facebook like button
     *
     * @param array $parameters Fb like parameters
     *
     * @see https://developers.facebook.com/docs/reference/plugins/like/
     *
     * @return string
     */
    public function getFacebookLikeButton($parameters = array())
    {
        $enabled = $this->container->getParameter('social.fb');

        if ( !$enabled ) {
            return '';
        }

        // default values, you can override the values by setting them
        $parameters = $parameters + array(
            'url'       => null,
            'locale'    => $this->container->getParameter('social.fb.locale'),
            'width'     => $this->container->getParameter('social.fb.width'),
            'showFaces' => $this->container->getParameter('social.fb.showFaces'),
            'layout'    => $this->container->getParameter('social.fb.layout'),
            'share'     => $this->container->getParameter('social.fb.share')
        );

        return $this->container->get('bach.socialBarHelper')
            ->facebookButton($parameters);
    }

    /**
     * Twitter button
     *
     * @param array $parameters Twitter parameters
     *
     * @return string
     */
    public function getTwitterButton($parameters = array())
    {
        $enabled = $this->container->getParameter('social.twitter');

        if ( !$enabled ) {
            return '';
        }

        $parameters = $parameters + array(
            'url'       => null,
            'locale'    => $this->container->getParameter('social.twitter.locale'),
            'message'   => $this->container->getParameter('social.twitter.message'),
            'text'      => $this->container->getParameter('social.twitter.text'),
            'via'       => $this->container->getParameter('social.twitter.via'),
            'tag'       => $this->container->getParameter('social.twitter.tag'),
        );

        return $this->container->get('bach.socialBarHelper')
            ->twitterButton($parameters);
    }

    /**
     * Google Plus button
     *
     * @param array $parameters G+ parameters
     *
     * @return string
     */
    public function getGooglePlusButton($parameters = array())
    {
        $enabled = $this->container->getParameter('social.gplus');

        if ( !$enabled ) {
            return '';
        }

        $parameters = $parameters + array(
            'url'           => null,
            'locale'        => $this->container->getParameter('social.gplus.locale'),
            'size'          => $this->container->getParameter('social.gplus.size'),
            'annotation'    => $this->container->getParameter('social.gplus.annotation'),
            'width'         => $this->container->getParameter('social.gplus.width'),
        );

        return $this->container->get('bach.socialBarHelper')
            ->googlePlusButton($parameters);
    }

    /**
     * Scoopit buttons
     *
     * @param array $parameters Scoopit parameters
     *
     * @return string
     */
    public function getScoopitButton($parameters = array())
    {
        $enabled = $this->container->getParameter('social.scoopit');

        if ( !$enabled ) {
            return '';
        }

        $parameters = $parameters + array(
            'url'       => null,
            'layout'    => $this->container->getParameter('social.scoopit.layout')
        );

        return $this->container->get('bach.socialBarHelper')
            ->scoopitButton($parameters);
    }

    /**
     * Return minify url
     *
     * @param string $url longUrl to minify
     *
     * @return string
     */
    public function getMinifyUrl($url)
    {

        $minifyProviderVersion = $this->container->getParameter(
            'minify.providerversion'
        );
        $minifyLogin           = $this->container->getParameter('minify.login');
        $minifyApikey          = $this->container->getParameter('minify.apikey');

        //create the URL
        $bitly = 'http://api.bit.ly/shorten?version='. $minifyProviderVersion.
            '&longUrl='.urlencode($url).
            '&login='.$minifyLogin.
            '&apiKey='.$minifyApikey.
            '&format=json';

        //get the url
        //could also use cURL here
        $response = file_get_contents($bitly);
        $json = @json_decode($response, true);
        return $json['results'][$url]['shortUrl'];
    }

}
