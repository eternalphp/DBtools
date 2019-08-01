<?php

require __DIR__ . "/vendor/autoload.php";

use DBtools\Worker;
use framework\Database\Schema\Control;
use framework\Database\Connection\Connector;

$config = array(
	'driver'=>'MySqli',
	'servername'=>'127.0.0.1',
	'username'=>'root',
	'password'=>'123',
	'table'=>'school'
);

$connector = new Connector($config);
$connector->connect();

Worker::createTaskProcess("task.php");

$list = $connector->query("show tables")->select();
Worker::$total = count($list);
file_put_contents("task.json",json_encode($list));

Worker::loop();	
?>