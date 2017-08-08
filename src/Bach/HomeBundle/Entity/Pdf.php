<?php
/**
 * PDF generation
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
 * @category Main
 * @package  Viewer
 * @author   Sebastien Chaptal <sebastien.chaptal@anaphore.eu>
 * @license  BSD 3-Clause http://opensource.org/licenses/BSD-3-Clause
 * @link     http://anaphore.eu
 */

namespace Bach\HomeBundle\Entity;

/**
 * PDF Generation
 *
 * @category Main
 * @package  Viewer
 * @author   Sebastien Chaptal <sebastien.chaptal@anaphore.eu>
 * @license  BSD 3-Clause http://opensource.org/licenses/BSD-3-Clause
 * @link     http://anaphore.eu
 */
class Pdf extends \TCPDF
{
    private $_header_height = 0;
    private $_footer_height = 0;
    private $_params = null;

    /**
     * Main constructor
     *
     * @param Array $params Params header and footer
     */
    public function __construct($params= null)
    {
        $orientation = 'P';

        parent::__construct($orientation, 'mm', 'A4', true, 'UTF-8');
        $this->_params = $params;
        $this->setCreator('Bach - ' . PDF_CREATOR);
        $this->setFont('helvetica', '', 8);
        $this->setTopMargin(20);
        $this->setTitle('document Bach');
    }

    /**
     * Default header
     *
     * @return void
     */
    public function Header()
    {
        $image = $this->_params['header']['image'];
        $content = $this->_params['header']['content'];
        $this->setFont('helvetica', '', 9);
        if (file_exists($image)) {
            $this->SetY(5);
            $this->writeHTML(
                '<table><tr><td with="50%"><img style="height:30px;" src="'. $image . '" /></td>' .
                '<td width="50%" style="text-align:right;">' .
                $content .
                '</td></tr></table>'
            );
        } else {
            $this->SetY(5);
            $this->writeHTML(
                '<table><tr><td with="20%">' .
                $this->getAliasNumPage(). '/'. $this->getAliasNbPages() . '</td>' .
                '<td width="80%" style="text-align:right;">' .
                date("Y-m-d H:i:s") .
                '</td></tr></table>'
            );
        }

        $this->_header_height = ceil($this->getY());
    }

    /**
     * Default footer
     *
     * @return void
     */
    public function Footer()
    {
        $image = $this->_params['footer']['image'];
        $content = $this->_params['footer']['content'];
        $this->setFont('helvetica', '', 8);
        if (file_exists($image)) {
            $this->SetY(280);
            $this->writeHTML(
                '<table><tr><td with="33%"><img style="height:45px;" src="'. $image . '" /></td>' .
                '<td width="33%" style="text-align:center"> ' .
                $this->getAliasNumPage(). '/'. $this->getAliasNbPages() . '</td>' .
                '<td width="33%" style="text-align:right;">' .
                date("Y-m-d H:i:s") .
                '</td></tr></table>'
            );
        } else {
            $this->SetY(280);
            $this->writeHTML(
                '<table><tr><td with="33%">'. $content . '</td>'.
                '<td with="33%" style="text-align:center">' .
                $this->getAliasNumPage(). '/'. $this->getAliasNbPages() . '</td>' .
                '<td width="33%" style="text-align:right;">' .
                date("Y-m-d H:i:s") .
                '</td></tr></table>'
            );

        }
        $this->_footer_height = ceil($this->getY());
    }

    /**
     * Retrieve PDF content for display in page
     *
     * @return string
     */
    public function getContent()
    {
        return $this->Output($this->_params['name'], 'S');
    }

    /**
     * Dowload PDF
     *
     * @return void
     */
    public function download()
    {
        $this->Output($this->_params['name'], 'D');
    }

}
