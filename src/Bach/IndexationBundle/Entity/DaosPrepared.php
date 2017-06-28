<?php
/**
 * Daos prepared images
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

namespace Bach\IndexationBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\File;

/**
 * Daos prepared entity
 *
 * @category Indexation
 * @package  Bach
 * @author   Sebastien Chaptal <sebastien.chaptal@anaphore.eu>
 * @license  BSD 3-Clause http://opensource.org/licenses/BSD-3-Clause
 * @link     http://anaphore.eu
 *
 * @ORM\Table(name="daos_prepared")
 * @ORM\Entity
 */
class DaosPrepared
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(name="href", type="string", length=1000)
     */
    protected $href;

    /**
     * @var string
     *
     * @ORM\Column(name="end_dao", type="string", length=1000)
     */
    protected $end_dao;

    /**
     * @var boolean
     *
     * @ORM\Column(name="action", type="boolean")
     */
    protected $action;

    /**
     * @var string
     *
     * @ORM\Column(name="last_file", type="string", length=1000)
     */
    protected $last_file = '';

    /**
     * Constructor
     *
     * @param string $href    Href image
     * @param string $end_dao End dao
     */
    public function __construct($href, $end_dao, $action, $lastFile = '')
    {
        $this->href = $href;
        $this->end_dao = $end_dao;
        $this->action = $action;
        $this->last_file = $lastFile;
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
     * Set id
     *
     * @param int $id Id
     *
     * @return DaosPrepared
     */
    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    /**
     * Set href
     *
     * @param string $href Hyperlink
     *
     * @return DaosPrepared
     */
    public function setHref($href)
    {
        $this->href = $href;
        return $this;
    }

    /**
     * Get end dao
     *
     * @return string
     */
    public function getEndDao()
    {
        return $this->end_dao;
    }

    /**
     * Set End dao
     *
     * @param string $end_dao end dao
     *
     * @return DaosPrepared
     */
    public function setEndDao($end_dao)
    {
        $this->end_dao = $end_dao;
        return $this;
    }

    /**
     * Get href
     *
     * @return string
     */
    public function getHref()
    {
        return $this->href;
    }

    /**
     * Set action
     *
     * @param boolean $action Action
     *
     * @return BachToken
     */
    public function setAction($action)
    {
        $this->action = $action;
        return $this;
    }

    /**
     * Get action
     *
     * @return boolean
     */
    public function getAction()
    {
        return $this->action;
    }

    /**
     * Get last file
     *
     * @return string
     */
    public function getLastFile()
    {
        return $this->last_file;
    }

    /**
     * Set last file
     *
     * @param string $last_file last file
     *
     * @return DaosPrepared
     */
    public function setLastFile($last_file)
    {
        $this->last_file = $last_file;
        return $this;
    }

    /**
     * Get array representation
     *
     * @return array
     */
    public function toArray()
    {
        $vars = get_object_vars($this);
        return $vars;
    }
}
