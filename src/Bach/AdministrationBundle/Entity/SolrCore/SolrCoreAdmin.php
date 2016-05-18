<?php
/**
 * Bach solr core administration
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

namespace Bach\AdministrationBundle\Entity\SolrCore;

use Bach\AdministrationBundle\Entity\SolrCore\BachCoreAdminConfigReader;
use Symfony\Component\Finder\Finder;
use Exception;
use DOMDocument;

/**
 * Bach solr core administration
 *
 * PHP version 5
 *
 * @category Administration
 * @package  Bach
 * @author   Johan Cwiklinski <johan.cwiklinski@anaphore.eu>
 * @license  BSD 3-Clause http://opensource.org/licenses/BSD-3-Clause
 * @link     http://anaphore.eu
 */
class SolrCoreAdmin
{
    const DELETE_INDEX = 0;
    const DELETE_DATA = 1;
    const DELETE_CORE = 2;

    private $_reader;
    private $_em;
    private $_errors = array();
    private $_warnings = array();

    /**
     * Constructor. Creates a necessary object to send queries.
     *
     * @param BachCoreAdminConfigReader $reader Config reader.
     */
    public function __construct(BachCoreAdminConfigReader $reader)
    {
        $this->_reader = $reader;
    }

    /**
     * Proceeed full import
     *
     * @param string $coreName Solr core name
     *
     * @return SolrCoreResponse
     */
    public function fullImport($coreName)
    {
        return $this->_send(
            $this->_reader->getCoresURL() . '/' . $coreName . '/dataimport',
            array(
                'command'   => 'full-import',
                'clean'     => 'false',
                'commit'    => 'true'
            )
        );
    }

    /**
     * Proceed delta import
     *
     * @param string $coreName Solr core name
     *
     * @return SolrCoreResponse
     */
    public function deltaImport($coreName)
    {
        return $this->_send(
            $this->_reader->getCoresURL() . '/' . $coreName . '/dataimport',
            array(
                'command'   => 'delta-import',
                'commit'    => true
            )
        );
    }

    /**
     * Retrieve import status
     *
     * @param string $coreName Solr core name
     *
     * @return SolrCoreResponse
     */
    public function getImportStatus($coreName)
    {
        return $this->_send(
            $this->_reader->getCoresURL() . '/' . $coreName . '/dataimport'
        );
    }

    /**
     * Create core with specified name. If a core directory or core of that name
     * already exists this function returns false otherwise it returns
     * SolrCoreResponse object.
     *
     * @param string $coreType  Solr core type
     * @param string $coreName  Solr core name
     * @param string $orm_name  ORM class name
     * @param mixed  $em        Entity manager
     * @param array  $db_params Database parameters for newly created core
     *
     * @return boolean|SolrCoreResponse
     */
    public function create($coreType, $coreName,
        $orm_name, $em, $db_params
    ) {
        $coreInstanceDir =  preg_replace('/[^a-zA-Z0-9-_]/', '', $coreName);
        $coreInstanceDirPath = null;
        $this->_em = $em;

        //check if cores dir is writeable
        if ( is_writeable($this->_reader->getCoresPath()) ) {
            //it is, we can directly create new core
            $coreInstanceDirPath = $this->_reader->getCoresPath() .
                $coreInstanceDir;
        } else {
            //cores dir is read only or does not exists locally,
            //let's use a temporary dir for new core creation
            $tmp_path = $this->_reader->getTempCorePath();
            if ( !file_exists($tmp_path) ) {
                mkdir($tmp_path);
            }
            $coreInstanceDirPath = $this->_reader->getTempCorePath() .
                $coreInstanceDir;
        }

        // Test if the core does not already exist.
        if ($this->_coreExist($coreName)) {
            $this->_errors[] = str_replace(
                '%corename',
                $coreName,
                _('A core named %corename already exists!')
            ) . '<br/>' . _('Core has not been created.');
            return false;
        }


        if (is_dir($coreInstanceDirPath)) {
            $this->_errors[] = str_replace(
                '%dir',
                $coreInstanceDirPath,
                _('A directory %dir already exists!')
            ) . '<br/>' . _('Core has not been created.');
            return false;
        } else {
            $created = $this->_createCoreDir(
                $coreType,
                $coreInstanceDirPath,
                $coreName,
                $orm_name,
                $db_params
            );
            if ( !$created ) {
                return false;
            }
        }

        $create_params = array(
            'action'        => 'CREATE',
            'name'          => $coreName,
            'instanceDir'   => $coreInstanceDir,
            'config'        => $this->_reader->getDefaultConfigFileName(),
            'schema'        => $this->_reader->getDefaultSchemaFileName(),
            'dataDir'       => $this->_reader->getDefaultDataDir()
        );

        if ( is_writeable($this->_reader->getCoresPath()) ) {
            return $this->_send(
                $this->_reader->getCoresURL() . '/admin/cores',
                $create_params
            );
        } else {
            //a temporary core has been created. User has to copy it the right
            //place, and only then tell Bach/Solr to register it
            $solr_url = $this->_reader->getCoresURL() . '/admin/cores?';
            foreach ( $create_params as $key=>$param ) {
                $solr_url .=  $key . '=' . $param . '&';
            }
            $this->_warnings[] = nl2br(
                str_replace(
                    array('%tempdir', '%solr_url'),
                    array(
                        $coreInstanceDirPath,
                        '<a href="' . $solr_url . '">' . $solr_url . '</a>'
                    ),
                    _("A temporary core has been created in %tempdir. You have to put it the right place.\n\nOnce done, run the following URL in your Solr instance:\n%solr_url")
                )
            );
            return false;
        }
    }

