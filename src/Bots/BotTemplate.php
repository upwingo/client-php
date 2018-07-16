<?php

namespace Bots;

use API\IBinaryAPI;

class BotTemplate extends BotBasic
{
    /**
     * BotTemplate constructor.
     * @param IBinaryAPI $api
     */
    public function __construct(IBinaryAPI $api)
    {
        parent::__construct($api);
    }

    protected function onInit()
    {
        // TODO: Implement onInit() method.
    }

    protected function onTick(array $data)
    {
        // TODO: Implement onTick() method.
    }
}