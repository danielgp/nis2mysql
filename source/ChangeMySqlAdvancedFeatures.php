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
class ChangeMySqlAdvancedFeatures extends MySQLactions
{

    use \danielgp\common_lib\CommonCode;

    const LOCALE_DOMAIN = 'nis_messages';

    private $applicationFlags;
    private $actions;
    private $definer = null;
    private $tabs    = 1;

    /**
     * Provides basic checking of requried parameters and initiates LDAP attributes
     *
     * @version 1.0.20131111
     *
     */
    public function __construct()
    {
        parent::__construct();
        $this->applicationFlags = [
            'available_languages' => [
                'en_US' => 'EN',
                'ro_RO' => 'RO',
            ],
            'default_language'    => $this->configuredDefaultLanguage(),
            'name'                => 'Normalize MySQL internal structures',
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

    private function actOnAdvancedMySqlFeatureStoredRoutines()
    {
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

    final private function actOnAdvancedMySqlFeatureTriggers()
    {
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

    final private function actOnAdvancedMySqlFeatureViews()
    {
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

    private function configDefaults()
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
                    $this->configDefaultsForListingDefiner();
                    break;
                case 'modifyDefinerOfAdvancedFeatures':
                    $this->configDefaultsForModifyDefiner();
                    break;
            }
        }
        if (isset($_SESSION['a']['definerToModify'])) {
            $this->definer['oldDefinerPlain']  = $_SESSION['a']['definerToModify'];
            $this->definer['oldDefinerSql']    = '`' . str_replace('@', '`@`', $_SESSION['a']['definerToModify'])
                . '`';
            $this->definer['oldDefinerQuoted'] = '\'' . str_replace('@', '\'@\'', $_SESSION['a']['definerToModify'])
                . '\'';
        }
        if (isset($_SESSION['a']['definerToBe'])) {
            $this->definer['newDefinerSql'] = '`' . str_replace('@', '`@`', $_SESSION['a']['definerToBe']) . '`';
        }
    }

    final private function configDefaultsForListingDefiner()
    {
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
    }

    final private function configDefaultsForModifyDefiner()
    {
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
    }

    private function getInterface()
    {
        $sReturn   = [];
        $sReturn[] = '<!DOCTYPE html>'
            . '<html lang="' . str_replace('_', '-', $_SESSION['lang']) . '">'
            . '<head>'
            . '<meta charset="utf-8" />'
            . '<meta name="viewport" content="width=device-width" />'
            . '<title>' . $this->applicationFlags['name'] . '</title>'
            . $this->setCssFile('css/main.css')
            . $this->setJavascriptFile('js/tabber.min.js')
            . '</head>'
            . '<body>'
            . $this->setJavascriptContent('document.write(\'<style type="text/css">.tabber{display:none;}</style>\');')
            . '<h1>' . $this->applicationFlags['name'] . '</h1>'
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
                            $this->setFileHeader($value, [
                                'MySQL_configuration'   => $this->mySQLconfig,
                                'MySQL_connection_info' => $this->mySQLconnection->server_info
                            ]);
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
}
