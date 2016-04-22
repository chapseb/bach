<?php
/**
 * Bach EAD header entity
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
use Doctrine\Common\NotifyPropertyChanged;
use Doctrine\Common\PropertyChangedListener;

/**
 * Bach EAD header entity
 *
 * PHP version 5
 *
 * @category Indexation
 * @package  Bach
 * @author   Johan Cwiklinski <johan.cwiklinski@anaphore.eu>
 * @license  BSD 3-Clause http://opensource.org/licenses/BSD-3-Clause
 * @link     http://anaphore.eu
 *
 * @ORM\Table(name="ead_header")
 * @ORM\Entity
 * @ORM\ChangeTrackingPolicy("NOTIFY")
 */
class EADHeader implements NotifyPropertyChanged
{
    use PublishTrait;

    private $_listeners = array();

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
    * @ORM\Column(type="string", nullable=true, length=100)
    */
    protected $headerId;

    /**
     * @ORM\Column(type="string", nullable=true, length=1000)
     */
    protected $headerTitle;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    protected $headerSubtitle;

    /**
    * @ORM\Column(type="string", nullable=true, length=500)
    */
    protected $headerAuthor;

    /**
    * @ORM\Column(type="string", nullable=true, length=100)
    */
    protected $headerDate;

    /**
    * @ORM\Column(type="string", nullable=true, length=100)
    */
    protected $headerPublisher;

    /**
    * @ORM\Column(type="text", nullable=true)
    */
    protected $headerAddress;

    /**
    * @ORM\Column(type="string", nullable=true, length=3)
    */
    protected $headerLanguage;

    /**
     * @ORM\Column(type="datetime")
     */
    protected $created;

    /**
     * @ORM\Column(type="datetime")
     */
    protected $updated;

    /**
      * The constructor
      *
      * @param array   $data    Data
      * @param boolean $changes Take care of changes
      */
    public function __construct($data, $changes = true)
    {
        $this->fragments = new ArrayCollection();
        $this->created = new \DateTime();
        $this->updated = new \DateTime();
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
     * @param array   $data    Data
     * @param boolean $changes Take care of changes
     *
     * @return void
     */
    protected function parseData($data, $changes = true)
    {
        $this->check_changes = $changes;
        foreach ($data as $key=>$value) {
            if (property_exists($this, $key)) {
                if ( $this->$key !== $value ) {
                    $this->onPropertyChanged($key, $this->$key, $value);
                    $this->$key = $value;
                }
            } else {
                throw new \RuntimeException(
                    __CLASS__ . ' - Key ' . $key . ' is not known!'
                );
            }
        }
    }

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set headerId
     *
     * @param string $headerId Header id
     *
     * @return EADHeader
     */
    public function setHeaderId($headerId)
    {
        $this->headerId = $headerId;

        return $this;
    }

    /**
     * Get headerId
     *
     * @return string
     */
    public function getHeaderId()
    {
        return $this->headerId;
    }

    /**
     * Set headerAuthor
     *
     * @param string $headerAuthor header author
     *
     * @return EADHeader
     */
    public function setHeaderAuthor($headerAuthor)
    {
        $this->headerAuthor = $headerAuthor;

        return $this;
    }

    /**
     * Get headerAuthor
     *
     * @return string
     */
    public function getHeaderAuthor()
    {
        return $this->headerAuthor;
    }

    /**
     * Set headerDate
     *
     * @param \DateTime $headerDate Header date
     *
     * @return EADHeader
     */
    public function setHeaderDate($headerDate)
    {
        $this->headerDate = $headerDate;
        return $this;
    }

    /**
     * Get headerDate
     *
     * @return \DateTime
     */
    public function getHeaderDate()
    {
        return $this->headerDate;
    }

    /**
     * Set headerPublisher
     *
     * @param string $headerPublisher Header publisher
     *
     * @return EADHeader
     */
    public function setHeaderPublisher($headerPublisher)
    {
        $this->headerPublisher = $headerPublisher;
        return $this;
    }

    /**
     * Get headerPublisher
     *
     * @return string
     */
    public function getHeaderPublisher()
    {
        return $this->headerPublisher;
    }

    /**
     * Set headerAddress
     *
     * @param string $headerAddress Header address
     *
     * @return EADHeader
     */
    public function setHeaderAddress($headerAddress)
    {
        $this->headerAddress = $headerAddress;
        return $this;
    }

    /**
     * Get headerAddress
     *
     * @return string
     */
    public function getHeaderAddress()
    {
        return $this->headerAddress;
    }

    /**
     * Get headerSubtitle
     *
     * @return string
     */
    public function getHeaderSubtitle()
    {
        return $this->headerSubtitle;
    }

    /**
     * Set headerSubtitle
     *
     * @param string $headerSubtitle Header subtitle
     *
     * @return EADHeader
     */
    public function setHeaderSubtitle($headerSubtitle)
    {
        $this->headerSubtitle = $headerSubtitle;
        return $this;
    }

    /**
     * Set headerLanguage
     *
     * @param string $headerLanguage Header language
     *
     * @return EADHeader
     */
    public function setHeaderLanguage($headerLanguage)
    {
        $this->headerLanguage = $headerLanguage;
        return $this;
    }

    /**
     * Get headerLanguage
     *
     * @return string
     */
    public function getHeaderLanguage()
    {
        return $this->headerLanguage;
    }

    /**
     * Set headerTitle
     *
     * @param string $headerTitle Title
     *
     * @return EADHeader
     */
    public function setHeaderTitle($headerTitle)
    {
        $this->headerTitle = $headerTitle;
        return $this;
    }

    /**
     * Get headerTitle
     *
     * @return string
     */
    public function getHeaderTitle()
    {
        return $this->headerTitle;
    }

    /**
     * Add fragment
     *
     * @param EADFileFormat $fragment EAD fragment
     *
     * @return EADHeader
     */
    public function addFragment(EADFileFormat $fragment)
    {
        $this->fragments[] = $fragment;
        return $this;
    }

    /**
     * Remove fragment
     *
     * @param EADFileFormat $fragment EAD fragment
     *
     * @return void
     */
    public function removeFragment(EADFileFormat $fragment)
    {
        $this->fragments->removeElement($fragment);
    }

    /**
     * Get fragments
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getFragments()
    {
        return $this->fragments;
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
     * Get array representation
     *
     * @return array
     */
    public function toArray()
    {
        $vars = get_object_vars($this);
        foreach ( $vars as &$var ) {
            if ( $var instanceof \DateTime ) {
                $var = $var->format('Y-m-d H:i:s');
            }
        }
        unset($vars['_listeners']);
        unset($vars['check_changes']);
        unset($vars['fragments']);
        unset($vars['has_changes']);
        return $vars;
    }
}