    /**
     * Get status of one or all cores. If $coreName parameter does not specified,
     * the status of all cores will be return.
     *
     * @param string $coreName Solr core name
     *
     * @return SolrCoreResponse
     */
    public function getStatus($coreName = null)
    {
        $options = array(
            'action' => 'STATUS'
        );
        if ($coreName !== null) {
            $options['core'] = $coreName;
        }
        return $this->_send(
            $this->_reader->getCoresURL() . '/admin/cores',
            $options
        );
    }

    /**
     * Retrieve temporary created cores names, if any
     *
     * @return array
     */
    public function getTempCoresNames()
    {
        $path = $this->_reader->getTempCorePath();
        if ( !file_exists($path) ) {
            return;
        }

        $finder = new Finder();
        $finder
            ->ignoreDotFiles(true)
            ->ignoreVCS(true)
            -> ignoreUnreadableDirs(true)
            ->depth(0);

        return $finder->directories()->in($path);
    }

    /**
     * Reload core.
     *
     * @param string $coreName Solr core name
     *
     * @return SolrCoreResponse
     */
    public function reload($coreName)
    {
        return $this->_send(
            $this->_reader->getCoresURL() . '/admin/cores',
            array(
                'action'    => 'RELOAD',
                'core'      => $coreName
            )
        );
    }

    /**
     * Renames a core. If core does not exist, returns false.
     *
     * @param string $oldCoreName Existing core name
     * @param string $newCoreName New core name
     *
     * @return SolrCoreResponse|boolean
     */
    public function rename($oldCoreName, $newCoreName)
    {
        if (!$this->_coreExist($newCoreName)) {
            return $this->_send(
                $this->_reader->getCoresURL() . '/admin/cores',
                array(
                    'action'    => 'RENAME',
                    'core='     => $oldCoreName,
                    'other'     => $newCoreName
                )
            );
        }
        return false;
    }

    /**
     * Swaps two cores.
     *
     * @param string $core1 First core name
     * @param string $core2 Second core name
     *
     * @return SolrCoreResponse
     */
    public function swap($core1, $core2)
    {
        return $this->_send(
            $this->_reader->getCoresURL() . '/admin/cores',
            array(
                'action'    => 'SWAP',
                'core'      => $core1,
                'other'     => $core2
            )
        );
    }

    /**
     * Removes a core from Solr.
     *
     * @param string $coreName Solr core name
     *
     * @return SolrCoreResponse
     */
    public function unload($coreName)
    {
        return $this->_send(
            $this->_reader->getCoresURL() . '/admin/cores',
            array(
                'action'    => 'UNLOAD',
                'core'      => $coreName
            )
        );
    }

    /**
     * Removes a core from Solr and deletes related files.
     * If $type parameter equals to:
     * DELETE_INDEX : deletes the index
     * DELETE_DATA : removes "data" and all sub-directories
     * DELETE_CORE : removes core directory and all sub-directories.
     *     NOTE: it does not work if you had changed core's name before
     *     (because core's directory does not equal to its name)
     *
     * @param string $coreName Solr core name
     * @param int    $type     Delete type
     *
     * @return SolrCoreResponse
     */
    public function delete($coreName, $type = self::DELETE_CORE)
    {
        $options = array(
            'action'    => 'UNLOAD',
            'core'      => $coreName
        );

        switch ($type) {
        case self::DELETE_INDEX:
            $options['deleteIndex'] = 'true';
            break;
        case self::DELETE_DATA:
            $options['deleteDataDir'] = 'true';
            break;
        case self::DELETE_CORE:
            // Get core status for retreive core instance directory.
            $responseStatus = $this->getStatus($coreName);
            $coreInstanceDir = $responseStatus->getCoreStatus($coreName)
                ->getInstanceDir();
            // Unload core
            $response = $this->unload($coreName);
            if (!$response->isOk()) {
                $this->_errors[] = str_replace(
                    '%corename',
                    $coreName,
                    _("Unable to unload %corename, therefore it should not be removed.")
                );
                return false;
            }
            // Delete core instance directory. If we do not succeed,
            //we recreate the core we have just unloaded
            //FIXME: WTF? $this->create is not just about loading core
            //into solr, but also create directory.
            $result = $this->_deleteCoreDir($coreInstanceDir);
            if (!$result) {
                $this->create($coreName, $coreInstanceDir);
            }
            return $result;
            break;
        default :
            return false;
        }

        return $this->_send(
            $this->_reader->getCoresURL() . '/admin/cores',
            $options
        );
    }

