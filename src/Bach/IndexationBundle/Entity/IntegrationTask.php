<?php
/**
 * Archfile integration task
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
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * Archfile integration task
 *
 * @category Indexation
 * @package  Bach
 * @author   Johan Cwiklinski <johan.cwiklinski@anaphore.eu>
 * @license  BSD 3-Clause http://opensource.org/licenses/BSD-3-Clause
 * @link     http://anaphore.eu
 *
 * @ORM\Entity
 * @ORM\Table(name="integration_task")
 * @UniqueEntity("document_id")
 */
class IntegrationTask
{
    const STATUS_NONE = 0;
    const STATUS_OK = 1;
    const STATUS_KO = 2;

    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $taskId;

    /**
     * @ORM\Column(type="string", length=200, nullable=true)
     */
    protected $preprocessor;

    /**
     * @ORM\Column(type="integer", length=1)
     */
    protected $status;

    /**
     * @ORM\OneToOne(targetEntity="Document")
     * @ORM\JoinColumn(name="document_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $document;

    /**
     * The constructor
     *
     * @param Document $document Stored document
     */
    public function __construct($document)
    {
        $this->document = $document;
        $this->status = self::STATUS_NONE;
    }

    /**
     * Get taskId
     *
     * @return integer
     */
    public function getTaskId()
    {
        return $this->taskId;
    }

    /**
     * Get filename
     *
     * @return string
     */
    public function getFilename()
    {
        return $this->document->getName();
    }

    /**
     * Get path
     *
     * @return string
     */
    public function getPath()
    {
        if (realpath($this->document->getAbsolutePath()) == null) {
            return $this->document->getAbsolutePath();
        } else {
            return realpath($this->document->getAbsolutePath());
        }
    }

    /**
     * Get format
     *
     * @return string
     */
    public function getFormat()
    {
        return $this->document->getExtension();
    }

    /**
    * Set preprocessor
    *
    * @param string $preprocessor Pre processor
    *
    * @return IntegrationTask
    */
    public function setPreprocessor($preprocessor)
    {
        $this->preprocessor = $preprocessor;
        return $this;
    }

    /**
     * Get preprocessor
     *
     * @return string
     */
    public function getPreprocessor()
    {
        return $this->preprocessor;
    }

    /**
     * Set status
     *
     * @param integer $status Stauts
     *
     * @return IntegrationTask
     */
    public function setStatus($status)
    {
        $this->status = $status;
        return $this;
    }

    /**
     * Get status
     *
     * @return integer
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set associated document
     *
     * @param Document $document Document
     *
     * @return IntegrationTask
     */
    public function setDocument($document)
    {
        $this->document = $document;
        return $this;
    }

    /**
     * Get associated document
     *
     * @return Document
     */
    public function getDocument()
    {
        return $this->document;
    }
}
