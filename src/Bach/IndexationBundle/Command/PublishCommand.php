<?php

/**
 * Publication command
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

namespace Bach\IndexationBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
Use Symfony\Component\HttpFoundation\File\File;
use Bach\IndexationBundle\Entity\Document;
use Bach\IndexationBundle\Entity\Geoloc;
use Bach\IndexationBundle\Entity\IntegrationTask;
use Bach\AdministrationBundle\Entity\SolrCore\SolrCoreAdmin;
use Bach\IndexationBundle\Service\ZendDb;
use Zend\Db\ResultSet\ResultSet;
use Aws\S3\S3Client;

/**
 * Publication command
 *
 * PHP version 5
 *
 * @category Indexation
 * @package  Bach
 * @author   Johan Cwiklinski <johan.cwiklinski@anaphore.eu>
 * @license  BSD 3-Clause http://opensource.org/licenses/BSD-3-Clause
 * @link     http://anaphore.eu
 */
class PublishCommand extends ContainerAwareCommand
{
    private $_insert_doc_stmt;
    private $_update_doc_stmt;
    private $_insert_geo_stmt;
    private $_update_geo_stmt;

    /**
     * Configures command
     *
     * @return void
     */
    protected function configure()
    {
        $this
            ->setName('bach:publish')
            ->setDescription('File publication')
            ->setHelp(
                <<<EOF
The <info>%command.name%</info> launches whole publishing process
(pre-processing, conversion, indexation)
EOF
            )->addArgument(
                'type',
                InputArgument::REQUIRED,
                _('Documents type')
            )->addArgument(
                'document',
                InputArgument::IS_ARRAY | InputArgument::REQUIRED,
                _('Documents names or directories to proceed')
            )->addOption(
                'assume-yes',
                null,
                InputOption::VALUE_NONE,
                _('Assume yes for all questions')
            )->addOption(
                'solr-only',
                null,
                InputOption::VALUE_NONE,
                _('Publish only in solr (full-import)')
            )->addOption(
                'dry-run',
                null,
                InputOption::VALUE_NONE,
                _('Do not really publish.')
            )->addOption(
                'no-change-check',
                null,
                InputOption::VALUE_NONE,
                _('Do not check if file has been modified')
            )->addOption(
                'pdf-indexation',
                null,
                InputOption::VALUE_NONE,
                _('Pdf indexation.')
            )->addOption(
                'stats',
                null,
                InputOption::VALUE_NONE,
                _('Give stats informations (memory used, etc)')
            )->addOption(
                'debug',
                null,
                InputOption::VALUE_NONE,
                _('Print last integration file')
            )->addOption(
                'token',
                'token',
                InputOption::VALUE_REQUIRED,
                'Which token for publish file in bach_token table ?',
                '-1'
            );
    }

