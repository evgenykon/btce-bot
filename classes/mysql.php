<?php
/**
 * Created by PhpStorm.
 * User: Effus
 * Date: 07.12.13
 * Time: 15:46
 */
class Mysql {

	protected $h;

    public function __construct($host,$user,$password,$dbName){
		$this->h = mysqli_connect($host, $user, $password, $dbName);
		if (mysqli_connect_errno($this->h)) {
			throw new BtceMysqlException(mysqli_connect_error(),BtceMysqlException::CONNECTION_FAIL);
		}
    }

    /**
     * @param $query
     * @return mixed
     */
    public function row($query) {
		$res = $this->h->query($query);
        $row = $res->fetch_assoc();
        return $row;
	}

    /**
     * @param $query
     * @return array
     */
    public function rows($query) {
        $res = $this->h->query($query);
        $res->data_seek(0);
        $out = array();
        while ($row = $res->fetch_assoc()) {
            $out[]=$row;
        }
        return $out;
    }

    /**
     * @param $query
     * @return bool|mysqli_result
     */
    public function execute($query) {
        return $this->h->query($query);
    }
}


class MysqlDb extends Mysql {

    public function __construct($host,$user,$password,$dbName) {
        parent::__construct($host,$user,$password,$dbName);
    }

    /**
     * @return mixed
     */
    public function loadActiveConfiguration() {
        $sql = "SELECT * FROM btc.conf where active = 1";
        return $this->row($sql);
    }

    /**
     * @return array
     */
    public function loadFunds() {
        $sql = "SELECT * FROM btc.funds where lock=0";
        return $this->rows($sql);
    }

    /**
     * @return array
     */
    public function loadPairPrices() {
        $sql = "SELECT * FROM btc.pair_prices";
        return $this->rows($sql);
    }

    /**
     * @param int $startTime
     * @param int $endTime
     * @return array
     */
    public function loadHistory($where,$limit=0) {
        $whereStr = implode(' and ',$where);
        $sql = "SELECT * FROM btc.price_history".($whereStr ? ' where '.$whereStr : '');
        if ($limit) {
            $sql .= ' LIMIT 0,'.(int)$limit;
        }
        echo $sql.PHP_EOL;
        return $this->rows($sql);
    }

    /**
     * @param Pair $pair
     * @return bool|mysqli_result
     */
    public function updatePairPrice(Pair $pair) {
        $sql = sprintf("call upsert_pair_price ('%s', '%s', %f, %f)",
			$pair->code, 
			date('Y-m-d H:i:s'),
			$pair->sell, 
			$pair->buy
		);
        echo $sql.PHP_EOL;
        $res = $this->execute($sql);
        if (!$res) {
            throw new BtceMysqlException($this->h->error);
        }
        return $res;
    }

    /**
     * @param Coin $coin
     * @return bool|mysqli_result
     */
    public function updateFund(Coin $coin) {
        $sql = sprintf("call upsert_funds ('%s', %f,'%s',%d)",
            $coin->code,
            $coin->amount,
            date('Y-m-d H:i:s'),
            (int)!$coin->active
        );
        echo $sql.PHP_EOL;
        $res = $this->execute($sql);
        if (!$res) {
            throw new BtceMysqlException($this->h->error);
        }
        return $res;
    }
}