<?php

$config = require_once __DIR__ . '/../config.php';

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
//$channel->qos(null, 1);
//AMQPC Exchange is the publishing mechanism
$exchangeName = 'amqp_direct_logs';
$exchange = new AMQPExchange($channel);
$exchange->setName($exchangeName);
$exchange->setType(AMQP_EX_TYPE_DIRECT);
$exchange->declareExchange();

$severity = isset($argv[1]) && !empty($argv[1]) ? $argv[1] : 'info';

$data = implode(' ', array_slice($argv, 2));
if(empty($data)) $data = "Hello World!";

$severities = array_slice($argv, 1);
if(empty($severities )) {
    file_put_contents('php://stderr', "Usage: $argv[0] [info] [warning] [error]\n");
    exit(1);
}

echo ' [*] Waiting for messages. To exit press CTRL+C', "\n";
$callback_func = function(AMQPEnvelope $message, AMQPQueue $q) use (&$exchange,&$routing_key) {
	echo " [x] Received ", $message->getBody(), PHP_EOL;
	$q->nack($message->getDeliveryTag());
};
try{
	$queue = new AMQPQueue($channel);
	$queue->setFlags(AMQP_DURABLE);//持久化
	$queue->declareQueue();
	foreach($severities as $severity) {
	    $queue->bind($exchangeName, $severity);
	}
	$queue->consume($callback_func);
}catch(AMQPQueueException $ex){
	print_r($ex);
}catch(Exception $ex){
	print_r($ex);
}

