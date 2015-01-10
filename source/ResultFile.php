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
 * Description of ResultFile
 *
 * @author E303778
 */
class ResultFile
{

    use UserConfiguration;

    protected $fileToStore = null;

    protected function closeFile($fileKind)
    {
        fclose($this->fileToStore[$fileKind]);
    }

    protected function createFile($fileKind, $fileNameTermination)
    {
        $resultsDir                   = $this->configuredFolderForResults();
        $fileName                     = $resultsDir . date('Y-m-d--H-i-s') . '__' . $fileNameTermination . '.sql';
        $this->fileToStore[$fileKind] = fopen($fileName, 'w');
    }

    protected function setFileContent($features)
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

    protected function setFileFooter($fileKind)
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

    protected function setFileHeader($fileKind, $featureArray)
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
            '-- MySQL host                          ' . $featureArray['MySQL_configuration']['host'],
            '-- MySQL port                          ' . $featureArray['MySQL_configuration']['port'],
            '-- MySQL username                      ' . $featureArray['MySQL_configuration']['user'],
            '-- MySQL server version                ' . $featureArray['MySQL_connection_info'],
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
}
