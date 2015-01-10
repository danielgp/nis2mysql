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
 * Description of Change
 *
 * @author Daniel-Gheorghe Popiniuc <daniel.popiniuc@honeywell.com>
 * @version 1.0.20140922
 */
class ChangeMySqlAdvancedFeatures extends AppQueries
{

    use \danielgp\common_lib\CommonCode;

    const LOCALE_DOMAIN = 'nis_messages';

    private $applicationFlags;
    private $actions;
    private $definer           = null;
    private $fileToStore       = null;
    private $tabs              = 1;
    protected $mySQLconfig     = null;
    protected $mySQLconnection = null;
    protected $queueDetails    = '';

    /**
     * Provides basic checking of requried parameters and initiates LDAP attributes
     *
     * @version 1.0.20131111
     *
     */
    public function __construct()
    {
        $this->applicationFlags = [
            'available_languages' => [
                'en_US' => 'EN',
                'ro_RO' => 'RO',
            ],
            'default_language'    => $this->configuredDefaultLanguage(),
            'Name'                => 'Normalize MySQL internal structures',
        ];
        $this->handleLocalizationNIS();
        $this->actions          = [
            'listAdvancedFeatureByChosenDefiner' => _('i18n_TabAction_OptionList'),
            'modifyDefinerOfAdvancedFeatures'    => _('i18n_TabAction_OptionModify'),
        ];
        echo $this->getInterface();
    }

