<?php

/**
 *
 * The MIT License (MIT)
 *
 * Copyright (c) 2015 Daniel Popiniuc
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 *  OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 *
 */

namespace danielgp\nis2mysql;

/**
 * Queries used to handle SQL data
 *
 * @author Popiniuc Daniel-Gheorghe
 */
class AppQueries extends UserConfiguration
{

    private function sAssesmentListOfDefiner($parameters)
    {
        return 'SELECT `DEFINER` '
            . 'FROM `INFORMATION_SCHEMA`.`EVENTS` '
            . 'WHERE (`EVENT_SCHEMA` IN ("' . implode('", "', $parameters['dbs']) . '")) '
            . 'GROUP BY `DEFINER` '
            . 'UNION '
            . 'SELECT `DEFINER` '
            . 'FROM `INFORMATION_SCHEMA`.`ROUTINES` '
            . 'WHERE (`ROUTINE_SCHEMA` IN ("' . implode('", "', $parameters['dbs']) . '")) '
            . 'GROUP BY `DEFINER` '
            . 'UNION '
            . 'SELECT `DEFINER` '
            . 'FROM `INFORMATION_SCHEMA`.`TRIGGERS` '
            . 'WHERE (`TRIGGER_SCHEMA` IN ("' . implode('", "', $parameters['dbs']) . '")) '
            . 'GROUP BY `DEFINER` '
            . 'UNION '
            . 'SELECT `DEFINER` '
            . 'FROM `INFORMATION_SCHEMA`.`VIEWS` '
            . 'WHERE (`TABLE_SCHEMA` IN ("' . implode('", "', $parameters['dbs']) . '")) '
            . 'GROUP BY `DEFINER` ';
    }

    private function sAssesmentListOfDefinerByType($parameters)
    {
        return 'SELECT "Events" AS `Type`, COUNT(*) AS `No.` '
            . 'FROM `INFORMATION_SCHEMA`.`EVENTS` '
            . 'WHERE (`EVENT_SCHEMA` IN ("' . implode('", "', $parameters['dbs']) . '")) '
            . 'AND (`DEFINER` = "' . $parameters['definerToModify'] . '") '
            . 'UNION '
            . 'SELECT "Routines" AS `Type`, COUNT(*) AS `No.` '
            . 'FROM `INFORMATION_SCHEMA`.`ROUTINES` '
            . 'WHERE (`ROUTINE_SCHEMA` IN ("' . implode('", "', $parameters['dbs']) . '")) '
            . 'AND (`DEFINER` = "' . $parameters['definerToModify'] . '") '
            . 'UNION '
            . 'SELECT "Triggers" AS `Type`, COUNT(*) AS `No.` '
            . 'FROM `INFORMATION_SCHEMA`.`TRIGGERS` '
            . 'WHERE (`TRIGGER_SCHEMA` IN ("' . implode('", "', $parameters['dbs']) . '")) '
            . 'AND (`DEFINER` = "' . $parameters['definerToModify'] . '") '
            . 'UNION '
            . 'SELECT "Views" AS `Type`, COUNT(*) AS `No.` '
            . 'FROM `INFORMATION_SCHEMA`.`VIEWS` '
            . 'WHERE (`TABLE_SCHEMA` IN ("' . implode('", "', $parameters['dbs']) . '")) '
            . 'AND (`DEFINER` = "' . $parameters['definerToModify'] . '") ';
    }

    private function sDropStoredRoutine($parameters)
    {
        return 'DROP ' . $value['ROUTINE_TYPE'] . ' '
            . '`' . $value['ROUTINE_SCHEMA'] . '`.`' . $value['ROUTINE_NAME'] . '`;';
    }

    private function sDropTrigger($parameters)
    {
        return 'DROP TRIGGER `' . $parameters['TRIGGER_NAME'] . '`;';
    }

    private function sListOfDatabases()
    {
        return 'SELECT `SCHEMA_NAME` '
            . 'FROM `INFORMATION_SCHEMA`.`SCHEMATA` '
            . 'WHERE (`SCHEMA_NAME` NOT IN ("information_schema", "mysql", "performance_schema", "sys"));';
    }

