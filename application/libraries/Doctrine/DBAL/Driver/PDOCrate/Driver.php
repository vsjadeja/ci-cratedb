<?php

/**
 * Licensed to CRATE Technology GmbH("Crate") under one or more contributor
 * license agreements.  See the NOTICE file distributed with this work for
 * additional information regarding copyright ownership.  Crate licenses
 * this file to you under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.  You may
 * obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS, WITHOUT
 * WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.  See the
 * License for the specific language governing permissions and limitations
 * under the License.
 *
 * However, if you have executed another commercial license agreement
 * with Crate these terms will supersede the license and you may use the
 * software solely pursuant to the terms of the relevant commercial agreement.
 */

namespace Doctrine\DBAL\Driver\PDOCrate;

use Doctrine\DBAL\Platforms\Crate057Platform;
use Doctrine\DBAL\Platforms\CratePlatform;
use Doctrine\DBAL\Schema\CrateSchemaManager;

class Driver implements \Doctrine\DBAL\Driver {

    const VERSION = '1.1.0';
    const NAME = 'crate';
    const SCHEMA_MIN_VERSION = '0.57.0';

    /**
     * {@inheritDoc}
     * @return PDOConnection The database connection.
     */
    public function connect(array $params, $username = null, $password = null, array $driverOptions = array()) {
        $conn = new \Doctrine\DBAL\Driver\PDOConnection(
                $this->constructPdoDsn($params), $username, $password, $driverOptions
        );
        return $conn;
    }

    /**
     * Constructs the Crate PDO DSN.
     *
     * @return string The DSN.
     */
    private function constructPdoDsn(array $params) {
        $dsn = self::NAME . ':';
        if (isset($params['host']) && $params['host'] != '') {
            $dsn .= $params['host'];
        } else {
            $dsn .= 'localhost';
        }
        $dsn .= ':' . (isset($params['port']) ? $params['port'] : '4200');

        return $dsn;
    }

    /**
     * {@inheritDoc}
     */
    public function getDatabasePlatform() {
        return new CratePlatform();
    }

    /**
     * {@inheritDoc}
     */
    public function getSchemaManager(\Doctrine\DBAL\Connection $conn) {
        return new CrateSchemaManager($conn);
    }

    /**
     * {@inheritDoc}
     */
    public function getName() {
        return self::NAME;
    }

    /**
     * {@inheritDoc}
     */
    public function getDatabase(\Doctrine\DBAL\Connection $conn) {
        return null;
    }

    /**
     * {@inheritDoc}
     */
    public function createDatabasePlatformForVersion($version) {
        if (version_compare($version, self::SCHEMA_MIN_VERSION, ">=")) {
            return new Crate057Platform();
        } else {
            return new CratePlatform();
        }
    }

}
