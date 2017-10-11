<?php
/**
 * Default indexation controller
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
 * @category Indexation
 * @package  Bach
 * @author   Johan Cwiklinski <johan.cwiklinski@anaphore.eu>
 * @author   Sebastien Chaptal <sebastien.chaptal@anaphore.eu>
 * @license  BSD 3-Clause http://opensource.org/licenses/BSD-3-Clause
 * @link     http://anaphore.eu
 */

namespace Bach\IndexationBundle\Controller;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Bach\IndexationBundle\Entity\Document;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Finder\SplFileInfo;
use Bach\IndexationBundle\Entity\IntegrationTask;
use Bach\AdministrationBundle\Entity\SolrCore\SolrCoreAdmin;
use Solarium\Exception\HttpException;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\HttpFoundation\Response;
use Bach\IndexationBundle\Command\PublishCommand;
use Bach\IndexationBundle\Command\UnpublishCommand;
use Bach\IndexationBundle\Entity\BachToken;
use Bach\HomeBundle\Entity\Comment;

/**
 * Default indexation controller
 *
 * PHP version 5
 *
 * @category Indexation
 * @package  Bach
 * @author   Johan Cwiklinski <johan.cwiklinski@anaphore.eu>
 * @author   Sebastien Chaptal <sebastien.chaptal@anaphore.eu>
 * @license  BSD 3-Clause http://opensource.org/licenses/BSD-3-Clause
 * @link     http://anaphore.eu
 */
class DefaultController extends Controller
{

    /**
     * Displays current indexed documents
     *
     * @param string  $type Documents type
     * @param int     $page Current page
     * @param boolean $ajax Ajax request
     *
     * @return void
     */
    public function indexAction($type = 'ead', $page = 1, $ajax = false)
    {
        $show = 30;
        if ( $page === 0 ) {
            $page = 1;
        }
        $repo = $this->getDoctrine()->getRepository('BachIndexationBundle:Document');

        $known_types = $this->container->getParameter('bach.types');

        if ( !in_array($type, $known_types) ) {
            foreach ( $known_types as $known_type ) {
                $type = $known_type;
                break;
            }
        }

        $documents = $repo->getPublishedDocuments($page, $show, $type);
        $template = ($ajax === false) ? 'index' : 'published_documents';

        return $this->render(
            'BachIndexationBundle:Indexation:' . $template  . '.html.twig',
            array(
                'current_type'  => $type,
                'documents'     => $documents,
                'currentPage'   => $page,
                'lastPage'      => ceil(count($documents) / $show),
                'known_types'   => $known_types
            )
        );
    }

    /**
     * Display indexation queue and form
     * with publication new version (upload4anaphore)
     *
     * @return void
     */
    public function queueAction()
    {
        $em = $this->getDoctrine()->getManager();
        $repository = $em
            ->getRepository('BachIndexationBundle:BachToken');

        $entities = $repository
            ->createQueryBuilder('t')
            ->orderBy('t.id', 'ASC')
            ->getQuery()
            ->getResult();

        foreach ($entities as $entity) {
            $action = 1;
            if ($entity->getAction() == 0) {
                $action = 0;
            }
            $tokens[] = array(
                'id'          => $entity->getId(),
                'filename'    => $entity->getFilename(),
                'bach_token'  => $entity->getBachToken(),
                'action'      => $action,
                'action_type' => $entity->getActionType()
            );
        }
        if (!isset($tokens)) {
            $tokens = array();
        }
        return $this->render(
            'BachIndexationBundle:Indexation:queue.html.twig',
            array(
                'tokens'         => $tokens
            )
        );
    }

    /**
     * Delete current token in database
     * can unblock token publication if problem
     *
     * @return void
     */
    public function unblockAction()
    {
        $logger = $this->get('logger');
        try {
            $em = $this->get('doctrine')->getManager();
            $query = $em->createQuery(
                'SELECT t FROM BachIndexationBundle:BachToken t
                WHERE t.action = 1'
            );

            if (!empty($query->getResult())) {
                $result = $query->getResult()[0];
                $em->remove($result);
                $em->flush();
            }
        } catch ( \Exception $e ) {
            $logger->error('Exception : '.  $e->getMessage(). "\n");
            throw $e;
        }
        return new RedirectResponse(
            $this->get("router")->generate("bach_indexation_queue")
        );
    }



