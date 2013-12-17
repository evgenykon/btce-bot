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
    private $pause;

    public function __construct() {

    }

    public function init($params) {
        $dbs = $params['mysql'];
        $this->pause = $params['strateg']['pause'];
        $this->db = new MysqlDb($dbs['host'],$dbs['user'],$dbs['password'],$dbs['dbname']);
    }

    public function run() {

        while(true) {
            log_msg('run >> wait for '.$this->pause.' seconds...');
            try {
                $lastUpdate = '';
                $updateTime = $this->db->getLastUpdateTime();
                if ($updateTime['dt'] != $lastUpdate) {
                    $lastUpdate = $updateTime['dt'];
                } else {
                    continue;
                }
                sleep($this->pause);
                log_msg('run >> loading data');
                $ms = microtime(true);

                $DBconf = $this->db->loadActiveConfiguration();
                if (!isset($DBconf['baseCoin'])) {
                    throw new BtceMysqlException('db conf not loaded');
                }

                /**
                 * 'ANL','BT','WP','SP','ST','WB','BB'
                 */

                if (!isset($trendPair)) {
                    $trendPair = '';
                }

                switch($DBconf['stage']) {
                    case 'ANL':
                        // analyze trends
                        log_msg('ANL: started');
                        $res = $this->db->getMaxFund();

                        $ms = round(microtime(true)-$ms,5);
                        log_msg('ANL >> funds loaded at '.$ms.' sec');

                        $fund = $res['code'];
                        $fundAmount = $res['amount'];

                        if ($fundAmount > 0) {
                            if ($DBconf['baseCoin'] != $fund) {
                                $this->db->setBaseCoin($fund);
                                log_msg('ANL >> change baseCoin to '.$fund.' with fund amount: '.$fundAmount);
                            }
                        } else {
                            log_msg('ANL >> expecting funds...');
                            $this->pause = 20;
                            continue;
                        }
                        // look at most tranded
                        $prices = $this->db->loadPrices('trend',$fund);
                        $maxtrend = 0;
                        for($i=0;$i<count($prices);$i++) {
                            if (abs($prices[$i]['trend']) > $maxtrend && abs($prices[$i]['trend']) > 3) {
                                $orders = $this->db->getLastOrder($trendPair);
                                if (count($orders)>0) {
                                    $maxtrend = $prices[$i]['trend'];
                                    $trendPair = $prices[$i]['pair'];
                                }
                            }
                        }
                        if (!$trendPair) {
                            log_msg('ANL >> no trands with our pairs, look anything...');
                            for($i=0;$i<count($prices);$i++) {
                                if (abs($prices[$i]['trend']) > $maxtrend && abs($prices[$i]['trend']) > 3) {
                                    $orders = $this->db->getLastOrder($trendPair);
                                    $maxtrend = $prices[$i]['trend'];
                                    $trendPair = $prices[$i]['pair'];
                                }
                            }
                        }
                        if ($maxtrend && $trendPair) {
                            log_msg('ANL >> get pair : '.$trendPair.' / trend: '.$maxtrend);
                            $coinA = substr($trendPair,0,3);
                            $coinB = substr($trendPair,4,3);
                            if ($maxtrend > 0) {
                                log_msg('ANL >> positive trend');
                                if ($fund == $coinB) { // XXX/FUND, up, need to buy XXX
                                    log_msg('ANL >> we need BUY(BT)');
                                    $this->db->setStrategy('BUY');
                                    $this->db->setStage('BT');
                                } else if ($fund == $coinA) { // FUND/XXX, up, wait peak
                                    log_msg('ANL >> we need wait peak(WP)');
                                    $this->db->setStrategy('BUY');
                                    $this->db->setStage('WP');
                                }
                            } else {
                                log_msg('ANL >> negative trend');
                                if ($fund == $coinB) { // XXX/FUND, down, wait
                                    log_msg('ANL >> we need wait bottom(WB)');
                                    $this->db->setStrategy('SELL');
                                    $this->db->setStage('WB');
                                } else if ($fund == $coinA) { // FUND/XXX, down, sell for XXX
                                    log_msg('ANL >> we need SELL(BB)');
                                    $this->db->setStrategy('SELL');
                                    $this->db->setStage('ST');
                                }
                            }
                            $this->pause = 0;
                        } else {
                            log_msg('ANL >> no interesting trends');
                            $this->db->setStrategy('WAIT');
                            $this->pause = 30;
                        }
                        break;

                    /* ------ BUY strategy ------- */
                    case 'BT':
                        // Buy at trend
                        log_msg('BT: started');
                        if (!$trendPair) {
                            // pair not defined
                            $this->db->setStrategy('WAIT');
                            $this->db->setStage('ANL');
                            log_msg('BT: undefined pair, return to ANL stage');
                            $this->pause = 30;
                            continue;
                        }
                        $prices = $this->db->loadPrices('trend',$trendPair);
                        $this->storage = Loader::storage();
                        $api = Loader::api($this->storage->data->key, $this->storage->data->secret);
                        // check for minimal amount for this fund
                        if (!$fundAmount || $fundAmount < $DBconf['minFund'] || !$prices[0]['buy']) {
                            throw new BtceLogicException('BT.fundAmount['.$DBconf['baseCoin'].'] is low than minFund:['.$DBconf['minFund'].']');
                        }
                        $fundAmount = $fundAmount - $DBconf['minFund'];
                        $fee = $fundAmount * 0.0002;
                        $fundAmount -= $fee;
                        $fundAmount = round($fundAmount,8);
                        $buyAmount = round($fundAmount/$prices[0]['buy'],8);
                        // expected result
                        $calcResult = Logic::getOrderResult($buyAmount,$prices[0]['buy'],0.02);
                        log_msg('BT: try to make order. Pair: '.$prices[0]['pair'].', BUY '.$buyAmount.' with price: '.$prices[0]['buy']);
                        // make real order
                        try {
                            $apiResult = $api->makeOrder($buyAmount,$prices[0]['pair'],BTCeAPI::DIRECTION_BUY,$prices[0]['buy']);
                        } catch(BTCeAPIException $e) {
                            throw new BTCeAPIException('BT: API exception message:'.$e->getMessage());
                        }
                        if (isset($apiResult['return']) && isset($apiResult['return']['order_id'])) {
                            $orderComplete = $this->expectOrder($apiResult['return']['order_id'],600); // expect order complete for 10 minutes
                            if ($orderComplete) {
                                // success
                                $this->db->registerOrder($apiResult['return']['order_id'],$prices[0]['pair'],'buy',$buyAmount,$prices[0]['buy'],$calcResult);
                                $bcCode = substr($prices[0]['pair'],0,3);
                                // set stage for old fund
                                $this->db->setStage('ANL');
                                $this->db->setBaseCoin($bcCode);
                                // set stage for new fund
                                $this->db->setStage('WP');
                                log_msg('BT: new base coin: '.$bcCode.', next stage is WP');
                                continue;
                            } else {
                                // fail
                                log_msg('BT: order is cancelled. Return to ANL stage in current funds');
                                $this->pause = 30;
                            }
                        } else {
                            throw new BTCeAPIException('Bad API result: '.print_r($apiResult,true));
                        }
                    break;

                    case 'WP':
                        // Wait for peak
                        log_msg('WP: check...');
                        $lastOrderRow = $this->db->getLastOrder($trendPair);
                        $pair = $lastOrderRow['pair'];
                        if (!$pair) {
                            log_msg('WP: havent orders with trend pair: '.$trendPair);
                            $lastOrderRow = $this->db->getLastOrder();
                            $pair = $lastOrderRow['pair'];
                        }
                        log_msg('WP: look at pair: '.$pair);
                        $border = $lastOrderRow['price'] + ($lastOrderRow['price']*0.02*2);
                        $prices = $this->db->loadPrices('trend',$pair);
                        if ($prices[0]['pair'] == $pair) {
                            if ($prices[0]['trend'] < 0) {
                                if ($prices[0]['sell'] > $border) {
                                    // trend rotated, price good
                                    $this->db->setStage('SP');
                                    $this->pause = 0;
                                    log_msg('WP >> '.$pair.' yes, time to sell');
                                } else {
                                    log_msg('WP >> '.$pair.' sell price low than border: '.$prices[0]['sell'].' / '.$border);
                                    $this->pause = 30;
                                }
                            } else {
                                log_msg('WP >> trend is grow up');
                                $this->pause = 30;
                            }
                        } else {
                            throw new BtceLogicException('WP: Unknown pair '.$pair.', trend pair: '.$trendPair);
                        }
                        break;

                    case 'SP':
                        // Sell at peak
                        if (!$trendPair) {
                            $lastOrderRow = $this->db->getLastOrder();
                            $pair = $lastOrderRow['pair'];
                        } else {
                            $pair = $trendPair;
                        }
                        $prices = $this->db->loadPrices('trend',$pair);
                        $this->storage = Loader::storage();
                        $api = Loader::api($this->storage->data->key, $this->storage->data->secret);
                        if ($prices[0]['pair'] != $pair) {
                            $this->pause = 10;
                            continue;
                        }

                        $res = $this->db->getFund($DBconf['baseCoin']);
                        $fundAmount = $res['amount'];

                        if (!$fundAmount || $fundAmount < $DBconf['minFund'] || !$prices[0]['sell']) {
                            throw new BtceLogicException('SP.fundAmount['.$DBconf['baseCoin'].'] is low than minFund:['.$DBconf['minFund'].']');
                        }
                        // expected result
                        $calcResult = Logic::getOrderResult($fundAmount,$prices[0]['sell'],0.02);
                        log_msg('SP: try to make order. Pair: '.$prices[0]['pair'].', SELL '.$fundAmount.' with price: '.$prices[0]['sell']);
                        // make real order
                        try {
                            $apiResult = $api->makeOrder($fundAmount,$prices[0]['pair'],BTCeAPI::DIRECTION_SELL,$prices[0]['sell']);
                        } catch(BTCeAPIException $e) {
                            if (strpos($e->getMessage(),'It is not enough')) {
                                $fundAmount -= 0.000001;
                                $apiResult = $api->makeOrder($fundAmount,$prices[0]['pair'],BTCeAPI::DIRECTION_SELL,$prices[0]['sell']);
                            }
                        }
                        if (isset($apiResult['return']) && isset($apiResult['return']['order_id'])) {
                            $orderComplete = $this->expectOrder($apiResult['return']['order_id'],600); // expect order complete for 10 minutes
                            if ($orderComplete) {
                                // success
                                $this->db->registerOrder($apiResult['return']['order_id'],$prices[0]['pair'],'sell',$fundAmount,$prices[0]['sell'],$calcResult);
                                $bcCode = substr($prices[0]['pair'],4,3);
                                // set stage for old fund
                                $this->db->setStage('ANL');
                                $this->db->setBaseCoin($bcCode);
                                // set stage for new fund
                                $this->db->setStage('ANL');
                                log_msg('SP: new base coin: '.$bcCode.', next stage is ANL');
                                continue;
                            } else {
                                // fail
                                log_msg('SP: order is cancelled. Return to ANL stage in current funds');
                                $this->pause = 30;
                            }
                        } else {
                            throw new BTCeAPIException('Bad API result: '.print_r($apiResult,true));
                        }
                        break;




                    /** ------------ SELL strategy ------------ */
                    case 'ST':
                        // Sell at trend
                        log_msg('ST: started');
                        if (!$trendPair) {
                            // pair not defined
                            $this->db->setStrategy('WAIT');
                            $this->db->setStage('ANL');
                            log_msg('ST: undefined pair, return to ANL stage');
                            $this->pause = 30;
                            continue;
                        }
                        $prices = $this->db->loadPrices('trend',$trendPair);
                        $this->storage = Loader::storage();
                        $api = Loader::api($this->storage->data->key, $this->storage->data->secret);
                        // check for minimal amount for this fund
                        if (!$fundAmount || $fundAmount < $DBconf['minFund'] || !$prices[0]['sell']) {
                            throw new BtceLogicException('ST.fundAmount['.$DBconf['baseCoin'].'] is low than minFund:['.$DBconf['minFund'].']');
                        }
                        $fundAmount = $fundAmount - $DBconf['minFund'];
                        // expected result
                        $calcResult = Logic::getOrderResult($fundAmount,$prices[0]['sell'],0.02);
                        log_msg('ST: try to make order. Pair: '.$prices[0]['pair'].', SELL '.$fundAmount.' with price: '.$prices[0]['sell']);
                        // make real order
                        try {
                            $apiResult = $api->makeOrder($fundAmount,$prices[0]['pair'],BTCeAPI::DIRECTION_SELL,$prices[0]['sell']);
                        } catch(BTCeAPIException $e) {
                            if (strpos($e->getMessage(),'It is not enough')) {
                                $fundAmount -= 0.000001;
                                $apiResult = $api->makeOrder($fundAmount,$prices[0]['pair'],BTCeAPI::DIRECTION_SELL,$prices[0]['sell']);
                            }
                        }
                        if (isset($apiResult['return']) && isset($apiResult['return']['order_id'])) {
                            $orderComplete = $this->expectOrder($apiResult['return']['order_id'],600); // expect order complete for 10 minutes
                            if ($orderComplete) {
                                // success
                                $this->db->registerOrder($apiResult['return']['order_id'],$prices[0]['pair'],'sell',$fundAmount,$prices[0]['sell'],$calcResult);
                                $bcCode = substr($prices[0]['pair'],4,3);
                                // set stage for old fund
                                $this->db->setStage('ANL');
                                $this->db->setBaseCoin($bcCode);
                                // set stage for new fund
                                $this->db->setStage('WB');
                                log_msg('ST: new base coin: '.$bcCode.', next stage is WP');
                                continue;
                            } else {
                                // fail
                                log_msg('BT: order is cancelled. Return to ANL stage in current funds');
                                $this->pause = 30;
                            }
                        } else {
                            throw new BTCeAPIException('Bad API result: '.print_r($apiResult,true));
                        }
                        break;

                    case 'WB':
                        // Wait for bottom
                        log_msg('WB: check...');
                        $lastOrderRow = $this->db->getLastOrder($trendPair);
                        $pair = $lastOrderRow['pair'];
                        if (!$pair) {
                            log_msg('WB: havent orders with trend pair: '.$trendPair);
                            $lastOrderRow = $this->db->getLastOrder();
                            $pair = $lastOrderRow['pair'];
                        }
                        log_msg('WB: look at pair: '.$pair);
                        $border = $lastOrderRow['price'] + ($lastOrderRow['price']*0.02*2);
                        $prices = $this->db->loadPrices('trend',$pair);
                        if ($prices[0]['pair'] == $pair) {
                            if ($prices[0]['trend'] > 0) {
                                if ($prices[0]['buy'] < $border) {
                                    // trend rotated, price good
                                    $this->db->setStage('BB');
                                    $this->pause = 0;
                                    log_msg('WB >> '.$pair.' yes, time to buy');
                                } else {
                                    log_msg('WB >> '.$pair.' buy price more than border: '.$prices[0]['buy'].' / '.$border);
                                    $this->pause = 30;
                                }
                            } else {
                                log_msg('WB >> trend is go down... '.$prices[0]['trend'].' / '.$prices[0]['buy']);
                                $this->pause = 30;
                            }
                        } else {
                            throw new BtceLogicException('WB: Unknown pair '.$pair.', trend pair: '.$trendPair);
                        }
                        break;

                    case 'BB':
                        // Buy at bottom
                        if (!$trendPair) {
                            $lastOrderRow = $this->db->getLastOrder();
                            $pair = $lastOrderRow['pair'];
                        } else {
                            $pair = $trendPair;
                        }
                        $prices = $this->db->loadPrices('trend',$pair);
                        $this->storage = Loader::storage();
                        $api = Loader::api($this->storage->data->key, $this->storage->data->secret);
                        if ($prices[0]['pair'] != $pair) {
                            $this->pause = 10;
                            continue;
                        }

                        $res = $this->db->getFund($DBconf['baseCoin']);
                        $fundAmount = $res['amount'];

                        if (!$fundAmount || $fundAmount < $DBconf['minFund'] || !$prices[0]['buy']) {
                            throw new BtceLogicException('BB.fundAmount['.$DBconf['baseCoin'].'] is low than minFund:['.$DBconf['minFund'].']');
                        }

                        $fundAmount = $fundAmount - $DBconf['minFund'];
                        $fee = $fundAmount * 0.0002;
                        $fundAmount -= $fee;
                        $fundAmount = round($fundAmount,8);
                        $buyAmount = round($fundAmount/$prices[0]['buy'],8);
                        // expected result
                        $calcResult = Logic::getOrderResult($buyAmount,$prices[0]['buy'],0.02);
                        log_msg('BB: try to make order. Pair: '.$prices[0]['pair'].', BUY '.$buyAmount.' with price: '.$prices[0]['buy']);
                        // make real order
                        try {
                            $apiResult = $api->makeOrder($buyAmount,$prices[0]['pair'],BTCeAPI::DIRECTION_BUY,$prices[0]['buy']);
                        } catch(BTCeAPIException $e) {
                            throw new BTCeAPIException('BB: API exception message:'.$e->getMessage());
                        }
                        if (isset($apiResult['return']) && isset($apiResult['return']['order_id'])) {
                            $orderComplete = $this->expectOrder($apiResult['return']['order_id'],600); // expect order complete for 10 minutes
                            if ($orderComplete) {
                                // success
                                $this->db->registerOrder($apiResult['return']['order_id'],$prices[0]['pair'],'buy',$buyAmount,$prices[0]['buy'],$calcResult);
                                $bcCode = substr($prices[0]['pair'],0,3);
                                // set stage for old fund
                                $this->db->setStage('ANL');
                                $this->db->setBaseCoin($bcCode);
                                // set stage for new fund
                                $this->db->setStage('WP');
                                log_msg('BB: new base coin: '.$bcCode.', next stage is WP');
                                continue;
                            } else {
                                // fail
                                log_msg('BB: order is cancelled. Return to ANL stage in current funds');
                                $this->pause = 30;
                            }
                        }
                        break;

                }
            } catch(Exception $e) {
                log_msg('run >> exception: ('.$e->getCode().') '.$e->getMessage());
                $this->db->setStage('ANL');
                $this->pause = 30;
            }
        }



    }

    /**
     * @param $idOrder
     * @param $timeOut
     * @return bool
     */
    private function expectOrder($idOrder,$timeOut) {
        $timeNow = time();
        $timeEnd = $timeNow + $timeOut;
        $api = Loader::api($this->storage->data->key, $this->storage->data->secret);
        while(true) {
            log_msg('expectOrder: check order history');
            try {
                $order = $api->getOrderFromHistory($idOrder);
            } catch (BTCeAPIErrorException $e) {
                log_msg('expectOrder: getOrderFromHistory result error: '.$e->getMessage);
            }
            if (isset($order['order_id'])) {
                return true;
            }

            if (time() > $timeEnd) {
                $api->cancelOrder($idOrder);
                return false;
            }
            log_msg('expectOrder: retry after 30 sec');
            sleep(30);
        }
    }

}

$logic = new Strateg();
$logic->init($startParams);
$logic->run();