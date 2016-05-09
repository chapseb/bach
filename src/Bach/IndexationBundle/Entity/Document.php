<?php

/**
 * Document
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
 * @author   Anaphore PI Team <uknown@unknown.com>
 * @author   Johan Cwiklinski <johan.cwiklinski@anaphore.eu>
 * @license  BSD 3-Clause http://opensource.org/licenses/BSD-3-Clause
 * @link     http://anaphore.eu
 */

namespace Bach\IndexationBundle\Entity;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Document
 *
 * @category Indexation
 * @package  Bach
 * @author   Anaphore PI Team <uknown@unknown.com>
 * @author   Johan Cwiklinski <johan.cwiklinski@anaphore.eu>
 * @license  BSD 3-Clause http://opensource.org/licenses/BSD-3-Clause
 * @link     http://anaphore.eu

 * @ORM\Entity(repositoryClass="DocumentRepository")
 * @ORM\Table(name="documents")
 * @ORM\HasLifecycleCallbacks
 */
class Document
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\Column(type="string", length=255, unique=true)
     */
    protected $docid;

    /**
     * @ORM\Column(type="string", length=255)
     */
    protected $name;

    /**
     * @ORM\Column(type="string", length=255)
     */
    protected $path;

    /**
     * @Assert\File(
     *    maxSize = "10M"
     * )
     */
    protected $file;

    /**
     * @ORM\Column(type="string", length=255)
     */
    protected $extension;

    /**
     * @ORM\Column(type="string", length=255)
     */
    protected $corename;

    /**
     * @ORM\Column(type="boolean")
     */
    protected $uploaded = false;

    /**
     * @ORM\Column(type="datetime")
     */
    protected $created;

    /**
     * @ORM\Column(type="datetime")
     */
    protected $updated;

    private $_store_dir;
    private $_upload_dir;
    private $_upload_done;

    /**
     * Constructor
     *
     * @param boolean $uploaded Is document uploaded from web interface?
     */
    public function __construct($uploaded = false)
    {
        $this->uploaded = $uploaded;
        $this->created = new \DateTime();
        $this->updated = new \DateTime();
    }

    /**
     * Get absolute path to document
     *
     * @return string
     */
    public function getAbsolutePath()
    {
        $path = null;
        if ( $this->uploaded && $this->path !== null ) {
            $path = $this->_upload_dir . '/' . $this->path;
        } else if ( !$this->uploaded && $this->path !== null ) {
            $path = $this->_store_dir . '/' . $this->path;
        }
        return $path;
    }

    /**
     * Set upload directory. Also creates subdirectory if missing.
     *
     * @param string $dir Application upload directory
     *
     * @return Document
     */
    public function setUploadDir($dir)
    {
        $this->_upload_dir = $dir . '/published_docs';
        if ( !file_exists($this->_upload_dir) ) {
            $res = mkdir($this->_upload_dir);
            if ( $res !== true ) {
                throw new \RuntimeException(
                    str_replace(
                        '%dir',
                        $this->_upload_dir,
                        _('Cannot create upload directory %dir')
                    )
                );
            }
        }
        return $this;
    }

    /**
     * Get upload directory
     *
     * @return string
     */
    public function getUploadDir()
    {
        return str_replace(
            '/published_docs',
            '',
            $this->_upload_dir
        );
    }

    /**
     * Prepare upload, set path and name
     *
     * @ORM\PrePersist()
     * @ORM\PreUpdate()
     *
     * @return void
     */
    public function preUpload()
    {
        if (null !== $this->file) {
            if ( $this->uploaded ) {
                $this->path = $this->file->getClientOriginalName();
                $this->name = $this->file->getClientOriginalName();
            } else {
                $this->path = str_replace(
                    $this->_store_dir,
                    '',
                    $this->file->getPathName()
                );
                $this->name = $this->file->getFileName();
            }
        } elseif ( $this->_upload_done !== true ) {
            throw new \RuntimeException('No file specified. Cannot continue.');
        }
    }

    /**
     * Upload file
     *
     * @ORM\PostPersist()
     * @ORM\PostUpdate()
     *
     * @return void
     */
    public function upload()
    {
        if (null === $this->file || $this->_upload_done ) {
            return;
        }

        if ( $this->uploaded ) {
            $this->file->move($this->_upload_dir, $this->path);
            $this->_upload_done = true;
        }
    }

    /**
     * Remove uploaded file
     *
     * @ORM\PostRemove()
     *
     * @return void
     */
    public function removeUpload()
    {
        if ( $this->uploaded && $file = $this->getAbsolutePath()) {
            unlink($file);
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
     * Set name
     *
     * @param string $name Name
     *
     * @return Document
     */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set path
     *
     * @param string $path Path
     *
     * @return Document
     */
    public function setPath($path)
    {
        $this->path = $path;
        return $this;
    }

    /**
     * Get path
     *
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * Get file
     *
     * @return SplFile
     */
    public function getFile()
    {
        return $this->file;
    }

    /**
     * Set file
     *
     * @param SplFile $file File
     *
     * @return Document
     */
    public function setFile($file)
    {
        $this->file = $file;
        return $this;
    }

    /**
     * Get document extension
     *
     * @return string
     */
    public function getExtension()
    {
        return $this->extension;
    }

    /**
     * Set extension
     *
     * @param string $extension Document extension
     *
     * @return Document
     */
    public function setExtension($extension)
    {
        $this->extension = $extension;
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
     * Set docid
     *
     * @param string $docid document id
     *
     * @return Document
     */
    public function setDocId($docid)
    {
        $this->docid = $docid;
        return $this;
    }

    /**
     * Try to generate unique document id
     *
     * @ORM\PrePersist
     *
     * @return Document
     */
    public function generateDocId()
    {
        switch ( $this->extension ) {
        case 'ead':
            $xml = simplexml_load_file($this->file->getPathName());
            $this->docid = (string)$xml->eadheader->eadid;
            break;
        case 'matricules':
            $xml = simplexml_load_file($this->file->getPathName());
            $this->docid = (string)$xml->id;
            break;
        default:
            throw new \RuntimeException('Document ID is mandatory!');
            break;
        }

        return $this;
    }

    /**
     * Get core name
     *
     * @return string
     */
    public function getCorename()
    {
        return $this->corename;
    }

    /**
     * Set core name
     *
     * @param string $corename Core name
     *
     * @return Document
     */
    public function setCorename($corename)
    {
        $this->corename = $corename;
        return $this;
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
     * Set modification date
     *
     * @param DateTime $updated Modification date
     *
     * @return Document
     */
    public function setUpdated(\DateTime $updated)
    {
        $this->updated = $updated;
        return $this;
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
     * Set storage directory for current document type
     *
     * @param string $dir Directory
     *
     * @return Document
     */
    public function setStoreDir($dir)
    {
        $this->_store_dir = $dir;
        return $this;
    }

    /**
     * Get storage directory
     *
     * @return string
     */
    public function getStoreDir()
    {
        return $this->_store_dir;
    }

    /**
     * Has document been uploaded?
     *
     * @return boolean
     */
    public function isUploaded()
    {
        return $this->uploaded;
    }

    /**
     * Set document as (not) uploaded
     *
     * @param boolean $uploaded Uploaded or not
     *
     * @return void
     */
    public function setUploaded($uploaded)
    {
        $this->uploaded = $uploaded;
    }

    /**
     * Get array representation
     *
     * @return array
     */
    public function toArray()
    {
        $this->preUpload();
        $vars = get_object_vars($this);
        foreach ( $vars as &$var ) {
            if ( $var instanceof \DateTime ) {
                $var = $var->format('Y-m-d H:i:s');
            }
        }
        unset($vars['file']);
        unset($vars['_store_dir']);
        unset($vars['_upload_dir']);
        unset($vars['_upload_done']);
        return $vars;
    }
}
