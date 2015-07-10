<?php

/*
 * The MIT License
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
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

namespace danielgp\nis2mysql;

/**
 * Description of MySQLactions
 *
 * @author E303778
 */
class MySQLactions extends ResultFile {

    use \danielgp\common_lib\CommonCode,
        \danielgp\network_components\NetworkComponentsByDanielGP;

    const LOCALE_DOMAIN = 'nis_messages';

    protected $mySQLconfig     = null;
    protected $mySQLconnection = null;
    protected $queryClass;
    protected $queueDetails    = '';

    public function __construct() {
        $this->queryClass = new AppQueries();
    }

    final protected function actOnAdvancedMySqlFeatureEvents() {
        $sReturn            = [];
        $this->queueDetails = '';
        $this->connectOrSetFeedbackIfNoMySqlConnection('events');
        $sQuery             = $this->storedQuery('ListOfEventsDetails', [
            'dbs'             => $_SESSION['a']['dbs'],
            'definerToModify' => $_SESSION['a']['definerToModify'],
        ]);
        $this->setFileContent([
            'FileKind'    => $this->fileToStore['relevant'],
            'Explanation' => 'List of Events',
            'Query'       => '-- ' . $sQuery,
        ]);
        $listOfEvents       = $this->runQuery($sQuery, 'fullArray3WithDisplay');
        $sReturn[]          = $this->queueDetails;
        $this->queueDetails = '';
        if ($listOfEvents) {
            if (is_array($listOfEvents)) {
                switch ($_SESSION['a']['actionChoosed']) {
                    case 'listAdvancedFeatureByChosenDefiner':
                        $listOfFields = [
                            'Database'             => 'EVENT_SCHEMA',
                            'Event name'           => 'EVENT_NAME',
                            'Event comment'        => 'EVENT_COMMENT',
                            'Event status'         => 'EVENT_STATUS',
                            'Event type'           => 'EVENT_TYPE',
                            'Event interval value' => 'INTERVAL_VALUE',
                            'Event interval field' => 'INTERVAL_FIELD',
                            'Time zone'            => 'TIME_ZONE',
                            'Event definer'        => 'DEFINER',
                            'Character set'        => 'CHARACTER_SET_CLIENT',
                            'Collation'            => 'COLLATION_CONNECTION',
                            'SQL mode'             => 'SQL_MODE',
                            'Starts on'            => 'STARTS',
                            'Created on'           => 'CREATED',
                            'Modified on'          => 'LAST_ALTERED',
                            'Last executed on'     => 'LAST_EXECUTED',
                        ];
                        $sReturn[]    = $this->runQueryWithFeedback($listOfFields, $listOfEvents);
                        break;
                    case 'modifyDefinerOfAdvancedFeatures':
                        foreach ($listOfEvents as $value) {
                            $sReturn[]          = $this->setMySQLsessionCharacterAndCollation($value);
                            $this->queueDetails = '';
                            $sQuery             = $this->storedQuery('ModifyView', [
                                'newDefinerSql'   => $this->definer['oldDefinerSql'],
                                'TABLE_NAME'      => $value['TABLE_NAME'],
                                'VIEW_DEFINITION' => $value['VIEW_DEFINITION'],
                            ]);
                            $explanation        = 'Modify view, old definer = "'
                                    . $_SESSION['a']['definerToBe'] . '" into "'
                                    . $_SESSION['a']['definerToModify'] . '"';
                            $this->setFileContent([
                                'FileKind'    => 'undo',
                                'Explanation' => $explanation,
                                'Query'       => $sQuery,
                            ]);
                            $sQuery             = $this->storedQuery('ModifyEvent', [
                                'newDefinerSql' => $this->definer['newDefinerSql'],
                                'EVENT_SCHEMA'  => $value['EVENT_SCHEMA'],
                                'EVENT_NAME'    => $value['EVENT_NAME'],
                                'ON_COMPLETION' => $value['ON_COMPLETION'],
                            ]);
                            $explanation        = 'Modify event, old definer = "'
                                    . $_SESSION['a']['definerToModify'] . '" into "'
                                    . $_SESSION['a']['definerToBe'] . '"';
                            $this->setFileContent([
                                'FileKind'    => 'do',
                                'Explanation' => $explanation,
                                'Query'       => $sQuery,
                            ]);
                            $this->runQuery($sQuery, 'runWithDisplayFirst');
                            $sReturn[]          = $this->queueDetails;
                            $this->queueDetails = '';
                        }
                }
            }
        }
        return implode('', $sReturn);
    }

