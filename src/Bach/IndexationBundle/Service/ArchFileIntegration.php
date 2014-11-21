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

    /**
     * Instanciate Service
     *
     * @param FileDriverManager $manager       The file driver manager
     * @param DataBagFactory    $factory       The databag factory instance
     * @param EntityManager     $entityManager The entity manager
     */
    public function __construct(FileDriverManager $manager,
        DataBagFactory $factory, EntityManager $entityManager
    ) {
        $this->_manager = $manager;
        $this->_factory = $factory;
        $this->_entityManager = $entityManager;
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
     * @param IntegrationTask $task  Task to proceed
     * @param boolean         $flush Wether to flush
     *
     * @return void
     */
    public function integrate(IntegrationTask $task, $flush = true)
    {
        $spl = new \SplFileInfo($task->getPath());
        $doc = $task->getDocument();
        $format = $task->getFormat();
        $preprocessor = $task->getPreprocessor();

        $this->_manager->convert(
            $this->_factory->encapsulate($spl),
            $format,
            $doc,
            $flush,
            $preprocessor
        );
    }

    /**
     * Integrate multiple tasks at once
     *
     * @param array          $tasks    Tasks to integrate
     * @param ProgressHelper $progress Progress bar
     *
     * @return void
     */
    public function integrateAll($tasks, ProgressHelper $progress)
    {
        $count = 0;
        $cleared = false;
        foreach ( $tasks as $task) {
            /*if ( $count > 0 ) {
                continue;
            }*/
            if ( $cleared ) {
                $doc = $task->getDocument();
                $doc = $this->_entityManager->merge($doc);
                $task->setDocument($doc);
            }
            $progress->advance();
            $this->integrate($task, false);
            $count++;

            /*if ( $count % 20000 === 0 ) {
                $this->_entityManager->flush();
                $this->_entityManager->clear();
                $cleared = true;
            }*/
        }

        /*$this->_entityManager->flush();
        $this->_entityManager->clear();*/

    }
}
