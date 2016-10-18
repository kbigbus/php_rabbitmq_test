<?php

$config = require_once __DIR__ . '/../config.php';

$routing_key = 'hello';
//Establish connection to AMQP
$initConfig = array(
    'host' => $config['rm_server'], 
    'port' => $config['rm_port'], 
    'login' => $config['rm_user'], 
    'password' => $config['rm_pwd'], 
    'vhost' => $config['rm_vhost']
);
$connection = new AMQPConnection($initConfig);

if(!$connection->connect()) {
	exit('connect failed');
}
//Create and declare channel
$channel = new AMQPChannel($connection);
//AMQPC Exchange is the publishing mechanism
$exchange = new AMQPExchange($channel);

$routing_key = empty($routing_key) ? 'hello' : $routing_key;

$queue = new AMQPQueue($channel);
$queue->setName($routing_key);
$queue->setFlags(AMQP_DURABLE);
$queue->declareQueue();
$result = $exchange->publish('heiheihei', $routing_key);
echo 'publish success';
$connection->disconnect();