    final protected function actOnAdvancedMySqlFeatureStoredRoutines() {
        $sReturn              = [];
        $this->queueDetails   = '';
        $this->connectOrSetFeedbackIfNoMySqlConnection('stored routines');
        $sQuery               = $this->storedQuery('ListOfStoredRoutinesDetails', [
            'dbs'             => $_SESSION['a']['dbs'],
            'definerToModify' => $_SESSION['a']['definerToModify'],
        ]);
        $this->setFileContent([
            'FileKind'    => $this->fileToStore['relevant'],
            'Explanation' => 'List of Stored Routines',
            'Query'       => '-- ' . $sQuery,
        ]);
        $listOfStoredRoutines = $this->runQuery($sQuery, 'fullArray3WithDisplay');
        $sReturn[]            = $this->queueDetails;
        $this->queueDetails   = '';
        if ($listOfStoredRoutines) {
            if (is_array($listOfStoredRoutines)) {
                switch ($_SESSION['a']['actionChoosed']) {
                    case 'listAdvancedFeatureByChosenDefiner':
                        $listOfFields = [
                            'Database'         => 'ROUTINE_SCHEMA',
                            'Routine type'     => 'ROUTINE_TYPE',
                            'Routine name'     => 'ROUTINE_NAME',
                            'Routine comment'  => 'ROUTINE_COMMENT',
                            'Is deterministic' => 'IS_DETERMINISTIC',
                            'Routine definer'  => 'DEFINER',
                            'Security type'    => 'SECURITY_TYPE',
                            'Character set'    => 'CHARACTER_SET_CLIENT',
                            'Collation'        => 'COLLATION_CONNECTION',
                            'SQL data access'  => 'SQL_DATA_ACCESS',
                            'SQL mode'         => 'SQL_MODE',
                            'Created on'       => 'CREATED',
                            'Modified on'      => 'LAST_ALTERED',
                        ];
                        $sReturn[]    = $this->runQueryWithFeedback($listOfFields, $listOfStoredRoutines);
                        break;
                    case 'modifyDefinerOfAdvancedFeatures':
                        foreach ($listOfStoredRoutines as $value) {
                            $sReturn[]          = $this->setMySQLsessionCharacterAndCollation($value);
                            $this->queueDetails = '';
                            $sQuery             = $this->storedQuery('ShowCreateStoredRoutine', [
                                'ROUTINE_TYPE'   => $value['ROUTINE_TYPE'],
                                'ROUTINE_SCHEMA' => $value['ROUTINE_SCHEMA'],
                                'ROUTINE_NAME'   => $value['ROUTINE_NAME'],
                            ]);
                            $this->setFileContent([
                                'FileKind'    => $this->fileToStore['relevant'],
                                'Explanation' => 'Show create routine',
                                'Query'       => '-- ' . $sQuery,
                            ]);
                            $crtRoutine         = $this->runQuery($sQuery, 'array_pairs_key_valueWithDisplay');
                            $sReturn[]          = $this->queueDetails;
                            $this->queueDetails = '';
                            $sQuery             = $this->storedQuery('SetSessionSqlMode', [
                                'sql_mode' => $crtRoutine['sql_mode']
                            ]);
                            $this->setFileContent([
                                'FileKind'    => $this->fileToStore['relevant'],
                                'Explanation' => 'Set SQL_MODE for current session',
                                'Query'       => $sQuery,
                            ]);
                            $this->runQuery($sQuery, 'runWithDisplayFirst');
                            $sReturn[]          = $this->queueDetails;
                            $this->queueDetails = '';
                            $sQuery             = $this->storedQuery('DropStoredRoutine', [
                                'ROUTINE_TYPE'   => $value['ROUTINE_TYPE'],
                                'ROUTINE_SCHEMA' => $value['ROUTINE_SCHEMA'],
                                'ROUTINE_NAME'   => $value['ROUTINE_NAME'],
                            ]);
                            $this->setFileContent([
                                'FileKind'    => $this->fileToStore['relevant'],
                                'Explanation' => 'Delete the old routine',
                                'Query'       => $sQuery,
                            ]);
                            $this->runQuery($sQuery, 'runWithDisplayFirst');
                            $sQuery             = $crtRoutine['Create ' . ucwords(strtolower($value['ROUTINE_TYPE']))];
                            $xplanation         = 'Create the original routine, definer = "'
                                    . $_SESSION['a']['definerToModify'] . '"';
                            $this->setFileContent([
                                'FileKind'    => 'undo',
                                'Explanation' => $xplanation,
                                'Query'       => $sQuery,
                            ]);
                            $sReturn[]          = $this->queueDetails;
                            $this->queueDetails = '';
                            $r                  = $crtRoutine['Create ' . ucwords(strtolower($value['ROUTINE_TYPE']))];
                            $sQuery             = $this->storedQuery('ModifyStoredRoutine', [
                                'oldDefinerSql'  => preg_quote($this->definer['oldDefinerSql']),
                                'newDefinerSql'  => preg_quote($this->definer['newDefinerSql']),
                                'RoutineContent' => $r,
                            ]);
                            $explanation        = 'Modify the routine, old definer = "'
                                    . $_SESSION['a']['definerToBe'] . '" into "'
                                    . $_SESSION['a']['definerToModify'] . '"';
                            $this->setFileContent([
                                'FileKind'    => 'do',
                                'Explanation' => $explanation,
                                'Query'       => $sQuery,
                            ]);
                            $this->runQuery($sQuery, 'runWithDisplayFirst');
                            $sReturn[]          = $this->queueDetails;
                            $this->queueDetails = '';
                        }
                }
            }
        }
        return implode('', $sReturn);
    }

