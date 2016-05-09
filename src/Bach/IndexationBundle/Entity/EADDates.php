<?php
/**
 * EAD Dates entity
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
use Bach\IndexationBundle\Entity\EADFileFormat;


/**
 * EAD Dates entity
 *
 * @category Indexation
 * @package  Bach
 * @author   Anaphore PI Team <uknown@unknown.com>
 * @author   Johan Cwiklinski <johan.cwiklinski@anaphore.eu>
 * @license  BSD 3-Clause http://opensource.org/licenses/BSD-3-Clause
 * @link     http://anaphore.eu
 *
 * @ORM\Table(name="ead_dates")
 * @ORM\Entity
 */
class EADDates
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
     * @ORM\Column(name="date", type="string", length=255)
     */
    protected $date;

    /**
     * @var string
     *
     * @ORM\Column(name="normal", type="string", length=255, nullable=true)
     */
    protected $normal;

    /**
     * @var string
     *
     * @ORM\Column(name="label", type="string", length=255, nullable=true)
     */
    protected $label;

    /**
     * @var string
     *
     * @ORM\Column(name="calendar", type="string", length=255, nullable=true)
     */
    protected $calendar;

    /**
     * @var string
     *
     * @ORM\Column(name="type", type="string", length=255, nullable=true)
     */
    protected $type;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="begin", type="date", nullable=true)
     */
    protected $begin;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="dend", type="date", nullable=true)
     */
    protected $end;

    /**
     * @ORM\ManyToOne(targetEntity="EADFileFormat", inversedBy="dates")
     * @ORM\JoinColumn(name="eadfile_id", referencedColumnName="uniqid", onDelete="CASCADE")
     */
    protected $eadfile;

    private $_is_valid = true;

    /**
      * The constructor
      *
      * @param EADFileFormat $ead  EAD document referenced
      * @param array         $data The input data
      */
    public function __construct($ead, $data)
    {
        $this->eadfile = $ead;
        $this->date = $data['value'];

        //set begin and end dates
        $this->_parseDates($data);

        foreach ( $data['attributes'] as $attr=>$value) {
            switch ( $attr ){
            case 'label':
            case 'calendar':
            case 'normal':
            case 'type':
                $this->$attr = $value;
                break;
            }
        }
    }

    /**
     * Parse date
     *
     * @param array $data Data
     *
     * @return void
     */
    private function _parseDates($data)
    {
        $date = $data['value'];
        $bdate = null;
        $edate = null;

        if ( isset($data['attributes']['normal']) ) {
            $extrems = explode('/', $data['attributes']['normal']);
            $bdate = $extrems[0];
            if ( isset($extrems[1]) ) {
                $edate = $extrems[1];
            } else {
                $edate = $bdate;
            }

            if ( $edate === '0' || $bdate === '0' ) {
                //not a valid date
                $this->_is_valid = false;
                return;
            }
        } else {
            if ( $date === '0' || $date === 'Sans date' || $date === 's.d' ) {
                //not a valid date
                $this->_is_valid = false;
                return;
            }

            //here the date is unique, and probably not well formed
            $bdate = $date;
            $edate = $date;
        }

        $now = new \DateTime();

        //regexp to check if date is Y-M-D, Y-M, Y (or the same without dashes)
        $reg = '/^(\d{3,4})-?(\d{2})?-?(\d{2})?$/';

        //try to set begin date
        try  {
            try {
                $this->begin = new \DateTime($bdate);
            } catch (\Exception $e) {
                //not well formatted date... Don't care, try to use the regexp.
            }
            if ( preg_match($reg, $bdate, $matches) ) {
                //DateTime initialized with year only will give current year...
                if ( count($matches) == 2 ) {
                    $this->begin = \DateTime::createFromFormat('Y', $bdate);
                }
                if ( $this->begin !== null ) {
                    if ( count($matches) <= 3 ) {
                        //day is not provided. set to 1st.
                        $this->begin->modify('first day of this month');
                    }
                    if ( count($matches) == 2 ) {
                        //month is not provided. set to 1st.
                        $this->begin->modify('january');
                    }
                }
            }
            //if date is in the future, we remove it
            if ( $this->begin > $now ) {
                $this->begin = null;
            }
        } catch ( \Exception $e ) {
            $this->_is_valid = false;
        }

        //try to set end date
        try {
            try {
                $this->end = new \DateTime($edate);
            } catch (\Exception $e) {
                //not well formatted date... Don't care, try to use the regexp.
            }

            if ( preg_match($reg, $edate, $matches) ) {
                if ( count($matches) == 2 ) {
                    $this->end = \DateTime::createFromFormat('Y', $edate);
                }
                if ( $this->end !== null ) {
                    if ( count($matches) <= 3 ) {
                        //day is not provided. set to last.
                        $this->end->modify('last day of december');
                    }
                    if ( count($matches) == 2 ) {
                        //month is not provided. set to 1st.
                        $this->end->modify('december');
                    }
                }
            }
            //if date is in the future, we remove it
            if ( $this->end > $now ) {
                $this->end = null;
            }
        } catch ( \Exception $e ) {
            $this->_is_valid = false;
        }

        if ( $this->begin === null || $this->end === null ) {
            $this->_is_valid = false;
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
     * @return EADDates
     */
    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    /**
     * Set date
     *
     * @param string $date Date (textual)
     *
     * @return EADDates
     */
    public function setDate($date)
    {
        $this->date = $date;
        return $this;
    }

    /**
     * Get date
     *
     * @return string
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     * Set normal
     *
     * @param string $normal Normalized form
     *
     * @return EADDates
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
     * Set label
     *
     * @param string $label label
     *
     * @return EADDates
     */
    public function setLabel($label)
    {
        $this->label = $label;
        return $this;
    }

    /**
     * Get label
     *
     * @return string
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * Set calendar
     *
     * @param string $calendar calendar
     *
     * @return EADDates
     */
    public function setCalendar($calendar)
    {
        $this->calendar = $calendar;
        return $this;
    }

    /**
     * Get calendar
     *
     * @return string
     */
    public function getCalendar()
    {
        return $this->calendar;
    }

    /**
     * Set type
     *
     * @param string $type type
     *
     * @return EADDates
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
     * Set begin
     *
     * @param \DateTime $begin Begin date
     *
     * @return EADDates
     */
    public function setBegin($begin)
    {
        $this->begin = $begin;
        return $this;
    }

    /**
     * Get begin
     *
     * @return \DateTime
     */
    public function getBegin()
    {
        return $this->begin;
    }

    /**
     * Set end
     *
     * @param \DateTime $end End date
     *
     * @return EADDates
     */
    public function setEnd($end)
    {
        $this->end = $end;
        return $this;
    }

    /**
     * Get end
     *
     * @return \DateTime
     */
    public function getEnd()
    {
        return $this->end;
    }

    /**
     * Set eadfile
     *
     * @param EADFileFormat $eadfile EAD File
     *
     * @return EADDates
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
     * Is date valid?
     *
     * @return boolean
     */
    public function isValid()
    {
        return $this->_is_valid;
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

        $vars['dend'] = $vars['end'];
        unset($vars['end']);

        unset($vars['eadfile']);
        unset($vars['_is_valid']);
        return $vars;
    }
}
