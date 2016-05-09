<?php
/**
 * Bach abstract file format
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
use Doctrine\Common\NotifyPropertyChanged;
use Doctrine\Common\PropertyChangedListener;
use Bach\IndexationBundle\Entity\Document;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * Bach abstract file format
 *
 * @category Indexation
 * @package  Bach
 * @author   Johan Cwiklinski <johan.cwiklinski@anaphore.eu>
 * @license  BSD 3-Clause http://opensource.org/licenses/BSD-3-Clause
 * @link     http://anaphore.eu
 *
 * @ORM\MappedSuperclass
 * @ORM\ChangeTrackingPolicy("NOTIFY")
 */
abstract class FileFormat implements NotifyPropertyChanged
{
    use PublishTrait;

    private $_listeners = array();

    /**
     * @ORM\ManyToOne(targetEntity="Document")
     * @ORM\JoinColumn(name="doc_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $document;

    /**
     * @ORM\Column(type="datetime")
     */
    protected $created;

    /**
     * @ORM\Column(type="datetime")
     */
    protected $updated;

    /**
     * Array of removed linked entities
     */
    protected $removed;

    /**
      * The constructor
      *
      * @param array   $data    The input data
      * @param boolean $changes Take care of changes
      */
    public function __construct($data, $changes = true)
    {
        $this->created = new \DateTime('NOW', new \DateTimeZone('UTC'));
        $this->updated = new \DateTime('NOW', new \DateTimeZone('UTC'));
        $this->parseData($data, $changes);
    }

    /**
     * Adds a listener that wants to be notified about property changes.
     *
     * @param PropertyChangedListener $listener Listener
     *
     * @return void
     */
    public function addPropertyChangedListener(PropertyChangedListener $listener)
    {
        $this->_listeners[] = $listener;
    }

    /**
     * Proceed data parsing
     *
     * @param array   $data    Data to parse
     * @param boolean $changes Take care of changes
     *
     * @return void
     */
    protected function parseData($data, $changes = true)
    {
        $this->check_changes = $changes;
        foreach ($data as $key=>$datum) {
            if (property_exists($this, $key)) {
                $this->onPropertyChanged($key, $this->$key, $datum);
                $this->$key = $datum;
            }
        }
    }

    /**
     * Set doc_id
     *
     * @param Document $document Document
     *
     * @return FileFormat
     */
    public function setDocument(Document $document = null)
    {
        if ( $this->document === null ) {
            $this->onPropertyChanged('document', $this->document, $document);
        }
        $this->document = $document;
        return $this;
    }

    /**
     * Get document
     *
     * @return Document
     */
    public function getDocument()
    {
        return $this->document;
    }

    /**
     * Get creation date
     *
     * @return DateTime
     */
    public function getCreated()
    {
        return $this->created;
    }

    /**
     * Get modification date
     *
     * @return DateTime
     */
    public function getUpdated()
    {
        return $this->updated;
    }

    /**
     * Get removed associated entities
     *
     * @return ArrayCollection
     */
    public function getRemoved()
    {
        return $this->removed;
    }

    /**
     * Get array representation
     *
     * @return array
     */
    public function toArray()
    {
        $vars = get_object_vars($this);
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
        }
        unset($vars['_listeners']);
        unset($vars['removed']);
        unset($vars['has_changes']);
        unset($vars['check_changes']);
        unset($vars['document']);
        return $vars;
    }
}