    final protected function actOnAdvancedMySqlFeatureTriggers() {
        $sReturn            = [];
        $this->queueDetails = '';
        $this->connectOrSetFeedbackIfNoMySqlConnection('triggers');
        $sQuery             = $this->storedQuery('ListOfTriggersDetails', [
            'dbs'             => $_SESSION['a']['dbs'],
            'definerToModify' => $_SESSION['a']['definerToModify'],
        ]);
        $this->setFileContent([
            'FileKind'    => $this->fileToStore['relevant'],
            'Explanation' => 'List of Triggers',
            'Query'       => '-- ' . $sQuery,
        ]);
        $listOfTriggers     = $this->runQuery($sQuery, 'fullArray3WithDisplay');
        $sReturn[]          = $this->queueDetails;
        $this->queueDetails = '';
        if ($listOfTriggers) {
            $lastActiveSchema = null;
            if (is_array($listOfTriggers)) {
                switch ($_SESSION['a']['actionChoosed']) {
                    case 'listAdvancedFeatureByChosenDefiner':
                        $listOfFields = [
                            'Database'             => 'TRIGGER_SCHEMA',
                            'Table'                => 'EVENT_OBJECT_TABLE',
                            'Trigger name'         => 'TRIGGER_NAME',
                            'Trigger manipulation' => 'EVENT_MANIPULATION',
                            'Trigger timing'       => 'ACTION_TIMING',
                            'Trigger orientation'  => 'ACTION_ORIENTATION',
                            'Trigger definer'      => 'DEFINER',
                            'Character set'        => 'CHARACTER_SET_CLIENT',
                            'Collation'            => 'COLLATION_CONNECTION',
                            'SQL mode'             => 'SQL_MODE',
                        ];
                        $sReturn[]    = $this->runQueryWithFeedback($listOfFields, $listOfTriggers);
                        break;
                    case 'modifyDefinerOfAdvancedFeatures':
                        foreach ($listOfTriggers as $value) {
                            if ($lastActiveSchema !== $value['TRIGGER_SCHEMA']) {
                                $sQuery             = $this->storedQuery('UseDatabase', [
                                    'Database' => $value['TRIGGER_SCHEMA']
                                ]);
                                $this->setFileContent([
                                    'FileKind'    => $this->fileToStore['relevant'],
                                    'Explanation' => 'Change Database',
                                    'Query'       => $sQuery,
                                ]);
                                $this->runQuery($sQuery, 'runWithDisplayFirst');
                                $sReturn[]          = $this->queueDetails;
                                $this->queueDetails = '';
                                $lastActiveSchema   = $value['TRIGGER_SCHEMA'];
                            }
                            $sReturn[]             = $this->setMySQLsessionCharacterAndCollation($value);
                            $this->queueDetails    = '';
                            $sQuery                = $this->storedQuery('ShowCreateTrigger', [
                                'TRIGGER_NAME' => $value['TRIGGER_NAME']
                            ]);
                            $this->setFileContent([
                                'FileKind'    => $this->fileToStore['relevant'],
                                'Explanation' => 'Show create trigger',
                                'Query'       => '-- ' . $sQuery,
                            ]);
                            $currentTriggerDetails = $this->runQuery($sQuery, 'array_pairs_key_valueWithDisplay');
                            $sReturn[]             = $this->queueDetails;
                            $this->queueDetails    = '';
                            $sQuery                = $this->storedQuery('SetSessionSqlMode', [
                                'sql_mode' => $currentTriggerDetails['sql_mode']
                            ]);
                            $this->setFileContent([
                                'FileKind'    => $this->fileToStore['relevant'],
                                'Explanation' => 'Set SQL_MODE for current session',
                                'Query'       => $sQuery,
                            ]);
                            $this->runQuery($sQuery, 'runWithDisplayFirst');
                            $sReturn[]             = $this->queueDetails;
                            $this->queueDetails    = '';
                            $sQuery                = $this->storedQuery('LockTableWrite', [
                                'TableName' => $value['EVENT_OBJECT_TABLE']
                            ]);
                            $explanation           = 'Lock the table of the trigger for Write '
                                    . '(to avoid modications while modification is being done';
                            $this->setFileContent([
                                'FileKind'    => $this->fileToStore['relevant'],
                                'Explanation' => $explanation,
                                'Query'       => $sQuery,
                            ]);
                            $this->runQuery($sQuery, 'runWithDisplayFirst');
                            $sReturn[]             = $this->queueDetails;
                            $this->queueDetails    = '';
                            $sQuery                = $this->storedQuery('DropTrigger', [
                                'TRIGGER_NAME' => $value['TRIGGER_NAME']
                            ]);
                            $this->setFileContent([
                                'FileKind'    => $this->fileToStore['relevant'],
                                'Explanation' => 'Delete the old trigger',
                                'Query'       => $sQuery,
                            ]);
                            $this->runQuery($sQuery, 'runWithDisplayFirst');
                            $sReturn[]             = $this->queueDetails;
                            $this->queueDetails    = '';
                            $sQuery                = $this->storedQuery('ModifyTrigger', [
                                'oldDefinerSql'  => preg_quote($this->definer['oldDefinerSql']),
                                'newDefinerSql'  => preg_quote($this->definer['newDefinerSql']),
                                'TriggerContent' => $currentTriggerDetails['SQL Original Statement'],
                            ]);
                            $explanation           = 'Modify the trigger, old definer = "'
                                    . $_SESSION['a']['definerToBe'] . '" into "'
                                    . $_SESSION['a']['definerToModify'] . '"';
                            $this->setFileContent([
                                'FileKind'    => 'do',
                                'Explanation' => $explanation,
                                'Query'       => $sQuery,
                            ]);
                            $this->runQuery($sQuery, 'runWithDisplayFirst');
                            $explanation           = 'Create the original trigger, definer = "'
                                    . $_SESSION['a']['definerToModify'] . '"';
                            $this->setFileContent([
                                'FileKind'    => 'undo',
                                'Explanation' => $explanation,
                                'Query'       => $currentTriggerDetails['SQL Original Statement'],
                            ]);
                            $sReturn[]             = $this->queueDetails;
                            $this->queueDetails    = '';
                            $this->runQuery($this->storedQuery(['UnlockTables']), 'runWithDisplayFirst');
                            $sReturn[]             = $this->queueDetails;
                            $this->queueDetails    = '';
                        }
                        break;
                }
            }
        }
        return implode('', $sReturn);
    }

