<?php
/**
 * Created by PhpStorm.
 * User: Effus
 * Date: 07.12.13
 * Time: 15:02
 */

/**
 * Class Pair
 */
class Pair {
    public $coin_a;
    public $coin_b;
    public $time;
    public $updated;
    public $fee;
    public $enabled;
    public $high;
    public $low;
    public $avg;
    public $vol;
    public $vol_cur;
    public $last;
    public $buy;
    public $sell;
    public $tradeList;
    public $depthList;
    public $code;

    /**
     * @param $ca
     * @param $cb
     */
    function __construct($ca,$cb) {
        $this->coin_a = new Coin($ca);
        $this->coin_b = new Coin($cb);
        $this->code = $this->getPairName();
        $this->time = time();
        $this->fee = 0;
        $this->enabled = true;
        $this->tradeList = array();
        $this->depthList = array();
        $this->refreshRequired = true;
    }

    /**
     * @return string
     */
    private function getPairName() {
        return $this->coin_a.'_'.$this->coin_b;
    }
}

/**
 * Class TradePairs
 */
class TradePairs {

    private $api; /** @var $api BTCeAPI */

    private $pairEnable = array(
        array(Coin::CODE_BTC,Coin::CODE_USD),
        array(Coin::CODE_BTC,Coin::CODE_RUR),
        array(Coin::CODE_BTC,Coin::CODE_EUR),
        array(Coin::CODE_LTC,Coin::CODE_BTC),
        array(Coin::CODE_LTC,Coin::CODE_USD),
        array(Coin::CODE_LTC,Coin::CODE_RUR),
        array(Coin::CODE_LTC,Coin::CODE_EUR),
        array(Coin::CODE_NMC,Coin::CODE_BTC),
        array(Coin::CODE_NMC,Coin::CODE_USD),
        array(Coin::CODE_NVC,Coin::CODE_BTC),
        array(Coin::CODE_NVC,Coin::CODE_USD),
        array(Coin::CODE_USD,Coin::CODE_RUR),
        array(Coin::CODE_EUR,Coin::CODE_USD),
        array(Coin::CODE_TRC,Coin::CODE_BTC),
        array(Coin::CODE_PPC,Coin::CODE_BTC),
        array(Coin::CODE_PPC,Coin::CODE_USD),
        array(Coin::CODE_FTC,Coin::CODE_BTC),
        array(Coin::CODE_XPM,Coin::CODE_BTC),
    );

    public $list = array();
    public $prev = array();

    public $updated = 0; // not exported
    public $preflife = 0;

    /**
     * @param array $storagePairs
     */
    function __construct($storagePairs=array()) {
        foreach($this->pairEnable as $item) {
            $pair = new Pair($item[0],$item[1]);
            $this->list[$pair->code] = $pair;
        }
        if (count($storagePairs)) {
            $this->import($storagePairs);
        }
    }

    /**
     * @return string
     */
    public function __toString() {
        $out = 'TradePairs /updated:'.date('Y-m-d H:i:s',$this->updated).'/'.PHP_EOL;
        foreach($this->list as $pairKey => $pair) {
            /** @var Pair $pair */
            $out .= $pairKey.': sell['.$pair->sell.'] buy['.$pair->buy.'] up['.date('Ymd H:i:s',$pair->updated).'] / refresh'.(int)$pair->refreshRequired.'/'.PHP_EOL;
        }
        return $out;
    }

    /**
     * @return array
     */
    public function export() {
        $data = array();
        foreach($this->list as $pairKey => $pair) {
            $data[$pairKey] = array(
                'coin_a'    => (string)$pair->coin_a,
                'coin_b'    => (string)$pair->coin_b,
                'time'      => $pair->time,
                'fee'       => $pair->fee,
                'enabled'   => $pair->enabled,
                'high'      => $pair->high,
                'low'       => $pair->low,
                'avg'       => $pair->avg,
                'vol'       => $pair->vol,
                'vol_cur'   => $pair->vol_cur,
                'last'      => $pair->last,
                'buy'       => $pair->buy,
                'sell'      => $pair->sell,
                'updated'   => $pair->updated
            );
        }
        $data['updated'] = $this->updated;
        return $data;
    }

