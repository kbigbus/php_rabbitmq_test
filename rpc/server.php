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

$channel = new AMQPChannel($connection);

$routing_key = 'amqp_rpc_queue';
$exchange = new AMQPExchange($channel);

echo ' [*] Waiting for messages. To exit press CTRL+C', "\n";
$callback_func = function(AMQPEnvelope $message, AMQPQueue $q) use (&$exchange,&$routing_key) {
	echo " [x] Received ", $message->getBody(), PHP_EOL;
	$data = "Server get the msg:".$message->getBody();
	$attributes = array(
			'correlation_id' => $message->getCorrelationId()
		);
	$exchange->publish($data, $message->getReplyTo(), AMQP_NOPARAM, $attributes);

	$q->nack($message->getDeliveryTag());
};
try{
	$queue = new AMQPQueue($channel);
	$queue->setName($routing_key);
	$queue->setFlags(AMQP_DURABLE);//持久化
	$queue->declareQueue();
	$queue->consume($callback_func);
}catch(AMQPQueueException $ex){
	print_r($ex);
}catch(Exception $ex){
	print_r($ex);
}

