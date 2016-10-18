# php_rabbitmq_test
利用PHP的amqp扩展 测试rabbitmq的相关特性<br>
rabbitmq 服务端 3.3.3<br>
amqp 版本 1.2.0<br>
php-amqplib 版本 2.5.*

##rabbitmqctl 命令操作<br>
参见官方文档  http://previous.rabbitmq.com/v3_3_x/man/rabbitmqctl.1.man.html

##坑爹问题记录  出现问题查日志 （默认路径 /var/log/rabbitmq/rabbitmq*******）
1/  amqp_error,access_refused,    "PLAIN login refused: user 'xxxx' - invalid credentials", <br>

出现这个问题是因为当前登陆的用户没有 vhost（默认是/）的权限，即便是管理员也需要先通过 rabbitmqctl set_permissions user ".*" ".*" ".*" 命令来设置权限<p>

2/ PHP Fatal error:  Uncaught exception 'PhpAmqpLib\Exception\AMQPProtocolChannelException' with message 'PRECONDITION_FAILED - parameters for queue 'hello' in vhost '/' not equivalent' in /mnt/hgfs/GIT/php_rabbitmq_test/vendor/php-amqplib/php-amqplib/PhpAmqpLib/Channel/AMQPChannel.php:191<br>

这是由于测试时另一个脚本用了 hello 这个队列导致的，改另一个名就行了。  补上网络上的解答（一般原因：建立channel或queue的参数不同。特别是declare已有的同名queue时容易出现此错误。解决办法：删除rabbitmq-server上相应的queue，重新建立）<p>

