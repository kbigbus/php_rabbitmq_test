<?php

//基于x-delay-message插件设置有效期的延迟队列   接收端
require_once __DIR__ . '/../vendor/autoload.php';
$config = require_once __DIR__ . '/../config.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;

$connection = new AMQPStreamConnection($config['rm_server'], $config['rm_port'], $config['rm_user'], $config['rm_pwd'], $config['rm_vhost']);
$channel    = $connection->channel();

$channel->exchange_declare('delay_exchange_v3', 'direct', false, false, false);

$channel->queue_declare('delay_queue_v3', false, false, false, false, false);
$channel->queue_bind('delay_queue_v3', 'delay_exchange_v3', 'delay_exchange_v3');

echo ' [*] Waiting for message. To exit press CTRL+C ' . PHP_EOL;

$callback = function ($msg) {
    echo date('Y-m-d H:i:s') . ' [x] Received',$msg->body,PHP_EOL;

    $msg->delivery_info['channel']->basic_ack($msg->delivery_info['delivery_tag']);
};

//只有consumer已经处理并确认了上一条message时queue才分派新的message给它
$channel->basic_qos(null, 1, null);
$channel->basic_consume('delay_queue_v3', '', false, false, false, false, $callback);

while (count($channel->callbacks)) {
    $channel->wait();
}
$channel->close();
$connection->close();
