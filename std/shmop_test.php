<?php 

$shm_id = shmop_open(time(), 'c', 0644, 100);
shmop_write($shm_id, 'hello', 0);
$res = shmop_read($shm_id, 0 ,50);
var_dump($res);