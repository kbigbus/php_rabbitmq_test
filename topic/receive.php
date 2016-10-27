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
$exchangeName = 'amqp_topic_logs';
$exchange = new AMQPExchange($channel);
$exchange->setName($exchangeName);
$exchange->setType(AMQP_EX_TYPE_TOPIC);
$exchange->declareExchange();

$routing_keys = array_slice($argv, 1);
if(!$routing_keys) {
	file_put_contents("php://stderr", "Usage: $argv[0] [routing_key]\n");
	exit;
}

echo ' [*] Waiting for messages. To exit press CTRL+C', "\n";
$callback_func = function(AMQPEnvelope $message, AMQPQueue $q) use (&$exchange,&$routing_key) {
	echo " [x] Received ", $message->getBody(), PHP_EOL;
	$q->nack($message->getDeliveryTag());
};

try{
	$queue = new AMQPQueue($channel);
	//$queue->setFlags(AMQP_NOPARAM);
	$queue->setFlags(AMQP_DURABLE);//持久化
	$queue->declareQueue();
	foreach($routing_keys as $routing_key) {
		$queue->bind($exchangeName, $routing_key);
	}
	$queue->consume($callback_func);
}catch(AMQPQueueException $ex){
	print_r($ex);
}catch(Exception $ex){
	print_r($ex);
}
		