    /**
     * Purge controller
     *
     * @return void
     */
    public function purgeAction()
    {
        $em = $this->getDoctrine()->getManager();
        $query = $em->createQuery(
            'SELECT t FROM BachIndexationBundle:IntegrationTask t ' .
            'WHERE t.status > ' . IntegrationTask::STATUS_NONE
        );
        $tasks = $query->getResult();

        foreach ($tasks as $task) {
            $em->remove($task);
        }
        $em->flush();
        return new RedirectResponse(
            $this->get("router")->generate("bach_indexation_queue")
        );
    }

    /**
     * Remove selected indexed documents, in both database and Solr
     *
     * @param array $documents List of id to remove. If missing,
     *                         we'll take documents in GET.
     *
     * @return void
     */
    public function removeDocumentsAction($documents = null)
    {
        $logger = $this->get('logger');
        if ( $documents === null ) {
            $documents = $this->get('request')->request->get('documents');
        }

        $extensions = array();
        $ids = array();
        foreach ( $documents as $document) {
            list($extension, $id) = explode('::', $document);
            if ( !isset($extensions[$extension]) ) {
                $extensions[$extension] = array();
            }
            $extensions[$extension][] = $id;
            $ids[] = $id;
        }

        $em = $this->getDoctrine()->getManager();

        $qb = $em->createQueryBuilder();
        $qb->add('select', 'd')
            ->add('from', 'BachIndexationBundle:Document d')
            ->add('where', 'd.id IN (:ids)')
            ->setParameter('ids', $ids);

        $query = $qb->getQuery();
        $docs = $query->getResult();

        //remove solr indexed documents per core
        $updates = array();
        $clients = array();

        foreach ($docs as $doc) {
            if ( !isset($updates[$doc->getCorename()]) ) {
                $client = $this->get('solarium.client.' . $doc->getExtension());
                $clients[$doc->getCorename()] = $client;
                $updates[$doc->getCorename()] = $client->createUpdate();
            }
            $doc->setUploadDir($this->container->getParameter('upload_dir'));
            $update = $updates[$doc->getCorename()];
            if ( $doc->getExtension() === 'matricules' ) {
                $update->addDeleteQuery('id:' . $doc->getDocId());
            } else {
                $update->addDeleteQuery('headerId:' . $doc->getDocId());
            }
            if ( $doc->getExtension() == 'ead' ) {
                $qb = $em->createQueryBuilder();
                $qb->add('select', 'd')
                    ->add('from', 'BachIndexationBundle:EADHeader h')
                    ->add('where', 'h.headerId = :id)')
                    ->setParameter('id', $doc->getDocId());
                $eadheader = $query->getResult();
                $em->remove($eadheader[0]);
            }
            $em->remove($doc);
        }

        foreach ( $updates as $key=>$update ) {
            $client = $clients[$key];
            $update->addCommit(null, null, true);
            $result = $client->update($update);
            if ( $result->getStatus() === 0 ) {
                $logger->info(
                    str_replace(
                        array('%doc', '%time'),
                        array($doc->getDocId(), $result->getQueryTime()),
                        _('Document %doc successfully deleted from Solr in %time')
                    )
                );
            } else {
                $logger->err(
                    str_replace(
                        '%doc',
                        $doc->getDocId(),
                        _('Solr failed to remove document %doc!')
                    )
                );
            }
        }

        $em->flush();

        return new RedirectResponse(
            $this->get("router")->generate("bach_indexation_homepage")
        );
    }