    private function sListOfDatabasesFullDetails()
    {
        return 'SELECT * '
            . 'FROM `INFORMATION_SCHEMA`.`SCHEMATA` '
            . 'WHERE (`SCHEMA_NAME` NOT IN ("information_schema", "mysql", "performance_schema", "sys"));';
    }

    private function sListOfEventsDetails($parameters)
    {
        return 'SELECT * '
            . 'FROM `INFORMATION_SCHEMA`.`EVENTS` '
            . 'WHERE (`EVENT_SCHEMA` IN ("' . implode('", "', $parameters['dbs']) . '")) '
            . 'AND (`DEFINER` = "' . $parameters['definerToModify'] . '") '
            . 'ORDER BY `EVENT_SCHEMA`, `EVENT_NAME`;';
    }

    private function sListOfStoredRoutinesDetails($parameters)
    {
        return 'SELECT * '
            . 'FROM `INFORMATION_SCHEMA`.`ROUTINES` '
            . 'WHERE (`ROUTINE_SCHEMA` IN ("' . implode('", "', $parameters['dbs']) . '")) '
            . 'AND (`DEFINER` = "' . $parameters['definerToModify'] . '") '
            . 'ORDER BY `ROUTINE_SCHEMA`, `ROUTINE_TYPE`, `ROUTINE_NAME`;';
    }

    private function sListOfTablesFullDetails()
    {
        return 'SELECT * '
            . 'FROM `INFORMATION_SCHEMA`.`TABLES` '
            . 'WHERE (`TABLE_SCHEMA` NOT IN ("information_schema", "mysql", "performance_schema", "sys"));';
    }

    private function sListOfTriggersDetails($parameters)
    {
        return 'SELECT * '
            . 'FROM `INFORMATION_SCHEMA`.`TRIGGERS` '
            . 'WHERE (`TRIGGER_SCHEMA` IN ("' . implode('", "', $parameters['dbs']) . '")) '
            . 'AND (`DEFINER` = "' . $parameters['definerToModify'] . '") '
            . 'ORDER BY `TRIGGER_SCHEMA`, `TRIGGER_NAME`;';
    }

    private function sListOfUsersWithAssignedPrivileges($parameters)
    {
        return 'SELECT REPLACE(`GRANTEE`, "\'", "") AS `Grantee` '
            . 'FROM information_schema.USER_PRIVILEGES '
            . 'WHERE (`GRANTEE` != "' . $parameters['definerToModify'] . '") '
            . 'GROUP BY `GRANTEE`;';
    }

    private function sListOfViewsDetails($parameters)
    {
        return 'SELECT * '
            . 'FROM `INFORMATION_SCHEMA`.`VIEWS` '
            . 'WHERE (`TABLE_SCHEMA` IN ("' . implode('", "', $parameters['dbs']) . '")) '
            . 'AND (`DEFINER` = "' . $parameters['definerToModify'] . '") '
            . 'ORDER BY `TABLE_SCHEMA`, `TABLE_NAME`;';
    }

    private function sLockTableWrite($parameters)
    {
        return 'LOCK TABLES `' . $parameters['TableName'] . '` WRITE;';
    }

    private function sModifyEvent($parameters)
    {
        return 'ALTER DEFINER=' . $parameters['newDefinerSql'] . ' '
            . 'EVENT `' . $parameters['EVENT_SCHEMA'] . '`.`' . $parameters['EVENT_NAME'] . '` '
            . 'ON COMPLETION ' . $parameters['ON_COMPLETION'] . ';';
    }

    private function sModifyStoredRoutine($parameters)
    {
        $o = $parameters['oldDefinerSql'];
        $n = $parameters['newDefinerSql'];
        $r = $parameters['RoutineContent'];
        return preg_replace('|^CREATE DEFINER=(' . $o . ') | ', 'CREATE DEFINER=' . $n . ' ', $r);
    }

