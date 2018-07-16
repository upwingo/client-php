<?php

namespace API\V1;

use API\IBinaryAPI;
use GuzzleHttp\Client;
use SocketCluster\WebSocket;

class Upwingo implements IBinaryAPI
{
    protected static $configDefault = [
        'ws_host' => 'ws.upwingo.com',
        'ws_port' => 443,
        'ws_secure' => true,
        'host' => 'api.upwingo.com',
        'secure' => true,
        'api_key' => ''
    ];

    /**
     * @var array
     */
    protected $config;

    /**
     * @var Client
     */
    protected $client;

    /**
     * Upwingo constructor.
     * @param array $config
     */
    public function __construct(array $config = [])
    {
        $this->config = array_merge(self::$configDefault, $config);
    }

    /**
     * @return SocketCluster
     */
    public function createSocket()
    {
        return new SocketCluster(WebSocket::factory([
            'secure' => $this->config['ws_secure'],
            'host' => $this->config['ws_host'],
            'port' => $this->config['ws_port'],
            'path' => '/socketcluster/',
        ]));
    }

    /**
     * @param array $channels
     * @param \Closure $onTick
     * @throws UpwingoException
     */
    public function ticker(array $channels, \Closure $onTick)
    {
        $socket = $this->createSocket();

        if ( !$socket->handshake(['authToken' => null]) ) {
            throw new UpwingoException($socket->error());
        }

        foreach ($channels as $channel) {
            if ( !$socket->subscribe($channel) ) {
                throw new UpwingoException($socket->error());
            }
        }

        if ( !$socket->watch($onTick) ) {
            throw new UpwingoException($socket->error());
        }
    }

    /**
     * @return array
     * @throws UpwingoException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getTablesList()
    {
        return $this->get('/v1/binary/tables');
    }

    /**
     * @param array $params
     * @return array
     * @throws UpwingoException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getNextRoundInfo(array $params)
    {
        return $this->get('/v1/binary/round', $params);
    }

    /**
     * @param array $params
     * @return array
     * @throws UpwingoException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getHistory(array $params)
    {
        return $this->get('/v1/binary/history', $params);
    }

    /**
     * @param array $params
     * @return array
     * @throws UpwingoException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function orderCreate(array $params)
    {
        return $this->post('/v1/binary/order', $params);
    }

    /**
     * @param $orderId
     * @return array
     * @throws UpwingoException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function orderCancel($orderId)
    {
        return $this->post("/v1/binary/order/$orderId/cancel");
    }

    /**
     * @return array
     * @throws UpwingoException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getBalance()
    {
        return $this->get('/v1/balance');
    }

    /**
     * @param string $uri
     * @param array $query
     * @return array
     * @throws UpwingoException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    protected function get($uri = '', $query = [])
    {
        $options = [];
        if ( !empty($query) ) {
            $options['query'] = $query;
        }

        return $this->request('GET', $uri, $options);
    }

    /**
     * @param string $uri
     * @param array $params
     * @return array
     * @throws UpwingoException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    protected function post($uri = '', $params = [])
    {
        $options = [];
        if ( !empty($params) ) {
            $options['json'] = $params;
        }

        return $this->request('POST', $uri, $options);
    }

    /**
     * @param $method
     * @param string $uri
     * @param array $options
     * @return array
     * @throws UpwingoException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    protected function request($method, $uri = '', $options = [])
    {
        try {
            $res = $this->client()->request($method, $uri, $options);
            $data = json_decode($res->getBody()->getContents(), true);

        } catch (\Throwable $e) {
            throw new UpwingoException($e->getMessage(), 0, $e);
        }

        if ( empty($data['code']) ) {
            throw new UpwingoException('bad response: ' . $res->getBody()->getContents());
        }

        if ( $data['code'] != 200 ) {
            throw new UpwingoException($res->getBody()->getContents(), intval($data['code']));
        }

        return $data;
    }

    /**
     * @return Client
     */
    protected function client()
    {
        if ( is_null($this->client) ) {
            $baseUri = $this->config['secure'] ? 'https' : 'http';
            $baseUri .= '://' . $this->config['host'];

            $this->client = new Client([
                'base_uri' => $baseUri,
                'timeout' => 30,
                'headers' => [
                    'Authorization' => "Bearer {$this->config['api_key']}"
                ]
            ]);
        }

        return $this->client;
    }
}