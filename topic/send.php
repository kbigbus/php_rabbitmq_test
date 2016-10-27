<?php

$config = require_once __DIR__ . '/../config.php';

$data = implode(' ', array_slice($argv, 2));
if(empty($data)) $data = "Hello World!";

//Establish connection to AMQP
$initConfig = array(
    'host' => $config['rm_server'], 
    'port' => $config['rm_port'], 
    'login' => $config['rm_user'], 
    'password' => $config['rm_pwd'], 
    'vhost' => $config['rm_vhost']
);
$connection = new AMQPConnection($initConfig);

$connection->connect();
// if(!$connection->connect()) {
// 	exit('connect failed');
// }
//Create and declare channel
$channel = new AMQPChannel($connection);
//AMQPC Exchange is the publishing mechanism
$exchangeName = 'amqp_topic_logs';
$exchange = new AMQPExchange($channel);
$exchange->setName($exchangeName);
$exchange->setType(AMQP_EX_TYPE_TOPIC);
$exchange->declareExchange();

$routing_key = isset($argv[1]) ? $argv[1] : '#';//未设置则 广播模式 设置routing-key的 receive将无法接收
echo "routing_key: $routing_key, publish data:$data\n";

$queue = new AMQPQueue($channel);
$queue->setFlags(AMQP_DURABLE);
$queue->declareQueue();
$result = $exchange->publish($data, $routing_key);
echo "publish success\n";
$connection->disconnect();