    final protected function actOnAdvancedMySqlFeatureViews() {
        $sReturn            = [];
        $this->connectOrSetFeedbackIfNoMySqlConnection('views');
        $this->queueDetails = '';
        $sQuery             = $this->storedQuery('ListOfViewsDetails', [
            'dbs'             => $_SESSION['a']['dbs'],
            'definerToModify' => $_SESSION['a']['definerToModify'],
        ]);
        $this->setFileContent([
            'FileKind'    => $this->fileToStore['relevant'],
            'Explanation' => 'List of Views',
            'Query'       => '-- ' . $sQuery,
        ]);
        $listOfViews        = $this->runQuery($sQuery, 'fullArray3WithDisplay');
        $sReturn[]          = $this->queueDetails;
        $this->queueDetails = '';
        if ($listOfViews) {
            $lastActiveSchema = null;
            if (is_array($listOfViews)) {
                switch ($_SESSION['a']['actionChoosed']) {
                    case 'listAdvancedFeatureByChosenDefiner':
                        $listOfFields = [
                            'Database'      => 'TABLE_SCHEMA',
                            'View name'     => 'TABLE_NAME',
                            'View definer'  => 'DEFINER',
                            'Character set' => 'CHARACTER_SET_CLIENT',
                            'Collation'     => 'COLLATION_CONNECTION',
                            'Security Type' => 'SECURITY_TYPE',
                            'Is updatable'  => 'IS_UPDATABLE',
                        ];
                        $sReturn[]    = $this->runQueryWithFeedback($listOfFields, $listOfViews);
                        break;
                    case 'modifyDefinerOfAdvancedFeatures':
                        foreach ($listOfViews as $value) {
                            if ($lastActiveSchema !== $value['TABLE_SCHEMA']) {
                                $sQuery             = $this->storedQuery('UseDatabase', [
                                    'Database' => $value['TABLE_SCHEMA']
                                ]);
                                $this->setFileContent([
                                    'FileKind'    => $this->fileToStore['relevant'],
                                    'Explanation' => 'Change Database',
                                    'Query'       => $sQuery,
                                ]);
                                $this->runQuery($sQuery, 'runWithDisplayFirst');
                                $sReturn[]          = $this->queueDetails;
                                $this->queueDetails = '';
                                $lastActiveSchema   = $value['TABLE_SCHEMA'];
                            }
                            $sReturn[]          = $this->setMySQLsessionCharacterAndCollation($value);
                            $this->queueDetails = '';
                            $sQuery             = $this->storedQuery('ModifyView', [
                                'newDefinerSql'   => $this->definer['oldDefinerSql'],
                                'TABLE_NAME'      => $value['TABLE_NAME'],
                                'VIEW_DEFINITION' => $value['VIEW_DEFINITION'],
                            ]);
                            $explanation        = 'Modify view, old definer = "'
                                    . $_SESSION['a']['definerToBe'] . '" into "'
                                    . $_SESSION['a']['definerToModify'] . '"';
                            $this->setFileContent([
                                'FileKind'    => 'undo',
                                'Explanation' => $explanation,
                                'Query'       => $sQuery,
                            ]);
                            $sQuery             = $this->storedQuery('ModifyView', [
                                'newDefinerSql'   => $this->definer['newDefinerSql'],
                                'TABLE_NAME'      => $value['TABLE_NAME'],
                                'VIEW_DEFINITION' => $value['VIEW_DEFINITION'],
                            ]);
                            $explanation        = 'Modify view, old definer = "'
                                    . $_SESSION['a']['definerToModify'] . '" into "'
                                    . $_SESSION['a']['definerToBe'] . '"';
                            $this->setFileContent([
                                'FileKind'    => 'do',
                                'Explanation' => $explanation,
                                'Query'       => $sQuery,
                            ]);
                            $this->runQuery($sQuery, 'runWithDisplayFirst');
                            $sReturn[]          = $this->queueDetails;
                            $this->queueDetails = '';
                        }
                        break;
                }
            }
        }
        return implode('', $sReturn);
    }

