<?php
/**
 * Created by PhpStorm.
 * User: Effus
 * Date: 07.12.13
 * Time: 15:41
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

class Zond extends Logic {

    private $api /** @var BTCeAPI $api */;
    private $storage /** @var Storage $storage */;
    private $strategy /** @var StrategyConf $strategy */;
    private $pairs /** @var TradePairs $pairs */;
    private $funds; /** @var Funds $funds */
    private $weights = array();
    private $db;

    public function __construct() {
        $this->storage = Loader::storage();
        $this->api = Loader::api($this->storage->data->key, $this->storage->data->secret);
        $this->funds = new Funds($this->storage->data->funds);
        $this->pairs = new TradePairs($this->storage->data->pairs);
        $this->strategy = new StrategyConf();
    }

    public function init($params) {
        parent::init($params);
        $dbs = $params['mysql'];
        $this->db = new MysqlDb($dbs['host'],$dbs['user'],$dbs['password'],$dbs['dbname']);
    }


    private function getFunds() {
        $this->funds->load($this->api);
        $funds = $this->funds;
        foreach($funds as $coin) {
            if (is_object($coin) && get_class ($coin) == 'Coin') {
                $coin->active = 1;
                $this->db->updateFund($coin);
            }
        }
    }

    private function getPairs() {
        foreach($this->pairs->list as $pairKey=>$pair) {
            $this->pairs->list[$pairKey]->enabled = true;
        }
        $this->pairs->setPrevPair();
        $this->pairs->load($this->api);
        $this->storage->data->pairs = $this->pairs->export();
        $this->storage->save();
        foreach($this->pairs->list as $pairKey=>$pair) {
            $pair->refreshRequired = true;
            $pair->enabled = true;
            $this->db->updatePairPrice($pair);
        }
    }

    public function run() {
        try {
            $this->getFunds();
            $this->getPairs();
        } catch(Exception $e) {
            log_msg('run >> exception: ('.$e->getCode().') '.$e->getMessage());
        }

    }

}
$logic = new Zond();
$logic->init($startParams);
$logic->run();

?>
