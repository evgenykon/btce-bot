<?php
/**
 * User: effus
 * Date: 18.12.13
 * Time: 8:39
 * checkOrderHistory.php
 */

include_once 'classes/exceptions.php';
include_once 'classes/lib.php';
include_once 'classes/storage.php';
include_once 'classes/coins.php';
include_once 'classes/funds.php';
include_once 'classes/pairs.php';
include_once 'classes/helpers.php';
include_once 'classes/mysql.php';
include_once 'classes/logic.php';
include_once 'params.php';

$storage = Loader::storage();
$api = Loader::api($storage->data->key, $storage->data->secret);
$dbs = $startParams['mysql'];
$db = new MysqlDb($dbs['host'],$dbs['user'],$dbs['password'],$dbs['dbname']);
log_msg('get all orders');
$result = $api->getAllHistory();
if ($result) {
    $db->updateOrderHistory($result);
}