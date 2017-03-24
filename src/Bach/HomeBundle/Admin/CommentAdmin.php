<?php
/**
 * Bach comments adminstration (for SonataAdminBundle)
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
namespace Bach\HomeBundle\Admin;

use Sonata\AdminBundle\Admin\Admin;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Bach\HomeBundle\Entity\Comment;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Bach comments management
 *
 * @category Search
 * @package  Bach
 * @author   Johan Cwiklinski <johan.cwiklinski@anaphore.eu>
 * @license  BSD 3-Clause http://opensource.org/licenses/BSD-3-Clause
 * @link     http://anaphore.eu
 */
class CommentAdmin extends Admin
{

    public $docId = '';
    public $statusComment = '';

    /**
     * Constructor
     *
     * @param string $code               ?
     * @param string $class              ?
     * @param string $baseControllerName ?
     */
    public function __construct($code, $class, $baseControllerName)
    {
        parent::__construct($code, $class, $baseControllerName);
    }

    /**
     * Fields to be shown on create/edit forms
     *
     * @param FormMapper $formMapper Mapper
     *
     * @return void
     */
    protected function configureFormFields(FormMapper $formMapper)
    {
        $formMapper
            ->add(
                'priority',
                'choice',
                array(
                    'choices'   => Comment::getKnownPriorities(),
                    'label'     => _('Type')
                )
            )
            ->add(
                'subject',
                null,
                array(
                    'label' => _('Subject')
                )
            )->add(
                'message',
                null,
                array(
                    'label' => _('Message')
                )
            )->add(
                'creation_date',
                null,
                array(
                    'label' => _('Creation date')
                )
            )->add(
                'opened_by',
                null,
                array(
                    'label' => _('From')
                )
            )->add(
                'message',
                null,
                array(
                    'label' => _('Message')
                )
            )->add(
                'state',
                'choice',
                array(
                    'choices'   => Comment::getKnownStates(),
                    'label'     => _('State')
                )
            );
        $this->docId = $this->getSubject()->getDocId();
        //FIXME optimize this call with related document type images
        $container  = $this->getConfigurationPool()->getContainer();
        $this->viewerAddress = $container ->getParameter('viewer_uri');
    }

    /**
     * Fields to be shown on filter forms
     *
     * @param DatagridMapper $datagridMapper Grid mapper
     *
     * @return void
     */
    protected function configureDatagridFilters(DatagridMapper $datagridMapper)
    {
        $datagridMapper
            ->add(
                'subject',
                null,
                array(
                    'label' => _('Subject')
                )
            )->add(
                'opened_by',
                null,
                array(
                    'label' => _('From')
                )
            )->add(
                'state',
                null,
                array(
                    'label'         => _('State'),
                    'field_type'    => 'choice',
                    'field_options' => array(
                        'choices' => Comment::getKnownStates()
                    )
                )
            )->add(
                'priority',
                null,
                array(
                    'label'         => _('Type'),
                    'field_type'    => 'choice',
                    'field_options' => array(
                        'choices' => Comment::getKnownPriorities()
                    )
                )
            );
    }

    /**
     * Fields to be shown on lists
     *
     * @param ListMapper $listMapper List mapper
     *
     * @return void
     */
    protected function configureListFields(ListMapper $listMapper)
    {
        $listMapper
            ->add(
                'opened_by',
                null,
                array(
                    'label' => _('From')
                )
            )->add(
                'priority',
                null,
                array(
                    'template'  => 'BachHomeBundle:Admin:show_type.html.twig',
                    'label'     => _('Type')
                )
            )->addIdentifier(
                'subject',
                null,
                array(
                    'label' => _('Subject')
                )
            )->add(
                'creation_date',
                null,
                array(
                    'label' => _('Date')
                )
            )->add(
                'state',
                null,
                array(
                    'template'  => 'BachHomeBundle:Admin:show_state.html.twig',
                    'label'     => _('State')
                )
            );
    }

    /**
     * Container injenction
     *
     * @param ContainerInterface $container Container
     *
     * @return void
     */
    public function setContainer(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * Configure batch actions
     *
     * @return array
     */
    public function getBatchActions()
    {
        $actions = parent::getBatchActions();

        if ( $this->hasRoute('edit') && $this->isGranted('EDIT') ) {
            $actions['publish'] = array(
                'label'             => _('Publish comments'),
                'ask_confirmation'  => true
            );
        }

        return $actions;
    }

    /**
     * Retrieve localized label for priority
     *
     * @param integer $index Priority
     *
     * @return string
     */
    public function getTypeLabel($index)
    {
        $types = Comment::getKnownPriorities();
        return $types[$index];
    }

    /**
     * Retrieve localized label for state
     *
     * @param integer $index State
     *
     * @return string
     */
    public function getStateLabel($index)
    {
        $states = Comment::getKnownStates();
        return $states[$index];
    }

    /**
    * Pre treatment before update comment
    *
    * @param Comment $comment Current comment
    *
    * @return void
    */
    public function preUpdate($comment)
    {
        $DM = $this->getConfigurationPool()->getContainer()->get('Doctrine')->getManager();
        $uow = $DM->getUnitOfWork();
        $OriginalEntityComment = $uow->getOriginalEntityData($comment);
        $this->statusComment = $OriginalEntityComment['state'];
    }

    /**
    * Post treatment after update comment
    *
    * @param Comment $comment Current comment
    *
    * @return void
    */
    public function postUpdate($comment)
    {
        if ($this->statusComment == '0'
            && ($comment->getState() == '1' || $comment->getState() == '3')
            && $comment->getOpenedBy() != null
        ) {
            $userTo  = $comment->getOpenedBy()->getEmail();
            if ($userTo != null && filter_var($userTo, FILTER_VALIDATE_EMAIL)) {
                $container  = $this->getConfigurationPool()->getContainer();
                $user       = $container->getParameter('mailer_user');
                $password   = $container->getParameter('mailer_password');
                $port       = $container->getParameter('mailer_port');
                $host       = $container->getParameter('mailer_host');
                $encryption = $container->getParameter('mailer_encryption');

                $transport  = \Swift_SmtpTransport::newInstance(
                    $host,
                    $port,
                    $encryption
                );
                $transport->setUserName($user);
                $transport->setPassword($password);
                $mailer  = \Swift_Mailer::newInstance($transport);
                $message = \Swift_Message::newInstance();
                //FIXME Manage english and translation
                $message->setSubject('Bach - Traitement commentaire');
                if ($container->getParameter('aws.sender') != null ) {
                    $message->setFrom($container->getParameter('aws.sender'));
                } else {
                    $message->setFrom($user);
                }
                $message->setTo($userTo);
                //FIXME Add a way to call here a template with the content
                $message->setBody(
                    "Bonjour, <br><br>Votre message " . $comment->getSubject() . " a été " . strtolower($this->getStateLabel($comment->getState())) .".<br><br>Cordialement.",
                    'text/html'
                );
                $mailer->send($message);
            }
        }
    }
}
