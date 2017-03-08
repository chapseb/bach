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
     * Add new documents to queue or index
     *
     * @return void
     */
    public function addAction()
    {
        $document = new Document(true);
        $document->setUploadDir(
            $this->container->getParameter('upload_dir')
        );
        $form = $this->createForm('document', $document);
        $form->handleRequest($this->getRequest());

        if ($form->isValid()) {
            $document = $form->getData();
            //generate document id
            $document->generateDocId();

            $em = $this->getDoctrine()->getManager();

            //check if doc exists
            $repo = $em->getRepository('BachIndexationBundle:Document');
            $exists = $repo->findOneByDocid($document->getDocId());

            if ( $exists ) {
                $exists->setFile($document->getFile());
                $document = $exists;
                $exists->setUpdated(new \DateTime());
                $exists->setUploadDir(
                    $this->container->getParameter('upload_dir')
                );
                $exists->setUploaded(true);
            } else {
                $document->setCorename(
                    $this->container->getParameter(
                        $document->getExtension() . '_corename'
                    )
                );
                $document->setStoreDir(
                    $this->container
                        ->getParameter('bach.typespaths')[$document->getExtension()]
                );
            }

            //store document
            $em->persist($document);
            $em->flush();

            //create a new task
            $task = new IntegrationTask($document);

            if ( $form->get('perform')->isClicked() ) {
                //store file task in the database if perform action was requested
                $em->persist($task);
                $em->flush();
                return new RedirectResponse(
                    $this->get("router")->generate("bach_indexation_queue")
                );
            } else {
                //if performall action was requested, do not store task in db
                //and launch indexation process

                $integrationService = $this->container
                    ->get('bach.indexation.process.arch_file_integration');
                $geonames = array();
                $integrationService->integrate($task, $geonames, true);

                $configreader = $this->container
                    ->get('bach.administration.configreader');
                $sca = new SolrCoreAdmin($configreader);
                $sca->fullImport($task->getDocument()->getCorename());

                return new RedirectResponse(
                    $this->get("router")->generate("bach_indexation_homepage")
                );
            }
        }

        //$tf = $this->container->get('bach.indexation.typesfiles');

        return $this->render(
            'BachIndexationBundle:Indexation:add.html.twig',
            array(
                'directory_contents'    => null,
                'upload_form'           => $form->createView(),
                'existing_files'        => array()//$tf->getExistingFiles()
            )
        );
    }

    /**
     * Displays indexation queue and form
     *
     * @return void
     */
    public function queueAction()
    {
        $em2 = $this->getDoctrine()->getManager();

        $repository = $em2
            ->getRepository('BachIndexationBundle:IntegrationTask');

        $entities = $repository
            ->createQueryBuilder('t')
            ->orderBy('t.taskId', 'DESC')
            ->getQuery()
            ->getResult();
        $tasks = array();

        foreach ($entities as $entity) {
            $entity->getDocument()->setUploadDir(
                $this->container->getParameter('upload_dir')
            );
            $spl = new \SplFileInfo($entity->getPath());
            $tasks[] = array(
                'filename'  => $entity->getFilename(),
                'format'    => $entity->getFormat(),
                'size'      => $spl->getSize()
            );

            switch ( (int)$entity->getStatus() ) {
            default:
            case IntegrationTask::STATUS_NONE:
                $status = "";
                break;
            case IntegrationTask::STATUS_OK:
                $status = "success";
                break;
            case IntegrationTask::STATUS_KO:
            default:
                $status = "error";
                break;
            }
            $tasks[count($tasks)-1]['status'] = $status;
        }

        return $this->render(
            'BachIndexationBundle:Indexation:queue.html.twig',
            array(
                'tasks'         => $tasks
            )
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
        $response = new Response("Mismatch send token/file and database token/file");
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
        $response = new Response("Mismatch send token/file and database token/file");
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
        $nbRowToTreat = $this->container->getParameter('nbrowgenerateimage');
        if ($testTreatment->getResult() == null) {
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
        $cptSend = 0;
        foreach ($data as $row) {
            $em = $this->getDoctrine()->getManager();
            $repository = $em->getRepository('BachIndexationBundle:DaosPrepared');

            $dao = $repository->findOneBy(
                array(
                    'id'=>$row['id']
                )
            );
            if (!empty(stripslashes($row['lastfile']))) {
                $dao->setAction(0);
                $dao->setLastFile(stripslashes($row['lastfile']));
                $this->getDoctrine()->getManager()->flush();
            } else if ($row['action'] == '1') {
                $dao->setAction(0);
                $this->getDoctrine()->getManager()->flush();
            } else {
                $em->remove($dao);
            }
            $em->flush();
            $cptSend++;
        }
        return new Response("Images prepared deleted from database.");
    }

}
