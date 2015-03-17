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
use Symfony\Component\HttpFoundation\File\File;
use easyrdf\easyrdf\lib\EasyRdf;

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
class RdfCommand extends ContainerAwareCommand
{

    /**
     * Configures command
     *
     * @return void
     */
    protected function configure()
    {
        $this
            ->setName('bach:rdf')
            ->setDescription('Split rdf files')
            ->setHelp(
                <<<EOF
The <info>%command.name%</info> launches whole publishing process
(pre-processing, conversion, indexation)
EOF
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

        if ($confirm === 'yes' || $confirm === 'y') {
            $output->writeln(
                '<fg=green;options=bold>' .
                _('RDF split begins...') .
                '</fg=green;options=bold>'
            );
            $container = $this->getContainer();
            $pathDirectory = $container->getParameter('rdf_files_path');
            $files = array();

            if ($repo = opendir($pathDirectory)) {
                while (false !== ($file = readdir($repo))) {
                    $ext = pathinfo($file, PATHINFO_EXTENSION);
                    if (strpos($ext, 'xml') !== false) {

                        $xmlFile = simplexml_load_file($pathDirectory . $file);
                        $openNodeRDF = '<rdf:RDF ';
                        foreach ($xmlFile->getDocNamespaces() as $key => $namespace) {
                            //$xmlFile->registerXPathNamespace($key, $namespace);
                            //$xmlroot->addAttribute('xmlns:xmlns:'.$key, $namespace);
                            //\EasyRdf_Namespace::set($key, $namespace);
                            $openNodeRDF .= 'xmlns:'. $key . '="'. $namespace . '" ';
                        }
                        $openNodeRDF .= '>';

                        $header = '<?xml version="1.0" encoding="UTF-8"?>';
                        foreach ($xmlFile->xpath('//mdfa:RessourceArchivistique') as $xmlNode) {
                            $rdf = $xmlNode;
                            $nodeAbout = $xmlNode->xpath('.//@rdf:about')[0];
                            $filename = $nodeAbout->about;
                            if (!file_exists($pathDirectory.$filename.'.xml')
                                || filemtime($pathDirectory.$filename.'.xml') < filemtime($pathDirectory.$file)
                            ) {
                                foreach ($xmlFile->getDocNamespaces() as $key => $namespace) {
                                    $rdf->registerXPathNamespace($key, $namespace);
                                }

                                $fp = fopen($pathDirectory.$filename.".xml", "a");
                                ftruncate($fp, 0);
                                fwrite(
                                    $fp,
                                    $header.$openNodeRDF.$rdf->asXML().'</rdf:RDF>'
                                );
                                fclose($fp);
                            }
                        }
                    }
                }
            }
        } else {
            $output->writeln(
                '<fg=red;options=bold>' .
                _('Command canceled by user.') .
                '</fg=red;options=bold>'
            );
        }

        if ( $stats === true ) {
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
