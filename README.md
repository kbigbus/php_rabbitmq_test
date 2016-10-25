# php_rabbitmq_test
利用PHP的amqp扩展 测试rabbitmq的相关特性<br>
rabbitmq 服务端 3.3.3<br>
amqp 版本 1.2.0 / php-amqplib 版本 2.5.* (两种客户端的区别)<br>

##rabbitmqctl 命令操作<br>
参见官方文档  http://previous.rabbitmq.com/v3_3_x/man/rabbitmqctl.1.man.html

##坑爹问题记录  出现问题查日志 （默认路径 /var/log/rabbitmq/rabbitmq*******）
1/  amqp_error,access_refused,    "PLAIN login refused: user 'xxxx' - invalid credentials", <br>

出现这个问题是因为当前登陆的用户没有 vhost（默认是/）的权限，即便是管理员也需要先通过 rabbitmqctl set_permissions user ".*" ".*" ".*" 命令来设置权限<p>

2/ PHP Fatal error:  Uncaught exception 'PhpAmqpLib\Exception\AMQPProtocolChannelException' with message 'PRECONDITION_FAILED - parameters for queue 'hello' in vhost '/' not equivalent' in /mnt/hgfs/GIT/php_rabbitmq_test/vendor/php-amqplib/php-amqplib/PhpAmqpLib/Channel/AMQPChannel.php:191<br>

这是由于测试时另一个脚本用了 hello 这个队列导致的，改另一个名就行了。  补上网络上的解答（一般原因：建立channel或queue的参数不同。特别是declare已有的同名queue时容易出现此错误。解决办法：删除rabbitmq-server上相应的queue，重新建立）<p>

##队列处理说明（p producer生成者 c consumer消费者）
###简单队列
一一对应关系 设置好对应的routekey 1p to 1c

###一对多
1p to Nc 一个生成者对应多个消费者， 需要确认的问题有 <br>
1、多进程的消息分配问题（rabbitmq自己解决）<br>
2、消息丢失恢复问题（一个消费者处理过程中挂掉了，保证当前这条消息能恢复处理。消息确认机制（ack/nack））<br>
3、rabbitmq 服务器挂掉（消息持久化durable，能解决大部分问题。 If you need a stronger guarantee then you can use publisher confirms.）<br>
4、多条复杂任务分配给一个c，多条简单任务分配给一个c。造成一个很忙一个很闲（消息公平调度，AMQPChannel::qos）<p>

###订阅