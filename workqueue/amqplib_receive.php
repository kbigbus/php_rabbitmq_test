<?php
require_once __DIR__ . '/../vendor/autoload.php';
$config = require_once __DIR__ . '/../config.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

$connection = new AMQPStreamConnection($config['rm_server'], $config['rm_port'], $config['rm_user'], $config['rm_pwd'], $config['rm_vhost']);
$channel    = $connection->channel();

$channel->queue_declare('task_queue', false, true, false, false);

echo ' [*] Waiting for messages. To exit press CTRL+C', "\n";

$callback = function ($msg) {
    try {
        $rand = rand(1, 5);
        if ($rand >= 4) {
            //模拟处理异常消息
            throw new Exception('Msg lost', 1);
        }

        echo ' [x] Received ', $msg->body,  "\n";
        sleep(substr_count($msg->body, '.'));
        echo ' [x] Done', "\n";
        $msg->delivery_info['channel']->basic_ack($msg->delivery_info['delivery_tag']);
    } catch (Exception $ex) {
        echo $ex->getMessage(),  "\n";
        //异常消息拒绝并重新添加到队列
        $msg->delivery_info['channel']->basic_nack($msg->delivery_info['delivery_tag'], false, true);
    }
};

$channel->basic_qos(null, 1, null);
$channel->basic_consume('task_queue', '', false, false, false, false, $callback);

while (count($channel->callbacks)) {
    $channel->wait();
}

$channel->close();
$connection->close();