    protected function connectOrSetFeedbackIfNoMySqlConnection($thingsToAnalyze, $type = 'analyze', $ftrs = null) {
        $this->connectToMySqlServer();
        if (is_null($this->mySQLconnection)) {
            extract($this->mySQLconfig);
            switch ($type) {
                case 'analyze':
                    $feedback          = _('i18n_Feedback_AnalyzeImpossible');
                    $feedbackToProvide = sprintf($feedback, $thingsToAnalyze, $host, $port, $username, $db);
                    break;
                case 'run':
                    $feedback          = _('i18n_Feedback_RunImpossible');
                    $feedbackToProvide = sprintf($feedback, $ftrs['query'], $host, $port, $username, $db);
                    break;
                default:
                    $feedbackToProvide = '';
                    break;
            }
            echo '<p style="color:red;">' . $this->getTimestamp() . $feedbackToProvide . '</p>';
        }
    }

    protected function connectToMySqlServer() {
        $cfg = $this->configuredMySqlServers();
        if (is_null($this->mySQLconfig)) {
            $this->mySQLconfig = [
                'host'     => $cfg[$_SESSION['a']['serverChoosed']]['host'],
                'port'     => $cfg[$_SESSION['a']['serverChoosed']]['port'],
                'username' => $cfg[$_SESSION['a']['serverChoosed']]['user'],
                'password' => $cfg[$_SESSION['a']['serverChoosed']]['password'],
                'database' => $cfg[$_SESSION['a']['serverChoosed']]['database'],
            ];
        }
        if (is_null($this->mySQLconnection)) {
            $this->connectToMySql($this->mySQLconfig);
        }
    }

