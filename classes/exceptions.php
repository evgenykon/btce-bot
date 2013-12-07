<?php
/**
 * Created by PhpStorm.
 * User: Effus
 * Date: 01.12.13
 * Time: 23:29
 */

class StorageException extends Exception {
    const DATA_FILE_NOT_FOUND   = 10;
    const NO_DATA_IN_RESULT     = 11;

    static $messages = array(
        self::DATA_FILE_NOT_FOUND   => 'Data storage not found',
        self::NO_DATA_IN_RESULT     => 'No required data in result',
    );
}


class BtceLibException extends Exception {
    const BAD_REQUEST = 1;
    const BAD_RESPONCE = 2;
    const TRANSPORT_ERROR = 3;

    static $messages = array(
        self::BAD_REQUEST       => 'Request not build',
        self::BAD_RESPONCE      => 'Invalid data received, please make sure connection is working and requested API exists',
        self::TRANSPORT_ERROR   => 'Could not get reply, transport error'
    );
}

class BTCeAPIException extends BtceLibException {}
class BTCeAPIFailureException extends BTCeAPIException {}
class BTCeAPIInvalidJSONException extends BTCeAPIException {}
class BTCeAPIErrorException extends BTCeAPIException {}
class BTCeAPIInvalidParameterException extends BTCeAPIException {}

class BtceLogicException extends BtceLibException {
    const BAD_FEE = 21;
    const BAD_TICKER = 22;
    const BAD_TRADE = 23;
    const BAD_DEPTH = 24;
    const EMPTY_BASE_FUND = 25;
    const NO_AVAILABLE_FUNDS = 251;
    const MINIMAL_FUNDS = 26;
    const ORDER_TIMEOUT = 27;
    const UNKNOWN_PAIR = 28;
    const REQUIRE_UPDATE_PRICE = 29;


    static $messages = array(
        self::BAD_FEE     => 'bad fee',
        self::BAD_TICKER  => 'bad ticker',
        self::BAD_TRADE   => 'bad trade',
        self::BAD_DEPTH   => 'bad depth',
        self::EMPTY_BASE_FUND => 'empty base fund',
        self::MINIMAL_FUNDS => 'minimal funds',
        self::ORDER_TIMEOUT => 'order timeout',
        self::UNKNOWN_PAIR => 'unknown pair',
        self::REQUIRE_UPDATE_PRICE => 'require update price',
        self::NO_AVAILABLE_FUNDS => 'no available funds'
    );
}

class BtceMysqlException extends BtceLibException {
	const CONNECTION_FAIL = 31;
}