    /**
     * Get core schema file path
     *
     * @param string $coreName Solr core name
     *
     * @return string
     */
    public function getSchemaPath($coreName)
    {
        return $this->_reader->getSchemaPath($coreName);
    }

    /**
     * Get core config file path
     *
     * @param string $coreName Solr core name
     *
     * @return string
     */
    public function getConfigPath($coreName)
    {
        $conf_dir = $this->_reader->getConfDir($coreName);
        return $conf_dir .
           $this->_reader->getDefaultConfigFileName();
    }

    /**
     * Create core directory with the same name as core name (sanitize).
     * If directory already exist, returns false.
     *
     * @param string $coreType            Solr core type
     * @param string $coreInstanceDirPath Core instance path
     * @param string $coreName            Core anme
     * @param array  $orm_name            ORM class name
     * @param array  $db_params           Database parameters for newly created core
     *
     * @return boolean
     */
    private function _createCoreDir($coreType, $coreInstanceDirPath, $coreName,
        $orm_name, $db_params
    ) {
        if (!is_dir($coreInstanceDirPath)) {
            $template  = $this->_reader->getCoreTemplatePath($coreType);
            $cmd = 'cp -r "' . $template . '" "' . $coreInstanceDirPath .
                '" 2>&1';
            exec(
                $cmd,
                $output,
                $status
            );

            if ( $status !== 0 ) {
                throw new \RuntimeException(
                    implode("\n", $output) .
                    "\nrunning: " . $cmd
                );
            }

            $this->_createSchema($coreInstanceDirPath, $coreName, $orm_name);
            $this->_createDataConfigFile(
                $coreInstanceDirPath,
                $orm_name,
                $db_params
            );
            return $status == 0 ? true : false;
        }
        return false;
    }