    /**
     * Executes the command
     *
     * @param InputInterface  $input  Stdin
     * @param OutputInterface $output Stdout
     *
     * @return void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $container = $this->getContainer();
        $count = 0;

        $stats = $input->getOption('stats');
        if ($stats === true) {
            $start_time = new \DateTime();
        }

        $dry = $input->getOption('dry-run');

        $pdfFlag = $input->getOption('pdf-indexation');

        if ($dry === true) {
            $output->writeln(
                '<fg=green;options=bold>' .
                _('Running in dry mode') .
                '</fg=green;options=bold>'
            );
        }

        $type = null;

        $logger = $container->get('publication.logger');
        $known_types = $container->getParameter('bach.types');

        if ($input->getArgument('type')) {
            $type = $input->getArgument('type');

            if (!in_array($type, $known_types)) {
                $msg = _('Unknown type! Please choose one of:');
                throw new \UnexpectedValueException(
                    $msg . "\n -" .
                    implode("\n -", $known_types)
                );
            }
        }

        $output->writeln(
            $msg = str_replace(
                '%type',
                $type,
                _('Publishing "%type" documents.')
            )
        );

        //let's check if files exists
        $to_publish = $input->getArgument('document');

        //let's proceed
        $tf = $container->get('bach.indexation.typesfiles');
        $files_to_publish = $tf->getExistingFiles($type, $to_publish);

        $output->writeln(
            "\n" .  _('Following files are about to be published: ')
        );
        $output->writeln(
            implode("\n", $files_to_publish[$type])
        );

        $confirm = null;
        if ($input->getOption('assume-yes')) {
            $confirm = 'yes';
        } else {
            $choices = array(_('yes'), _('no'));
            $dialog = $this->getHelperSet()->get('dialog');
            $confirm = $dialog->ask(
                $output,
                "\n" . _('Are you ok (y/n)?'),
                null,
                $choices
            );
        }

        if ($confirm === 'yes' || $confirm === 'y') {
            $em = $this->getContainer()->get('doctrine')->getManager();

            $output->writeln(
                '<fg=green;options=bold>' .
                _('Publication begins...') .
                '</fg=green;options=bold>'
            );

            $cdc_documents = $container->getParameter('cdcdocuments');

            if ($input->getOption('solr-only')) {
                $steps = 2;
            } else {
                $steps = count($files_to_publish[$type]);
                //add solr steps
                $steps+=2;
            }

            $flagAws = $container->getParameter('aws.s3');
            if (!$flagAws) {
                $progress = $this->getHelperSet()->get('progress');
                $progress->start($output, $steps);
            }

            if (!$input->getOption('solr-only')) {
                $integrationService = $this->getContainer()
                    ->get('bach.indexation.process.arch_file_integration');
                $no_check_changes = $input->getOption('no-change-check');

                $docs = array();
                $geonames = array();

                foreach ( $files_to_publish[$type] as $ftp ) {
                    $f = new File($ftp);
                    $document = new Document();
                    $document->setFile($f);
                    $document->setExtension($type);
                    $document->generateDocId();
                    $flagCdcDocument = false;
                    if (is_array($cdc_documents)
                        && in_array($document->getDocId(), $cdc_documents)
                    ) {
                        $flagCdcDocument = true;
                    }

                    //check if doc exists
                    $repo = $em->getRepository('BachIndexationBundle:Document');
                    $exists = $repo->findOneByDocid($document->getDocId());
                    if ($exists) {
                        if (!$no_check_changes) {
                            $change_date = new \DateTime();
                            $last_file_change = $f->getMTime();
                            $change_date->setTimestamp($last_file_change);

                            if ($exists->getUpdated() > $change_date) {
                                if (!$flagAws) {
                                    $progress->advance();
                                }
                                $logger->info(
                                    str_replace(
                                        '%doc',
                                        $ftp,
                                        _('Document %doc has not been changed, no publication required.')
                                    )
                                );
                                continue;
                            }
                        }
                        $document = $exists;
                        $exists->setFile($f);
                        $exists->setUpdated(new \DateTime());
                        $exists->setUploaded(false);
                        $exists->setStoreDir(
                            $this->getContainer()
                                ->getParameter('bach.typespaths')[$type]
                        );
                    } else {
                        $document->setCorename(
                            $this->getContainer()->getParameter(
                                $type . '_corename'
                            )
                        );
                        $document->setStoreDir(
                            $this->getContainer()
                                ->getParameter('bach.typespaths')[$type]
                        );
                    }

                    if ($type !== 'matricules') {
                        if (!$flagAws) {
                            $progress->advance();
                        }
                        if ($dry === false) {
                            $zdb = $this->getContainer()->get('zend_db');
                            try {
                                $zdb->connection->beginTransaction();
                                $this->_storeDocument($zdb, $document);
                                $zdb->connection->commit();
                            } catch ( \Exception $e ) {
                                $zdb->connection->rollBack();
                                throw $e;
                            }
                        }

                        if (!$flagCdcDocument) {
                            //create a new task
                            $task = new IntegrationTask($document);

                            if ($dry === false) {
                                $integrationService->integrate(
                                    $task,
                                    $geonames,
                                    $pdfFlag
                                );
                                $logger->info(
                                    str_replace(
                                        '%doc',
                                        $ftp,
                                        _('Document %doc has been successfully published.')
                                    )
                                );

                            }
                            if (!empty($geonames)) {
                                $zdb = $this->getContainer()->get('zend_db');

                                try {
                                    $zdb->connection->beginTransaction();
                                    /* get geoloc in database
                                    * and treatment to match with geodata in ead
                                    */
                                    $select = $zdb->select('geoloc');
                                    $stmt = $zdb->sql->prepareStatementForSqlObject(
                                        $select
                                    );
                                    $result = $stmt->execute();

