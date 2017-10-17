<?php
/**
 * Bach comments controller
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
 * @category Security
 * @package  Bach
 * @author   Johan Cwiklinski <johan.cwiklinski@anaphore.eu>
 * @license  BSD 3-Clause http://opensource.org/licenses/BSD-3-Clause
 * @link     http://anaphore.eu
 */

namespace Bach\HomeBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Bach\HomeBundle\Entity\Comment;
use Bach\HomeBundle\Form\Type\CommentType;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Bach comments controller
 *
 * PHP version 5
 *
 * @category Security
 * @package  Bach
 * @author   Johan Cwiklinski <johan.cwiklinski@anaphore.eu>
 * @license  BSD 3-Clause http://opensource.org/licenses/BSD-3-Clause
 * @link     http://anaphore.eu
 */
class CommentsController extends Controller
{
    /**
     * Add a comment
     *
     * @param string  $docid Document id
     * @param string  $type  Document type
     * @param boolean $ajax  Ajax render
     *
     * @return void
     */
    public function addAction($docid, $type, $ajax = false)
    {
        $request = $this->getRequest();

        $class = 'Comment';
        $class = ucfirst($type) . $class;
        $class = 'Bach\HomeBundle\Entity\\' . $class;
        $comment = new $class();

        $comment->setDocId($docid);

        //get user, if any
        $user = $this->getUser();
        if ( $user ) {
            $comment->setOpenedBy($user);
        }

        $form = $this->createForm(
            new CommentType(),
            $comment,
            array(
                'action' => $this->generateUrl(
                    'bach_add_comment',
                    array(
                        'type'  => $type,
                        'docid' => $docid
                    )
                )
            )
        );

        $tpl_vars = array(
            'docid' => $docid,
            'form'  => $form->createView(),
            'type'  => $type
        );

        //retrieve related document
        switch ( $type ) {
        case 'archives':
            $repo = $this->getDoctrine()
                ->getRepository('BachIndexationBundle:EADFileFormat');
            $eadfile = $repo->findOneByFragmentid($docid);
            $tpl_vars['eadfile'] = $eadfile;
            break;
        case 'matricules':
            $repo = $this->getDoctrine()
                ->getRepository('BachIndexationBundle:MatriculesFileFormat');
            $matricule = $repo->findOneById($docid);
            $tpl_vars['matricule'] = $matricule;
            break;
        }

        $form->handleRequest($request);
        if ( $form->isValid() ) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($comment);
            $em->flush();
            $this->get('session')->getFlashBag()->add(
                'success',
                _('Your comment has been stored. Thank you!')
            );
            if ( $type === 'images' ) {
                $template = 'confirmAddComment.html.twig';
                return $this->render(
                    'BachHomeBundle:Comment:' . $template
                );
            } else {
                $path = 'bach_display_document';
                if ( $type === 'matricules' ) {
                    $path = 'bach_display_matricules';
                }

                return $this->redirect(
                    $this->generateUrl(
                        $path,
                        array(
                            'docid' => $docid
                        )
                    )
                );
            }
        } else {
            $template = 'add.html.twig';
            if ( $ajax === 'ajax' ) {
                $template = 'add_form.html.twig';
            }
            return $this->render(
                'BachHomeBundle:Comment:' . $template,
                $tpl_vars
            );
        }
    }

    /**
     * Default page
     *
     * @param int    $docid Document Unique Identifier
     * @param string $type  Document type
     *
     * @return JsonResponse
     */
    public function getAction($docid, $type)
    {
        $show_comments = $this->container->getParameter('feature.comments');
        if ( $show_comments ) {

            $class = 'Comment';
            $class = ucfirst($type) . $class;

            $query = $this->getDoctrine()->getManager()
                ->createQuery(
                    'SELECT c FROM BachHomeBundle:' . $class . ' c
                    WHERE c.state = :state
                    AND c.docid = :docid
                    ORDER BY c.creation_date DESC'
                )->setParameters(
                    array(
                        'state'=> Comment::PUBLISHED,
                        'docid'=> $docid
                    )
                );
            $results = $query->getResult();
            $comments = array();
            foreach ( $results as $comment ) {
                $comments[] = array(
                    'subject'   => $comment->getSubject(),
                    'message'   => $comment->getMessage()
                );
            }

            $response = new JsonResponse();
            $response->setData($comments);
            return $response;
        }
        return new JsonResponse();
    }
}
