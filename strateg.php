<?php
/**
 * Created by PhpStorm.
 * User: Effus
 * Date: 07.12.13
 * Time: 19:52
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

class Strateg {

    private $db;
    private $operationCoin;

    public function __construct() {

    }

    public function init($params) {
        $dbs = $params['mysql'];
        $this->db = new MysqlDb($dbs['host'],$dbs['user'],$dbs['password'],$dbs['dbname']);

    }

    public function run() {
        try {

            $DBconf = $this->db->loadActiveConfiguration();
            if (!isset($DBconf['baseCoin'])) {
                throw new BtceMysqlException('db conf not loaded');
            }
            print_r($DBconf);
            $this->operationCoin = new OperationCoin($DBconf['baseCoin'],0,new Coin('usd',0));
            $changePairs = Coin::getPairKeys($DBconf['baseCoin']);
            print_r($changePairs);
            if (!count($changePairs)) {
                throw new BtceLogicException('no change pairs for base coin: '.$DBconf['baseCoin']);
            }
            for($i=0;$i<count($changePairs);$i++) {
                $pairCode=$changePairs[$i];
                $history = $this->db->loadHistory(array(
                    sprintf("dt >= '%s'",date('Y-m-d H:i:s',strtotime('-10 minutes'))),
                    sprintf("dt <= '%s'",date('Y-m-d H:i:s')),
                    sprintf("pair = '%s'",$pairCode),
                ));
                print_r($history);
            }
        } catch(Exception $e) {
            log_msg('run >> exception: ('.$e->getCode().') '.$e->getMessage());
        }

    }

}

$logic = new Strateg();
$logic->init($startParams);
$logic->run();