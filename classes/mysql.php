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
        if (!$res) {
            throw new BtceMysqlException($this->h->error);
        }
        $row = $res->fetch_assoc();
        return $row;
	}

    /**
     * @param $query
     * @return array
     */
    public function rows($query) {
        $res = $this->h->query($query);
        if (!$res) {
            throw new BtceMysqlException($this->h->error);
        }
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

    public function getMaxFund() {
        $sql = "SELECT code,amount FROM btc.funds where funds.lock=0 and amount>0 order by amount desc limit 1";
        return $this->row($sql);
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
        //echo $sql.PHP_EOL;
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
        $res = $this->execute($sql);
        if (!$res) {
            throw new BtceMysqlException($this->h->error);
        }
        return $res;
    }

    /**
     * @param $code
     * @param $value
     * @return bool|mysqli_result
     * @throws BtceMysqlException
     */
    public function setTrend($code,$value,$weight=0) {
        $sql = sprintf("update pair_prices set trend=%d,weight=%d where pair='%s'",$value,$weight,$code);
        $res = $this->execute($sql);
        if (!$res) {
            throw new BtceMysqlException($this->h->error);
        }
        return $res;
    }

    /**
     * @return mixed
     */
    public function getLastUpdateTime() {
        $sql = "select max(updated) as dt from pair_prices";
        return $this->row($sql);
    }

    /**
     * @param string $order
     * @return array
     */
    public function loadPrices($order = 'trend',$withPair='') {
        $like = '';
        if ($withPair) {
            $like = "where pair like '%$withPair%'";
        }
        $sql = "select * from pair_prices $like order by ".$order;
        return $this->rows($sql);
    }

    /**
     * @param $code
     */
    public function setBaseCoin($code) {
        $sql = "update conf set active=0 where id>0";
        $this->execute($sql);
        $sql = "update conf set active=1 where baseCoin='$code'";
        $this->execute($sql);
    }

    /**
     * @param $stage
     */
    public function setStage($stage) {
        $sql = "update conf set stage='$stage' where active=1";
        log_msg('setStage >> '.$stage);
        $this->execute($sql);
    }

    /**
     * @param $pair
     * @return mixed
     */
    public function getLastOrder($pair='') {
        $where = '';
        if ($pair)  {
            $where = "where pair='$pair'";
        }
        $sql = "select * from order_history $where order by dt desc limit 1";
        return $this->row($sql);
    }

    public function getBestPairForSale($coin) {
        $sql = "select pair from pair_prices $like order by ".$order;
        return $this->rows($sql);
    }

    /**
     * @param $btceId
     * @param $pair
     * @param $operation
     * @param $amount
     * @param $price
     * @param $result
     */
    public function registerOrder($btceId,$pair,$operation,$amount,$price,$result) {
        $dt = date('Y-m-d H:i:s');
        $comment = $operation.' '.$amount.' '.substr($pair,0,3).' for '.$result.' '.substr($pair,3,3);
        $sql = sprintf("insert into order_history set btceId=%d, pair='%s', operation='%s', amount=%f, price=%f, result=%f, dt=%s, comment='%s'",
            $btceId,
            $pair,
            $operation,
            $amount,
            $price,
            $result,
            $dt,
            $comment
        );
        log_msg('registerOrder >> '.$sql);
        //return $this->execute($sql);
    }

}