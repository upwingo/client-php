<?php

require_once __DIR__ . '/src/bootstrap.php';

mb_internal_encoding('UTF-8');
date_default_timezone_set('UTC');

ini_set("memory_limit", "1024M");
ini_set("max_execution_time", "0");

$upwingo = new \API\V1\Upwingo([
    'api_key' => 'XXXXXXX'
]);

$bot = new \Bots\BotSimple(
    $upwingo,
    'bina', 'btc_usdt', 10, 'micro', 'FREE', 10
);

$bot->run();
