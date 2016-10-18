# php_rabbitmq_test
利用PHP的amqp扩展 测试rabbitmq的相关特性<br>
rabbitmq 服务端 3.3.3<br>
amqp 版本 1.2.0<br>
php-amqplib 版本 2.5.*

##坑爹问题记录  出现问题查日志 （默认路径 /var/log/rabbitmq/rabbitmq*******）
1/  amqp_error,access_refused,    "PLAIN login refused: user 'xxxx' - invalid credentials", <br>

出现这个问题是因为当前登陆的用户没有 vhost（默认是/）的权限，即便是管理员也需要先通过 rabbitmqctl set_permissions user ".*" ".*" ".*" 命令来设置权限
