<?php

namespace API;

use API\V1\UpwingoException;

interface IBinaryAPI
{
    public function ticker(array $channels, \Closure $onTick);
    public function getTablesList();
    public function getNextRoundInfo(array $params);
    public function getHistory(array $params);
    public function orderCreate(array $params);
    public function orderCancel($orderId);
    public function getBalance();
}