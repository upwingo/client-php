<?php

namespace API\V1;

use API\IBinaryAPI;
use SocketCluster\WebSocket;

class Upwingo implements IBinaryAPI
{
    protected static $configDefault = [
        'ws_host' => 'ws.upwingo.com',
        'ws_port' => 443,
        'ws_secure' => true,
    ];

    protected $config;

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
     * @param array $params
     * @return array
     * @throws UpwingoException
     */
    public function orderCreate(array $params)
    {
        // TODO: Implement orderCreate() method.
        return [];
    }

    /**
     * @return array
     */
    public function getBalance()
    {
        // TODO: Implement getBalance() method.
        return [];
    }
}