    /**
     * Remove all indexed documents, in both database and Solr
     *
     * @param string $type Type to remove
     *
     * @return void
     */
    public function emptyAction($type = 'all')
    {
        $logger = $this->get('logger');
        //first, remove from database
        $em = $this->getDoctrine()->getManager();
        $connection = $em->getConnection();
        $platform   = $connection->getDatabasePlatform();

        try {
            $connection->query('SET FOREIGN_KEY_CHECKS=0');
        } catch (\Exception $e) {
            //database does not support that. it is ok.
        }

        if ( $type == 'ead' || $type == 'all' ) {
            $connection->executeUpdate(
                $platform->getTruncateTableSQL('ead_header', true)
            );

            $connection->executeUpdate(
                $platform->getTruncateTableSQL('ead_dates', true)
            );
            $connection->executeUpdate(
                $platform->getTruncateTableSQL('ead_indexes', true)
            );
            $connection->executeUpdate(
                $platform->getTruncateTableSQL('ead_daos', true)
            );
            $connection->executeUpdate(
                $platform->getTruncateTableSQL('ead_parent_title', true)
            );
            $connection->executeUpdate(
                $platform->getTruncateTableSQL('ead_file_format', true)
            );
        }

        if ( $type == 'matricules' || $type == 'all' ) {
            $connection->executeUpdate(
                $platform->getTruncateTableSQL('matricules_file_format', true)
            );
        }

        if ( $type == 'all' ) {
            $connection->executeUpdate(
                $platform->getTruncateTableSQL('documents', true)
            );
            $connection->executeUpdate(
                $platform->getTruncateTableSQL('integration_task', true)
            );
        }

        try {
            $connection->query('SET FOREIGN_KEY_CHECKS=1');
        } catch (\Exception $e) {
            //database does not support that. it is ok.
        }

        if ( $type !== 'all' ) {
            $connection->query(
                str_replace(
                    '%ext',
                    $type,
                    'DELETE FROM documents WHERE extension="%ext"'
                )
            );
        }

        //remove solr indexed documents
        $known_types = $this->container->getParameter('bach.types');
        if ( $type !== 'all' ) {
            $known_types = array($type);
        }
        foreach ( $known_types as $type ) {
            $client = $this->get('solarium.client.' . $type);
            $update = $client->createUpdate();
            $update->addDeleteQuery('*:*');
            $update->addCommit();

            try {
                $result = $client->update($update);

                if ( $result->getStatus() === 0 ) {
                    $logger->info(
                        str_replace(
                            array('%core', '%time'),
                            array($type, $result->getQueryTime()),
                            _('%core core has been truncated in %time')
                        )
                    );
                } else {
                    $logger->err(
                        str_replace(
                            '%core',
                            $type,
                            _('Solr failed to empty %core core!')
                        )
                    );
                }
            } catch ( HttpException $ex ) {
                $logger->err(
                    str_replace(
                        '%core',
                        $type,
                        _('Solr failed to empty %core core!') . ' | ' .
                        $ex->getMessage()
                    )
                );
            }
        }

        return new RedirectResponse(
            $this->get("router")->generate("bach_indexation_homepage")
        );
    }

    /**
     * Validate document
     *
     * @param int     $docid Document unique identifier
     * @param string  $type  Document type
     * @param boolean $ajax  Called from ajax
     *
     * @return void
     */
    public function validateDocumentAction($docid, $type, $ajax = false)
    {
        $msg = '';
        //for now, we can only validate EAD DTD
        if ( $type !== 'ead' ) {
             $msg = _('Could not validate non EAD documents (for now).');
        } else {
            $repo = $this->getDoctrine()
                ->getRepository('BachIndexationBundle:Document');
            $document = $repo->findOneByDocid($docid);

            if ( $document->isUploaded() ) {
                $document->setUploadDir(
                    $this->container->getParameter('upload_dir')
                );
            } else {
                $document->setStoreDir(
                    $this->container->getParameter('bach.typespaths')[$type]
                );
            }
            $xml_file = $document->getAbsolutePath();

            if ( !file_exists($xml_file) ) {
                $msg = str_replace(
                    '%docid',
                    $docid,
                    _('Corresponding file for %docid document no longer exists on disk.')
                );
            } else {
                $oxml_document = new \DOMDocument();
                $oxml_document->load($xml_file);

                $root = 'ead';
                $creator = new \DOMImplementation;
                $doctype = $creator->createDocumentType(
                    $root,
                    null,
                    __DIR__ . '/../Resources/dtd/ead-2002/ead.dtd'
                );
                $xml_document = $creator->createDocument(null, null, $doctype);
                $xml_document->encoding = "utf-8";

                $oldNode = $oxml_document->getElementsByTagName($root)->item(0);
                $newNode = $xml_document->importNode($oldNode, true);
                $xml_document->appendChild($newNode);

                libxml_use_internal_errors(true);

                $valid = @$xml_document->validate();

                if ( $valid ) {
                    $msg = str_replace(
                        '%docid%',
                        $docid,
                        _('Document %docid% is valid and DTD compliant!')
                    );
                } else {
                    $msg = str_replace(
                        array('%type%', '%docid%'),
                        array($type, $docid),
                        _('%type% document %docid% is not valid!')
                    );
                }

                foreach ( libxml_get_errors() as $error ) {
                    $this->get('session')->getFlashBag()->add(
                        'documentvalidation_errors',
                        $error->message . ' (line: ' . $error->line .
                        ' col: ' . $error->column . ')'
                    );
                }
            }
        }

        $this->get('session')->getFlashBag()->add(
            'documentvalidation',
            $msg
        );

        if ( $ajax === false ) {
            return new RedirectResponse(
                $this->get("router")->generate("bach_indexation_homepage")
            );
        } else {
            return $this->render(
                'BachIndexationBundle:Indexation:validation.html.twig'
            );
        }
    }

