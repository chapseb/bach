<?php
/**
 * Bach virtual expositions controller
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
 * @category Search
 * @package  Bach
 * @author   Johan Cwiklinski <johan.cwiklinski@anaphore.eu>
 * @license  BSD 3-Clause http://opensource.org/licenses/BSD-3-Clause
 * @link     http://anaphore.eu
 */

namespace Bach\ExposBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

/**
 * Bach virtual expositions controller
 *
 * PHP version 5
 *
 * @category Search
 * @package  Bach
 * @author   Johan Cwiklinski <johan.cwiklinski@anaphore.eu>
 * @license  BSD 3-Clause http://opensource.org/licenses/BSD-3-Clause
 * @link     http://anaphore.eu
 */
class DefaultController extends Controller
{

    /**
     * Expositions list
     *
     * @return void
     */
    public function indexAction()
    {
        $repository = $this->getDoctrine()->getRepository('ExposBundle:Exposition');

        $expos = $repository->findCurrent();

        $tpl_vars['parameters'] = $this->getApplicationParameters();
        $tpl_vars['expos']      = $expos;
        return $this->render(
            'ExposBundle:Default:index.html.twig',
            $tpl_vars
        );
    }

    /**
     * Exposition page
     *
     * @param string $expo Exposition name
     *
     * @return void
     */
    public function showAction($expo)
    {
        $repository = $this->getDoctrine()->getRepository('ExposBundle:Exposition');

        $expos = $repository->findCurrent();
        $expo = $repository->findOneByUrl($expo);

        if ( $expo ) {
            $position = -1;
            foreach ( $expos as $ex ) {
                $position++;
                if ( $ex->getId() === $expo->getId() ) {
                    break;
                }
            }

            return $this->render(
                'ExposBundle:Default:show_expo.html.twig',
                array(
                    'position'      => $position,
                    'expos'         => $expos,
                    'current_expo'  => $expo,
                    'parameters'    => $this->getApplicationParameters()
                )
            );
        } else {
            throw $this->createNotFoundException(
                _('Cannot found requested exposition!')
            );
        }
    }

    /**
     * Room page
     *
     * @param string $expo Exposition name
     * @param string $room Room name
     *
     * @return void
     */
    public function showRoomAction($expo, $room)
    {
        $repository = $this->getDoctrine()->getRepository('ExposBundle:Room');
        $room = $repository->findOneByUrl($room);

        $repository = $this->getDoctrine()->getRepository('ExposBundle:Exposition');
        $expos = $repository->findCurrent();
        $expo = $repository->findOneByUrl($expo);

        $position = -1;
        foreach ( $expos as $ex ) {
            $position++;
            if ( $ex->getId() === $expo->getId() ) {
                break;
            }
        }

        return $this->render(
            'ExposBundle:Default:show_room.html.twig',
            array(
                'position'      => $position,
                'expos'         => $expos,
                'current_expo'  => $expo,
                'current_room'  => $room,
                'parameters'    => $this->getApplicationParameters()
            )
        );
    }

    /**
     * Panel page
     *
     * @param string $expo  Exposition name
     * @param string $room  Room name
     * @param string $panel Panel name
     *
     * @return void
     */
    public function showPanelAction($expo, $room, $panel)
    {
        $repository = $this->getDoctrine()->getRepository('ExposBundle:Panel');
        $panel = $repository->findOneByUrl($panel);

        $room = $panel->getRoom();

        $repository = $this->getDoctrine()->getRepository('ExposBundle:Exposition');
        $expos = $repository->findCurrent();
        $expo = $repository->findOneByUrl($expo);

        $position = -1;
        foreach ( $expos as $ex ) {
            $position++;
            if ( $ex->getId() === $expo->getId() ) {
                break;
            }
        }

        return $this->render(
            'ExposBundle:Default:show_panel.html.twig',
            array(
                'position'      => $position,
                'expos'         => $expos,
                'current_expo'  => $expo,
                'current_room'  => $room,
                'current_panel' => $panel,
                'parameters'    => $this->getApplicationParameters()
            )
        );
    }

    /**
     * Retrieve application parameters
     *
     * @return array
     */
    public function getApplicationParameters()
    {
        $tpl_vars = $this->getDoctrine()
            ->getRepository('BachHomeBundle:Parameters')
            ->createQueryBuilder('a')
            ->select('a.name, a.value')
            ->getQuery()
            ->getResult();

        $tpl_vars_param = array();
        foreach ( $tpl_vars as $var) {
            $tpl_vars_param[$var['name']] = $var['value'];
        }

        return $tpl_vars_param;
    }

}