    /**
     * @param array $data
     * @return bool
     */
    public function import($data=array()) {
        foreach($data as $pairKey => $pair) {
            if ($pairKey == 'updated') {
                $this->updated = $pair;
                continue;
            }
            if (isset($this->list[$pairKey])) {
                $this->list[$pairKey] = new Pair($pair['coin_a'],$pair['coin_b']);
                $obj = &$this->list[$pairKey];
                /** @var Pair $obj */
                $obj->time      = $pair['time'];
                $obj->fee       = $pair['fee'];
                $obj->enabled   = $pair['enabled'];
                $obj->high      = $pair['high'];
                $obj->low       = $pair['low'];
                $obj->avg       = $pair['avg'];
                $obj->vol       = $pair['vol'];
                $obj->vol_cur   = $pair['vol_cur'];
                $obj->last      = $pair['last'];
                $obj->buy       = $pair['buy'];
                $obj->sell      = $pair['sell'];
                $obj->updated   = $pair['updated'];
                $obj->refreshRequired = true;
            }
        }
        return true;
    }

    /**
     * @param BTCeAPI $api
     * @return bool
     */
    public function load(BTCeAPI &$api) {
        $this->api = $api;
        foreach($this->list as $pairKey => $pair) {
            /** @var Pair $pair */
            if ($pair->refreshRequired) {
                $start_time = microtime(true);
                try {
                    $this->loadFee($pairKey);
                    $this->loadTicker($pairKey);
                } catch (BtceLogicException $e) {
                    $this->list[$pairKey]->enabled = false;
                    log_msg('TradePairs:load >> error:'.$e->getMessage());
                }
                $end_time = microtime(true);
                log_msg('TradePairs:load >> '.$pairKey.'; time:'.(number_format($end_time-$start_time,4)) );
            }
        }
        $this->updated = time();
        return true;
    }

    /**
     * @param $pairCode
     * @throws BtceLogicException
     */
    private function loadFee($pairCode) {
        if (isset($this->list[$pairCode])) {
            if ($this->list[$pairCode]->enabled) {
                $data = $this->api->getPairFee($pairCode);
                if (isset($data['trade'])) {
                    $this->list[$pairCode]->fee = $data['trade'];
                } else {
                    throw new BtceLogicException(BtceLogicException::$messages[BtceLogicException::BAD_FEE],BtceLogicException::BAD_FEE);
                }
            }
        }
    }

    /**
     * @param $pairCode
     * @throws BtceLogicException
     */
    private function loadTicker($pairCode) {
        if (isset($this->list[$pairCode])) {
            if ($this->list[$pairCode]->enabled) {
                $data = $this->api->getPairTicker($pairCode);
                if (isset($data['ticker'])) {
                    $ticker = $data['ticker'];
                    $this->list[$pairCode]->high = $ticker['high'];
                    $this->list[$pairCode]->low = $ticker['low'];
                    $this->list[$pairCode]->avg = $ticker['avg'];
                    $this->list[$pairCode]->vol = $ticker['vol'];
                    $this->list[$pairCode]->vol_cur = $ticker['vol_cur'];
                    $this->list[$pairCode]->last = $ticker['last'];
                    $this->list[$pairCode]->buy = $ticker['buy'];
                    $this->list[$pairCode]->sell = $ticker['sell'];
                    $this->list[$pairCode]->updated = $ticker['updated'];
                } else {
                    throw new BtceLogicException(BtceLogicException::$messages[BtceLogicException::BAD_TICKER],BtceLogicException::BAD_TICKER);
                }
            } else
                log_msg('loadTicker >> disabled pair: '.$pairCode);
        } else
            log_msg('loadTicker >> undefined pair: '.$pairCode);
    }

    /**
     * @param $pairCode
     */
    public function loadTrades($pairCode) {
        if (isset($this->list[$pairCode])) {
            if ($this->list[$pairCode]->enabled) {
                $data = $this->api->getPairTrades($pairCode);
                $this->list[$pairCode]->tradeList = $data;
            }
        }
    }

    /**
     * @param $pairCode
     */
    public function loadPairDepth($pairCode) {
        if (isset($this->list[$pairCode])) {
            if ($this->list[$pairCode]->enabled) {
                $data = $this->api->getPairDepth($pairCode);
                $this->list[$pairCode]->depthList = $data;
            }
        }
    }

    public function setPrevPair() {
        $this->prev = array();
        foreach($this->list as $key=>$item) {
            $this->prev[$key] = clone $item;
        }
        $this->preflife = $this->updated;
    }
}