    /**
     * Publish document command
     *
     * @return Response
     */
    public function publishCommandAction()
    {
        $request = $this->getRequest();

        $queryTest = $this->getDoctrine()->getManager()
            ->createQuery(
                'SELECT t FROM BachIndexationBundle:BachToken t
                WHERE t.action = 1'
            );

        if (!empty($queryTest->getResult())) {
            $response = new Response("alreadyBusy");
            $response->setStatusCode(500);
            return $response;
        }

        $query = $this->getDoctrine()->getManager()
            ->createQuery(
                'SELECT t FROM BachIndexationBundle:BachToken t
                    WHERE t.bach_token = :token
                    AND t.filename = :filename'
            )->setParameters(
                array(
                    'token' => $request->get('bach_token'),
                    'filename'   => $request->get('document')
                )
            );
        if (!empty($query->getResult())) {
            $result = $query->getResult()[0];
            $result->setAction(1);
            $this->getDoctrine()->getManager()->flush();
            if ($result->getBachToken() == $request->get('bach_token')
                && $result->getFilename() == $request->get('document')
            ) {
                $cmd = "php -d date.timezone=UTC -d memory_limit=3G ../app/console bach:publish " .
                    $request->get('type') . " " . $request->get('document') .
                    " --assume-yes --token=".$request->get('bach_token')." ";

                if (strcmp($request->get('pdf-indexation'), 'true') == 0) {
                    $cmd .= " --pdf-indexation";
                }

                if (strcmp($request->get('generate-image'), 'true') == 0) {
                    $cmd .= " --generate-image";
                }

                $cmd .= " > /dev/null 2>/dev/null &";
                exec($cmd);
                return new Response(
                    "Publish launch for " . $request->get('document') . ' :::::: '. $cmd
                );
            }
        }
        $response = new Response("mismatchTokenFile");
        $response->setStatusCode(500);
        return $response;
    }

    /**
     * Unpublish document command
     *
     * @return Response
     */
    public function unpublishCommandAction()
    {
        $request = $this->getRequest();

        $queryTest = $this->getDoctrine()->getManager()
            ->createQuery(
                'SELECT t FROM BachIndexationBundle:BachToken t
                WHERE t.action = 1'
            );

        if (!empty($queryTest->getResult())) {
            $response = new Response("alreadyBusy");
            $response->setStatusCode(500);
            return $response;
        }

        $query = $this->getDoctrine()->getManager()
            ->createQuery(
                'SELECT t FROM BachIndexationBundle:BachToken t
                    WHERE t.bach_token = :token
                    AND t.filename = :filename'
            )->setParameters(
                array(
                    'token' => $request->get('bach_token'),
                    'filename'   => $request->get('document')
                )
            );
        if (!empty($query->getResult())) {
            $result = $query->getResult()[0];
            if ($result->getBachToken() == $request->get('bach_token')
                && $result->getFilename() == $request->get('document')
            ) {
                $kernel = $this->get('kernel');
                $result->setAction(1);
                $this->getDoctrine()->getManager()->flush();

                $cmd = "php -d date.timezone=UTC ../app/console bach:unpublish " .
                    $request->get('type') . " " . $request->get('document') .
                    " --assume-yes --token=".$request->get('bach_token')." ";
                if ($request->get('not-delete-file') == true) {
                    $cmd .= " --not-delete-file";
                }

                $cmd .= " > /dev/null 2>/dev/null &";

                exec($cmd);
                return new Response(
                    "Unpublish launch for " . $request->get('document')
                );
            }
        }
        $response = new Response("mismatchTokenFile");
        $response->setStatusCode(500);
        return $response;
    }

