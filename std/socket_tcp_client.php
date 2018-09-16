<?php 
//创建连接
$socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP); 
if(!$socket) die("create server fail:".socket_strerror(socket_last_error())."\n");

//连接server
$ret = socket_connect($socket, "127.0.0.1", 9201);
if(!$ret) die("client connect fail:".socket_strerror(socket_last_error())."\n");

//发送消息
socket_write($socket, "hello, I'm client!\n");

//读取消息
$buffer = socket_read($socket, 1024);
echo "from server: $buffer\n";

//关闭连接
socket_close($socket);