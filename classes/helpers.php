<?php
/**
 * Created by PhpStorm.
 * User: Effus
 * Date: 07.12.13
 * Time: 15:04
 */
/**
 * Class Loader
 */
class Loader {

    /**
     * @return Storage
     */
    static function storage() {
        global $argv;
        try {
            $storage = new Storage($argv[1]);
        } catch (StorageException $e) {
            if ($e->getCode() == StorageException::DATA_FILE_NOT_FOUND) {
                log_msg('Loader::storage >> no json file');
                // User input
                echo "Put your key:".PHP_EOL;
                $handle = fopen ("php://stdin","r");
                $line = fgets($handle);
                $_input_key = trim($line);
                fclose($handle);
                echo "Put your secret:".PHP_EOL;
                $handle = fopen ("php://stdin","r");
                $line = fgets($handle);
                $_input_secret = trim($line);
                fclose($handle);
                $storage = Storage::create($argv[1],$_input_key,$_input_secret);
                if (!$storage)
                    log_msg('Access denied to create file storage',true);
            } else {
                log_msg($e->getMessage(),true);
            }
        }
        return $storage;
    }

    /**
     * @param $key
     * @param $secret
     * @return BTCeAPI
     */
    static function api($key,$secret) {
        try {
            $api = new BTCeAPI($key,$secret);
            return $api;
        } catch (BtceLibException $e) {
            log_msg('Connection failed: '.$e->getMessage(),true);
        }

    }
}



/**
 * Class StrategyConf
 */
class StrategyConf {

    const SELL = 'sell';
    const BUY  = 'buy';
    const WAIT = 'wait';
    const NONE = 'none';

    public $baseCoin; /** @var Coin $baseCoin */
    public $expire_fund;
    public $expire_pairs;
    public $expire_pairs_life;
    public $min_fund_amount;
    public $diff_sell;
    public $diff_buy;
    public $capture_count_sell;
    public $capture_count_buy;

    /**
     * @return array
     */
    public function export() {
        $out = array(
            'baseCoin'  => $this->baseCoin->code
        );
        return $out;
    }
}


function stdDev($array) {
    if (!count($array))
        return 0.0;
    $middle = array_sum($array) / count($array);
    $vol = 0.0;
    foreach($array as $i) {
        $vol += pow($i - $middle,2);
    }
    $vol = $vol / count($array);
    return (float)sqrt($vol);
}