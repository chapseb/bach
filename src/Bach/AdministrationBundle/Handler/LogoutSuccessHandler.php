<?php
/**
 * Bach Sonata user admin model
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
 * @category Administration
 * @package  Bach
 * @author   Sebastien Chaptal <sebastien.chaptal@anaphore.eu>
 * @license  BSD 3-Clause http://opensource.org/licenses/BSD-3-Clause
 * @link     http://anaphore.eu
 */

namespace Bach\AdministrationBundle\Handler;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Logout\DefaultLogoutSuccessHandler;
use Symfony\Component\Security\Http\HttpUtils;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Class with custom authentication success.
 *
 * PHP version 5
 *
 * @category Administration
 * @package  Bach
 * @author   Sebastien Chaptal <sebastien.chaptal@anaphore.eu>
 * @license  BSD 3-Clause http://opensource.org/licenses/BSD-3-Clause
 * @link     http://anaphore.eu
 */

class LogoutSuccessHandler extends DefaultLogoutSuccessHandler
{
    protected $httpUtils;
    protected $targetUrl;

    /**
     * {@inheritdoc}
     *
     * @param HttpUtils $httpUtils Http utils
     * @param String    $targetUrl Url to go to after log out
     */
    public function __construct(HttpUtils $httpUtils, $targetUrl = '/')
    {
        parent::__construct($httpUtils, $targetUrl);
        $this->HttpUtils = $httpUtils;
        $this->targetUrl = $targetUrl;
    }

    /**
     * {@inheritdoc}
     *
     * @param Request $request request
     *
     * @return RedirectResponse
     */
    public function onLogoutSuccess(Request $request)
    {
        $host = str_replace('.', '_', $request->getHost());

        if (isset($_COOKIE[$host.'_bach_cookie_reader'])) {
            setcookie(
                $host.'_bach_cookie_reader',
                '',
                time()-3600
            );
        }

        $host_names = explode(".", $_SERVER['HTTP_HOST']);
        $domain = $host_names[count($host_names)-2] . "." . $host_names[count($host_names)-1];
        setcookie($host.'_bach_cookie_reader', '', -1, '/', $domain);
        return $this->httpUtils->createRedirectResponse($request, $this->targetUrl);
    }
}
