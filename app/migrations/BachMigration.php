<?php
/**
 * Bach Migration abstract class
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
 * @category Migrations
 * @package  Bach
 * @author   Johan Cwiklinski <johan.cwiklinski@anaphore.eu>
 * @license  BSD 3-Clause http://opensource.org/licenses/BSD-3-Clause
 * @link     http://anaphore.eu
 */

namespace Bach\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Bach Migration abstract class
 *
 * @category Migrations
 * @package  Bach
 * @author   Johan Cwiklinski <johan.cwiklinski@anaphore.eu>
 * @license  BSD 3-Clause http://opensource.org/licenses/BSD-3-Clause
 * @link     http://anaphore.eu
 */
abstract class BachMigration extends AbstractMigration
{
    private $_known_dbs = array(
        'mysql',
        'postgresql'
    );

    /**
     * Checks if database engine is known
     *
     * @return boolean
     */
    protected function checkDbPlatform()
    {
        $db_platform = $this->connection->getDatabasePlatform()->getName();
        $this->abortIf(
            !in_array($db_platform, $this->_known_dbs),
            'Migration can only be executed safely on ' .
            implode(', ', $this->_known_dbs) . '.'
        );
    }

    /**
     * Create a table from schema
     *
     * @param Schema $schema  Database schema
     * @param string $name    Table name
     * @param array  $columns Table columns
     * @param mixed  $pkey    Primary key as string or array
     *
     * @return Table
     */
    protected function createTable(Schema $schema, $name, $columns, $pkey)
    {
        $table = $schema->createTable($name);

        foreach ( $columns as $colname=>$column ) {
            $table->addColumn(
                $colname,
                $column['type'],
                $column['options']
            );
        }

        if ( !is_array($pkey) ) {
            $pkey = array($pkey);
        }
        $table->setPrimaryKey($pkey);

        return $table;
    }
}
