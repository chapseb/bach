<?php
/**
 * Bach files controller
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

namespace Bach\HomeBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpFoundation\Response;

/**
 * Bach files controller
 *
 * PHP version 5
 *
 * @category Search
 * @package  Bach
 * @author   Johan Cwiklinski <johan.cwiklinski@anaphore.eu>
 * @license  BSD 3-Clause http://opensource.org/licenses/BSD-3-Clause
 * @link     http://anaphore.eu
 */
class FilesController extends Controller
{
    /**
     * Get a file
     *
     * @param string $type File type
     * @param string $name File name
     *
     * @return void
     */
    public function getFileAction($type, $name)
    {
        $path = null;
        switch ( $type ) {
        case 'video':
            if ( !defined('BACH_FILES_VIDEOS') ) {
                throw new \RuntimeException(
                    _('Videos path is not defined!')
                );
            } else {
                $path = BACH_FILES_VIDEOS;
            }
            break;
        case 'music':
            if ( !defined('BACH_FILES_MUSICS') ) {
                throw new \RuntimeException(
                    _('Musics path is not defined!')
                );
            } else {
                $path = BACH_FILES_MUSICS;
            }
            break;
        case 'misc':
            if ( !defined('BACH_FILES_MISC') ) {
                throw new \RuntimeException(
                    _('Misc files path is not defined!')
                );
            } else {
                $path = BACH_FILES_MISC;
            }
            break;
        }
        $path .= '/' . $name;

        if ( !file_exists($path) ) {
            $msg_file = $name;
            if ( $this->container->get('kernel')->getEnvironment() === 'DEBUG' ) {
                $msg_file = $path;
            }
            throw new NotFoundHttpException(
                str_replace(
                    '%file',
                    $msg_file,
                    _('File %file does not exists.')
                )
            );
        }

        $file = fopen($path, 'rb');
        $out = fopen('php://output', 'wb');

        $mime = mime_content_type($path);
        header('Cache-Control: public');
        header('Content-type: ' . $mime);
        header('Content-Length:' . filesize($path));
        stream_copy_to_stream($file, $out);

        fclose($out);
        fclose($file);

        return new Response();
    }

    /**
     * Get a cover
     *
     * @param string $name File name
     *
     * @return void
     */
    public function getCoverAction($name)
    {
        $path = $this->container->getParameter('covers_dir');
        $path .= '/' . $name;

        if ( !file_exists($path) ) {
            throw new \RuntimeException(
                str_replace(
                    '%file',
                    $path,
                    _('File %file does not exists.')
                )
            );
        }

        $mime = mime_content_type($path);
        header('Cache-Control: public');
        header('Content-type: ' . $mime);

        $width = 214;
        $height = 214;

        list($owidth, $oheight) = getimagesize($path);
        $ratio_orig = $owidth/$oheight;
        if ($width/$height > $ratio_orig) {
            $width = $height*$ratio_orig;
        } else {
            $height = $width/$ratio_orig;
        }

        $image_p = imagecreatetruecolor($width, $height);
        $image = imagecreatefromjpeg($path);
        imagecopyresampled(
            $image_p,
            $image,
            0,
            0,
            0,
            0,
            $width,
            $height,
            $owidth,
            $oheight
        );

        imagejpeg($image_p, null, 100);

        return new Response();
    }

    /**
     * Retrieve HTML contents linked file
     *
     * @param string $name File name
     *
     * @return void
     */
    public function getHtmlIntroFileAction($name)
    {
        $path = $this->container->getParameter('html_intros_path');
        $path .= '/' . $name;

        $file = fopen($path, 'rb');
        $out = fopen('php://output', 'wb');

        $mime = mime_content_type($path);
        header('Cache-Control: public');
        header('Content-type: ' . $mime);
        header('Content-Length:' . filesize($path));
        stream_copy_to_stream($file, $out);

        fclose($out);
        fclose($file);

    }

    /**
     * Display the ChangeLog file
     *
     * @return void
     */
    public function displayChangelogAction()
    {
        $path = $this->get('kernel')->getRootDir();
        $path .= '/../ChangeLog';

        $file = fopen($path, 'rb');
        $out = fopen('php://output', 'wb');

        $mime = mime_content_type($path);
        header('Cache-Control: public');
        header('Content-type: ' . $mime);
        header('Content-Length:' . filesize($path));
        stream_copy_to_stream($file, $out);

        fclose($out);
        fclose($file);
        return new Response();

    }
}
