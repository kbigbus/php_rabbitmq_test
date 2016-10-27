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

$connection->connect();
// if(!$connection->connect()) {
// 	exit('connect failed');
// }
//Create and declare channel
$channel = new AMQPChannel($connection);
//$channel->qos(null, 1);
//AMQPC Exchange is the publishing mechanism
$exchange = new AMQPExchange($channel);

$routing_key = empty($routing_key) ? 'hello' : $routing_key;

$consumeType = isset($argv[1]) ? $argv[1] : '';
$consumeType = in_array($consumeType, array('direct', 'get')) ? $consumeType : 'direct';
switch($consumeType) {
	case 'get':
		$queue = new AMQPQueue($channel);
		$queue->setName($routing_key);
		$queue->setFlags(AMQP_DURABLE);
		$queue->declareQueue();

		while ($message = $queue->get(AMQP_AUTOACK)) {
			$data = $message->getBody();
			print_r("get the data ".$data."\n");
		}
		$queue->delete();
		break;
	case 'direct':
		echo ' [*] Waiting for messages. To exit press CTRL+C', "\n";
		$callback_func = function(AMQPEnvelope $message, AMQPQueue $q) use (&$exchange,&$routing_key) {
			echo " [x] Received ", $message->getBody(), PHP_EOL;
			$q->nack($message->getDeliveryTag());
		};
		try{
			$queue = new AMQPQueue($channel);
			$queue->setName($routing_key);
			//$queue->setFlags(AMQP_NOPARAM);
			$queue->setFlags(AMQP_DURABLE);//持久化
			$queue->declareQueue();
			$queue->consume($callback_func);
		}catch(AMQPQueueException $ex){
			print_r($ex);
		}catch(Exception $ex){
			print_r($ex);
		}
		break;
}
