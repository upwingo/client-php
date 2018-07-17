<?php

namespace Bots;

use API\IBinaryAPI;
use API\IException;
use API\ISocketException;

abstract class BotBasic
{
    /**
     * @var array
     */
    protected $channels = [];

    /**
     * @var bool
     */
    protected $reconnect = false;

    /**
     * @var bool
     */
    protected $stopped = false;

    /**
     * @var IBinaryAPI
     */
    protected $api;

    /**
     * BotBasic constructor.
     * @param IBinaryAPI $api
     */
    public function __construct(IBinaryAPI $api)
    {
        $this->api = $api;
    }

    /**
     * @throws IException
     */
    public function run()
    {
        $this->stopped = false;

        $this->onInit();

        while ( !$this->isStopped() ) {
            try {
                $this->api->ticker($this->channels, function ($data) {
                    $this->onTick($data);
                    return !$this->isStopped();
                });

            } catch (ISocketException $e) {
                if ( !$this->isReconnect() ) {
                    throw $e;
                }
            }
        }
    }

    protected function stop()
    {
        $this->stopped = true;
    }

    /**
     * @return bool
     */
    protected function isStopped()
    {
        return $this->stopped;
    }

    /**
     * @param $channel
     */
    protected function subscribe($channel)
    {
        $this->channels[] = $channel;
    }

    /**
     * @return bool
     */
    protected function isReconnect()
    {
        return $this->reconnect;
    }

    /**
     * @param bool $reconnect
     * @return BotBasic
     */
    protected function setReconnect($reconnect)
    {
        $this->reconnect = $reconnect;
        return $this;
    }

    /**
     * @param $csv
     * @return array
     */
    protected function getOHLCVT($csv)
    {
        $candle = array_map('trim', explode(',', $csv));

        return [
            'open' => $candle[1],
            'high' => $candle[2],
            'low' => $candle[3],
            'close' => $candle[4],
            'volume' => $candle[5],
            'time' => intval($candle[0]),
        ];
    }

    abstract protected function onInit();
    abstract protected function onTick(array $data);
}