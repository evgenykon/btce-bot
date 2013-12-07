<?php
/**
 * Created by PhpStorm.
 * User: Effus
 * Date: 07.12.13
 * Time: 15:01
 */

/**
 * Class Coin
 */
class Coin {

    const CODE_USD = 'usd';
    const CODE_RUR = 'rur';
    const CODE_EUR = 'eur';
    const CODE_BTC = 'btc';
    const CODE_LTC = 'ltc';
    const CODE_NMC = 'nmc';
    const CODE_NVC = 'nvc';
    const CODE_TRC = 'trc';
    const CODE_PPC = 'ppc';
    const CODE_FTC = 'ftc';
    const CODE_XPM = 'xpm';

    public $code;
    public $amount;
    public $active=false;

    function __construct($code,$amount=0) {
        $this->code = $code;
        $this->amount = $amount;
        if ($this->amount > 0) {
            $this->active = true;
        }
    }
    function __toString() {
        return $this->code;
    }

    static function getPairKeys($code) {
        $keys = array(
            self::CODE_BTC  => array(
                self::CODE_BTC.'_'.self::CODE_RUR,
                self::CODE_BTC.'_'.self::CODE_USD,
                self::CODE_BTC.'_'.self::CODE_EUR,
                self::CODE_LTC.'_'.self::CODE_BTC,
                self::CODE_NMC.'_'.self::CODE_BTC,
                self::CODE_NVC.'_'.self::CODE_BTC,
                self::CODE_TRC.'_'.self::CODE_BTC,
                self::CODE_PPC.'_'.self::CODE_BTC,
                self::CODE_FTC.'_'.self::CODE_BTC,
                self::CODE_XPM.'_'.self::CODE_BTC
            ),
            self::CODE_RUR => array(
                self::CODE_BTC.'_'.self::CODE_RUR,
                self::CODE_LTC.'_'.self::CODE_RUR,
                self::CODE_USD.'_'.self::CODE_RUR,
            ),
            self::CODE_USD => array(
                self::CODE_BTC.'_'.self::CODE_USD,
                self::CODE_LTC.'_'.self::CODE_USD,
                self::CODE_NMC.'_'.self::CODE_USD,
                self::CODE_NVC.'_'.self::CODE_USD,
                self::CODE_EUR.'_'.self::CODE_USD,
                self::CODE_PPC.'_'.self::CODE_USD,
                self::CODE_USD.'_'.self::CODE_RUR,
            ),
            self::CODE_EUR => array(
                self::CODE_BTC.'_'.self::CODE_EUR,
                self::CODE_LTC.'_'.self::CODE_EUR,
                self::CODE_EUR.'_'.self::CODE_USD,
            ),
            self::CODE_LTC => array(
                self::CODE_LTC.'_'.self::CODE_BTC,
                self::CODE_LTC.'_'.self::CODE_USD,
                self::CODE_LTC.'_'.self::CODE_RUR,
                self::CODE_LTC.'_'.self::CODE_EUR,
            ),
            self::CODE_FTC => array(
                self::CODE_FTC.'_'.self::CODE_BTC
            ),
            self::CODE_NMC => array(
                self::CODE_NMC.'_'.self::CODE_BTC,
                self::CODE_NMC.'_'.self::CODE_USD,
            ),
            self::CODE_NVC => array(
                self::CODE_NVC.'_'.self::CODE_BTC,
                self::CODE_NVC.'_'.self::CODE_USD,
            ),
            self::CODE_PPC => array(
                self::CODE_PPC.'_'.self::CODE_BTC,
                self::CODE_PPC.'_'.self::CODE_USD,
            ),
            self::CODE_TRC => array(
                self::CODE_TRC.'_'.self::CODE_BTC,
            ),
            self::CODE_XPM => array(
                self::CODE_XPM.'_'.self::CODE_BTC
            )
        );
        if (isset($keys[$code])) {
            return $keys[$code];
        }
    }

    /**
     * @return string
     */
    public function infoString() {
        return $this->code.' '.$this->amount.' ['.(int)$this->active.']';
    }
}



/**
 * Class OperationCoin
 */
class OperationCoin extends Coin {

    public $buyPrice; /** @var Coin buyPrice */

    /**
     * @param      $code
     * @param int  $amount
     * @param Coin $buyPrice
     */
    function __construct($code,$amount=0,Coin $buyPrice=null) {
        parent::__construct($code,$amount=0);
        $this->buyPrice = $buyPrice;
    }

    /**
     * @param $price
     * @return mixed
     */
    public function getPriceDiff($price) {
        return $price - $this->buyPrice->amount;
    }
}
