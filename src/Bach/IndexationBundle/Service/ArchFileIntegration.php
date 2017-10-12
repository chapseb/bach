<?php
/**
 * Archival file integration in database
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
 * @license  BSD 3-Clause http://opensource.org/licenses/BSD-3-Clause
 * @link     http://anaphore.eu
 */

namespace Bach\IndexationBundle\Service;

use Doctrine\ORM\EntityManager;
use Bach\IndexationBundle\Entity\IntegrationTask;
use Symfony\Component\Console\Helper\ProgressHelper;

/**
 * Archival file integration in database
 *
 * PHP version 5
 *
 * @category Indexation
 * @package  Bach
 * @author   Johan Cwiklinski <johan.cwiklinski@anaphore.eu>
 * @license  BSD 3-Clause http://opensource.org/licenses/BSD-3-Clause
 * @link     http://anaphore.eu
 */
class ArchFileIntegration
{
    private $_manager;
    private $_factory;
    private $_entityManager;
    private $_zdb;

    /**
     * Instanciate Service
     *
     * @param FileDriverManager $manager       The file driver manager
     * @param DataBagFactory    $factory       The databag factory instance
     * @param EntityManager     $entityManager The entity manager
     * @param ZendDb            $zdb           Zend database wrapper
     */
    public function __construct(FileDriverManager $manager,
        DataBagFactory $factory, EntityManager $entityManager, ZendDb $zdb
    ) {
        $this->_manager = $manager;
        $this->_factory = $factory;
        $this->_entityManager = $entityManager;
        $this->_zdb = $zdb;
    }

    /**
     * Integrate files in queue into the database
     *
     * @return void
     */
    public function proceedQueue()
    {
        $repository = $this->_entityManager
            ->getRepository('BachIndexationBundle:IntegrationTask');
        $tasks = $repository->findByStatus(IntegrationTask::STATUS_NONE);

        foreach ($tasks as $task) {
            try {
                $this->integrate($task);
                $task->setStatus(IntegrationTask::STATUS_OK);
            } catch(\Exception $e) {
                $task->setStatus(IntegrationTask::STATUS_KO);
            }

            //anyways, presist task
            $this->_entityManager->persist($task);
            $this->_entityManager->flush();
        }

    }

    /**
     * Proceed task database integration
     *
     * @param IntegrationTask $task        Task to proceed
     * @param array           $geonames    Geoloc data
     * @param boolean         $pdfFlag     Flag to index pdf
     * @param boolean         $transaction Wether to flush
     *
     * @return void
     */
    public function integrate(IntegrationTask $task, &$geonames, $pdfFlag,
        $transaction = true
    ) {
        $spl = new \SplFileInfo($task->getPath());
        $doc = $task->getDocument();
        $format = $task->getFormat();
        $preprocessor = $task->getPreprocessor();

        $this->_manager->convert(
            $this->_factory->encapsulate($spl),
            $format,
            $doc,
            $transaction,
            $preprocessor,
            $geonames,
            $pdfFlag
        );
    }

    /**
     * Integrate multiple tasks at once
     *
     * @param array          $tasks    Tasks to integrate
     * @param ProgressHelper $progress Progress bar
     * @param array          $geonames Geoloc data
     * @param boolean        $debug    Flag to display an issue
     * @param boolean        $pdfFlag  Flag to index pdf
     *
     * @return void
     */
    public function integrateAll(
        $tasks,
        $progress,
        &$geonames,
        $debug,
        $pdfFlag = false
    ) {
        $count = 0;
        $cleared = false;
        try {
            $this->_zdb->connection->beginTransaction();
            foreach ( $tasks as $task) {
                if ( $cleared ) {
                    $doc = $task->getDocument();
                    $doc = $this->_entityManager->merge($doc);
                    $task->setDocument($doc);
                }
                if ($progress != null) {
                    $progress->advance();
                }
                if ($debug == true) {
                    print_r($task->getDocument()->getName());
                }
                $this->integrate(
                    $task,
                    $geonames,
                    $pdfFlag,
                    false
                );
                $count++;
            }
            $this->_zdb->connection->commit();
        } catch ( \Exception $e ) {
            $this->_zdb->connection->rollBack();
        }
    }
}
