<?php

namespace Bots;

use API\IBinaryAPI;

class BotSimple extends BotBasic
{
    const CAP = 5;
    const TREND = 2;
    const POINT = 0.00000001;

    protected $tableId;
    protected $channel;
    protected $timeframe;
    protected $currency;
    protected $amount;

    protected $balance;

    protected $candles = [];
    protected $currentCandleTime = 0;
    protected $lastOrderCandleTime = 0;

    /**
     * BotSimple constructor.
     * @param IBinaryAPI $api
     * @param $exchange
     * @param $symbol
     * @param $timeframe
     * @param $limit
     * @param $currency
     * @param $amount
     */
    public function __construct(IBinaryAPI $api, $exchange, $symbol, $timeframe, $limit, $currency, $amount)
    {
        parent::__construct($api);

        $this->currency = strtoupper($currency);
        $this->timeframe = intval($timeframe);
        $this->amount = floatval($amount);

        $this->channel = strtoupper("CANDLES--$exchange-$symbol--$timeframe");
        $this->tableId = strtolower("$exchange--$symbol--$timeframe--$limit--single:$currency--pvp");
    }

    protected function onInit()
    {
        $this->balance = floatval($this->api->getBalance()['data']['FREE'][$this->currency]);

        $this->setReconnect(true);
        $this->subscribe($this->channel);

        $this->log('init');
    }

    protected function onTick(array $data)
    {
        if ($this->amount > $this->balance) {
            $this->stop();
            return;
        }

        if ( !$this->updateCandles($data) ) {
            return;
        }

        $type = $this->condition();
        if (!$type) {
            return;
        }

        try {
            $order = [
                'amount' => $this->amount,
                'currency' => $this->currency,
                'table_id' => $this->tableId,
                'type' => $type,
            ];

            $data = $this->api->orderCreate($order);
            if ( empty($data['data']['order_id']) ) {
                return;
            }

            $this->balance = floatval($data['balance']['FREE'][$this->currency]);

            $this->lastOrderCandleTime = $this->currentCandleTime;

            $this->log("order {$data['data']['order_id']} created [" . implode(' ', $order) . "] balance: {$this->balance}");

        } catch (\Exception $e) {
            $this->log($e->getMessage());
        }
    }

    /**
     * @return int
     */
    protected function condition()
    {
        if ($this->currentCandleTime <= $this->lastOrderCandleTime) {
            return 0;
        }

        $time = $this->currentCandleTime + $this->timeframe;
        if ( !isset($this->candles[$time]) ) {
            return 0;
        }

        $dir = floatval($this->candles[$time]['close']) - floatval($this->candles[$time]['open']);
        if ( abs($dir) < self::POINT ) {
            return 0;
        }

        $dir = $dir > 0 ? 1 : -1;

        $type = 0;

        for ($i = 2; $i < self::CAP; ++$i) {
            $time += $this->timeframe;
            if ( !isset($this->candles[$time]) ) {
                return $type;
            }

            if ( ( floatval($this->candles[$time]['close']) - floatval($this->candles[$time]['open']) )*$dir <= self::POINT ) {
                return $type;
            }

            if ($i == self::TREND) {
                $type = $dir;
            }
        }

        $type = -$type;

        return $type;
    }

    /**
     * @param array $data
     * @return bool
     */
    protected function updateCandles(array $data)
    {
        if ( empty($data['candles']) ) {
            return false;
        }

        foreach ($data['candles'] as $csv) {
            $candle = $this->getOHLCVT($csv);
            $this->candles[ $candle['time'] ] = $candle;
        }

        krsort($this->candles);

        $rest = count($this->candles) - self::CAP;

        for ($i = 0; $i < $rest; ++$i) {
            array_pop($this->candles);
        }

        foreach ($this->candles as $candle) {
            $this->currentCandleTime = $candle['time'];
            break;
        }

        return true;
    }

    /**
     * @param $message
     */
    protected function log($message)
    {
        echo date('Y-m-d H:i:s') . ': ' . $message . "\n";
    }
}