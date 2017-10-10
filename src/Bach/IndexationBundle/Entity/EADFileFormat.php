<?php
/**
 * Bach EAD File Format entity
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
namespace Bach\IndexationBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * Bach EAD File Format entity
 *
 * @category Indexation
 * @package  Bach
 * @author   Johan Cwiklinski <johan.cwiklinski@anaphore.eu>
 * @license  BSD 3-Clause http://opensource.org/licenses/BSD-3-Clause
 * @link     http://anaphore.eu
 *
 * @ORM\Entity
 * @ORM\Table(name="ead_file_format")
 */
class EADFileFormat extends FileFormat
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer", length=10)
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $uniqid;

    /**
     * @ORM\Column(type="string", nullable=true, length=100)
     */
    protected $parents;

    /**
     * @ORM\Column(type="string", nullable=true, length=1000)
     */
    protected $cUnitid;

    /**
     * @ORM\Column(type="string", nullable=true, length=500)
     */
    protected $cUnitidbegin;

   /**
     * @ORM\Column(type="string", nullable=true, length=500)
     */
    protected $cUnitidend;

    /**
     * @ORM\Column(type="string", nullable=true, length=3000)
     */
    protected $cUnittitle;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    protected $cScopcontent;

    /**
     * @ORM\Column(type="string", nullable=true, length=200)
     */
    protected $cControlacces;

    /**
     * @ORM\Column(type="string", nullable=true, length=200)
     */
    protected $cLegalstatus;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    protected $fragment;

    /**
     * @ORM\Column(type="string", nullable=true, length=200)
     */
    protected $cRepository;

    /**
     * @ORM\Column(type="string", nullable=true, length=200)
     */
    protected $cAudience;

    /**
     * @ORM\Column(type="integer", length=10, nullable=true)
     */
    protected $elt_order;

    /**
     * @ORM\OneToMany(targetEntity="EADIndexes", mappedBy="eadfile", cascade={"persist", "remove", "merge"}, orphanRemoval=true)
     */
    protected $indexes;

    /**
     * @ORM\OneToMany(targetEntity="EADDates", mappedBy="eadfile", cascade={"persist", "remove", "merge"}, orphanRemoval=true)
     */
    protected $dates;

    /**
     * @ORM\OneToMany(targetEntity="EADDaos", mappedBy="eadfile", cascade={"persist", "remove", "merge"}, orphanRemoval=true)
     */
    protected $daos;

    /**
     * @ORM\OneToMany(targetEntity="EADParentTitle", mappedBy="eadfile", cascade={"persist", "remove", "merge"}, orphanRemoval=true)
     */
    protected $parents_titles;

    /**
     * @ORM\Column(type="text", length=100)
     */
    protected $fragmentid;

    /**
     * @ORM\ManyToOne(targetEntity="EADHeader")
     * @ORM\JoinColumn(name="eadheader_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $eadheader;

    /**
     * @ORM\ManyToOne(targetEntity="EADFileFormat")
     * @ORM\JoinColumn(name="archdesc_id", referencedColumnName="uniqid", onDelete="CASCADE")
     */
    protected $archdesc;

    /**
     * @ORM\Column(type="string", nullable=true, length=100)
     */
    protected $previous_id;

    /**
     * @ORM\Column(type="string", nullable=true, length=3000)
     */
    protected $previous_title;

    /**
     * @ORM\Column(type="string", nullable=true, length=100)
     */
    protected $next_id;

    /**
     * @ORM\Column(type="string", nullable=true, length=3000)
     */
    protected $next_title;

    /**
     * The constructor
     *
     * @param array   $data    The input data
     * @param boolean $changes Take care of changes
     */
    public function __construct($data, $changes = true, $pdfFlag)
    {
        $this->indexes = new ArrayCollection();
        $this->dates = new ArrayCollection();
        $this->daos = new ArrayCollection();
        $this->parents_titles = new ArrayCollection();
        parent::__construct($data, $changes, $pdfFlag);
    }

    /**
     * Extra fields not in database
     */
    public static $extra_entities = array(
        'parents_titles' => 'unittitle'
    );

    /**
     * Additional fields not directly managed by the entity
     */
    public static $known_indexes = array(
        'cCorpname',
        'cFamname',
        'cGenreform',
        'cGeogname',
        'cName',
        'cPersname',
        'cSubject',
        'cDate',
        'cDateNormal',
        'cDateBegin',
        'cDateEnd',
        'dao',
        'cTitle',
        'cMediaContent',
        'cFunction',
        'headerId',
        'headerTitle',
        'headerSubtitle',
        'archDescUnitId',
        'archDescUnitTitle',
        'archDescScopeContent'
    );

    /**
     * Fields that are mutlivalued
     */
    public static $multivalued = array(
        'dao',
        'cCorpname',
        'cFamname',
        'cGenreform',
        'cGeogname',
        'cName',
        'cPersname',
        'cSubject',
        'subject_w_expanded',
        'parents_titles',
        'cTitle',
        'cFunction'
    );

    /**
     * Fields that will be excluded from fulltext field
     */
    public static $nonfulltext = array(
        'uniqid',
        'headerId',
        'parents',
        'elt_order',
        'archDescScopeContent',
        'fragment',
        'fragmentid',
        'cDateBegin',
        'cDateEnd',
        'dao',
        'subject_w_expanded',
        'created',
        'updated',
        'previous_id',
        'previous_title',
        'next_id',
        'next_title',
        'cLegalstatus',
        'cMediaContent',
        'audience'
    );

    /**
     * Fields included in spell field
     */
    public static $spellers = array(
        'c*'
    );

    /**
     * Fields included in suggestions field
     */
    public static $suggesters = array(
        'cUnittitle',
        'cCorpname',
        'cFamname',
        'cGenreform',
        'cGeogname',
        'cName',
        'cPersname',
        'cSubject',
        'cTitle',
        'cFunction'
    );

    /**
     * Descriptors fields
     */
    public static $descriptors = array(
        'cCorpname',
        'cFamname',
        'cGenreform',
        'cGeogname',
        'cName',
        'cPersname',
        'cSubject',
        'cTitle',
        'cFunction'
    );

    /**
     * Fields types, if not string
     */
    public static $types = array(
        'cUnittitle'            => 'text',
        'ocUnittitle'           => 'alphaOnlySort',
        'elt_order'             => 'int',
        'subject_w_expanded'    => 'skosLabel',
        'parents_titles'        => 'text',
        'cDateBegin'            => 'date',
        'cDateEnd'              => 'date',
        'dao'                   => 'ancestor_path',
        'cUnitidbegin'          => 'int',
        'cUnitidend'            => 'int',
        'cMediaContent'         => 'text',
        'fragment'              => 'text'
    );

    /**
     * Fields that are not indexed
     */
    public static $nonindexed = array(
        'fragment'
    );

    /**
     * Dynamic descriptors discover
     *
     * array(
     *  db column name not null => solr function called (defined in schema.xml)
     * )
     */
    public static $dynamic_descriptors = array(
        'source'    => 'makeSourcesDynamics',
        'role'      => 'makeRolesDynamics'
    );

    /**
     * Fields that should not be used for facetting
     */
    public static $facet_excluded = array(
        '_version_',
        'fragment',
        'fragmentid',
        'fulltext',
        'parents_titles',
        'parents',
        'spell',
        'suggestions',
        'ocUnittitle',
        'uniqid',
        'cScopcontent',
        'cDateNormal',
        'cDate',
        'cDateEnd',
        'archDescOrigination',
        'subject_w_expanded',
        'archDescScopeContent',
        'next_id',
        'next_title',
        'previous_id',
        'previous_title',
        'updated',
        'created',
        'elt_order',
        'cMediaContent'
    );

    /**
     * Expanded fields mappings
     */
    public static $expanded_mappings = array(
        array(
            'source'        => 'cSubject',
            'dest'          => 'subject_w_expanded',
            'type'          => 'skos_w_label',
            'multivalued'   => 'true',
            'indexed'       => 'true',
            'stored'        => 'true'
        ),
        array(
            'source'        => 'cUnittitle',
            'dest'          => 'ocUnittitle',
            'type'          => 'alphaOnlySort',
            'multivalued'   => 'false',
            'indexed'       => 'true',
            'stored'        => 'false'
        )
    );

    /**
     * Proceed data parsing
     *
     * @param array   $data    Data
     * @param boolean $changes Take care of changes
     *
     * @return void
     */
    protected function parseData($data, $pdfFlag, $changes = true)
    {
        $this->check_changes = $changes;
        foreach ($data as $key=>$value) {
            if ( $key === 'descriptors' ) {
                $this->parseIndexes($value);
            } else if ( $key === 'parents_titles' ) {
                $this->parseParentsTitles($value);
            } elseif (property_exists($this, $key)) {
                if ( $this->$key !== $value ) {
                    $this->onPropertyChanged($key, $this->$key, $value);
                    $this->$key = $value;
                }
            } elseif ($key === 'geolocalized' ) {
            } elseif ($key === 'cDate') {
                $this->parseDates($value);
            } elseif ( $key == 'daolist' ) {
                $this->parseDaos($value, $pdfFlag);
            } else {
                throw new \RuntimeException(
                    __CLASS__ . ' - Key ' . $key . ' is not known!'
                );
            }
        }

        //check for sub-elements removal
        $this->_checkRemovals($data);
    }

    /**
     * Check for elements that have all been removed
     *
     * @param array $data Data
     *
     * @return void
     */
    private function _checkRemovals($data)
    {
        //check if indexes has been removed
        if ( !isset($data['descriptors'])
            && $this->indexes->count() > 0
        ) {
            $this->onPropertyChanged(
                'indexes',
                $this->indexes,
                new ArrayCollection()
            );
            foreach ( $this->indexes as $index ) {
                $this->removeIndex($index);
                $this->removed[] = $index;
            }
        }

        if ( !isset($data['cDate'])
            && $this->dates->count() > 0
        ) {
            $this->onPropertyChanged(
                'dates',
                $this->dates,
                new ArrayCollection()
            );
            foreach ( $this->dates as $date ) {
                $this->removeDate($date);
                $this->removed[] = $date;
            }
        }

        if ( !isset($data['daolist'])
            && $this->daos->count() > 0
        ) {
            $this->onPropertyChanged(
                'daos',
                $this->daos,
                new ArrayCollection()
            );
            foreach ( $this->daos as $dao ) {
                $this->removeDao($dao);
                $this->removed[] = $dao;
            }
        }

        if ( !isset($data['parents_titles'])
            && $this->parents_titles->count() > 0
        ) {
            $this->onPropertyChanged(
                'parents_titles',
                $this->parents_titles,
                new ArrayCollection()
            );
            foreach ( $this->parents_titles as $ptitle ) {
                $this->removeParentTitle($ptitle);
                $this->removed[] = $ptitle;
            }
        }
    }

    /**
     * Set parents
     *
     * @param string $parents Parents
     *
     * @return EADFileFormat
     */
    public function setParents($parents)
    {
        $this->parents = $parents;
        return $this;
    }

    /**
     * Get parents
     *
     * @return string
     */
    public function getParents()
    {
        return $this->parents;
    }

    /**
     * Set cUnitid
     *
     * @param string $cUnitid unitid
     *
     * @return EADFileFormat
     */
    public function setCUnitid($cUnitid)
    {
        $this->cUnitid = $cUnitid;
        return $this;
    }

    /**
     * Get cUnitid
     *
     * @return string
     */
    public function getCUnitid()
    {
        return $this->cUnitid;
    }

    /**
     * Set cUnitidbegin
     *
     * @param string $cUnitidbegin unitidbegin
     *
     * @return EADFileFormat
     */
    public function setCUnitidbegin($cUnitidbegin)
    {
        $this->cUnitidbegin = $cUnitidbegin;
        return $this;
    }

    /**
     * Get cUnitidbegin
     *
     * @return string
     */
    public function getCUnitidbegin()
    {
        return $this->cUnitidbegin;
    }

    /**
     * Set cUnitidend
     *
     * @param string $cUnitidend unitidend
     *
     * @return EADFileFormat
     */
    public function setCUnitidend($cUnitidend)
    {
        $this->cUnitidend = $cUnitidend;
        return $this;
    }

    /**
     * Get cUnitidend
     *
     * @return string
     */
    public function getCUnitidend()
    {
        return $this->cUnitidend;
    }

    /**
     * Set cUnittitle
     *
     * @param string $cUnittitle unittitle
     *
     * @return EADFileFormat
     */
    public function setCUnittitle($cUnittitle)
    {
        $this->cUnittitle = $cUnittitle;
        return $this;
    }

    /**
     * Get cUnittitle
     *
     * @return string
     */
    public function getCUnittitle()
    {
        return $this->cUnittitle;
    }

    /**
     * Set cScopcontent
     *
     * @param string $cScopcontent scopecontent
     *
     * @return EADFileFormat
     */
    public function setCScopcontent($cScopcontent)
    {
        $this->cScopcontent = $cScopcontent;
        return $this;
    }

    /**
     * Get cScopcontent
     *
     * @return string
     */
    public function getCScopcontent()
    {
        return $this->cScopcontent;
    }

    /**
     * Set cControlacces
     *
     * @param string $cControlacces controlaccess
     *
     * @return EADFileFormat
     */
    public function setCControlacces($cControlacces)
    {
        $this->cControlacces = $cControlacces;
        return $this;
    }

    /**
     * Get cControlacces
     *
     * @return string
     */
    public function getCControlacces()
    {
        return $this->cControlacces;
    }

    /**
     * Set repository
     *
     * @param string $repository repository
     *
     * @return EADFileFormat
     */
    public function setRepository($repository)
    {
        $this->repository = $repository;
        return $this;
    }

    /**
     * Get repository
     *
     * @return string
     */
    public function getRepository()
    {
        return $this->repository;
    }

    /**
     * Set CAudience
     *
     * @param string $audience audience
     *
     * @return EADFileFormat
     */
    public function setCAudience($audience)
    {
        $this->cAudience = $audience;
        return $this;
    }

    /**
     * Get CAudience
     *
     * @return string
     */
    public function getCAudience()
    {
        return $this->cAudience;
    }

    /**
     * Parse indexes objects from bag
     *
     * @param array $data Indexes data
     *
     * @return void
     */
    protected function parseIndexes($data)
    {
        $indexes = clone $this->indexes;
        $has_changed = false;

        //check for removal
        foreach ( $this->indexes as $index ) {
            $found = false;
            foreach ( $data as $type => $values ) {
                foreach ( $values as $new_index ) {
                    $source = null;
                    if ( isset($new_index['attributes']['source']) ) {
                        $source = $new_index['attributes']['source'];
                    }

                    $role = null;
                    if ( isset($new_index['attributes']['role']) ) {
                        $role = $new_index['attributes']['role'];
                    }

                    if ( $index->getType() == $type
                        && $index->getName() == $new_index['value']
                        && $index->getRole() == $role
                        && $index->getSource() == $source
                    ) {
                        $found = true;
                        break;
                    }
                }
            }
            if ( !$found ) {
                $this->removeIndex($index);
                $this->removed[] = $index;
                $has_changed = true;
            }
        }

        //check for new
        foreach ( $data as $type => $values ) {
            foreach ( $values as $index ) {
                $unique = true;

                foreach ( $this->indexes as $i ) {
                    $source = null;
                    if ( isset($index['attributes']['source']) ) {
                        $source = $index['attributes']['source'];
                    }

                    $role = null;
                    if ( isset($index['attributes']['role']) ) {
                        $role = $index['attributes']['role'];
                    }

                    if ( $i->getType() == $type
                        && $i->getName() == $index['value']
                        && $i->getRole() == $role
                        && $i->getSource() == $source
                    ) {
                        $unique = false;
                        break;
                    }
                }

                if ( $unique === true ) {
                    $id = null;
                    if ( isset($index['id']) ) {
                        $id = $index['id'];
                        unset($index['id']);
                    }
                    $idx = new EADIndexes($this, $type, $index);
                    if ( $id !== null ) {
                        $idx->setId($id);
                    }
                    $this->addIndex($idx);
                    $has_changed = true;
                }
            }
        }

        //notify if something has changed
        if ( $has_changed ) {
            $this->onPropertyChanged('indexes', $indexes, $this->indexes);
        }
    }

    /**
     * Add index
     *
     * @param EADIndexes $index Index data
     *
     * @return EADFileFormat
     */
    public function addIndex(EADIndexes $index)
    {
        $this->indexes[] = $index;
        return $this;
    }

    /**
     * Remove index
     *
     * @param EADIndexes $index Index
     *
     * @return void
     */
    public function removeIndex(EADIndexes $index)
    {
        $this->indexes->removeElement($index);
    }

    /**
     * Get indexes
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getIndexes()
    {
        return $this->indexes;
    }

    /**
     * Parse dates objects from bag
     *
     * @param array $data Dates data
     *
     * @return void
     */
    protected function parseDates($data)
    {
        $dates = clone $this->dates;
        $has_changed = false;

        //check for removal
        foreach ( $this->dates as $date ) {
            $found = false;
            foreach ( $data as $new_date ) {
                $odate = new EADDates($this, $new_date);

                if ( $odate->isValid() ) {
                    $begin = $date->getBegin()->format('Y-m-d');
                    $obegin = $odate->getBegin()->format('Y-m-d');

                    $end = $date->getEnd()->format('Y-m-d');
                    $oend = $odate->getEnd()->format('Y-m-d');

                    if ( $date->getDate() == $odate->getDate()
                        && $date->getNormal() == $odate->getNormal()
                        && $begin == $obegin
                        && $end == $oend
                    ) {
                        $found = true;
                        break;
                    }
                }
            }
            if ( !$found ) {
                $this->removeDate($date);
                $this->removed[] = $date;
                $has_changed = true;
            }
        }

        //check for new
        foreach ( $data as $date ) {
            $odate = new EADDates($this, $date);

            if ( $odate->isValid() ) {
                $unique = true;

                foreach ( $this->dates as $i ) {

                    $begin = $i->getBegin()->format('Y-m-d');
                    $obegin = $odate->getBegin()->format('Y-m-d');

                    $end = $i->getEnd()->format('Y-m-d');
                    $oend = $odate->getEnd()->format('Y-m-d');

                    if ( $i->getDate() == $odate->getDate()
                        && $i->getNormal() == $odate->getNormal()
                        && $begin == $obegin
                        && $end == $oend
                    ) {
                        $unique = false;
                        break;
                    }
                }

                if ( $unique === true ) {
                    $id = null;
                    if ( isset($date['id']) ) {
                        $odate->setId($date['id']);
                    }
                    $this->addDate($odate);
                    $has_changed = true;
                }
            }
        }

        //notify if something has changed
        if ( $has_changed ) {
            $this->onPropertyChanged('dates', $dates, $this->dates);
        }
    }
    /**
     * Add date
     *
     * @param EADDates $date Date
     *
     * @return EADFileFormat
     */
    public function addDate(EADDates $date)
    {
        $this->dates[] = $date;
        return $this;
    }

    /**
     * Remove date
     *
     * @param EADDates $date Date
     *
     * @return void
     */
    public function removeDate(EADDates $date)
    {
        $this->dates->removeElement($date);
    }

    /**
     * Get dates
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getDates()
    {
        return $this->dates;
    }

    /**
     * Parse daos objects from bag
     *
     * @param array $data Dao data
     *
     * @return void
     */
    protected function parseDaos($data, $pdfFlag)
    {
        $daos = clone $this->daos;
        $has_changed = false;

        //check for removal
        foreach ( $this->daos as $dao ) {
            $found = false;
            $href = $dao->getHref();
            foreach ( $data as $new_dao ) {
                if ( $href === $new_dao['attributes']['href'] ) {
                    $found = true;
                    break;
                }
            }
            if ( !$found ) {
                $this->removeDao($dao);
                $this->removed[] = $dao;
                $has_changed = true;
            }
        }

        //check for new
        foreach ( $data as $dao ) {
            $unique = true;

            foreach ( $this->daos as $i ) {
                if ( $i->getHref() == $dao['attributes']['href'] ) {
                    $unique = false;
                    break;
                }
            }

            if ( $unique === true ) {
                $id = null;
                if ( isset($dao['id']) ) {
                    $id = $dao['id'];
                    unset($dao['id']);
                }

                if ($pdfFlag == true && $dao['attributes']['href']) {
                    $daolink = $dao['attributes']['href'];
                    $flag = false;
                    if (file_exists(BACH_FILES_MISC . $daolink)
                    ) {
                        $flag = true;
                    } else if (strpos(BACH_FILES_MISC, 'http') === 0 // test if begin with http
                        && strpos($daolink, 'pdf') !== false
                    ) {
                        // test if remote file exist
                        $ch = curl_init(BACH_FILES_MISC .$daolink);
                        curl_setopt($ch, CURLOPT_NOBODY, true);
                        curl_exec($ch);
                        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

                        if ($code == 200) {
                            print_r(BACH_FILES_MISC .$daolink);
                            $flag = true;
                        }
                        curl_close($ch);
                    }
                    if ($flag) {
                        $parser = new \Smalot\PdfParser\Parser();
                        try {
                            $pdf = $parser->parseFile(BACH_FILES_MISC . $daolink);
                            if ($pdf->getDetails() != null) {
                                $content = $pdf->getText();
                                $search = array("\t", "\n", "\r", ".");
                                $content = str_replace($search, ' ', $content);
                                // Replace Multiple spaces with single space
                                $content = preg_replace('/ +/', ' ', $content);
                                // Trim the string of leading/trailing space
                                $content = trim($content);
                                $dao['attributes']['cMediaContent'] = $content;
                            }
                        } catch (\Exception $e) {
                            //FIXME Add a track in publication log file
                        }
                    }
                }

                $ndao = new EADDaos($this, $dao);
                if ( $id !== null ) {
                    $ndao->setId($id);
                }
                $this->addDao($ndao);
                $has_changed = true;
            }
        }

        //notify if something has changed
        if ( $has_changed ) {
            $this->onPropertyChanged('daos', $daos, $this->daos);
        }
    }

    /**
     * Add dao
     *
     * @param EADDaos $dao dao
     *
     * @return EADFileFormat
     */
    public function addDao(EADDaos $dao)
    {
        $this->daos[] = $dao;
        return $this;
    }

    /**
     * Remove dao
     *
     * @param EADDaos $dao Dao
     *
     * @return void
     */
    public function removeDao(EADDaos $dao)
    {
        $this->daos->removeElement($dao);
    }

    /**
     * Get daos
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getDaos()
    {
        return $this->daos;
    }

    /**
     * Parse parents titles objects from bag
     *
     * @param array $data Parents title data
     *
     * @return void
     */
    protected function parseParentsTitles($data)
    {
        $parents_titles = clone $this->parents_titles;
        $has_changed = false;

        //check for removal
        foreach ( $this->parents_titles as $ptitle ) {
            $found = false;
            $title = $ptitle->getTitle();
            foreach ( $data as $new_ptitle ) {
                if ( $title === $new_ptitle['value'] ) {
                    $found = true;
                    break;
                }
            }
            if ( !$found ) {
                $this->removeParentTitle($ptitle);
                $this->removed[] = $ptitle;
                $has_changed = true;
            }
        }

        //check for new
        foreach ( $data as $ptitle ) {
            $unique = true;

            foreach ( $this->parents_titles as $i ) {
                if ( $i->getTitle() == $ptitle['value'] ) {
                    $unique = false;
                    break;
                }
            }

            if ( $unique === true ) {
                $id = null;
                if ( isset($ptitle['id']) ) {
                    $id = $ptitle['id'];
                    unset($ptitle['id']);
                }
                $parent_title = new EADParentTitle($this, $ptitle['value']);
                if ( $id !== null ) {
                    $parent_title->setId($id);
                }
                $this->addParentTitle($parent_title);
                $has_changed = true;
            }
        }

        //notify if something has changed
        if ( $has_changed ) {
            $this->onPropertyChanged(
                'parents_titles',
                $parents_titles,
                $this->parents_titles
            );
        }
    }

    /**
     * Add parent title
     *
     * @param EADParentTitle $title Parent title
     *
     * @return EADFileFormat
     */
    public function addParentTitle(EADParentTitle $title)
    {
        $this->parents_titles[] = $title;
        return $this;
    }

    /**
     * Remove parent title
     *
     * @param EADParentTitle $title Title
     *
     * @return void
     */
    public function removeParentTitle(EADParentTitle $title)
    {
        $this->parents_titles->removeElement($title);
    }

    /**
     * Get parents titles
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getParentsTitles()
    {
        return $this->parents_titles;
    }

    /**
     * Get uniqid
     *
     * @return integer
     */
    public function getUniqid()
    {
        return $this->uniqid;
    }

    /**
     * Set fragment
     *
     * @param string $fragment Fragment
     *
     * @return EADFileFormat
     */
    public function setFragment($fragment)
    {
        $this->fragment = $fragment;
        return $this;
    }

    /**
     * Get fragment
     *
     * @return string
     */
    public function getFragment()
    {
        return $this->fragment;
    }

    /**
     * Set elt_order
     *
     * @param integer $eltOrder Order
     *
     * @return EADFileFormat
     */
    public function setEltOrder($eltOrder)
    {
        $this->elt_order = $eltOrder;
        return $this;
    }

    /**
     * Get elt_order
     *
     * @return integer
     */
    public function getEltOrder()
    {
        return $this->elt_order;
    }

    /**
     * Set fragmentid
     *
     * @param string $fragmentid Fragment id
     *
     * @return EADFileFormat
     */
    public function setFragmentid($fragmentid)
    {
        $this->fragmentid = $fragmentid;
        return $this;
    }

    /**
     * Get fragmentid
     *
     * @return string
     */
    public function getFragmentid()
    {
        return $this->fragmentid;
    }

    /**
     * Set eadheader
     *
     * @param EADHeader $eadheader EAD header
     *
     * @return EADFileFormat
     */
    public function setEadheader(EADHeader $eadheader)
    {
        $this->eadheader = $eadheader;
        return $this;
    }

    /**
     * Get eadheader
     *
     * @return EADHeader
     */
    public function getEadheader()
    {
        return $this->eadheader;
    }

    /**
     * Set archdesc
     *
     * @param EADFileFormat $archdesc Archdesc
     *
     * @return EADFileFormat
     */
    public function setArchdesc(EADFileFormat $archdesc)
    {
        $this->archdesc = $archdesc;
        return $this;
    }

    /**
     * Get archdesc
     *
     * @return EADFileFormat
     */
    public function getArchdesc()
    {
        return $this->archdesc;
    }

    /**
     * Get array representation
     *
     * @return array
     */
    public function toArray()
    {
        $vars = parent::toArray();
        /*$vars = get_object_vars($this);
        foreach ( $vars as &$var ) {
            if ( $var instanceof ArrayCollection ) {
                $subvars = array();
                foreach ( $var as $subvar ) {
                    $subvars[] = $subvar->toArray();
                }
                $var = $subvars;
            }

            if ( $var instanceof \DateTime ) {
                $var = $var->format('Y-m-d H:i:s');
            }
        }*/
        unset($vars['eadheader']);
        unset($vars['archdesc']);
        /*unset($vars['document']);
        unset($vars['removed']);
        unset($vars['has_changes']);
        unset($vars['check_changes']);*/
        return $vars;
    }
}
