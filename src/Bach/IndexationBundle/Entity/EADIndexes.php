<?php
/**
 * EAD indexes entity
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
use Bach\IndexationBundle\Entity\Geoloc;
/**
 * EAD indexes entity
 *
 * @category Indexation
 * @package  Bach
 * @author   Anaphore PI Team <uknown@unknown.com>
 * @author   Johan Cwiklinski <johan.cwiklinski@anaphore.eu>
 * @license  BSD 3-Clause http://opensource.org/licenses/BSD-3-Clause
 * @link     http://anaphore.eu
 *
 * @ORM\Table(name="ead_indexes")
 * @ORM\Entity
 */
class EADIndexes
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
     * @ORM\Column(name="altrender", type="string", length=255, nullable=true)
     */
    protected $altrender;

    /**
     * @var string
     *
     * @ORM\Column(name="audience", type="string", length=50, nullable=true)
     */
    protected $audience;

    /**
     * @var string
     *
     * @ORM\Column(name="authfilenumber", type="string", length=255, nullable=true)
     */
    protected $authfilenumber;

    /**
     * @var string
     *
     * @ORM\Column(name="encodinganalog", type="string", length=255, nullable=true)
     */
    protected $encodinganalog;

    /**
     * @var string
     * Maps id attribute, since id is here the default auto increment field
     *
     * @ORM\Column(name="eadid", type="string", length=100, nullable=true)
     */
    protected $eadid;

    /**
     * @var string
     *
     * @ORM\Column(name="type", type="string", length=20, nullable=true)
     */
    protected $type;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=1000)
     */
    protected $name;

    /**
     * @var string
     *
     * @ORM\Column(name="normal", type="string", length=255, nullable=true)
     */
    protected $normal;

    /**
     * @var string
     *
     * @ORM\Column(name="role", type="string", length=100, nullable=true)
     */
    protected $role;

    /**
     * @var string
     *
     * @ORM\Column(name="rules", type="string", length=100, nullable=true)
     */
    protected $rules;

    /**
     * @var string
     *
     * @ORM\Column(name="source", type="string", length=255, nullable=true)
     */
    protected $source;

    /**
     * @ORM\ManyToOne(targetEntity="EADFileFormat", inversedBy="indexes")
     * @ORM\JoinColumn(name="eadfile_id", referencedColumnName="uniqid", onDelete="CASCADE")
     */
    protected $eadfile;

    /**
      * The constructor
      *
      * @param EADFileFormat $ead  EAD document referenced
      * @param string        $type Index type (persname, geogname, etc)
      * @param array         $data The input data
      */
    public function __construct($ead, $type, $data)
    {
        $this->eadfile = $ead;
        $this->name = $data['value'];
        $this->type = $type;
        if ( $type == 'cGeogname' ) {
            $ent = new Geoloc();
            $ent->setName(explode(" ", $data['value'])[0]);
            $ent->setIndexedName($data['value']);
        }
        foreach ( $data['attributes'] as $attr=>$value) {
            switch ( $attr ){
            case 'role':
            case 'source':
            case 'normal':
                $this->$attr = $value;
                break;
            case 'latitude' :
                if ($type == 'cGeogname') {
                    $ent->setLat($value);
                    $lat = $ent->getLat();
                }
                break;
            case 'longitude' :
                if ($type == 'cGeogname') {
                    $ent->setLon($value);
                    $lon = $ent->getLon();
                }
                break;
            default:
                //attribute is not mapped, no action
            }
        }
        if ( $type == 'cGeogname' ) {
            if ( !empty( $lat ) && !empty( $lon ) ) {
                $ent->setFound(true);
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
     * Set id
     *
     * @param int $id Id
     *
     * @return EADIndexes
     */
    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    /**
     * Set type
     *
     * @param string $type Type
     *
     * @return EADIndexes
     */
    public function setType($type)
    {
        $this->type = $type;
        return $this;
    }

    /**
     * Get type
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set name
     *
     * @param string $name Name
     *
     * @return EADIndexes
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
     * Set normal
     *
     * @param string $normal Normalized representation
     *
     * @return EADIndexes
     */
    public function setNormal($normal)
    {
        $this->normal = $normal;
        return $this;
    }

    /**
     * Get normal
     *
     * @return string
     */
    public function getNormal()
    {
        return $this->normal;
    }

    /**
     * Set role
     *
     * @param string $role Role
     *
     * @return EADIndexes
     */
    public function setRole($role)
    {
        $this->role = $role;
        return $this;
    }

    /**
     * Get role
     *
     * @return string
     */
    public function getRole()
    {
        return $this->role;
    }

    /**
     * Set source
     *
     * @param string $source Source
     *
     * @return EADIndexes
     */
    public function setSource($source)
    {
        $this->source = $source;
        return $this;
    }

    /**
     * Get source
     *
     * @return string
     */
    public function getSource()
    {
        return $this->source;
    }

    /**
     * Set eadfile
     *
     * @param EADFileFormat $eadfile EAD file reference
     *
     * @return EADIndexes
     */
    public function setEadfile(EADFileFormat $eadfile = null)
    {
        $this->eadfile = $eadfile;
        return $this;
    }

    /**
     * Get eadfile
     *
     * @return EADFileFormat
     */
    public function getEadfile()
    {
        return $this->eadfile;
    }

    /**
     * Set altrender
     *
     * @param string $altrender Alternate render
     *
     * @return EADIndexes
     */
    public function setAltrender($altrender)
    {
        $this->altrender = $altrender;
        return $this;
    }

    /**
     * Get altrender
     *
     * @return string 
     */
    public function getAltrender()
    {
        return $this->altrender;
    }

    /**
     * Set audience
     *
     * @param string $audience Audience
     *
     * @return EADIndexes
     */
    public function setAudience($audience)
    {
        $this->audience = $audience;
        return $this;
    }

    /**
     * Get audience
     *
     * @return string
     */
    public function getAudience()
    {
        return $this->audience;
    }

    /**
     * Set authfilenumber
     *
     * @param string $authfilenumber Authfilenumber
     *
     * @return EADIndexes
     */
    public function setAuthfilenumber($authfilenumber)
    {
        $this->authfilenumber = $authfilenumber;
        return $this;
    }

    /**
     * Get authfilenumber
     *
     * @return string
     */
    public function getAuthfilenumber()
    {
        return $this->authfilenumber;
    }

    /**
     * Set encodinganalog
     *
     * @param string $encodinganalog Encodinganalog
     *
     * @return EADIndexes
     */
    public function setEncodinganalog($encodinganalog)
    {
        $this->encodinganalog = $encodinganalog;
        return $this;
    }

    /**
     * Get encodinganalog
     *
     * @return string
     */
    public function getEncodinganalog()
    {
        return $this->encodinganalog;
    }

    /**
     * Set eadid
     *
     * @param string $eadid EAD id
     *
     * @return EADIndexes
     */
    public function setEadid($eadid)
    {
        $this->eadid = $eadid;
        return $this;
    }

    /**
     * Get eadid
     *
     * @return string
     */
    public function getEadid()
    {
        return $this->eadid;
    }

    /**
     * Set rules
     *
     * @param string $rules Rules
     *
     * @return EADIndexes
     */
    public function setRules($rules)
    {
        $this->rules = $rules;
        return $this;
    }

    /**
     * Get rules
     *
     * @return string
     */
    public function getRules()
    {
        return $this->rules;
    }

    /**
     * Get array representation
     *
     * @return array
     */
    public function toArray()
    {
        $vars = get_object_vars($this);
        unset($vars['eadfile']);
        return $vars;
    }

}
