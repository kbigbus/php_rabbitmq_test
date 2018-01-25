<?php

//基于x-delay-message插件设置有效期的延迟队列   发送端
require_once __DIR__ . '/../vendor/autoload.php';
$config = require_once __DIR__ . '/../config.php';

use Enqueue\AmqpLib\AmqpConnectionFactory;
use Enqueue\AmqpTools\RabbitMqDelayPluginDelayStrategy;
use Enqueue\AmqpTools\RabbitMqDlxDelayStrategy;

$r_config = [
    'host'  => $config['rm_server'],
    'port'  => $config['rm_port'],
    'user'  => $config['rm_user'],
    'pass'  => $config['rm_pwd'],
    'vhost' => $config['rm_vhost'],
];
$context = (new AmqpConnectionFactory($r_config))->createContext();
//$context->setDelayStrategy(new RabbitMqDlxDelayStrategy()); //会根据时间生成多个queue  放弃
$context->setDelayStrategy(new RabbitMqDelayPluginDelayStrategy()); //生成单个队列  延迟未到不显示

$queue = $context->createQueue('delay_queue_v3');
$context->declareQueue($queue, false, true);

$producer = $context->createProducer();

$seconds = isset($argv[1]) ? (int) $argv[1] : 1000;
$message = $context->createMessage('Hello world!' . $seconds);

$producer->setDeliveryDelay($seconds);
$producer->send($queue, $message);