    /**
     * Create schema file
     *
     * @param string $coreInstanceDirPath Core instance dir
     * @param string $coreName            Core name
     * @param string $orm_name            ORM class name
     *
     * @return void
     */
    private function _createSchema($coreInstanceDirPath, $coreName, $orm_name)
    {
        $schemaFilePath = $coreInstanceDirPath . '/' .
            $this->_reader->getDefaultConfigDir() . '/' .
            $this->_reader->getDefaultSchemaFileName();
        $doc = new DOMDocument();
        $doc->formatOutput = true;
        $doc->preserveWhiteSpace = false;
        $doc->load($schemaFilePath);

        //set schema name
        $schema = $doc->getElementsByTagName('schema')->item(0);
        $schema->setAttribute('name', $coreName);

        // Creation of fields
        $elt = $doc->getElementsByTagName('fields')->item(0);

        //main fields from entity
        $fields = $this->_em->getClassMetadata($orm_name)->getFieldNames();

        if ( property_exists($orm_name, 'known_indexes') ) {
            //retrieve and add indexes fields from entity
            $ad_fields = $orm_name::$known_indexes;
            $fields = array_merge($fields, $ad_fields);
        }

        if ( property_exists($orm_name, 'extra_fields') ) {
            //retrieve and add additional fields from entity
            $ex_fields = $orm_name::$extra_fields;
            $fields = array_merge($fields, array_keys($ex_fields));
        }

        if ( property_exists($orm_name, 'extra_entities') ) {
            //retrieve and add additional fields from entity
            $ex_fields = $orm_name::$extra_entities;
            $fields = array_merge($fields, array_keys($ex_fields));
        }

        //retrieve multivalued fields
        $multivalued_fields = array();
        if ( property_exists($orm_name, 'multivalued') ) {
            //retrieve multi valued fields from entity
            $multivalued_fields = $orm_name::$multivalued;
        }

        $nonstored_fields = array();
        if ( property_exists($orm_name, 'nonstored') ) {
            //retrieve fields that are not stored from entity
            $nonstored_fields = $orm_name::$nonstored;
        }

        $nonindexed_fields = array();
        if ( property_exists($orm_name, 'nonindexed') ) {
            //retrieve fields that are not indexed from entity
            $nonindexed_fields = $orm_name::$nonindexed;
        }

        $fields_types = array();
        if ( property_exists($orm_name, 'types') ) {
            //retrieve fields types from entity
            $fields_types = $orm_name::$types;
        }

        $fields_text_mapped = array();
        if ( property_exists($orm_name, 'textMapped') ) {
            //retrieve fields text mapping from entity
            $fields_text_mapped = $orm_name::$textMapped;
        }

        foreach ($fields as $f) {
            $newFieldType = $doc->createElement('field');
            $newFieldType->setAttribute('name', $f);

            //set default type to string
            $type = 'string';
            if ( isset($fields_types[$f]) ) {
                $type = $fields_types[$f];
            }
            $newFieldType->setAttribute('type', $type);

            if ( in_array($f, $multivalued_fields) ) {
                $newFieldType->setAttribute('multiValued', 'true');
            }

            if ( in_array($f, $nonindexed_fields) ) {
                $newFieldType->setAttribute('indexed', 'false');
            } else {
                $newFieldType->setAttribute('indexed', 'true');
            }

            if ( in_array($f, $nonstored_fields) ) {
                $newFieldType->setAttribute('stored', 'false');
            } else {
                $newFieldType->setAttribute('stored', 'true');
            }

            $elt->appendChild($newFieldType);

            if ( isset($fields_text_mapped[$f]) ) {
                $newFieldType = $doc->createElement('field');
                $newFieldType->setAttribute('name', 't' . $f);
                $newFieldType->setAttribute('type', 'text');

                if ( in_array($f, $multivalued_fields) ) {
                    $newFieldType->setAttribute('multiValued', 'true');
                }

                $newFieldType->setAttribute('indexed', 'true');
                $newFieldType->setAttribute('stored', 'false');
                $elt->appendChild($newFieldType);
            }

        }

        //add fulltext field
        $fulltext = $doc->createElement('field');
        $fulltext->setAttribute('name', 'fulltext');
        $fulltext->setAttribute('type', 'text');
        $fulltext->setAttribute('multiValued', 'true');
        $fulltext->setAttribute('indexed', 'true');
        $fulltext->setAttribute('stored', 'false');
        $elt->appendChild($fulltext);

        //add suggestions field
        $suggestions = $doc->createElement('field');
        $suggestions->setAttribute('name', 'suggestions');
        $suggestions->setAttribute('type', 'phrase_suggest');
        $suggestions->setAttribute('multiValued', 'true');
        $suggestions->setAttribute('indexed', 'true');
        $suggestions->setAttribute('stored', 'false');
        $elt->appendChild($suggestions);

        $spell = $doc->createElement('field');
        $spell->setAttribute('name', 'spell');
        $spell->setAttribute('type', 'phrase_suggest');
        $spell->setAttribute('multiValued', 'true');
        $spell->setAttribute('indexed', 'true');
        $spell->setAttribute('stored', 'false');
        $elt->appendChild($spell);

        //add descriptors field
        $descriptors = $doc->createElement('field');
        $descriptors->setAttribute('name', 'descriptors');
        $descriptors->setAttribute('type', 'text');
        $descriptors->setAttribute('multiValued', 'true');
        $descriptors->setAttribute('indexed', 'true');
        $descriptors->setAttribute('stored', 'false');
        $elt->appendChild($descriptors);

        //add dynamic descriptors field
        $dyn_descriptors = $doc->createElement('dynamicField');
        $dyn_descriptors->setAttribute('name', 'dyndescr_*');
        $dyn_descriptors->setAttribute('type', 'string');
        $dyn_descriptors->setAttribute('multiValued', 'true');
        $dyn_descriptors->setAttribute('indexed', 'true');
        $dyn_descriptors->setAttribute('stored', 'true');
        $elt->appendChild($dyn_descriptors);

        //expanded fields
        if ( property_exists($orm_name, 'expanded_mappings') ) {
            $expanded_mappings = $orm_name::$expanded_mappings;

            foreach ( $expanded_mappings as $f ) {
                if ( in_array($f['source'], $fields) ) {
                    $expanded = $doc->createElement('field');
                    $expanded->setAttribute('name', $f['dest']);
                    $expanded->setAttribute('type', $f['type']);
                    $expanded->setAttribute('multiValued', $f['multivalued']);
                    $expanded->setAttribute('indexed', $f['indexed']);
                    $expanded->setAttribute('stored', $f['stored']);
                    $elt->appendChild($expanded);
                }
            }
        }

        //add new created elements
        $doc->documentElement->appendChild($elt);

        //add copyField for fulltext (all fields, minus nonfulltext
        //specified in entity)
        $nonfulltext = array();
        if ( property_exists($orm_name, 'nonfulltext') ) {
            $nonfulltext = $orm_name::$nonfulltext;
        }

        foreach ( $fields as $f ) {
            if ( !in_array($f, $nonfulltext) ) {
                $cf = $doc->createElement('copyField');
                $cf->setAttribute('source', $f);
                $cf->setAttribute('dest', 'fulltext');
                $doc->documentElement->appendChild($cf);
            }
        }

        //add spell field
        if ( property_exists($orm_name, 'spellers') ) {
            $spell_fields = $orm_name::$spellers;

            foreach ( $spell_fields as $f ) {
                $cf = $doc->createElement('copyField');
                $cf->setAttribute('source', $f);
                $cf->setAttribute('dest', 'spell');
                $doc->documentElement->appendChild($cf);
            }
        }

        //add copyField for suggestions (specific fields)
        if ( property_exists($orm_name, 'suggesters') ) {
            $suggestions_fields = $orm_name::$suggesters;

            foreach ( $suggestions_fields as $f ) {
                if ( in_array($f, $fields) ) {
                    $cf = $doc->createElement('copyField');
                    $cf->setAttribute('source', $f);
                    $cf->setAttribute('dest', 'suggestions');
                    $doc->documentElement->appendChild($cf);
                }
            }
        }

        //add copyFields for descriptors
        if ( property_exists($orm_name, 'descriptors') ) {
            $descriptors_fields = $orm_name::$descriptors;

            foreach ( $descriptors_fields as $f ) {
                if ( in_array($f, $fields) ) {
                    $cf = $doc->createElement('copyField');
                    $cf->setAttribute('source', $f);
                    $cf->setAttribute('dest', 'descriptors');
                    $doc->documentElement->appendChild($cf);
                }
            }
        }

        //add copy fields for text mapped fields
        foreach ( $fields_text_mapped as $fo=>$fd ) {
            if ( in_array($fo, $fields) ) {
                $cf = $doc->createElement('copyField');
                $cf->setAttribute('source', $fo);
                $cf->setAttribute('dest', $fd);
                $doc->documentElement->appendChild($cf);
            }
        }

        //other specific mappings
        if ( property_exists($orm_name, 'expanded_mappings') ) {
            $expanded_mappings = $orm_name::$expanded_mappings;

            foreach ( $expanded_mappings as $f ) {
                if ( in_array($f['source'], $fields) ) {
                    $cf = $doc->createElement('copyField');
                    $cf->setAttribute('source', $f['source']);
                    $cf->setAttribute('dest', $f['dest']);
                    $doc->documentElement->appendChild($cf);
                }
            }
        }

        //save XML document
        $doc->save($schemaFilePath);
    }

