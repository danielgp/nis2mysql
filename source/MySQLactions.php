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
class MySQLactions extends ResultFile
{

    use \danielgp\common_lib\CommonCode;

    protected $mySQLconfig     = null;
    protected $mySQLconnection = null;
    protected $queryClass;
    protected $queueDetails    = '';

    public function __construct()
    {
        $this->queryClass = new AppQueries();
    }

    protected function connectOrSetFeedbackIfNoMySqlConnection($thingsToAnalyze, $type = 'analyze', $ftrs = null)
    {
        $this->connectToMySql();
        if (is_null($this->mySQLconnection)) {
            extract($this->mySQLconfig);
            switch ($type) {
                case 'analyze':
                    $feedback          = _('i18n_Feedback_AnalyzeImpossible');
                    $feedbackToProvide = sprintf($feedback, $thingsToAnalyze, $host, $port, $user, $db);
                    break;
                case 'run':
                    $feedback          = _('i18n_Feedback_RunImpossible');
                    $feedbackToProvide = sprintf($feedback, $ftrs['query'], $host, $port, $user, $db);
                    break;
                default:
                    $feedbackToProvide = '';
                    break;
            }
            echo '<p style="color:red;">' . $this->getTimestamp() . $feedbackToProvide . '</p>';
        }
    }

    protected function connectToMySql()
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

    protected function runQuery($sQuery, $sReturnType = null, $prefixKey = null)
    {
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
                    $aReturn = $this->setQuery2ServerAndGetSimpleArray($result);
                case 'fullArray3WithDisplay':
                case 'fullArray3':
                    $aReturn = $this->setQuery2ServerAndGetFullArray($result, $prefixKey);
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

    private function setQuery2ServerAndGetFullArray($result, $prefixKey = null)
    {
        $aReturn   = [];
        $iNoOfRows = $result->num_rows;
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
        return $aReturn;
    }

    private function setQuery2ServerAndGetSimpleArray($result, $prefixKey = null)
    {
        $iNoOfRows = $result->num_rows;
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
        $sReturn = $this->queryClass->setRightQuery($label, $given_parameters);
        if ($sReturn === false) {
            echo $this->setFeedback(0, _('i18n_Feedback_Error'), sprintf(_('i18n_Feedback_UndefinedQuery'), $label));
        }
        return $sReturn;
    }
}
