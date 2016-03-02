<?php
/**
 * Search view parameters for matricules
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
 * @category Parameters
 * @package  Bach
 * @author   Johan Cwiklinski <johan.cwiklinski@anaphore.eu>
 * @license  BSD 3-Clause http://opensource.org/licenses/BSD-3-Clause
 * @link     http://anaphore.eu
 */

namespace Bach\HomeBundle\Entity;

/**
 * Search view parameters for matricules
 *
 * @category Parameters
 * @package  Bach
 * @author   Johan Cwiklinski <johan.cwiklinski@anaphore.eu>
 * @license  BSD 3-Clause http://opensource.org/licenses/BSD-3-Clause
 * @link     http://anaphore.eu
 */
class MatriculesViewParams extends ViewParams
{
    const ORDER_MATRICULE = 10;
    const ORDER_NAME = 11;
    const ORDER_SURNAME = 12;
    const ORDER_BIRTHYEAR = 13;
    const ORDER_BIRTHPLACE = 14;
    const ORDER_CLASS = 15;
    const ORDER_RECORDPLACE = 16;
    const ORDER_COTE = 17;
    const ORDER_RECORDYEAR = 18;
    const ORDER_RESIDENCEPLACE = 19;

    /**
     * Set order
     *
     * @param int $order New order
     *
     * @return void
     */
    public function setOrder($order)
    {
        if ($order === self::ORDER_RELEVANCE
            || $order === self::ORDER_MATRICULE
            || $order === self::ORDER_NAME
            || $order === self::ORDER_SURNAME
            || $order === self::ORDER_BIRTHYEAR
            || $order === self::ORDER_BIRTHPLACE
            || $order === self::ORDER_CLASS
            || $order === self::ORDER_RECORDPLACE
            || $order === self::ORDER_COTE
            || $order === self::ORDER_RECORDYEAR
            || $order === self::ORDER_RESIDENCEPLACE
        ) {
            $this->order = $order;
        } else {
            throw new \RuntimeException(
                str_replace(
                    '%s',
                    $order,
                    _('Order %s is not known!')
                )
            );
        }
    }
}