    /**
     * Deletes core directory and all sub-directories.
     *
     * @param string $coreInstanceDirPath Directory name
     *
     * @return boolean
     */
    private function _deleteCoreDir($coreInstanceDirPath)
    {
        if (is_dir($coreInstanceDirPath)) {
            $output = array();
            exec('rm -r "' . $coreInstanceDirPath . '"', $output, $status);
            return $status == 0 ? true : false;
        }
        return true;
    }

    /**
     * Verify whether a core exist. Throw an exception if can not
     * obtain Solr cores status.
     *
     * @param string $coreName Solr core name
     *
     * @throws Exception
     *
     * @return boolean
     */
    private function _coreExist($coreName)
    {
        try {
            $status = $this->getStatus();
            if ($status->isOk()) {
                $cores = $status->getCoreNames();
                foreach ($cores as $c) {
                    if ($c == $coreName) {
                        return true;
                    }
                }
                return false;
            } else {
                throw new Exception('Can not obtain Solr cores status');
            }
        } catch (Exception $e) {
            echo 'Caught exception : ' .  $e->getMessage();
        }
    }

    /**
     * Sends an HTTP query (POST method) to Solr and returns
     * result as a SolrCoreResponse object.
     *
     * @param string $url     HTTP URL
     * @param array  $options Request options
     *
     * @return SolrCoreResponse
     */
    private function _send($url, $options = null)
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        if ( $options !== null && is_array($options) ) {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $options);
        }

        $response = curl_exec($ch);
        if ( $response === false ) {
            throw new \RuntimeException(
                "Error on request:\n\tURI:" . $url . "\n\toptions:\n" .
                print_r($options, true)
            );
        }

        //get request infos
        $infos = curl_getinfo($ch);
        if ( $infos['http_code'] !== 200 ) {
            $trace = debug_backtrace();
            $caller = $trace[1];

            //At this point, core has been created, but is failing 
            //to load in solr.
            throw new \RuntimeException(
                'Something went wrong in function ' . __CLASS__ . '::' .
                $caller['function'] . "\nHTTP Request URI: " . $url .
                "\nSent options: " . print_r($options, true) .
                "\nCheck cores status for more informations."
            );
        }

        return new SolrCoreResponse($response);
    }

    /**
     * Create data config file
     *
     * @param string $coreInstanceDirPath Core instance path
     * @param string $orm_name            ORM class name
     * @param array  $db_params           Database parameters for newly created core
     *
     * @return void
     */
    private function _createDataConfigFile($coreInstanceDirPath,
        $orm_name, $db_params
    ) {
        $dataConfigFilePath = $coreInstanceDirPath . '/' .
            $this->_reader->getDefaultConfigDir() . '/' .
            $this->_reader->getDefaultDataConfigFileName();

        $doc = new DOMDocument();
        $doc->formatOutput = true;
        $doc->preserveWhiteSpace = false;
        $doc->load($dataConfigFilePath);

        $meta = $this->_em->getClassMetadata($orm_name);
        //table name from entity
        $table_name = $meta->getTablename();
        //main fields from entity
        $fields = $meta->getFieldNames();

        $elt = $doc->getElementsByTagName('dataSource')->item(0);
        $elt->setAttribute('type', 'JdbcDataSource');
        $elt->setAttribute('driver', $db_params['driver']);
        $elt->setAttribute('url', $db_params['url']);
        $elt->setAttribute('user', $db_params['user']);
        $elt->setAttribute('password', $db_params['password']);

        $elt = $doc->getElementsByTagName('entity')->item(0);
        $query = 'SELECT *';
        //query specific additionnal fields
        if ( property_exists($orm_name, 'qry_fields') ) {
            $qry_fields = $orm_name::$qry_fields;
            foreach ( $qry_fields as $k=>$v ) {
                $query .= ', ' . $v . ' AS ' . $k;
            }
        }
        $query .= ' FROM ' . $table_name . ' WHERE '.
            '\'${dataimporter.request.clean}\'' . ' != \'false\' '.
            ' OR updated > ' . '\'${dataimporter.last_index_time}\'';

        $elt->setAttribute('query', $query);

        //fields specific attributes
        if ( property_exists($orm_name, 'dataconfig_attrs') ) {
            $attrs = $orm_name::$dataconfig_attrs;
        }

        foreach ($fields as $f ) {
            $newField = $doc->createElement('field');
            $newField->setAttribute('column', $f);
            $newField->setAttribute('name', $f);

            //other specific mappings
            if ( isset($attrs) && isset($attrs[$f]) ) {
                $field_attrs = $attrs[$f];
                foreach ( $field_attrs as $name=>$value ) {
                    $newField->setAttribute($name, $value);
                }
            }
            $elt->appendChild($newField);
        }

        if ( property_exists($orm_name, 'descriptors')
            && $meta->hasAssociation('indexes')
        ) {
            //retrieve and add additional fields from entity
            $ad_fields = $orm_name::$descriptors;

            $mapping = $meta->getAssociationMapping('indexes');
            $mapping_entity = $this->_em->getClassMetadata(
                $mapping['targetEntity']
            );
            $mapping_table = $mapping_entity->getTablename();

            foreach ( $ad_fields as $f ) {
                $newEntity = $doc->createElement('entity');
                $newEntity->setAttribute('name', $f);
                $newEntity->setAttribute(
                    'query',
                    'SELECT * FROM ' . $mapping_table . ' WHERE type=\'' . $f . '\''
                );

                $newEntity->setAttribute('cacheKey', 'eadfile_id');
                $newEntity->setAttribute('cacheLookup', 'SolrXMLFile.uniqid');
                $newEntity->setAttribute('processor', 'SqlEntityProcessor');
                $newEntity->setAttribute('cacheImpl', 'SortedMapBackedCache');

                $newField = $doc->createElement('field');
                $newField->setAttribute('column', 'name');
                $newField->setAttribute('name', $f);

                $newEntity->appendChild($newField);
                $elt->appendChild($newEntity);
            }
        }

        //take care of dynamic descriptors
        if ( property_exists($orm_name, 'dynamic_descriptors') ) {
            //retrieve and add additional fields from entity
            $dyndescr_fields = $orm_name::$dynamic_descriptors;
            foreach ( $dyndescr_fields as $cond=>$func ) {
                $newEntity = $doc->createElement('entity');
                $newEntity->setAttribute('name', 'dyn_' . $cond);
                $newEntity->setAttribute(
                    'query',
                    'SELECT * FROM ' . $mapping_table . ' WHERE ' . $cond .
                    ' IS NOT NULL'
                );

                $newEntity->setAttribute('transformer', 'script:' . $func);

                $newEntity->setAttribute('cacheKey', 'eadfile_id');
                $newEntity->setAttribute('cacheLookup', 'SolrXMLFile.uniqid');
                $newEntity->setAttribute('processor', 'SqlEntityProcessor');
                $newEntity->setAttribute('cacheImpl', 'SortedMapBackedCache');

                $elt->appendChild($newEntity);
            }

            //descriptors with no dynamics
            $newEntity = $doc->createElement('entity');
            $newEntity->setAttribute('name', 'dyn_none');
            $newEntity->setAttribute(
                'query',
                'SELECT * FROM ' . $mapping_table . ' WHERE source IS NULL AND ' .
                'role IS NULL AND type != \'cDate\''
            );

            $newEntity->setAttribute('transformer', 'script:makeSourcesDynamics');

            $newEntity->setAttribute('cacheKey', 'eadfile_id');
            $newEntity->setAttribute('cacheLookup', 'SolrXMLFile.uniqid');
            $newEntity->setAttribute('processor', 'SqlEntityProcessor');
            $newEntity->setAttribute('cacheImpl', 'SortedMapBackedCache');

            $elt->appendChild($newEntity);
        }

        //take care of dates
        if ( property_exists($orm_name, 'dates') ) {
            $mapping = $meta->getAssociationMapping('dates');
            $mapping_entity = $this->_em->getClassMetadata(
                $mapping['targetEntity']
            );
            $mapping_table = $mapping_entity->getTablename();

            $newEntity = $doc->createElement('entity');
            $newEntity->setAttribute('name', 'dates');
            $newEntity->setAttribute(
                'query',
                'SELECT * FROM ' . $mapping_table . ' WHERE eadfile_id=' .
                '\'${SolrXMLFile.uniqid}\''
            );

            $newField = $doc->createElement('field');
            $newField->setAttribute('column', 'date');
            $newField->setAttribute('name', 'cDate');
            $newEntity->appendChild($newField);

            $newField = $doc->createElement('field');
            $newField->setAttribute('column', 'normal');
            $newField->setAttribute('name', 'cDateNormal');
            $newEntity->appendChild($newField);

            $newField = $doc->createElement('field');
            $newField->setAttribute('column', 'begin');
            $newField->setAttribute('name', 'cDateBegin');
            $newEntity->appendChild($newField);

            $newField = $doc->createElement('field');
            $newField->setAttribute('column', 'dend');
            $newField->setAttribute('name', 'cDateEnd');
            $newEntity->appendChild($newField);

            $elt->appendChild($newEntity);
        }

        //take care of eadheader
        if ( $meta->hasAssociation('eadheader')) {
            $mapping = $meta->getAssociationMapping('eadheader');
            $mapping_entity = $this->_em->getClassMetadata(
                $mapping['targetEntity']
            );
            $mapping_table = $mapping_entity->getTablename();

            $newEntity = $doc->createElement('entity');
            $newEntity->setAttribute('name', 'eadheader');
            $newEntity->setAttribute(
                'query',
                'SELECT * FROM ' . $mapping_table . ' WHERE id=' .
                '\'${SolrXMLFile.eadheader_id}\''
            );

            $newField = $doc->createElement('field');
            $newField->setAttribute('column', 'headerId');
            $newField->setAttribute('name', 'headerId');
            $newEntity->appendChild($newField);

            $newField = $doc->createElement('field');
            $newField->setAttribute('column', 'headerTitle');
            $newField->setAttribute('name', 'headerTitle');
            $newEntity->appendChild($newField);

            $newField = $doc->createElement('field');
            $newField->setAttribute('column', 'headerSubtitle');
            $newField->setAttribute('name', 'headerSubtitle');
            $newEntity->appendChild($newField);

            $newField = $doc->createElement('field');
            $newField->setAttribute('column', 'headerAuthor');
            $newField->setAttribute('name', 'headerAuthor');
            $newEntity->appendChild($newField);

            $newField = $doc->createElement('field');
            $newField->setAttribute('column', 'headerDate');
            $newField->setAttribute('name', 'headerDate');
            $newEntity->appendChild($newField);

            $newField = $doc->createElement('field');
            $newField->setAttribute('column', 'headerPublisher');
            $newField->setAttribute('name', 'headerPublisher');
            $newEntity->appendChild($newField);

            $newField = $doc->createElement('field');
            $newField->setAttribute('column', 'headerAddress');
            $newField->setAttribute('name', 'headerAddress');
            $newEntity->appendChild($newField);

            $newField = $doc->createElement('field');
            $newField->setAttribute('column', 'headerLanguage');
            $newField->setAttribute('name', 'headerLanguage');
            $newEntity->appendChild($newField);

            $elt->appendChild($newEntity);
        }

        //take care of archdesc
        if ( $meta->hasAssociation('archdesc') ) {
            $newEntity = $doc->createElement('entity');
            $newEntity->setAttribute('name', 'archdesc');
            $newEntity->setAttribute(
                'query',
                'SELECT * FROM ' . $table_name . ' WHERE uniqid=' .
                '\'${SolrXMLFile.archdesc_id}\''
            );

            $newField = $doc->createElement('field');
            $newField->setAttribute('column', 'cUnitid');
            $newField->setAttribute('name', 'archDescUnitId');
            $newEntity->appendChild($newField);

            $newField = $doc->createElement('field');
            $newField->setAttribute('column', 'cUnitidbegin');
            $newField->setAttribute('name', 'cUnitidbegin');
            $newEntity->appendChild($newField);

            $newField = $doc->createElement('field');
            $newField->setAttribute('column', 'cUnitidend');
            $newField->setAttribute('name', 'cUnitidend');
            $newEntity->appendChild($newField);

            $newField = $doc->createElement('field');
            $newField->setAttribute('column', 'cUnittitle');
            $newField->setAttribute('name', 'archDescUnitTitle');
            $newEntity->appendChild($newField);

            $newField = $doc->createElement('field');
            $newField->setAttribute('column', 'cScopcontent');
            $newField->setAttribute('name', 'archDescScopeContent');
            $newEntity->appendChild($newField);

            $elt->appendChild($newEntity);
        }

        //take care of daos
        if ( $meta->hasAssociation('daos') ) {
            $mapping = $meta->getAssociationMapping('daos');
            $mapping_entity = $this->_em->getClassMetadata(
                $mapping['targetEntity']
            );
            $mapping_table = $mapping_entity->getTablename();

            $newEntity = $doc->createElement('entity');
            $newEntity->setAttribute('name', 'daos');
            $newEntity->setAttribute(
                'query',
                'SELECT * FROM ' . $mapping_table . ' WHERE eadfile_id=' .
                '\'${SolrXMLFile.uniqid}\''
            );
            $newField = $doc->createElement('field');
            $newField->setAttribute('column', 'href');
            $newField->setAttribute('name', 'dao');
            $newEntity->appendChild($newField);

            $newField = $doc->createElement('field');
            $newField->setAttribute('column', 'cMediaContent');
            $newField->setAttribute('name', 'cMediaContent');
            $newEntity->appendChild($newField);

            $elt->appendChild($newEntity);
        }

        //take care of extra fields
        if ( property_exists($orm_name, 'extra_fields') ) {
            $ex_fields = $orm_name::$extra_fields;
            foreach ( $ex_fields as $fname=>$fdb ) {
                $newField = $doc->createElement('field');
                $newField->setAttribute('column', $fdb);
                $newField->setAttribute('name', $fname);
                $elt->appendChild($newField);
            }
        }

        //take care of extra entities
        if ( property_exists($orm_name, 'extra_entities') ) {
            //retrieve and add additional fields from entity
            $ex_fields = $orm_name::$extra_entities;
            foreach ( $ex_fields as $fname=>$fdb ) {
                if ( $meta->hasAssociation($fname) ) {
                    $mapping = $meta->getAssociationMapping($fname);
                    $mapping_entity = $this->_em->getClassMetadata(
                        $mapping['targetEntity']
                    );
                    $mapping_table = $mapping_entity->getTablename();

                    $newEntity = $doc->createElement('entity');
                    $newEntity->setAttribute('name', $fname);
                    $newEntity->setAttribute(
                        'query',
                        'SELECT * FROM ' . $mapping_table . ' WHERE eadfile_id=' .
                        '\'${SolrXMLFile.uniqid}\''
                    );
                    $newField = $doc->createElement('field');
                    $newField->setAttribute('column', $fdb);
                    $newField->setAttribute('name', $fname);

                    $newEntity->appendChild($newField);
                    $elt->appendChild($newEntity);
                }
            }
        }

        $doc->save($dataConfigFilePath);
    }

    /**
     * Retrieve warnings
     *
     * @return array
     */
    public function getWarnings()
    {
        return $this->_warnings;
    }

    /**
     * Retrieve errors
     *
     * @return array
     */
    public function getErrors()
    {
        return $this->_errors;
    }

    /**
     * Get database parameters from current config,
     * to use values in newly created core
     *
     * @param string $driver   Database driver
     * @param string $host     Database host
     * @param string $port     Database port
     * @param string $dbname   Database name
     * @param string $user     Database user
     * @param string $password Database password
     *
     * @return array
     */
    public function getJDBCDatabaseParameters(
        $driver, $host, $port, $dbname, $user, $password
    ) {
        $params = array();

        $driver = str_replace(
            'pdo_',
            '',
            $driver
        );
        if ( $driver == 'pgsql' ) {
            $driver = 'postgresql';
        }
        if ( $port !== null ) {
            $port = ':' . $port;
        } else {
            $port = '';
        }

        $dsn = 'jdbc:' . $driver . '://' . $host . $port . '/' . $dbname;

        $jdbc_driver = null;
        switch ( $driver ) {
        case 'mysql':
            $jdbc_driver = 'com.mysql.jdbc.Driver';
            break;
        case 'postgresql':
            $jdbc_driver = 'org.postgresql.Driver';
            break;
        default:
            throw new \RuntimeException('Unknown database driver ' . $driver);
        }

        $params['driver'] = $jdbc_driver;
        $params['url'] = $dsn;
        $params['user'] = $user;
        $params['password'] = $password;

        return $params;
    }
}