    protected function runQuery($sQuery, $sReturnType = null, $prefixKey = null) {
        $this->connectOrSetFeedbackIfNoMySqlConnection('', 'run', ['query' => htmlentities($sQuery)]);
        switch ($sReturnType) {
            case 'noRunJustDisplay':
                $this->queueDetails .= '<p style="color:grey;">' . $this->getTimestamp()
                        . sprintf(_('i18n_Feedback_MySQL_DisplayedOnly'), htmlentities($sQuery), $sReturnType)
                        . '</p>';
                return '';
                break;
            case 'runWithDisplayFirst':
                $this->queueDetails .= '<p style="color:grey;">' . $this->getTimestamp()
                        . sprintf(_('i18n_Feedback_MySQL_ToBeExecuted'), htmlentities($sQuery), $sReturnType)
                        . '</p>';
                break;
        }
        $result = $this->mySQLconnection->query($sQuery);
        if ($result) {
            if (is_object($result)) {
                $iNoOfRows = $result->num_rows;
                if (($sReturnType != 'runWithDisplayFirst') && (strpos($sReturnType, 'WithDisplay') !== false)) {
                    $this->queueDetails .= '<p style="color:green;">' . $this->getTimestamp()
                            . sprintf(_('i18n_Feedback_MySQL_Executed'), htmlentities($sQuery), $sReturnType, $iNoOfRows)
                            . '</p>';
                }
            }
            switch ($sReturnType) {
                case 'array_pairs_key_valueWithDisplay':
                case 'array_pairs_key_value':
                    $aReturn = $this->setMySQLquery2ServerByPattern([
                                'NoOfColumns' => $result->field_count,
                                'NoOfRows'    => $result->num_rows,
                                'QueryResult' => $result,
                                'returnType'  => 'array_pairs_key_value',
                                'return'      => $aReturn
                            ])['result'];
                    break;
                case 'fullArray3WithDisplay':
                case 'fullArray3':
                    $aReturn = $this->setMySQLquery2ServerByPattern([
                                'NoOfColumns' => $result->field_count,
                                'NoOfRows'    => $result->num_rows,
                                'QueryResult' => $result,
                                'returnType'  => 'full_array_key_numbered',
                                'return'      => $aReturn
                            ])['result'];
                    break;
                case 'runWithDisplayFirst':
                    break;
                case 'valueWithDisplay':
                case 'value':
                    if ($iNoOfRows == 1) {
                        $aReturn = $result->fetch_row();
                    } else {
                        $result->close();
                        return false;
                    }
                    break;
            }
            if (is_object($result)) {
                $result->close();
            }
            return $aReturn;
        } else {
            extract($this->mySQLconfig);
            $err = $this->mySQLconnection->error;
            $this->queueDetails .= '<p style="color:red;">' . $this->getTimestamp()
                    . sprintf(_('i18n_Feedback_MySQL_Error'), htmlentities($sQuery), $host, $port, $username, $err)
                    . '</p>';
        }
    }

