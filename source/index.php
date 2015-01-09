<?php

session_start();
require_once 'config.inc.php';
ini_set('error_log', $cfg['PhpLogDir'] . '/php' . PHP_VERSION_ID . 'errors_nis2mysql_' . date('Y-m-d') . '.log');
/**
 * Below 1 file is just a private list of MySQL servers
 * with usernames and clear text password,
 * therefore for privacy reasons will not be distributed
 * so you need to either comment its reference from your environment
 * or delete the reference
 *
 */
require_once 'private.config.inc.php';
require_once '../vendor/danielgp/common-lib/source/common.inc.php';
require_once '../vendor/inetsys/phpgettext/gettext.inc';
require_once 'class.inc.php';

$app = new danielgp\nis2mysql\ChangeMySqlAdvancedFeatures;
