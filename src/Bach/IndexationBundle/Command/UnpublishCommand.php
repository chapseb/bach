<?php

/**
 * Unpublication command
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
 * @author   Sebastien Chaptal <sebastien.chaptal@anaphore.eu>
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

/**
 * Publication command
 *
 * PHP version 5
 *
 * @category Indexation
 * @package  Bach
 * @author   Sebastien Chaptal <johan.cwiklinski@anaphore.eu>
 * @license  BSD 3-Clause http://opensource.org/licenses/BSD-3-Clause
 * @link     http://anaphore.eu
 */
class UnpublishCommand extends ContainerAwareCommand
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
            ->setName('bach:unpublish')
            ->setDescription('File unpublication')
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
                'stats',
                null,
                InputOption::VALUE_NONE,
                _('Give stats informations (memory used, etc)')
            )->addOption(
                'debug',
                null,
                InputOption::VALUE_NONE,
                _('Print last integration file')
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
        $stats = $input->getOption('stats');
        if ( $stats === true ) {
            $start_time = new \DateTime();
        }

        $dry = $input->getOption('dry-run');
        if ( $dry === true ) {
            $output->writeln(
                '<fg=green;options=bold>' .
                _('Running in dry mode') .
                '</fg=green;options=bold>'
            );
        }

        $type = null;
        $container = $this->getContainer();

        $logger = $container->get('publication.logger');
        $known_types = $container->getParameter('bach.types');

        if ( $input->getArgument('type')) {
            $type = $input->getArgument('type');

            if ( !in_array($type, $known_types) ) {
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

        $to_publish = $input->getArgument('document');


        //let's proceed
        $tf = $container->get('bach.indexation.typesfiles');
        $documents = $tf->getExistingFiles($type, $to_publish);

        $output->writeln(
            "\n" .  _('Following files are about to be published: ')
        );
        $output->writeln(
            implode("\n", $documents[$type])
        );

        $confirm = null;
        if ( $input->getOption('assume-yes') ) {
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

        if ( $confirm === 'yes' || $confirm === 'y' ) {

            $output->writeln(
                '<fg=green;options=bold>' .
                _('Publication begins...') .
                '</fg=green;options=bold>'
            );

            // recuperation de l'id des documents
            $ids = array();
            $documents = $documents[$type];
            foreach ( $documents as $document) {
                $extension = $type;

                $getXML = simplexml_load_file($document);
                if ($type == 'ead') {
                    $id = strip_tags($getXML->eadheader->eadid->asXml());
                } else {
                    $id = strip_tags($getXML->id);
                }

                if ( !isset($extensions[$extension]) ) {
                    $extensions[$extension] = array();
                }
                $extensions[$extension][] = $id;
                $ids[] = $id;
            }

            // appel à zend db
            $zdb = $this->getContainer()->get('zend_db');

            ////////////////////////////////////////////////////////
            // recuperation des documents à dépublier
            try {
                $zdb->connection->beginTransaction();
                $select = $zdb->select('documents')
                    ->where(
                        array(
                            'extension' => $type,
                            'docid'     => $ids
                        )
                    );

                $stmt = $zdb->sql->prepareStatementForSqlObject(
                    $select
                );
                $result = $stmt->execute();
                $results = new ResultSet();
                $rows = $results->initialize($result)->toArray();
                $zdb->connection->commit();
                print_r($rows);
            } catch ( \Exception $e ) {
                $zdb->connection->rollBack();
                throw $e;
            }
            ////////////////////////////////////////////////////////

            $docs = $rows;
            //remove solr indexed documents per core
            $updates = array();
            $clients = array();
            $clients = array();

            foreach ($docs as $doc) {
                if ( !isset($updates[$doc['corename']]) ) {
                    $client = $this->getContainer()->get('solarium.client.' . $doc['extension']);
                    $clients[$doc['corename']] = $client;
                    $updates[$doc['corename']] = $client->createUpdate();
                }
                $update = $updates[$doc['corename']];
                if ( $doc['extension'] === 'matricules' ) {
                    $update->addDeleteQuery('id:' . $doc['docid']);
                } else {
                    $update->addDeleteQuery('headerId:' . $doc['docid']);
                }

                if ( $doc['extension'] == 'ead' ) {
                    ////////////////////////////////////////////////////////
                    // Suppression des header des ead
                    try {
                        $zdb->connection->beginTransaction();
                        $deleteHeader = $zdb->delete('ead_header')
                            ->where(
                                array(
                                    'headerId'  => $doc['docid']
                                )
                            );

                        $stmt = $zdb->sql->prepareStatementForSqlObject(
                            $deleteHeader
                        );
                        $stmt->execute();
                        $zdb->connection->commit();
                    } catch ( \Exception $e ) {
                        $zdb->connection->rollBack();
                        throw $e;
                    }
                    ////////////////////////////////////////////////////////
                }
                ////////////////////////////////////////////////////////
                // Suppression du document
                try {
                    $zdb->connection->beginTransaction();
                    $deleteDocument = $zdb->delete('documents')
                        ->where(
                            array(
                                'docid'  => $doc['docid']
                            )
                        );
                    $stmt = $zdb->sql->prepareStatementForSqlObject(
                        $deleteDocument
                    );
                    $stmt->execute();
                    $zdb->connection->commit();
                } catch ( \Exception $e ) {
                    $zdb->connection->rollBack();
                    throw $e;
                }
                ////////////////////////////////////////////////////////

            }

            foreach ( $updates as $key=>$update ) {
                $client = $clients[$key];
                $update->addCommit(null, null, true);
                $result = $client->update($update);
                if ( $result->getStatus() === 0 ) {
                    $logger->info(
                        str_replace(
                            array('%doc', '%time'),
                            array($doc['docid'], $result->getQueryTime()),
                            _('Document %doc successfully deleted from Solr in %time')
                        )
                    );
                } else {
                    $logger->err(
                        str_replace(
                            '%doc',
                            $doc['docid'],
                            _('Solr failed to remove document %doc!')
                        )
                    );
                }
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
        if ( $document->getId() === null ) {
            if ( $this->_insert_doc_stmt === null ) {
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
            if ( $this->_update_doc_stmt === null ) {
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
     *
     * @return void
     */
    private function _solrFullImport($output, $type, $progress, $dry)
    {
        $progress->advance();
        $configreader = $this->getContainer()
            ->get('bach.administration.configreader');
        $corename = $this->getContainer()->getParameter($type . '_corename');
        $sca = new SolrCoreAdmin($configreader);
        if ( $dry === false ) {
            $sca->fullImport($corename);
        }

        $done = false;

        while ( !$done ) {
            sleep(2);
            $response = $sca->getImportStatus($corename);
            if ( $response->getImportStatus() === 'idle' ) {
                $progress->advance();
                $done = true;
                $messages = $response->getImportMessages();
                $messages = \simplexml_import_dom($messages);
                $output->writeln('');
                $output->writeln('');
                foreach ( $messages as $message ) {
                    $str = (string)$message;
                    if ( isset($message['name']) && trim($message['name']) !== '' ) {
                        $str = $message['name'] . ': ' . $str;
                    }

                    $output->writeln(
                        '<fg=green>' . $str . '</fg=green>'
                    );
                }

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
        if ( $bytes < 0 ) {
            $multiplicator = -1;
            $bytes = $bytes * $multiplicator;
        }
        $unit = array('b','kb','mb','gb','tb','pb');
        $fmt = @round($bytes/pow(1024, ($i=floor(log($bytes, 1024)))), 2)
            * $multiplicator . ' ' . $unit[$i];
        return $fmt;
    }
}