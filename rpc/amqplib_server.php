<?php
require_once __DIR__ . '/../vendor/autoload.php';
$config = require_once __DIR__ . '/../config.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

$connection = new AMQPStreamConnection($config['rm_server'], $config['rm_port'], $config['rm_user'], $config['rm_pwd'], $config['rm_vhost']);
$channel = $connection->channel();

$channel->queue_declare('rpc_queue', false, false, false, false);

echo "[x] Waiting Msg Comming\n";
$callback = function($req){
	echo "Get msg:".$req->body."\n";
	sleep(10);
	$msg = new AMQPMessage('handle the data:'.$req->body, array('correlation_id'=> $req->get('correlation_id')));
	$req->delivery_info['channel']->basic_publish($msg, '', $req->get('reply_to'));
	$req->delivery_info['channel']->basic_ack($req->delivery_info['delivery_tag']);
};

$channel->basic_qos(null, 1, null);
$channel->basic_consume('rpc_queue', '', false, false, false, false, $callback);

while(count($channel->callbacks)) {
    $channel->wait();
}

$channel->close();
$connection->close();