    /**
     * Launch generate image
     *
     * @return Response
     */
    public function generateImageAction()
    {
        $testTreatment = $this->getDoctrine()->getManager()
            ->createQuery(
                'SELECT t FROM BachIndexationBundle:DaosPrepared t
                WHERE t.action = 1'
            );
        if ($testTreatment->getResult() == null) {
            $nbRowToTreat = $this->container->getParameter('nbrowgenerateimage');
            $query = $this->getDoctrine()->getManager()
                ->createQuery(
                    'SELECT t FROM BachIndexationBundle:DaosPrepared t'
                )->setMaxResults($nbRowToTreat);
            if (!empty($query->getResult())) {
                $results = $query->getResult();
                $params = array();
                foreach ($results as $result) {
                    $result->setAction(1);
                    array_push($params, $result->toArray());
                }
                $this->getDoctrine()->getManager()->flush();
                $urlViewer = $this->container->getParameter('viewer_uri');

                $url = $urlViewer . 'ajax/generateimages';
                // add its url 'cause viewer can send response to this bach instance
                $params['urlSender'] = $_SERVER['SERVER_NAME'];
                $jsonData = json_encode($params);
                $cmd = "curl -X POST -H 'Content-Type: application/json'";
                $cmd.= " -d '" . $jsonData . "' " . "'" . $url . "'";
                $cmd .= " > /dev/null 2>/dev/null &";
                exec($cmd, $output);
            }
            return new Response("Image generation launch");
        }
        return new Response("Already a treatment");
    }

    /**
     * Delete image in database
     *
     * @return Response
     */
    public function deleteImageAction()
    {
        $json = $this->getRequest()->getContent();
        $data = json_decode($json, true);
        $em = $this->getDoctrine()->getManager();
        $repository = $em->getRepository('BachIndexationBundle:DaosPrepared');
        foreach ($data as $row) {
            $dao = $repository->findOneBy(
                array(
                    'id'=>$row['id']
                )
            );
            if (!empty(stripslashes($row['lastfile']))) {
                $dao->setAction(0);
                $dao->setLastFile(stripslashes($row['lastfile']));
            } else if ($row['action'] == '1') {
                $dao->setAction(0);
            } else {
                $em->remove($dao);
            }
            $em->flush();
        }
        return new Response("Images prepared deleted from database.");
    }

    /**
     * Daily comments report
     *
     * @return Response
     */
    public function sendReportCommentAction()
    {
        $mailReport  = $this->container->getParameter('report_mail');
        $getDailyComment = $this->getDoctrine()->getManager()
            ->createQuery(
                'SELECT t FROM BachHomeBundle:Comment t
                WHERE t.state = 0'
            );

        $em = $this->getDoctrine()->getManager();
        $date = new \DateTime();
        $date->sub(new \DateInterval('P1D'));
        $dateSql = $date->format('Y-m-d');
        $query = $em->createQuery(
            'SELECT c FROM BachHomeBundle:Comment c WHERE c.creation_date = :yesterday AND c.state = 0'
        )->setParameter('yesterday', $dateSql);
        $commentResults = $query->getResult();

        $dateShow = $date->format('d/m/Y');
        if (!empty($commentResults)
            && $mailReport != null
            && filter_var($mailReport, FILTER_VALIDATE_EMAIL)
        ) {
            $container  = $this->container;
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
            $message->setSubject('Bach - CR commentaires du ' . $dateShow);
            if ($container->getParameter('aws.sender') != null ) {
                $message->setFrom($container->getParameter('aws.sender'));
            } else {
                $message->setFrom($user);
            }
            $message->setTo($mailReport);
            //FIXME Add a way to call here a template with the content
            $headerMessage = 'Bonjour,<br><br>Compte rendu des commentaires du ' . $dateShow;
            $thead = "<table border='1'><thead><tr><th>Sujet</th><th>Message</th><th>Type</th></tr></thead>";
            $tbody = '<tbody>';
            foreach ($commentResults as $comment) {
                $tbody .= '<tr><td>'.$comment->getSubject().'</td><td>'.$comment->getMessage().'</td><td>'.Comment::getKnownPriorities()[$comment->getPriority()].'</td></tr>';
            }

            $tbody .= "</tbody></table>";
            $message->setBody(
                $headerMessage.$thead.$tbody,
                'text/html'
            );
            $mailer->send($message);
        }

        return new Response();
    }

    /**
     * Test if publish is working
     * If working return true
     * Else return false
     *
     * @return Response
     */
    public function publishTestAction()
    {
        $logger = $this->get('logger');
        try {
            $em = $this->get('doctrine')->getManager();
            $query = $em->createQuery(
                'SELECT t FROM BachIndexationBundle:BachToken t
                WHERE t.action = 1'
            );

            if (!empty($query->getResult())) {
                return new Response(1);
            } else {
                return new Response(0);
            }
        } catch ( \Exception $e ) {
            $logger->error('Exception : '.  $e->getMessage(). "\n");
            throw $e;
        }
    }

}
