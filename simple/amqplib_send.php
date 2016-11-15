<?php
require_once __DIR__ . '/../vendor/autoload.php';
$config = require_once __DIR__ . '/../config.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

$connection = new AMQPStreamConnection($config['rm_server'], $config['rm_port'], $config['rm_user'], $config['rm_pwd'], $config['rm_vhost']);
$channel = $connection->channel();
$channel->set_ack_handler(
    function (AMQPMessage $message) {
        echo "Message acked with content " . $message->body . PHP_EOL;
    }
);

$channel->set_nack_handler(
    function (AMQPMessage $message) {
        echo "Message nacked with content " . $message->body . PHP_EOL;
    }
);

$channel->confirm_select();

$channel->queue_declare('world', false, false, false, false);

$msg = new AMQPMessage('Hello World!', array('correlation_id'=>uniqid()));
$channel->basic_publish($msg, '', 'world');

echo " [x] Sent 'Hello World!'\n";

$channel->wait_for_pending_acks_returns();

$channel->close();
$connection->close();