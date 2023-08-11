<?php

namespace App\Services;

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

class RabbitMQService
{
    public function publish($message)
    {
        $connection = new AMQPStreamConnection(
            env('MQ_HOST'),
            env('MQ_PORT'),
            env('MQ_USER'),
            env('MQ_PASSWORD'),
            env('MQ_VHOST')
        );
        $channel = $connection->channel();
        $channel->exchange_declare('tut_exc', 'direct', false, false, false, false);
        $channel->queue_declare('tut_queue', false, true, false, false);
        $channel->queue_bind('tut_queue', 'tut_exc', 'tut_key');

        $msg = new AMQPMessage($message);
        $channel->basic_publish($msg, 'tut_exc', 'tut_key', false, false, false);
        echo "publishing '$message' to tut_exc/tut_queue";
        $channel->close();
        $connection->close();
    }
    public function consume()
    {
        $connection = new AMQPStreamConnection(
            env('MQ_HOST'),
            env('MQ_PORT'),
            env('MQ_USER'),
            env('MQ_PASSWORD'),
            env('MQ_VHOST')
        );
        $channel = $connection->channel();
        $channel->queue_declare('tut_queue', false, true, false, false);
        $callBack = function (AMQPMessage $msg) {
            echo $msg->getBody(), " received";
            $msg->ack();  
        };
        $channel->basic_consume('tut_queue', '', false, false, false, false, $callBack);
        echo 'waiting for message...',"\n";
        while($channel->is_consuming()){
            $channel->wait();
        }
        $channel->close();
        $connection->close();
    }
}