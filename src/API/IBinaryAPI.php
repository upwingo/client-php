<?php

namespace API;

interface IBinaryAPI
{
    /**
     * @param array $channels
     * @param \Closure $onTick
     * @throws IException
     * @throws ISocketException
     */
    public function ticker(array $channels, \Closure $onTick);

    public function getTablesList();
    public function getNextRoundInfo(array $params);
    public function getHistory(array $params);
    public function orderCreate(array $params);
    public function orderCancel($orderId);
    public function getBalance();
}