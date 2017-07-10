<?php
/**
 * Bach core administration controller
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
 * @author   Johan Cwiklinski <johan.cwiklinski@anaphore.eu>
 * @license  BSD 3-Clause http://opensource.org/licenses/BSD-3-Clause
 * @link     http://anaphore.eu
 */

namespace Bach\AdministrationBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Bach\AdministrationBundle\Entity\Helpers\FormObjects\CoreCreation;
use Bach\AdministrationBundle\Entity\SolrCore\SolrCoreAdmin;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sonata\AdminBundle\Controller\CoreController as BaseCoreContBroller;

/**
 * Bach core administration controller
 *
 * @category Administration
 * @package  Bach
 * @author   Johan Cwiklinski <johan.cwiklinski@anaphore.eu>
 * @license  BSD 3-Clause http://opensource.org/licenses/BSD-3-Clause
 * @link     http://anaphore.eu
 */
class CoreAdminController extends BaseCoreContBroller
{

    /**
     * New Solr core creation page
     *
     * @param boolean $ajax Display from ajax call
     *
     * @return void
     */
    public function newCoreAction($ajax = false)
    {
        $session = $this->getRequest()->getSession();
        $form = $this->createForm('corecreation');

        $template = 'newcore';
        if ( $ajax !== false ) {
            $template = 'newcore-content';
        }

        return $this->render(
            'AdministrationBundle:Default:' . $template . '.html.twig',
            array(
                'form'      => $form->createView(),
                'coreNames' => $session->get('coreNames')
            )
        );
    }

    /**
     * Adds new core
     *
     * @param Request $request Request
     *
     * @return void
     */
    public function addAction(Request $request)
    {
        $this->_createCore($request);
        return $this->redirect(
            $this->generateUrl('administration_dashboard')
        );
    }

    /**
     * Create a new core
     *
     * @param Request $request Request
     *
     * @return Form
     */
    private function _createCore(Request $request)
    {
        $session = $request->getSession();
        $configreader = $this->container->get('bach.administration.configreader');
        $sca = new SolrCoreAdmin($configreader);
        $cc = new CoreCreation();
        $form = $this->createForm('corecreation', $cc);
        $form->bind($request);

        $em = $this->getDoctrine()->getManager();
        $orm_name = 'Bach\IndexationBundle\Entity';
        switch ( $cc->core ) {
        case 'EADFileFormat':
        case 'ead':
            $orm_name .= '\EADFileFormat';
            break;
        case 'MatriculesFileFormat':
        case 'matricules':
            $orm_name .= '\MatriculesFileFormat';
            break;
        default:
            throw new \RuntimeException(
                str_replace(
                    '%type',
                    $cc->core,
                    _('Unkwown type %type')
                )
            );
            break;
        }

        $db_params = $sca->getJDBCDatabaseParameters(
            $this->container->getParameter('database_driver'),
            $this->container->getParameter('database_host'),
            $this->container->getParameter('database_port'),
            $this->container->getParameter('database_name'),
            $this->container->getParameter('database_user'),
            $this->container->getParameter('database_password')
        );

        $result = $sca->create(
            $cc->core,
            $cc->name,
            $orm_name,
            $em,
            $db_params
        );

        if ( count($sca->getErrors()) > 0) {
            foreach ( $sca->getErrors() as $w ) {
                $this->get('session')->getFlashBag()->add('errors', $w);
            }
        }

        if ( count($sca->getWarnings()) > 0) {
            foreach ( $sca->getWarnings() as $w ) {
                $this->get('session')->getFlashBag()->add('warnings', $w);
            }
        }

        if ($result !== false && $result->isOk()) {
            $coreNames = $sca->getStatus()->getCoreNames();
            $session->set('coreNames', $coreNames);
        }
        return $form;
    }


    /**
     * Return the dashboard admin
     *
     * @return Response
     */
    public function dashboardAction()
    {
        $tpl_vars = $this->getDoctrine()
            ->getRepository('BachHomeBundle:Parameters')
            ->createQueryBuilder('a')
            ->select('a.name, a.value')
            ->getQuery()
            ->getResult();

        $tpl_vars_admin = array();
        foreach ( $tpl_vars as $var) {
            $tpl_vars_admin[$var['name']] = $var['value'];
        }

        $session = $this->get('session');
        $session->set(
            'tpl_vars_admin',
            $tpl_vars_admin
        );
        return $this->render(
            $this->getAdminPool()->getTemplate('dashboard'),
            array(
                'base_template'   => $this->getBaseTemplate(),
                'admin_pool'      => $this->container->get('sonata.admin.pool'),
                'blocks'          => $this->container->getParameter('sonata.admin.configuration.dashboard_blocks')
            )
        );
    }

}