    private function sModifyTrigger($parameters)
    {
        $o = $parameters['oldDefinerSql'];
        $n = $parameters['newDefinerSql'];
        $t = $parameters['TriggerContent'];
        return preg_replace('|^CREATE DEFINER=(' . $o . ') | ', 'CREATE DEFINER=' . $n . ' ', $t);
    }

    private function sModifyView($parameters)
    {
        return 'ALTER DEFINER=' . $parameters['newDefinerSql'] . ' '
            . 'VIEW `' . $parameters['TABLE_NAME'] . '` AS ' . $parameters['VIEW_DEFINITION'] . ';';
    }

    private function sSetSessionCharacterAndCollation($parameters)
    {
        return 'SET SESSION `character_set_client` = "' . $parameters['CHARACTER_SET_CLIENT'] . '", '
            . '`collation_connection` = "' . $parameters['COLLATION_CONNECTION'] . '";';
    }

    private function sSetSessionSqlMode($parameters)
    {
        return 'SET SESSION SQL_MODE = "' . $parameters['sql_mode'] . '";';
    }

    private function sShowCreateStoredRoutine($parameters)
    {
        return 'SHOW CREATE ' . $parameters['ROUTINE_TYPE'] . ' '
            . '`' . $parameters['ROUTINE_SCHEMA'] . '`.`' . $parameters['ROUTINE_NAME'] . '`;';
    }

    private function sShowCreateTrigger($parameters)
    {
        return 'SHOW CREATE TRIGGER `' . $parameters['TRIGGER_NAME'] . '`;';
    }

    private function sUnlockTables()
    {
        return 'UNLOCK TABLES;';
    }

    private function sUseDatabase($parameters)
    {
        return 'USE `' . $parameters['Database'] . '`;';
    }

    public function setRightQuery($label, $givenParameters = null)
    {
        $label               = 's' . $label;
        $predefinedFunctions = [
            'AssesmentListOfDefiner'            => $this->sAssesmentListOfDefiner($givenParameters),
            'AssesmentListOfDefinerByType'      => $this->sAssesmentListOfDefinerByType($givenParameters),
            'DropStoredRoutine'                 => $this->sDropStoredRoutine($givenParameters),
            'DropTrigger'                       => $this->sDropTrigger($givenParameters),
            'ListOfDatabases'                   => $this->sListOfDatabases(),
            'ListOfDatabasesFullDetails'        => $this->sListOfDatabasesFullDetails(),
            'ListOfEventsDetails'               => $this->sListOfEventsDetails($givenParameters),
            'ListOfStoredRoutinesDetails'       => $this->sListOfStoredRoutinesDetails($givenParameters),
            'ListOfTablesFullDetails'           => $this->sListOfTablesFullDetails(),
            'ListOfTriggersDetails'             => $this->sListOfTriggersDetails($givenParameters),
            'ListOfUsersWithAssignedPrivileges' => $this->sListOfUsersWithAssignedPrivileges($givenParameters),
            'LockTableWrite'                    => $this->sLockTableWrite($givenParameters),
            'ModifyEvent'                       => $this->sModifyEvent($givenParameters),
            'ModifyStoredRoutine'               => $this->sModifyStoredRoutine($givenParameters),
            'ModifyTrigger'                     => $this->sModifyTrigger($givenParameters),
            'ModifyView'                        => $this->sModifyView($givenParameters),
            'SetSessionCharacterAndCollation'   => $this->sSetSessionCharacterAndCollation($givenParameters),
            'SetSessionSqlMode'                 => $this->sSetSessionSqlMode($givenParameters),
            'ShowCreateStoredRoutine'           => $this->sShowCreateStoredRoutine($givenParameters),
            'ShowCreateTrigger'                 => $this->sShowCreateTrigger($givenParameters),
            'UnlockTables'                      => $this->sUnlockTables(),
            'UseDatabase'                       => $this->sUseDatabase($givenParameters),
        ];
        if (in_arrray($label, array_keys($predefinedFunctions))) {
            return $predefinedFunctions[$label];
        } else {
            echo '<hr/>Unknown query... (wanted was `' . $label . '`)<hr/>';
            return false;
        }
    }
}
