<?php
/**
 * Bach comments
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
 * @category Search
 * @package  Bach
 * @author   Johan Cwiklinski <johan.cwiklinski@anaphore.eu>
 * @license  BSD 3-Clause http://opensource.org/licenses/BSD-3-Clause
 * @link     http://anaphore.eu
 */

namespace Bach\HomeBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Application\Sonata\UserBundle\Entity\User;

/**
 * Bach comments management
 *
 * @ORM\Entity
 * @ORM\Table(name="comments")
 * @ORM\HasLifecycleCallbacks()
 * @ORM\InheritanceType("SINGLE_TABLE")
 * @ORM\DiscriminatorColumn(name="related", type="integer")
 * @ORM\DiscriminatorMap({"0" = "ArchivesComment", "1" = "MatriculesComment", "2" = "ImagesComment"})
 *
 * @category Search
 * @package  Bach
 * @author   Johan Cwiklinski <johan.cwiklinski@anaphore.eu>
 * @license  BSD 3-Clause http://opensource.org/licenses/BSD-3-Clause
 * @link     http://anaphore.eu
 */
class Comment
{
    //related
    const REL_ARCHIVES = 0;
    const REL_MATRICULES = 1;
    const REL_IMAGES = 2;

    //priorities
    const COMMENT = 0;
    const IMPROVEMENT = 1;
    const BUG = 2;

    //states
    const MODERATED = 0;
    const PUBLISHED = 1;
    const REJECTED = 2;
    const PROCESSEED = 3;

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="creation_date", type="date", nullable=true)
     */
    protected $creation_date;

    /**
     * @ORM\ManyToOne(targetEntity="\Application\Sonata\UserBundle\Entity\User")
     * @ORM\JoinColumn(name="opened_by_id", referencedColumnName="id", nullable=true, onDelete="SET NULL")
     */
    protected $opened_by;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="close_date", type="date", nullable=true)
     */
    protected $close_date;

    /**
     * @ORM\ManyToOne(targetEntity="\Application\Sonata\UserBundle\Entity\User")
     * @ORM\JoinColumn(name="closed_by_id", referencedColumnName="id", onDelete="SET NULL")
     */
    protected $closed_by;

    /**
     * @var string
     *
     * @ORM\Column(name="subject", type="string", length=255)
     */
    protected $subject;

    /**
     * @var text
     *
     * @ORM\Column(name="message", type="text")
     */
    protected $message;

    /**
     * @var integer
     *
     * @ORM\Column(name="priority", type="integer")
     */
    protected $priority;

    /**
     * @var integer
     *
     * @ORM\Column(name="state", type="integer")
     */
    protected $state;

    /**
     * @var string
     *
     * @ORM\Column(name="docid", type="string", length=500)
     */
    protected $docid;

    /**
     * Main constructor
     */
    public function __construct()
    {
        $this->setDefaultRelated();
    }

    /**
     * Set default related field for current entity
     *
     * @return void
     */
    protected function setDefaultRelated()
    {
        $this->related = self::REL_ARCHIVES;
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
     * Set creation_date
     *
     * @param \DateTime $creationDate Date of creation
     *
     * @return Comment
     */
    public function setCreationDate($creationDate)
    {
        $this->creation_date = $creationDate;
        return $this;
    }

    /**
     * Get creation_date
     *
     * @return \DateTime
     */
    public function getCreationDate()
    {
        return $this->creation_date;
    }

    /**
     * Get creation_date
     *
     * @return \DateTime
     */
    public function getLocalizedCreationDate()
    {
        return $this->creation_date->format(_('Y-m-d'));
    }


    /**
     * Set close_date
     *
     * @param \DateTime $closeDate Date of close
     *
     * @return Comment
     */
    public function setCloseDate($closeDate)
    {
        $this->close_date = $closeDate;
        return $this;
    }

    /**
     * Get close_date
     *
     * @return \DateTime
     */
    public function getCloseDate()
    {
        return $this->close_date;
    }

    /**
     * Set subject
     *
     * @param string $subject Subject
     *
     * @return Comment
     */
    public function setSubject($subject)
    {
        $this->subject = $subject;
        return $this;
    }

    /**
     * Get subject
     *
     * @return string
     */
    public function getSubject()
    {
        return $this->subject;
    }

    /**
     * Set message
     *
     * @param string $message Comment message
     *
     * @return Comment
     */
    public function setMessage($message)
    {
        $this->message = $message;
        return $this;
    }

    /**
     * Get message
     *
     * @return string
     */
    public function getMessage()
    {
        return nl2br($this->message);
    }

    /**
     * Set priority
     *
     * @param integer $priority Comment priority
     *
     * @return Comment
     */
    public function setPriority($priority)
    {
        $this->priority = $priority;
        return $this;
    }

    /**
     * Get priority
     *
     * @return integer
     */
    public function getPriority()
    {
        return $this->priority;
    }

    /**
     * Set state
     *
     * @param integer $state Comment state
     *
     * @return Comment
     */
    public function setState($state)
    {
        $this->state = $state;
        return $this;
    }

    /**
     * Get state
     *
     * @return integer
     */
    public function getState()
    {
        return $this->state;
    }


    /**
     * Set opened_by
     *
     * @param User $openedBy User who opened the comment
     *
     * @return Comment
     */
    public function setOpenedBy(User $openedBy = null)
    {
        $this->opened_by = $openedBy;
        return $this;
    }

    /**
     * Get opened_by
     *
     * @return User
     */
    public function getOpenedBy()
    {
        return $this->opened_by;
    }

    /**
     * Set closed_by
     *
     * @param User $closedBy User who closed the comment
     *
     * @return Comment
     */
    public function setClosedBy(User $closedBy = null)
    {
        $this->closed_by = $closedBy;
        return $this;
    }

    /**
     * Get closed_by
     *
     * @return User
     */
    public function getClosedBy()
    {
        return $this->closed_by;
    }

    /**
     * Set docid
     *
     * @param string $docid Related document ID
     *
     * @return Comment
     */
    public function setDocId($docid=null)
    {
        $this->docid = $docid;
        return $this;
    }

    /**
     * Get docid
     *
     * @return string
     */
    public function getDocId()
    {
        return $this->docid;
    }

    /**
     * Set creation date
     *
     * @ORM\PrePersist
     *
     * @return Comment
     */
    public function setCreationDateValue()
    {
        $this->creation_date = new \DateTime();
        return $this;
    }

    /**
     * Set default state
     *
     * @ORM\PrePersist
     *
     * @return Comment
     */
    public function setStateValue()
    {
        $this->state = self::MODERATED;
        return $this;
    }

    /**
     * string representation
     *
     * @return string
     */
    public function __tostring()
    {
        if ( $this->getSubject() ) {
            $txt = $this->getSubject();
            if ( $this->getOpenedBy() ) {
                $txt .= ' (by ' . $this->getOpenedBy()  . ')';
            }
            return $txt;
        } else {
            return _('New comment');
        }
    }

    /**
     * Get a list of known priorities
     *
     * @return array
     */
    public static function getKnownPriorities()
    {
        return array(
            self::COMMENT       => _('Comment'),
            self::IMPROVEMENT   => _('Improvement'),
            self::BUG           => _('Bug')
        );
    }

    /**
     * Get a list of known states
     *
     * @return array
     */
    public static function getKnownStates()
    {
        return array(
            self::MODERATED  => _('Moderated'),
            self::PUBLISHED  => _('Published'),
            self::REJECTED   => _('Rejected'),
            self::PROCESSEED => _('Processed')
        );
    }

}
