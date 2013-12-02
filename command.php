<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Effus
 * Date: 01.12.13
 * Time: 22:47
 * To change this template use File | Settings | File Templates.
 */

include_once 'exceptions.php';
include_once 'lib.php';
include_once 'storage.php';
include_once 'logic.php';


if (count($argv)<2) {
    log_msg('Usage: php command.php <config-file.json>',true);
}

try {

    $logic = new Logic();
    $startParams = array(
        'baseCoin'          => new Coin('btc'),
        'expire_fund'       => 60*10,
        'expire_pairs'      => 60,
        'expire_pairs_life' => 60*2,
    );
    $logic->init($startParams);
    $logic->run();

} catch (Exception $e) {
    log_msg('Connection failed: '.$e->getMessage(),true);
}