                                    $results = new ResultSet();
                                    $rows = $results->initialize($result)->toArray();
                                    $zdb->connection->commit();
                                } catch ( \Exception $e ) {
                                    $zdb->connection->rollBack();
                                    throw $e;
                                }

                                $resultsSelect = array();
                                foreach ( $rows as $key => $row ) {
                                    $resultSelect                 = array();
                                    $resultSelect['indexed_name']
                                        = $row['indexed_name'];
                                    $resultSelect['lon']          = $row['lon'];
                                    $resultSelect['lat']          = $row['lat'];
                                    $resultSelect['id']           = $row['id'];
                                    $resultsSelect[$resultSelect['indexed_name']]
                                        = $resultSelect;
                                }
                                /***************************************************/

                                if ($dry === false) {
                                    try {
                                        $zdb->connection->beginTransaction();
                                        $this->_storeGeodata(
                                            $zdb,
                                            $geonames,
                                            $resultsSelect
                                        );
                                        $zdb->connection->commit();
                                    } catch ( \Exception $e ) {
                                        $zdb->connection->rollBack();
                                        throw $e;
                                    }
                                }
                            }
                            unset($task);
                        }
                    } else {
                        $docs[] = $document;
                    }
                    unset($document, $exists);
                }
            }

            if ($type === 'matricules' && count($docs) > 0) {
                $tasks = array();
                $count = 0;

                $zdb = $this->getContainer()->get('zend_db');
                try {
                    $zdb->connection->beginTransaction();

                    $fields = array();
                    foreach ( array_keys($docs[0]->toArray()) as $field ) {
                        $fields[$field] = ':' . $field;
                    }

                    foreach ( $docs as $document ) {
                        $this->_storeDocument($zdb, $document);
                        $task = new IntegrationTask($document);
                        $tasks[] = $task;
                        $count++;
                    }
                    unset($docs);
                    $zdb->connection->commit();
                } catch ( \Exception $e ) {
                    $zdb->connection->rollBack();
                    throw $e;
                }

                $debug = $input->getOption('debug');
                if ($debug != true) {
                    $debug = false;
                }
                $integrationService->integrateAll($tasks, null, $geonames, $debug);
            }

            if (!$flagCdcDocument) {
                if ($flagAws) {
                    $this->_solrFullImport($output, $type, null, $dry, $logger);
                } else {
                    $this->_solrFullImport($output, $type, $progress, $dry, $logger);
                }
            }

            if ($input->getOption('token') != '-1') {
                $query = $em->createQuery(
                    'SELECT t FROM BachIndexationBundle:BachToken t
                        WHERE t.bach_token = :token
                        AND t.filename = :filename'
                )->setParameters(
                    array(
                        'token' => $input->getOption('token'),
                        'filename'   => $input->getArgument('document')
                    )
                );

                if ($query->getResult()[0]) {
                    $result = $query->getResult()[0];
                    $em->remove($result);
                    $em->flush();
                }
            } else {
                $logger->error('Not token ' . $input->getOption('token'));
            }

            if (!$flagAws) {
                $progress->finish();
            }
        } else {
            $output->writeln(
                '<fg=red;options=bold>' .
                _('Command canceled by user.') .
                '</fg=red;options=bold>'
            );

        }

        if ($stats === true) {
            $peak = $this->formatBytes(memory_get_peak_usage());

            $end_time = new \DateTime();
            $diff = $start_time->diff($end_time);

            $hours = $diff->h;
            $hours += $diff->days * 24;

            $elapsed = str_replace(
                array(
                    '%hours',
                    '%minutes',
                    '%seconds'
                ),
                array(
                    $hours,
                    $diff->i,
                    $diff->s
                ),
                '%hours:%minutes:%seconds'
            );

            $output->writeln('Time elapsed: ' . $elapsed);
            $output->writeln('Memory peak: ' . $peak);
        }
    }


    /**
     * Store Geodata
     *
     * @param ZendDb $zdb           ZDB instance
     * @param array  $geonames      Contains geodata in ead file
     * @param array  $resultsSelect Contains geodata store in database
     *
     * @return void
     */
    private function _storeGeodata(ZendDb $zdb, array $geonames,
        array $resultsSelect
    ) {
        $geonamesUpdate = array_intersect_key($resultsSelect, $geonames);
        $geonamesInsert = array_diff_key($geonames, $geonamesUpdate);
        $stmt = null;
        foreach ( $geonamesInsert as $key => $geonameInsert ) {
            $insertObject = array();
            $insertObject['lat']    = $geonames[$key]['attributes']['latitude'];
            $insertObject['lon']    = $geonames[$key]['attributes']['longitude'];
            $insertObject['indexed_name'] = $geonames[$key]['value'];
            $insertObject['name']   = preg_replace(
                "/\([^)]+\)/",
                "",
                $geonames[$key]['value']
            );
            $insertObject['found']  = '1';

            if ($this->_insert_geo_stmt === null) {
                $insert = $zdb->insert('geoloc')
                    ->values($insertObject);
                $stmt = $zdb->sql->prepareStatementForSqlObject($insert);
                $this->_insert_geo_stmt = $stmt;
            } else {
                $stmt = $this->_insert_geo_stmt;
            }
            $stmt->execute($insertObject);
        }
        $stmt = null;
        foreach ( $geonamesUpdate as $key => $geonameUpdate ) {
            $updateObject = array();
            $updateObject['lat']    = $geonames[$key]['attributes']['latitude'];
            $updateObject['lon']    = $geonames[$key]['attributes']['longitude'];
            $updateObject['indexed_name'] = $geonames[$key]['value'];
            $updateObject['name']   = preg_replace(
                "/\([^)]+\)/",
                "",
                $geonames[$key]['value']
            );
            $updateObject['id']     = $geonameUpdate['id'];
            $updateObject['found']  = '1';

            if ($this->_update_geo_stmt === null) {
                $update = $zdb->update('geoloc')
                    ->set($updateObject)
                    ->where(
                        array('id' => ':id'
                        )
                    );
                $stmt = $zdb->sql->prepareStatementForSqlObject(
                    $update
                );
                $this->_update_geo_stmt = $stmt;
            } else {
                $stmt = $this->_update_geo_stmt;
            }
            $updateObject['where1'] = $updateObject['id'];
            $stmt->execute($updateObject);
        }
    }

    /**
     * Store document
     *
     * @param ZendDb   $zdb      ZDB instance
     * @param Document $document Document to store
     *
     * @return void
     */
    private function _storeDocument(ZendDb $zdb, Document $document)
    {
        $fields = array();
        $values = $document->toArray();
        foreach ( array_keys($values) as $field ) {
            $fields[$field] = ':' . $field;
        }

        $stmt = null;
        if ($document->getId() === null) {
            if ($this->_insert_doc_stmt === null) {
                $insert = $zdb->insert('documents')
                    ->values($fields);
                $stmt = $zdb->sql->prepareStatementForSqlObject(
                    $insert
                );
                $this->_insert_doc_stmt = $stmt;
            } else {
                $stmt = $this->_insert_doc_stmt;
            }
        } else {
            if ($this->_update_doc_stmt === null) {
                $update = $zdb->update('documents')
                    ->set($fields)
                    ->where(
                        array(
                            'id' => ':id'
                        )
                    );
                $stmt = $zdb->sql->prepareStatementForSqlObject(
                    $update
                );
                $this->_update_doc_stmt = $stmt;
            } else {
                $stmt = $this->_update_doc_stmt;
            }

            $values['where1'] = $values['id'];
        }

        $stmt->execute($values);
    }

    /**
     * Proceedd solr full data import
     *
     * @param OutputInterface $output   Stdout
     * @param string          $type     Documents type
     * @param Helper          $progress Progress bar instance
     * @param boolean         $dry      Dry run mode
     * @param Logger          $logger   Logger
     *
     * @return void
     */
    private function _solrFullImport($output, $type, $progress, $dry, $logger)
    {
        if ($progress != null) {
            $progress->advance();
        }
        $configreader = $this->getContainer()
            ->get('bach.administration.configreader');
        $corename = $this->getContainer()->getParameter($type . '_corename');
        $sca = new SolrCoreAdmin($configreader);
        if ($dry === false) {
            $sca->fullImport($corename);
        }

        $done = false;
        while ( !$done ) {
            sleep(5);
            $response = $sca->getImportStatus($corename);
            if ($response->getImportStatus() === 'idle') {
                if ($progress != null) {
                    $progress->advance();
                }
                $done = true;
                $messages = $response->getImportMessages();
                $messages = \simplexml_import_dom($messages);
                $output->writeln('');
                $output->writeln('');
                foreach ($messages as $message) {
                    $str = (string)$message;
                    if (isset($message['name']) && trim($message['name']) !== '') {
                        $str = $message['name'] . ': ' . $str;
                    }
                    $output->writeln(
                        '<fg=green>' . $str . '</fg=green>'
                    );
                }
                $logger->info('import solr status: '. $response->getImportStatus());
            } else {
                $logger->error(
                    'import solr status error: '. $response->getImportStatus()
                );
            }
        }
    }

    /**
     * Format bytes to human readable value
     *
     * @param int $bytes Bytes
     *
     * @return string
     */
    public function formatBytes($bytes)
    {
        $multiplicator = 1;
        if ($bytes < 0) {
            $multiplicator = -1;
            $bytes = $bytes * $multiplicator;
        }
        $unit = array('b','kb','mb','gb','tb','pb');
        $fmt = @round($bytes/pow(1024, ($i=floor(log($bytes, 1024)))), 2)
            * $multiplicator . ' ' . $unit[$i];
        return $fmt;
    }
}