    final private function actOnAdvancedMySqlFeatureEvents()
    {
        $sReturn            = [];
        $this->queueDetails = '';
        $this->setFeedbackNoMySqlConnection('events');
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
                        $sQuery       = implode('"' . $this->configuredGlue() . '"', array_keys($listOfFields));
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
                            $sReturn[] = '<p>' . $this->getTimestamp()
                                . json_encode($infoDisplayed, JSON_FORCE_OBJECT | JSON_PRETTY_PRINT)
                                . '</p>';
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
                        break;
                    case 'modifyDefinerOfAdvancedFeatures':
                        foreach ($listOfEvents as $value) {
                            $sQuery             = $this->storedQuery('SetSessionCharacterAndCollation', [
                                'CHARACTER_SET_CLIENT' => $value['CHARACTER_SET_CLIENT'],
                                'COLLATION_CONNECTION' => $value['COLLATION_CONNECTION'],
                            ]);
                            $this->setFileContent([
                                'FileKind'    => $this->fileToStore['relevant'],
                                'Explanation' => 'Set session charset and collation',
                                'Query'       => $sQuery,
                            ]);
                            $this->runQuery($sQuery, 'runWithDisplayFirst');
                            $sReturn[]          = $this->queueDetails;
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

    private function actOnAdvancedMySqlFeatureStoredRoutines()
    {
        $sReturn              = [];
        $this->queueDetails   = '';
        $this->setFeedbackNoMySqlConnection('stored routines');
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
                        $sQuery       = implode('"' . $this->configuredGlue() . '"', array_keys($listOfFields));
                        $this->setFileContent([
                            'FileKind' => $this->fileToStore['relevant'],
                            'Query'    => '-- "' . $sQuery . '"',
                        ]);
                        foreach ($listOfStoredRoutines as $value) {
                            $infoLine      = null;
                            $infoDisplayed = null;
                            foreach ($listOfFields as $key => $value2) {
                                $infoLine[]          = $value[$value2];
                                $infoDisplayed[$key] = $value[$value2];
                            }
                            $sReturn[] = '<p>' . $this->getTimestamp()
                                . json_encode($infoDisplayed, JSON_FORCE_OBJECT | JSON_PRETTY_PRINT)
                                . '</p>';
                            $sQuery    = implode('"' . $this->configuredGlue() . '"', $infoLine);
                            $this->setFileContent([
                                'FileKind' => $this->fileToStore['relevant'],
                                'Query'    => '-- "' . $sQuery . '"',
                            ]);
                        }
                        unset($infoLine);
                        $this->setFileContent([
                            'FileKind' => $this->fileToStore['relevant'],
                            'Query'    => '--',
                        ]);
                        break;
                    case 'modifyDefinerOfAdvancedFeatures':
                        foreach ($listOfStoredRoutines as $value) {
                            $sQuery             = $this->storedQuery('SetSessionCharacterAndCollation', [
                                'CHARACTER_SET_CLIENT' => $value['CHARACTER_SET_CLIENT'],
                                'COLLATION_CONNECTION' => $value['COLLATION_CONNECTION'],
                            ]);
                            $this->setFileContent([
                                'FileKind'    => $this->fileToStore['relevant'],
                                'Explanation' => 'Set session charset and collation',
                                'Query'       => $sQuery,
                            ]);
                            $this->runQuery($sQuery, 'runWithDisplayFirst');
                            $sReturn[]          = $this->queueDetails;
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

    final private function actOnAdvancedMySqlFeatureTriggers()
    {
        $sReturn            = [];
        $this->queueDetails = '';
        $this->setFeedbackNoMySqlConnection('triggers');
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
                        $sQuery       = implode('"' . $this->configuredGlue() . '"', array_keys($listOfFields));
                        $this->setFileContent([
                            'FileKind' => $this->fileToStore['relevant'],
                            'Query'    => '-- "' . $sQuery . '"',
                        ]);
                        foreach ($listOfTriggers as $value) {
                            $infoLine      = null;
                            $infoDisplayed = null;
                            foreach ($listOfFields as $key => $value2) {
                                $infoLine[]          = $value[$value2];
                                $infoDisplayed[$key] = $value[$value2];
                            }
                            $sReturn[] = '<p>' . $this->getTimestamp()
                                . json_encode($infoDisplayed, JSON_FORCE_OBJECT | JSON_PRETTY_PRINT) . '</p>';
                            $sQuery    = implode('"' . $this->configuredGlue() . '"', $infoLine);
                            $this->setFileContent([
                                'FileKind' => $this->fileToStore['relevant'],
                                'Query'    => '-- "' . $sQuery . '"',
                            ]);
                        }
                        unset($infoLine);
                        $this->setFileContent([
                            'FileKind' => $this->fileToStore['relevant'],
                            'Query'    => '--',
                        ]);
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
                            $sQuery                = $this->storedQuery('SetSessionCharacterAndCollation', [
                                'CHARACTER_SET_CLIENT' => $value['CHARACTER_SET_CLIENT'],
                                'COLLATION_CONNECTION' => $value['COLLATION_CONNECTION'],
                            ]);
                            $this->setFileContent([
                                'FileKind'    => $this->fileToStore['relevant'],
                                'Explanation' => 'Set session charset and collation',
                                'Query'       => $sQuery,
                            ]);
                            $this->runQuery($sQuery, 'runWithDisplayFirst');
                            $sReturn[]             = $this->queueDetails;
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

    final private function actOnAdvancedMySqlFeatureViews()
    {
        $sReturn            = [];
        $this->setFeedbackNoMySqlConnection('views');
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
                        $sQuery       = implode('"' . $this->configuredGlue() . '"', array_keys($listOfFields));
                        $this->setFileContent([
                            'FileKind' => $this->fileToStore['relevant'],
                            'Query'    => '-- "' . $sQuery . '"',
                        ]);
                        foreach ($listOfViews as $value) {
                            $infoLine      = null;
                            $infoDisplayed = null;
                            foreach ($listOfFields as $key => $value2) {
                                $infoLine[]          = $value[$value2];
                                $infoDisplayed[$key] = $value[$value2];
                            }
                            $sReturn[] = '<p>' . $this->getTimestamp()
                                . json_encode($infoDisplayed, JSON_FORCE_OBJECT | JSON_PRETTY_PRINT) . '</p>';
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
                            $sQuery             = $this->storedQuery('SetSessionCharacterAndCollation', [
                                'CHARACTER_SET_CLIENT' => $value['CHARACTER_SET_CLIENT'],
                                'COLLATION_CONNECTION' => $value['COLLATION_CONNECTION'],
                            ]);
                            $this->setFileContent([
                                'FileKind'    => $this->fileToStore['relevant'],
                                'Explanation' => 'Set session charset and collation',
                                'Query'       => $sQuery,
                            ]);
                            $this->runQuery($sQuery, 'runWithDisplayFirst');
                            $sReturn[]          = $this->queueDetails;
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

    final private function configDefaults()
    {
        if (isset($_REQUEST['serverChoosed'])) {
            session_destroy();
            session_start();
            $this->handleLocalizationNIS();
            $_SESSION['a']['serverChoosed'] = $_REQUEST['serverChoosed'];
            $this->tabs                     = 1;
        }
        if (isset($_SESSION['a']['serverChoosed'])) {
            if (isset($_REQUEST['actionChoosed'])) {
                $_SESSION['a']['actionChoosed'] = $_REQUEST['actionChoosed'];
                if (isset($_SESSION['a']['dbs'])) {
                    unset($_SESSION['a']['dbs']);
                }
                if (isset($_SESSION['a']['definerToModify'])) {
                    unset($_SESSION['a']['definerToModify']);
                }
                if (isset($_SESSION['a']['definerToBe'])) {
                    unset($_SESSION['a']['definerToBe']);
                }
                if (isset($_SESSION['a']['afType'])) {
                    unset($_SESSION['a']['afType']);
                }
                $this->tabs = 2;
            }
        } else {
            $this->tabs = 0;
        }
        if (isset($_SESSION['a']['actionChoosed'])) {
            switch ($_SESSION['a']['actionChoosed']) {
                case 'listAdvancedFeatureByChosenDefiner':
                    $this->fileToStore['relevant'] = 'info';
                    if (isset($_REQUEST['dbs'])) {
                        $_SESSION['a']['dbs'] = $_REQUEST['dbs'];
                        if (isset($_SESSION['a']['definerToModify'])) {
                            unset($_SESSION['a']['definerToModify']);
                        }
                        if (isset($_SESSION['a']['definerToBe'])) {
                            unset($_SESSION['a']['definerToBe']);
                        }
                        if (isset($_SESSION['a']['afType'])) {
                            unset($_SESSION['a']['afType']);
                        }
                    }
                    if (isset($_SESSION['a']['dbs'])) {
                        $this->tabs = 3;
                    }
                    if (isset($_REQUEST['definerToModify'])) {
                        $_SESSION['a']['definerToModify'] = $_REQUEST['definerToModify'];
                        $_SESSION['a']['definerToBe']     = $_REQUEST['definerToModify'];
                        if (isset($_SESSION['a']['afType'])) {
                            unset($_SESSION['a']['afType']);
                        }
                    }
                    if (isset($_SESSION['a']['definerToModify'])) {
                        $this->tabs = 4;
                    }
                    if (isset($_REQUEST['afType'])) {
                        $_SESSION['a']['afType'] = implode('|', $_REQUEST['afType']);
                    }
                    if (isset($_SESSION['a']['afType'])) {
                        $this->tabs = 5;
                    }
                    break;
                case 'modifyDefinerOfAdvancedFeatures':
                    $this->fileToStore['relevant'] = ['do', 'undo'];
                    if (isset($_REQUEST['dbs'])) {
                        $_SESSION['a']['dbs'] = $_REQUEST['dbs'];
                        unset($_SESSION['a']['definerToModify']);
                        unset($_SESSION['a']['definerToBe']);
                        unset($_SESSION['a']['afType']);
                    }
                    if (isset($_SESSION['a']['dbs'])) {
                        $this->tabs = 3;
                    }
                    if (isset($_REQUEST['definerToModify'])) {
                        $_SESSION['a']['definerToModify'] = $_REQUEST['definerToModify'];
                        unset($_SESSION['a']['definerToBe']);
                        unset($_SESSION['a']['afType']);
                    }
                    if (isset($_SESSION['a']['definerToModify'])) {
                        $this->tabs = 4;
                    }
                    if (isset($_REQUEST['definerToBe'])) {
                        $this->tabs                   = 4;
                        $_SESSION['a']['definerToBe'] = $_REQUEST['definerToBe'];
                        unset($_SESSION['a']['afType']);
                    }
                    if (isset($_SESSION['a']['definerToBe'])) {
                        $this->tabs = 5;
                    }
                    if (isset($_REQUEST['afType'])) {
                        $_SESSION['a']['afType'] = implode('|', $_REQUEST['afType']);
                    }
                    if (isset($_SESSION['a']['afType'])) {
                        $this->tabs = 6;
                    }
                    break;
            }
        }
        if (isset($_SESSION['a']['definerToModify'])) {
            $this->definer['oldDefinerPlain']  = $_SESSION['a']['definerToModify'];
            $this->definer['oldDefinerSql']    = '`'
                . str_replace('@', '`@`', $_SESSION['a']['definerToModify'])
                . '`';
            $this->definer['oldDefinerQuoted'] = '\''
                . str_replace('@', '\'@\'', $_SESSION['a']['definerToModify'])
                . '\'';
        }
        if (isset($_SESSION['a']['definerToBe'])) {
            $this->definer['newDefinerSql'] = '`'
                . str_replace('@', '`@`', $_SESSION['a']['definerToBe'])
                . '`';
        }
    }

    private function closeFile($fileKind)
    {
        fclose($this->fileToStore[$fileKind]);
    }

    final protected function connectToMySql()
    {
        $cfg = $this->configuredMySqlServers();
        if (is_null($this->mySQLconfig)) {
            $this->mySQLconfig = [
                'host'     => $cfg[$_SESSION['a']['serverChoosed']]['host'],
                'port'     => $cfg[$_SESSION['a']['serverChoosed']]['port'],
                'user'     => $cfg[$_SESSION['a']['serverChoosed']]['user'],
                'password' => $cfg[$_SESSION['a']['serverChoosed']]['password'],
                'database' => $cfg[$_SESSION['a']['serverChoosed']]['database'],
            ];
        }
        if (is_null($this->mySQLconnection)) {
            extract($this->mySQLconfig);
            $this->mySQLconnection = new \mysqli($host, $user, $password, $database, $port);
            if ($this->mySQLconnection->connect_error) {
                $erNo  = $this->mySQLconnection->connect_errno;
                $erMsg = $this->mySQLconnection->connect_error;
                echo $this->getTimestamp()
                . sprintf(_('i18n_Feedback_ConnectionError'), $erNo, $erMsg, $host, $port, $user, $database);
            }
        }
    }

    private function createFile($fileKind, $fileNameTermination)
    {
        $resultsDir                   = $this->configuredFolderForResults();
        $fileName                     = $resultsDir . date('Y-m-d--H-i-s') . '__' . $fileNameTermination . '.sql';
        $this->fileToStore[$fileKind] = fopen($fileName, 'w');
    }

    private function getInterface()
    {
        $sReturn   = [];
        $sReturn[] = '<!DOCTYPE html>'
            . '<html lang="' . str_replace('_', '-', $_SESSION['lang']) . '">'
            . '<head>'
            . '<meta charset="utf-8" />'
            . '<meta name="viewport" content="width=device-width" />'
            . '<title>' . $this->applicationFlags['Name'] . '</title>'
            . $this->setCssFile('css/main.css')
            . $this->setJavascriptFile('js/tabber.min.js')
            . '</head>'
            . '<body>'
            . $this->setJavascriptContent('document.write(\'<style type="text/css">.tabber{display:none;}</style>\');')
            . '<h1>' . $this->applicationFlags['Name'] . '</h1>'
            . $this->setHeaderLanguages()
            . '<div class="tabber" id="tab">';
        $this->configDefaults();
        for ($counter = 0; $counter <= $this->tabs; $counter++) {
            $sReturn[] = $this->getInterfaceSteps($counter);
        }
        $sReturn[] = '<div class="tabbertab" id="tab0" title="' . _('i18n_TabDebug') . '">'
            . (isset($_REQUEST) ? 'REQUEST = ' . $this->setArray2json($_REQUEST) : '')
            . '<hr/>' . (isset($_SESSION) ? 'SESSION = ' . $this->setArray2json($_SESSION) : '')
            . '<hr/>' . 'actions SESSION counted = ' . (isset($_SESSION['a']) ? count($_SESSION['a']) : 0)
            . '<br/>' . 'total SESSION counted = ' . count($_SESSION)
            . '</div><!-- tab0 end -->'
            . '</div><!-- tabber end -->'
            . '</body>'
            . '</html>';
        return implode('', $sReturn);
    }

    private function getInterfaceSteps($stepNo = 0)
    {
        switch ($stepNo) {
            case 0:
                $sReturn[] = $this->getStepServer(_('i18n_TabServer'));
                break;
            case 1:
                $sReturn[] = $this->getStepAction(_('i18n_TabAction'));
                break;
            default:
                $nextSteps = null;
                switch ($_SESSION['a']['actionChoosed']) {
                    case 'listAdvancedFeatureByChosenDefiner':
                        $nextSteps = [
                            2 => $this->getStepAssessVariations(_('i18n_TabAssesVariations')),
                            3 => $this->getStepVariationsToChooseFrom(_('i18n_TabVariations')),
                            4 => $this->getStepAdvancedFeatureToApplyTo(_('i18n_TabAdvancedFeature')),
                            5 => $this->getStepActionDetails('Action details'),
                        ];
                        break;
                    case 'modifyDefinerOfAdvancedFeatures':
                        $nextSteps = [
                            2 => $this->getStepAssessVariations(_('i18n_TabAssesVariations')),
                            3 => $this->getStepVariationsToChooseFrom(_('i18n_TabVariations')),
                            4 => $this->getStepDefineNewValue('Provide new definer'),
                            5 => $this->getStepAdvancedFeatureToApplyTo(_('i18n_TabAdvancedFeature')),
                            6 => $this->getStepActionDetails('Action details'),
                        ];
                        break;
                    default:
                        break;
                }
                if (is_null($nextSteps)) {
                    $sReturn[] = '';
                } else {
                    $sReturn[] = $nextSteps[$stepNo];
                }
                break;
        }
        return implode('', $sReturn);
    }

    private function getStepAction($stepTitle)
    {
        $sReturn   = [];
        $sReturn[] = '<p>' . _('i18n_TabAction_ChooseActionToTake') . '</p>'
            . '<form action="' . $_SERVER['PHP_SELF'] . '" method="post">';
        if (isset($_SESSION['a']['actionChoosed'])) {
            $valueSelected = $_SESSION['a']['actionChoosed'];
        } else {
            $valueSelected = '';
        }
        foreach ($this->actions as $key => $value) {
            $sReturn[] = '<input type="radio" name="actionChoosed" value="'
                . $key . '" id="' . $key . '"'
                . ($valueSelected == $key ? ' checked' : '') . ' />'
                . '<label for="' . $key . '" style="width:auto;">'
                . $value . '</label><br/>';
        }
        $sReturn[] = '<input type="submit" style="display:block;" '
            . 'value="' . _('i18n_TabAction_IchoseProceed') . '" />'
            . '</form>';
        return '<div class="tabbertab'
            . (count($_SESSION['a']) == 1 ? ' tabbertabdefault' : '')
            . '" id="tab2" title="' . $stepTitle . '">'
            . implode('', $sReturn)
            . '</div><!-- tab1 end -->';
    }

    private function getStepActionDetails($stepTitle = '')
    {
        $sReturn = [];
        switch ($_SESSION['a']['actionChoosed']) {
            case 'listAdvancedFeatureByChosenDefiner':
            case 'modifyDefinerOfAdvancedFeatures':
                if (isset($_SESSION['a']['afType'])) {
                    $sReturn[]           = '<div class="tabber" id="tabAF">';
                    $advancedFeatureType = explode('|', $_SESSION['a']['afType']);
                    if (is_array($this->fileToStore['relevant'])) {
                        foreach ($this->fileToStore['relevant'] as $value) {
                            $this->createFile($value, $value);
                            $this->setFileHeader($value);
                        }
                    } else {
                        $this->createFile($this->fileToStore['relevant'], $this->fileToStore['relevant']);
                        $this->setFileHeader($this->fileToStore['relevant']);
                    }
                    foreach ($advancedFeatureType as $value) {
                        switch ($value) {
                            case 'Events':
                                $sReturn[] = '<div class="tabbertab" id="tabEvents" title="' . $value . '">'
                                    . $this->actOnAdvancedMySqlFeatureEvents()
                                    . '</div><!-- tabEvents end -->';
                                break;
                            case 'Routines':
                                $sReturn[] = '<div class="tabbertab" id="tabEvents" title="' . $value . '">'
                                    . $this->actOnAdvancedMySqlFeatureStoredRoutines()
                                    . '</div><!-- tabEvents end -->';
                                break;
                            case 'Triggers':
                                $sReturn[] = '<div class="tabbertab" id="tabEvents" title="' . $value . '">'
                                    . $this->actOnAdvancedMySqlFeatureTriggers()
                                    . '</div><!-- tabEvents end -->';
                                break;
                            case 'Views':
                                $sReturn[] = '<div class="tabbertab" id="tabEvents" title="' . $value . '">'
                                    . $this->actOnAdvancedMySqlFeatureViews()
                                    . '</div><!-- tabEvents end -->';
                                break;
                        }
                        $this->setFileContent([
                            'FileKind' => $this->fileToStore['relevant'],
                            'Query'    => '',
                        ]);
                    }
                    if (is_array($this->fileToStore['relevant'])) {
                        foreach ($this->fileToStore['relevant'] as $value) {
                            $this->setFileFooter($value);
                            $this->closeFile($value);
                        }
                    } else {
                        $this->setFileFooter($this->fileToStore['relevant']);
                        $this->closeFile($this->fileToStore['relevant']);
                    }
                    $sReturn[] = '</div><!-- tabAF end -->';
                }
                break;
        }
        return '<div class="tabbertab'
            . (count($_SESSION['a']) == 6 ? ' tabbertabdefault' : '')
            . '" id="tab6" title="' . $stepTitle . '">'
            . implode('', $sReturn)
            . '</div><!-- tab5 end -->';
    }

    private function getStepAdvancedFeatureToApplyTo($stepTitle = '')
    {
        $sReturn = [];
        switch ($_SESSION['a']['actionChoosed']) {
            case 'listAdvancedFeatureByChosenDefiner':
            case 'modifyDefinerOfAdvancedFeatures':
                $listOfDefinersByType = $this->runQuery($this->storedQuery('AssesmentListOfDefinerByType', [
                        'dbs'             => $_SESSION['a']['dbs'],
                        'definerToModify' => $_SESSION['a']['definerToModify']
                    ]), 'fullArray3');
                $sReturn[]            = '<p>' . _('i18n_TabAdvancedFeature_Choose') . '</p>'
                    . '<form action="' . $_SERVER['PHP_SELF'] . '" method="post">';
                if (isset($_SESSION['a']['afType'])) {
                    $valueSelected = $_SESSION['a']['afType'];
                } else {
                    $valueSelected = '';
                }
                foreach ($listOfDefinersByType as $value) {
                    $sReturn[] = '<input type="checkbox" name="afType[]" '
                        . 'value="' . $value['Type'] . '" id="af_' . $value['Type'] . '"'
                        . ($valueSelected == $value['Type'] ? ' checked' : '')
                        . ($value['No.'] == 0 ? ' disabled' : '') . ' />'
                        . '<label for="af_' . $value['Type'] . '" '
                        . 'style="margin-left:5px;'
                        . ($value['No.'] == 0 ? 'color:grey;' : '') . '">'
                        . sprintf(_('i18n_TabAdvancedFeature_Choice_' . $value['Type']), $value['No.'])
                        . '</label><br/>';
                }
                $sReturn[] = '<input type="submit" style="display:block;" value="'
                    . _('i18n_TabAdvancedFeature_IchoseProceed') . '" />'
                    . '</form>';
                break;
        }
        return '<div class="tabbertab'
            . (count($_SESSION['a']) == 5 ? ' tabbertabdefault' : '')
            . '" id="tab5" title="' . $stepTitle . '">'
            . implode('', $sReturn)
            . '</div><!-- tab5 end -->';
    }

    private function getStepAssessVariations($stepTitle)
    {
        $cfg     = $this->configuredMySqlServers();
        $sReturn = [];
        switch ($_SESSION['a']['actionChoosed']) {
            case 'listAdvancedFeatureByChosenDefiner':
            case 'modifyDefinerOfAdvancedFeatures':
                $listOfDatabases = $this->runQuery($this->storedQuery('ListOfDatabases'), 'fullArray3');
                if (is_null($listOfDatabases)) {
                    $value2use = $cfg[$_SESSION['a']['serverChoosed']]['verbose'];
                    $sReturn[] = '<p>'
                        . sprintf(_('i18n_TabAssesVariations_NoValuesToChooseFrom'), $value2use)
                        . '</p>';
                } else {
                    if (isset($_SESSION['a']['dbs'])) {
                        $choosenDbs = $_SESSION['a']['dbs'];
                    } else {
                        $choosenDbs = [];
                    }
                    $sReturn[] = '<p>' . _('i18n_TabAssesVariations_ChooseDatabases') . '</p>'
                        . '<form action="' . $_SERVER['PHP_SELF'] . '" method="post">'
                        . '<select name="dbs[]" multiple size="'
                        . min([15, count($listOfDatabases)]) . '">';
                    foreach ($listOfDatabases as $value) {
                        $sReturn[] = '<option value="' . $value['SCHEMA_NAME'] . '"'
                            . (in_array($value['SCHEMA_NAME'], $choosenDbs) ? ' selected' : '')
                            . '>' . $value['SCHEMA_NAME'] . '</option>';
                    }
                    $sReturn[] = '</select>'
                        . '<div style="color:#C7C7C7;font-style:italic;">'
                        . _('i18n_Feedback_MultipleSelectionAdvise') . '</div>'
                        . '<input type="submit" style="display:block;" '
                        . 'value="' . _('i18n_TabAssesVariations_IchoseProceed') . '" />'
                        . '</form>';
                }
                break;
            default:
                $sReturn[] = _('i18n_Feedback_UndefinedAction');
                break;
        }
        return '<div class="tabbertab'
            . (count($_SESSION['a']) == 2 ? ' tabbertabdefault' : '')
            . '" id="tab2" title="' . $stepTitle . '">'
            . implode('', $sReturn)
            . '</div><!-- tab2 end -->';
    }

    private function getStepDefineNewValue($stepTitle)
    {
        $sReturn = [];
        switch ($_SESSION['a']['actionChoosed']) {
            case 'modifyDefinerOfAdvancedFeatures':
                $listOfUsersWithPrivileges = $this->runQuery($this->storedQuery('ListOfUsersWithAssignedPrivileges', [
                        'definerToModify' => $this->definer['oldDefinerQuoted']
                    ]), 'fullArray3WithDisplay');
                if (is_array($listOfUsersWithPrivileges)) {
                    $sReturn[] = '<p>Choose one of the users listed below:</p>'
                        . '<form action="' . $_SERVER['PHP_SELF'] . '" method="post">';
                    if (isset($_SESSION['a']['definerToBe'])) {
                        $valueSelected = $_SESSION['a']['definerToBe'];
                    } else {
                        $valueSelected = '';
                    }
                    foreach ($listOfUsersWithPrivileges as $value) {
                        $sReturn[] = '<input type="radio" name="definerToBe" '
                            . 'value="' . $value['Grantee'] . '" id="dTB_' . $value['Grantee'] . '"'
                            . ($valueSelected == $value['Grantee'] ? ' checked' : '')
                            . ' />'
                            . '<label for="dTB_' . $value['Grantee'] . '" style="margin-left:5px;">'
                            . $value['Grantee'] . '</label><br/>';
                    }
                    $sReturn[] = '<input type="submit" style="display:block;" '
                        . 'value="That is my new Definer of choice, proceed!" />'
                        . '</form>';
                } else {
                    $sReturn[] = '<p style="color:red;">There is no other user with privileges assigned '
                        . 'at this monent to choose from...</p>'
                        . '<p>So this is an end road, nothing more to do from this point on!</p>';
                }
                break;
        }
        return '<div class="tabbertab'
            . (count($_SESSION['a']) == 4 ? ' tabbertabdefault' : '')
            . '" id="tab4" title="' . $stepTitle . '">'
            . implode('', $sReturn)
            . '</div><!-- tab4 end -->';
    }

    private function getStepServer($stepTitle)
    {
        $cfg       = $this->configuredMySqlServers();
        $sReturn   = [];
        $sReturn[] = '<p>' . _('i18n_TabServer_ChooseServerToConnectTo') . '</p>'
            . '<form action="' . $_SERVER['PHP_SELF'] . '" method="post">';
        if (isset($_SESSION['a']['serverChoosed'])) {
            $valueSelected = $_SESSION['a']['serverChoosed'];
        } else {
            $valueSelected = '';
        }
        foreach ($cfg as $key => $value) {
            $sReturn[] = '<input type="radio" name="serverChoosed" value="'
                . $key . '" id="srv' . $key . '"'
                . ($valueSelected == $key ? ' checked' : '') . ' />'
                . '<label for="srv' . $key . '" style="width:auto;">'
                . $value['verbose'] . '</label><br/>';
        }
        $sReturn[] = '<input type="submit" style="display:block;" '
            . 'value="' . _('i18n_TabServer_IchoseProceed') . '" />'
            . '</form>';
        return '<div class="tabbertab'
            . (!isset($_SESSION) ? ' tabbertabdefault' : '')
            . '" id="tab1" title="' . $stepTitle . '">'
            . implode('', $sReturn)
            . '</div><!-- tab1 end -->';
    }

    private function getStepVariationsToChooseFrom($stepTitle)
    {
        $sReturn = [];
        switch ($_SESSION['a']['actionChoosed']) {
            case 'listAdvancedFeatureByChosenDefiner':
            case 'modifyDefinerOfAdvancedFeatures':
                $listOfDefiners = $this->runQuery($this->storedQuery('AssesmentListOfDefiner', [
                        'dbs' => $_SESSION['a']['dbs']
                    ]), 'fullArray3');
                if (is_array($listOfDefiners)) {
                    asort($listOfDefiners);
                }
                if (is_null($listOfDefiners)) {
                    $sReturn[] = '<p style="color:red;">'
                        . _('i18n_TabVariations__NoValuesToChooseFrom')
                        . '</p>';
                } else {
                    $sReturn[] = '<p>' . _('i18n_TabVariations__ChooseValue') . '</p>'
                        . '<form action="' . $_SERVER['PHP_SELF'] . '" method="post">';
                    if (isset($_SESSION['a']['definerToModify'])) {
                        $valueSelected = $_SESSION['a']['definerToModify'];
                    } else {
                        $valueSelected = '';
                    }
                    foreach ($listOfDefiners as $value) {
                        $sReturn[] = '<br/><input type="radio" name="definerToModify" '
                            . 'value="' . $value['DEFINER'] . '" id="' . $value['DEFINER'] . '"'
                            . ($valueSelected == $value['DEFINER'] ? ' checked' : '')
                            . ' />'
                            . '<label for="' . $value['DEFINER'] . '">'
                            . $value['DEFINER'] . '</label>';
                    }
                    $sReturn[] = '<input type="submit" style="display:block;" '
                        . 'value="' . _('i18n_TabVariations__IchoseProceed') . '" />'
                        . '</form>';
                }
                break;
        }
        return '<div class="tabbertab'
            . (count($_SESSION['a']) == 3 ? ' tabbertabdefault' : '')
            . '" id="tab3" title="' . $stepTitle . '">'
            . implode('', $sReturn)
            . '</div><!-- tab3 end -->';
    }

    private function handleLocalizationNIS()
    {
        if (isset($_GET['lang'])) {
            $_SESSION['lang'] = filter_var($_GET['lang'], FILTER_SANITIZE_STRING);
        } elseif (!isset($_SESSION['lang'])) {
            $_SESSION['lang'] = $this->applicationFlags['default_language'];
        }
        /* to avoid potential language injections from other applications that do not applies here */
        if (!in_array($_SESSION['lang'], array_keys($this->applicationFlags['available_languages']))) {
            $_SESSION['lang'] = $this->applicationFlags['default_language'];
        }
        T_setlocale(LC_MESSAGES, $_SESSION['lang']);
        if (function_exists('bindtextdomain')) {
            bindtextdomain(self::LOCALE_DOMAIN, realpath('./locale'));
            bind_textdomain_codeset(self::LOCALE_DOMAIN, 'UTF-8');
            textdomain(self::LOCALE_DOMAIN);
        } else {
            echo 'No gettext extension is active in current PHP configuration!';
        }
    }

    final protected function runQuery($sQuery, $sReturnType = null, $prefixKey = null)
    {
        $this->connectToMySql();
        if (is_null($this->mySQLconnection)) {
            $this->queueDetails .= '<p style="color:red;">' . $this->getTimestamp()
                . 'As there is no connection to MySQL server, I could not run given query '
                . '(`' . htmlentities($sQuery) . '` on `' . __FUNCTION__ . '`)...</p>';
            return null;
        }
        switch ($sReturnType) {
            case 'noRunJustDisplay':
                $this->queueDetails .= '<p style="color:grey;">' . $this->getTimestamp()
                    . 'The query <i>' . htmlentities($sQuery) . '</i> is desired to be displayed but not executed '
                    . '($sReturnType = "' . $sReturnType . '")</p>';
                return '';
                break;
            case 'runWithDisplayFirst':
                $this->queueDetails .= '<p style="color:grey;">' . $this->getTimestamp()
                    . 'The query <i>' . htmlentities($sQuery) . '</i> is desired to be executed '
                    . 'w. $sReturnType = "' . $sReturnType . '"</p>';
                break;
        }
        $result = $this->mySQLconnection->query($sQuery);
        if ($result) {
            if (is_object($result)) {
                $iNoOfRows = $result->num_rows;
                if (($sReturnType != 'runWithDisplayFirst') && (strpos($sReturnType, 'WithDisplay') !== false)) {
                    $this->queueDetails .= '<p style="color:green;">' . $this->getTimestamp()
                        . 'The query <i>' . htmlentities($sQuery) . '</i> has been executed '
                        . 'w. $sReturnType = "' . $sReturnType . '" (' . $iNoOfRows . ' record(s) resulted)</p>';
                }
            }
            switch ($sReturnType) {
                case 'array_pairs_key_valueWithDisplay':
                case 'array_pairs_key_value':
                    if ($iNoOfRows == 1) {
                        $array2return = null;
                        for ($counter = 0; $counter < $iNoOfRows; $counter++) {
                            $line           = $result->fetch_row();
                            $finfo          = $result->fetch_fields();
                            $column_counter = 0;
                            foreach ($finfo as $value) {
                                $key                = $value->name;
                                $array2return[$key] = $line[$column_counter];
                                $column_counter += 1;
                            }
                        }
                        $result->close();
                        return $array2return;
                    } else {
                        $result->close();
                        return false;
                    }
                    break;
                case 'fullArray3WithDisplay':
                case 'fullArray3':
                    if ($iNoOfRows == 0) {
                        if (is_null($prefixKey)) {
                            $aReturn = null;
                        } else {
                            $aReturn[$prefixKey] = null;
                        }
                    } else {
                        $counter2 = 0;
                        for ($counter = 0; $counter < $iNoOfRows; $counter++) {
                            $line           = $result->fetch_row();
                            $finfo          = $result->fetch_fields();
                            $column_counter = 0;
                            foreach ($finfo as $value) {
                                $key = $value->name;
                                if (is_null($prefixKey)) {
                                    $aReturn[$counter2][$key] = $line[$column_counter];
                                } else {
                                    $aReturn[$prefixKey][$counter2][$key] = $line[$column_counter];
                                }
                                $column_counter += 1;
                            }
                            $counter2 += 1;
                        }
                    }
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
            $this->queueDetails .= '<p style="color:red;">' . $this->getTimestamp()
                . 'There was an error running the query ' . htmlentities($sQuery) . ' as `'
                . $this->mySQLconfig['user'] . '` on `' . $this->mySQLconfig['user']
                . '` (<b>' . $this->mySQLconnection->error . '</b>)</p>';
        }
    }

    protected function setFeedbackNoMySqlConnection($thingsToAnalyze)
    {
        $this->connectToMySql();
        if (is_null($this->mySQLconnection)) {
            extract($this->mySQLconfig);
            echo '<p style="color:red;">' . $this->getTimestamp()
            . sprintf(_('i18n_Feedback_AnalyzeImpossible'), $thingsToAnalyze, $host, $port, $user, $database)
            . '</p>';
        }
    }

    private function setFileContent($features)
    {
        if (is_array($features['FileKind'])) {
            foreach ($features['FileKind'] as $value) {
                if (isset($features['Explanation'])) {
                    fwrite($this->fileToStore[$value], '-- ' . $features['Explanation'] . PHP_EOL);
                }
                if (isset($features['Query'])) {
                    fwrite($this->fileToStore[$value], $features['Query'] . PHP_EOL);
                }
                if (isset($features['Explanation'])) {
                    fwrite($this->fileToStore[$value], '-- ' . PHP_EOL . PHP_EOL);
                }
            }
        } else {
            if (isset($features['Explanation'])) {
                fwrite($this->fileToStore[$features['FileKind']], '-- ' . $features['Explanation'] . PHP_EOL);
            }
            if (isset($features['Query'])) {
                fwrite($this->fileToStore[$features['FileKind']], $features['Query'] . PHP_EOL);
            }
            if (isset($features['Explanation'])) {
                fwrite($this->fileToStore[$features['FileKind']], '-- ' . PHP_EOL . PHP_EOL);
            }
        }
    }

    private function setFileFooter($fileKind)
    {
        $sLineToWrite = [
            '',
            '',
            '/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;',
            '/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;',
            '/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;',
            '/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;',
            '/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;',
            '/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;',
            '/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;',
            '',
            '-- File completed at                   ' . date('Y-m-d H:i:s'),
        ];
        foreach ($sLineToWrite as $value) {
            fwrite($this->fileToStore[$fileKind], $value . PHP_EOL);
        }
    }

    private function setFileHeader($fileKind)
    {
        $architectureType = [
            'AMD64' => 'x64 (64 bit)',
            'i386'  => 'x86 (32 bit)',
        ];
        if (in_array(php_uname('m'), array_keys($architectureType))) {
            $serverMachineType = $architectureType[php_uname('m')];
        } else {
            $serverMachineType = php_uname('m');
        }
        $sLineToWrite = [
            '-- File started at                     ' . date('Y-m-d H:i:s'),
            '',
            '-- Traceability info is displayed below --',
            '--',
            '-- OS Ip                               ' . $_SERVER['SERVER_ADDR'],
            '-- OS Name                             ' . php_uname('s'),
            '-- OS Host                             ' . php_uname('n'),
            '-- OS Release                          ' . php_uname('r'),
            '-- OS Version                          ' . php_uname('v'),
            '-- OS Machine Type (from PHP)          ' . $serverMachineType,
            '-- PHP version used                    ' . phpversion(),
            '-- Zend engine version used            ' . zend_version(),
            '-- Web server used                     ' . $_SERVER['SERVER_SOFTWARE'],
            '-- Client IP direct                    ' . $_SERVER['REMOTE_ADDR'],
            '-- Client Browser Agent                ' . $_SERVER['HTTP_USER_AGENT'],
            '--',
            '',
            '',
            '-- MySQL info is displayed below (version is very important for compatibility reasons) --',
            '--',
            '-- MySQL host                          ' . $this->mySQLconfig['host'],
            '-- MySQL port                          ' . $this->mySQLconfig['port'],
            '-- MySQL username                      ' . $this->mySQLconfig['user'],
            '-- MySQL server version                ' . $this->mySQLconnection->server_info,
            '--',
            '',
            '',
            '-- Application choices made to generate this file --',
            '--',
            '-- Action chose                        ' . $_SESSION['a']['actionChoosed'],
            '-- MySQL server # (from stored config.)' . $_SESSION['a']['serverChoosed'],
            '-- MySQL databases chose               ' . implode(', ', $_SESSION['a']['dbs']),
            '-- Definer to modify from              ' . $_SESSION['a']['definerToModify'],
            '-- Definer to modify into              ' . $_SESSION['a']['definerToBe'],
            '-- Advanced features to apply to       ' . str_replace('|', ', ', $_SESSION['a']['afType']),
            '--',
            '',
            '',
            '/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;',
            '/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;',
            '/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;',
            '/*!40101 SET NAMES utf8 */;',
            '/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;',
            '/*!40103 SET TIME_ZONE=\'+00:00\' */;',
            '/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;',
            '/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;',
            '/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE=\'NO_AUTO_VALUE_ON_ZERO\' */;',
            '/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;',
            '',
            '',
        ];
        foreach ($sLineToWrite as $value) {
            fwrite($this->fileToStore[$fileKind], $value . PHP_EOL);
        }
    }

    private function setHeaderLanguages()
    {
        $sReturn = [];
        foreach ($this->applicationFlags['available_languages'] as $key => $value) {
            if ($_SESSION['lang'] === $key) {
                $sReturn[] = '<b>' . $value . '</b>';
            } else {
                $sReturn[] = '<a href="?'
                    . (isset($_REQUEST) ? $this->setArray2String4Url('&amp;', $_REQUEST, ['lang']) . '&amp;' : '')
                    . 'lang=' . $key . '">' . $value . '</a>';
            }
        }
        return '<span class="language_box">'
            . implode(' | ', $sReturn)
            . '</span>';
    }

    /**
     * Place for all MySQL queries used within current class
     *
     * @param string $label
     * @param array $given_parameters
     * @return string
     */
    final protected function storedQuery($label, $given_parameters = null)
    {
        $sReturn = $this->setRightQuery($label, $given_parameters);
        if ($sReturn === false) {
            echo $this->setFeedback(0, _('i18n_Feedback_Error'), sprintf(_('i18n_Feedback_UndefinedQuery'), $label));
        }
        return $sReturn;
    }
}
