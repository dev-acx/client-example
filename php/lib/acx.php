<?php

namespace ACX;

use GuzzleHttp\Psr7;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Uri;

class ACXAPIException extends \ErrorException {};

class Acx
{
    private $key;     // API key
    private $secret;  // API secret
    private $url;     // API base URL
    private $client;    // http request

    function __construct(string $key = "", string $secret = "", string $url = 'https://acx.io:443/api/v2/')
    {
        $this->key = $key;
        $this->secret = $secret;
        $this->url = $url;

        if (!$this->client) {
            $this->client = new \GuzzleHttp\Client();
        }
    }

    public function timestamp()
    {
        return $this->query_public('timestamp.json');
    }

    public function k(string $pair, array $parameters = array())
    {
        $parameters['market'] = $pair;
        return $this->query_public('k.json', $parameters);
    }

    public function trades(string $pair, array $parameters = array())
    {
        $parameters['market'] = $pair;
        return $this->query_public('trades.json', $parameters);
    }

    public function depth(string $pair, array $parameters = array())
    {
        $parameters['market'] = $pair;
        return $this->query_public("depth.json", $parameters);
    }

    public function orderbook(string $pair, array $parameters = array())
    {
        $parameters['market'] = $pair;
        return $this->query_public("order_book.json", $parameters);
    }

    public function markets()
    {
        return $this->query_public('markets.json');
    }

    public function ticker(string $pair = '')
    {
        $method = empty($pair) ? 'tickers.json' : "tickers/{$pair}.json";
        return $this->query_public($method);
    }

    protected function query_public(string $method, array $parameters = array(), string $verb = 'GET')
    {
        $url = $this->url.$method;
        if (!empty($parameters)) {
            $url .= "?".http_build_query($parameters);
        }
        $response = $this->client->request($verb, $url);
        return json_decode($response->getBody(), true);
    }

    public function buy(string $pair, $orders)
    {
        if (isset($orders['price']) && isset($orders['amount'])) {
            $orders['side'] = 'buy';
        } else {
            foreach ($orders as &$order) {
                $order['side'] = 'buy';
            }
        }
        return $this->orders($pair, $orders);
    }

    public function sell(string $pair, $orders)
    {
        if (isset($orders['price']) && isset($orders['amount'])) {
            $orders['side'] = 'sell';
        } else {
            foreach ($orders as &$order) {
                $order['side'] = 'sell';
            }
        }
        return $this->orders($pair, $orders);
    }

    public function orders(string $pair, $orders)
    {
        if (!is_array($orders)) {
            throw new ACXAPIException('Order format not correct.');
        }
        if (isset($orders['price']) && isset($orders['amount'])) {
            extract($orders);
            assert(in_array($side, ['buy', 'sell']));
            $uri = new Uri($this->url . 'orders.json');
            $data = $this->createAuth(
                $uri->getPath(),
                 ['market'=> $pair,
                'side' => $side,
                'price' => $price,
                'volume' => $amount ], 'POST' );
            $request =  new Request('POST', $uri,
                ['Content-Type' => 'application/x-www-form-urlencoded'],
                Psr7\stream_for($data));
            $response = $this->client->send($request);
            return json_decode($response->getBody(), true);
        }
        $os = [];
        foreach ($orders as $order) {
            if (isset($order['price']) && isset($order['amount'])) {
                $os[] = [
                    'price' => $order['price'],
                    'side' => $order['side'],
                    'volume' => $order['amount'],
                ];
            } else {
                throw new \InvalidArgumentException('Order must have price and amount');
            }
        }
        $uri = new Uri($this->options['base_url'] . 'orders/multi.json');
        $data = $this->createAuth(
            $uri->getPath(),
             ['market'=> $this->pair,
            'orders' => $os], 'POST' );
        $request = new Request('POST', $uri,
            ['Content-Type' => 'application/x-www-form-urlencoded'],
            Psr7\stream_for($data));
        $response = $this->client->send($request);
        return json_decode($response->getBody(), true);
    }

    public function cancel($id)
    {
        if ($id == 'bids' || $id == 'asks' || $id == 'both') {
            $uri = new Uri($this->url . 'orders/clear.json');
            if ($id == 'both') {
                $data = $this->createAuth($uri->getPath(), [], 'POST' );
            // } else {
                // $data = $this->createAuth($uri->getPath(), ['side'=> $id], 'POST' );
            }
        } else {
            $uri = new Uri($this->url . 'order/delete.json');
            $data = $this->createAuth($uri->getPath(), ['id'=>(int) $id], 'POST' );
        }

        $requst =  new Request('POST', $uri,
            ['Content-Type' => 'application/x-www-form-urlencoded'],
            Psr7\stream_for($data));
        $response = $this->client->send($requst);
        return json_decode($response->getBody(), true);
    }
    /**
     * get all orders
     */
    public function getorders(string $pair, array $parameters = array())
    {
        $uri = new Uri($this->url . 'orders.json');
        $parameters['market'] = $pair;
        $data = $this->createAuth($uri->getPath(), $parameters, 'GET');
        $response = $this->client->request('GET', $uri->withQuery($data));
        return json_decode($response->getBody(), true);
    }

    /**
     * get order details by id
     */
    public function getorder(string $id)
    {
        $uri = new Uri($this->url . 'order.json');
        $data = $this->createAuth($uri->getPath(),['id'=>$id], 'GET');
        $response = $this->client->request('GET', $uri->withQuery($data));
        return json_decode($response->getBody(), true);
    }
    /**
     * Get details of specific deposit
     */
    public function deposit(string $txid)
    {
        $uri = new Uri($this->url . 'deposit.json');
        $data = $this->createAuth($uri->getPath(),['txid' => $txid], 'GET');
        $response = $this->client->request('GET', $uri->withQuery($data));
        return json_decode($response->getBody(), true);
    }

    /**
     * fetch deposit history
     * supportted fiter keys
     * [
     *   'currency' =>'' // btc, aud, eth etc
     *   'limit' =>  10 // result limit
     *   'state' => '' // deposit status
     * ]
     */
    public function deposits(array $parameters = array())
    {
        $uri = new Uri($this->url . 'deposits.json');
        $data = $this->createAuth($uri->getPath(), $parameters, 'GET');
        $response = $this->client->request('GET', $uri->withQuery($data));
        return json_decode($response->getBody(), true);
    }

    /**
     * get account info
     */
    public function me()
    {
        $uri = new Uri($this->url . 'members/me.json');
        $data = $this->createAuth($uri->getPath(), [], 'GET' );
        $response = $this->client->request('GET', $uri->withQuery($data));
        return json_decode($response->getBody(), true);
    }

    private function createAuth($path, $apiParams, $verb)
    {
        if (empty($this->key) || empty($this->secret)) {
            throw new ACXAPIException("API key and secrect can't be empty");
        }

        static $i=0;
        $mt = explode(' ', microtime());
        $apiParams['tonce'] = $mt[1] . substr($mt[0], 2, 3);
        $apiParams['tonce'] += $i++%900;
        $apiParams['access_key'] = $this->key;
        ksort($apiParams);
        $query = http_build_query($apiParams, '', '&');
        // Server received decoded value
        $query = preg_replace('/%5B[0-9]*([a-z]+)?%5D/simU', '[\1]', $query);
        $signature = hash_hmac('sha256', "{$verb}|{$path}|{$query}", $this->secret);
        return $query .'&signature=' . $signature;
    }
}