    protected function runQueryWithFeedback($listOfFields, $listOfEvents) {
        $sQuery = implode('"' . $this->configuredGlue() . '"', array_keys($listOfFields));
        $this->setFileContent([
            'FileKind' => $this->fileToStore['relevant'],
            'Query'    => '-- "' . $sQuery . '"',
        ]);
        foreach ($listOfEvents as $value) {
            $infoLine      = null;
            $infoDisplayed = null;
            foreach ($listOfFields as $key => $value2) {
                $infoLine[]          = $value[$value2];
                $infoDisplayed[$key] = $value[$value2];
            }
            $sReturn[] = '<p>' . $this->getTimestamp() . $this->setArrayToJson($infoDisplayed) . '</p>';
            $sQuery    = implode('"' . $this->configuredGlue() . '"', $infoLine);
            $this->setFileContent([
                'FileKind' => $this->fileToStore['relevant'],
                'Query'    => '-- "' . $sQuery . '"',
            ]);
        }
        unset($infoDisplayed);
        unset($infoLine);
        $this->setFileContent([
            'FileKind' => $this->fileToStore['relevant'],
            'Query'    => '--',
        ]);
        return implode('', $sReturn);
    }

    protected function setMySQLsessionCharacterAndCollation($value) {
        $sQuery = $this->storedQuery('SetSessionCharacterAndCollation', [
            'CHARACTER_SET_CLIENT' => $value['CHARACTER_SET_CLIENT'],
            'COLLATION_CONNECTION' => $value['COLLATION_CONNECTION'],
        ]);
        $this->setFileContent([
            'FileKind'    => $this->fileToStore['relevant'],
            'Explanation' => 'Set session charset and collation',
            'Query'       => $sQuery,
        ]);
        $this->runQuery($sQuery, 'runWithDisplayFirst');
        return $this->queueDetails;
    }

    /**
     * Place for all MySQL queries used within current class
     *
     * @param string $label
     * @param array $given_parameters
     * @return string
     */
    final protected function storedQuery($label, $given_parameters = null) {
        $sReturn = $this->queryClass->setRightQuery($label, $given_parameters);
        if ($sReturn === false) {
            echo $this->setFeedback(0, _('i18n_Feedback_Error'), sprintf(_('i18n_Feedback_UndefinedQuery'), $label));
        }
        return $sReturn;
    }

}
