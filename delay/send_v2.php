<?php

//基于queue有效期的延迟队列   发送端
require_once __DIR__ . '/../vendor/autoload.php';
$config = require_once __DIR__ . '/../config.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;

$connection = new AMQPStreamConnection($config['rm_server'], $config['rm_port'], $config['rm_user'], $config['rm_pwd'], $config['rm_vhost']);
$channel    = $connection->channel();

if (isset($argv[1])) {
    $expiration =  intval($argv[1]);
} else {
    $expiration = 1000;
}

$cache_exchange_name = 'cache_exchange' . $expiration;
$cache_queue_name    = 'cache_queue' . $expiration;

$channel->exchange_declare('delay_exchange', 'direct', false, false, false);
$channel->exchange_declare($cache_exchange_name, 'direct', false, false, false);

$tale = new AMQPTable();
$tale->set('x-dead-letter-exchange', 'delay_exchange');
$tale->set('x-dead-letter-routing-key', 'delay_exchange');
$tale->set('x-message-ttl', $expiration);
$channel->queue_declare($cache_queue_name, false, true, false, false, false, $tale);
$channel->queue_bind($cache_queue_name, $cache_exchange_name, '');

$channel->queue_declare('delay_queue', false, true, false, false, false);
$channel->queue_bind('delay_queue', 'delay_exchange', 'delay_exchange');

$msg = new AMQPMessage('Hello World' . $argv[1], [
    //'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT,
]);

$channel->basic_publish($msg, $cache_exchange_name, '');
echo date('Y-m-d H:i:s') . " [x] Sent 'Hello World!' " . PHP_EOL;

$channel->close();
$connection->close();
