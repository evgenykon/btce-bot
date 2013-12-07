<?php
/**
 * User: effus
 * Date: 02.12.13
 * Time: 14:11
 * storage.php
 */

/**
 * Class Storage
 */
class Storage {
    private $_src;
    public  $data /** @var StorageData $data */;

    function __construct($path) {
        log_msg("Storage::construct >> inited");
        $this->_src = $path;
        if (file_exists($this->_src)) {
            $_file = file_get_contents($this->_src);
            $_dataArr = json_decode($_file,true);
            log_msg("Storage::construct >> load from ".$this->_src);
        } else {
            throw new StorageException(StorageException::$messages[StorageException::DATA_FILE_NOT_FOUND],StorageException::DATA_FILE_NOT_FOUND);
        }
        $this->data = new StorageData($_dataArr);
    }

    /**
     * @param $path
     * @param $key
     * @param $secret
     * @return bool|Storage
     */
    static function create($path,$key,$secret) {
        $_data = new StorageData(array());
        $_data->key = $key;
        $_data->secret = $secret;
        $_dataArr = $_data->export();
        $_file = json_encode($_dataArr);
        $fe = file_put_contents($path,$_file);
        if (!$fe)
            return false;
        return new Storage($path);
    }

    /**
     * @return bool
     */
    public function save() {
        $_dataArr = $this->data->export();
        $_file = json_encode($_dataArr);
        file_put_contents($this->_src,$_file);
        return true;
    }
}

/**
 * Class StorageData
 */
class StorageData {
    public $modtime = 0;
    public $key = '';
    public $secret = '';
    public $countTransactions = 0;
    public $countOpenOrders = 0;

    public $funds = array();
    public $pairs = array();

    /**
     * @param $dataArr
     */
    function __construct($dataArr) {
        $this->modtime = time();
        if (isset($dataArr['modtime']))
            $this->modtime = $dataArr['modtime'];
        if (isset($dataArr['key']))
            $this->key = $dataArr['key'];
        if (isset($dataArr['secret']))
            $this->secret = $dataArr['secret'];
        if (isset($dataArr['countTransactions']))
            $this->countTransactions = $dataArr['countTransactions'];
        if (isset($dataArr['countOpenOrders']))
            $this->countOpenOrders = $dataArr['countOpenOrders'];
        if (isset($dataArr['funds']))
            $this->funds = $dataArr['funds'];
        if (isset($dataArr['pairs']))
            $this->pairs = $dataArr['pairs'];
        //print_r($this->pairs);
    }

    /**
     * @return array
     */
    public function export() {
        return array(
            'modtime'   => $this->modtime,
            'key'       => $this->key,
            'secret'    => $this->secret,
            'countTransactions' => $this->countTransactions,
            'countOpenOrders'   => $this->countOpenOrders,
            'funds'     => $this->funds,
            'pairs'     => $this->pairs
        );
    }
}