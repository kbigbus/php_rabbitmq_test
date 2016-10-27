<?php

$config = require_once __DIR__ . '/../config.php';

class RcpClient {
	private $connection;
	private $channel;
	private $exchange;
	private $queue;
	private $corId;

	public function __construct(){
		global $config;
		$initConfig = array(
		    'host' => $config['rm_server'], 
		    'port' => $config['rm_port'], 
		    'login' => $config['rm_user'], 
		    'password' => $config['rm_pwd'], 
		    'vhost' => $config['rm_vhost']
		);
		$this->connection = new AMQPConnection($initConfig);
		$this->connection->connect();
		$this->channel = new AMQPChannel($this->connection);
		$this->channel->setPrefetchCount(1);
		$this->exchange = new AMQPExchange($this->channel);

	}

	public function callBack(AMQPEnvelope $msg, AMQPQueue $q) {
		if($msg->getCorrelationId() == $this->corId){//保证唯一
			echo $msg->getBody().PHP_EOL;
			$q->nack($msg->getDeliveryTag());
			return false;
		}
	}

	public function call($val) {
		$this->corId = uniqid();

		$this->queue = new AMQPQueue($this->channel);
		$this->queue->declareQueue();

		$attributes = array(
			'reply_to' => $this->queue->getName(),
			'correlation_id' => $this->corId,
		);

		$this->exchange->publish($val, 'amqp_rpc_queue', AMQP_NOPARAM, $attributes);

		$this->queue->consume(array($this, 'callBack'));

	}

}

$clientmsg = implode(" ", array_slice($argv, 1));
$clientmsg = $clientmsg ? $clientmsg : 'client msg';
$client = new RcpClient();
$client->call($clientmsg);
