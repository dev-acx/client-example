<?php

require_once 'vendor/autoload.php';

use ACX\Acx;

$acx = new Acx();

// get ticker with all pairs
$res = $acx->ticker();

// get ticker with specific pair
$res = $acx->ticker('ethaud');

// get public markets list
$res = $acx->markets(); // get public orderbook of btcaud
$res = $acx->orderbook('btcaud');

// get public orderbook with limits
$res = $acx->orderbook('btcaud', ['asks_limit' => 10, 'bids_limit' => 10]);

// get market depth of btcaud
$res = $acx->depth('btcaud');

// get market depth of btcaud limit to 10 result
$res = $acx->depth('btcaud', ['limit' => 10]);

// get public trade data of btcaud
$res = $acx->trades('btcaud');

// get public trade data of btcaud with all supported parameters
$res = $acx->trades(
    'btcaud',
    [
        'limit' => 20,
        'timestamp' =>  time() - 3600,
        // 'from' => '', // order id from
        // 'to' => '',   // order id to
        'order_by' => 'desc'
    ]
);

// get k line of btc data
$res = $acx->k('btcaud');

// get current server timestamp
$res = $acx->timestamp();


########### private methods ###########

$key = ''; // set your api key here
$secret = ''; // set your api key here

$acx = new Acx($key, $secret);

// get personal account info, including balance
$res = $acx->me();

// get deposit history
$res = $acx->deposits();

// get deposit details with tx id
// $res = $acx->deposit('6e290b0aa149ca2138122b2f3bf698678a45e9951af90e38329b5cdd61134a50');

// get all orders
$res = $acx->getorders('btcaud');

// get all open orders
$res = $acx->getorders('btcaud', ['state' => 'wait']);

// get one order details
// $res = $acx->getorder('111');

// cancel all open orders
$res = $acx->cancel('both');

// cancel one specific order
// $res = $acx->cancel('118698');

$res = $acx->buy('btcaud',['amount' => 0.1, 'price' => 1000]);

// sell multi orders
$res = $acx->sell('btcaud', [['amount' => 0.1, 'price' => 1000], ['amount' => 0.1, 'price' => 2000]])

// finally wish you be rich.
var_dump($res);
