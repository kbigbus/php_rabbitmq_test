<?php

//基于message和queue有效期的延迟队列   发送端
require_once __DIR__ . '/../vendor/autoload.php';
$config = require_once __DIR__ . '/../config.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;

$connection = new AMQPStreamConnection($config['rm_server'], $config['rm_port'], $config['rm_user'], $config['rm_pwd'], $config['rm_vhost']);
$channel    = $connection->channel();

$channel->exchange_declare('delay_exchange', 'direct', false, false, false);
$channel->exchange_declare('cache_exchange', 'direct', false, false, false);

$tale = new AMQPTable();
//设置相关dead-letter参数
$tale->set('x-dead-letter-exchange', 'delay_exchange');
$tale->set('x-dead-letter-routing-key', 'delay_exchange');
$tale->set('x-message-ttl', 10000); //设置queue的超时时间  queue生成后不可更改

$channel->queue_declare('cache_queue', false, true, false, false, false, $tale); //过期设置作为参数传入
$channel->queue_bind('cache_queue', 'cache_exchange');

$channel->queue_declare('delay_queue', false, true, false, false, false);
$channel->queue_bind('delay_queue', 'delay_exchange');

$msg = new AMQPMessage('Hello World' . $argv[1], [
    'expiration'    => intval($argv[1]),
]);
$channel->basic_publish($msg, 'cache_exchange');
echo date('Y-m-d H:i:s') . " [x] Sent 'Hello World!' " . PHP_EOL;

$channel->close();
$connection->close();
