<?php
require_once __DIR__ . '/../vendor/autoload.php';
$config = require_once __DIR__ . '/../config.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
$connection = new AMQPStreamConnection($config['rm_server'], $config['rm_port'], $config['rm_user'], $config['rm_pwd']);
$channel = $connection->channel();

$channel->queue_declare('hello', false, false, false, false);

echo ' [*] Waiting for messages. To exit press CTRL+C', "\n";

$channel->close();
$connection->close();