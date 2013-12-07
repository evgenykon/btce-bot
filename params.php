<?php
/**
 * Created by PhpStorm.
 * User: Effus
 * Date: 07.12.13
 * Time: 19:59
 */
$startParams = array(
    'baseCoin'          => new Coin('btc'),
    'min_fund_amount'   => 0.012,
    'expire_fund'       => 60*10,
    'expire_pairs'      => 60,
    'expire_pairs_life' => 60*2,
    'diffs_sell'  => array(
        'btc_rur'       => 15,
        'btc_usd'       => 1,
        'btc_eur'       => 2,
        'ltc_btc'       => 0.002,
    ),
    'diffs_buy'  => array(
        'btc_rur'       => 60,
        'btc_usd'       => 2,
        'btc_eur'       => 2,
        'ltc_btc'       => 0.002,
    ),
    'capture_count'     => array(
        'sell'          => 2,
        'buy'           => 6
    ),
    'mysql'             => array(
        'host'          => '',
        'user'          => '',
        'password'      => '',
        'dbname'        => 'btce'
    )
);