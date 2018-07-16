<?php

namespace API\V1;


use WebSocket\Exception;

class SocketCluster extends \SocketCluster\SocketCluster
{
    /**
     * @var array
     */
    protected $channels = [];

    /**
     * @param array $data
     * @return bool
     */
    public function handshake(array $data = [])
    {
        return $this->emit('#handshake', $data);
    }

    /**
     * @param $channel
     * @return bool
     */
    public function subscribe($channel)
    {
        if ( !$this->emit('#subscribe', ['channel' => $channel]) ) {
            return false;
        }

        $this->channels[] = $channel;

        return true;
    }

    /**
     * @param \Closure $watch
     * @return bool
     */
    public function watch(\Closure $watch)
    {
        try {
            $continue = true;

            while ($continue) {
                $payload = $this->websocket->receive();

                if ($payload === '#1') {
                    $this->websocket->send('#2');
                    continue;
                }

                $data = json_decode($payload, true);
                if ( empty($data['event']) ) {
                    continue;
                }
                if ( $data['event'] !== '#publish' ) {
                    continue;
                }
                if ( empty($data['data']['channel']) || empty($data['data']['data']) ) {
                    continue;
                }
                if ( !in_array($data['data']['channel'], $this->channels) ) {
                    continue;
                }

                $continue = boolval( $watch($data['data']['data']) );
            }

        } catch (Exception $e) {
            $this->error = $e->getMessage();
            return false;
        }

        return true;
    }
}