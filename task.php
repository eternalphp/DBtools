<?php

@set_time_limit(3600*2);
@ini_set('memory_limit','2028M');//2G;

require __DIR__ . "/vendor/autoload.php";

use framework\Database\Schema\Control;
use framework\Database\Connection\Connector;

$config = array(
	'driver'=>'MySqli',
	'servername'=>'127.0.0.1',
	'username'=>'root',
	'password'=>'123',
	'table'=>'school'
);

$taskList = array();
$connector = new Connector($config);
$connector->connect();
$pid = getmypid();

while(true){
	
	if(file_exists("task.json")){
		$data = file_get_contents("task.json");
		$list = json_decode($data,true);
		$json = array_shift($list);
		
		file_put_contents("task.json",json_encode($list));
		if($json){
			
			$json["pid"] = $pid;
			$filename = sprintf("%s_%d.sql",$config["table"],$pid);
		
			$start = time();
			$table = $json["Tables_in_school"];
			$row = $connector->query("show create table $table")->find();
			$sql = $row["Create Table"];
			
			//echo "Create table: $table";
			file_put_contents($filename,"-- ----------------------------\n",FILE_APPEND);
			file_put_contents($filename,"-- Table structure for $table \n",FILE_APPEND);
			file_put_contents($filename,"-- ----------------------------\n",FILE_APPEND);
			file_put_contents($filename,"DROP TABLE IF EXISTS `$table`; \n",FILE_APPEND);
			file_put_contents($filename,"$sql; \n\n",FILE_APPEND);
			
			//echo "Get table data for: $table";
			file_put_contents($filename,"-- ----------------------------\n",FILE_APPEND);
			file_put_contents($filename,"-- Records of $table \n",FILE_APPEND);
			file_put_contents($filename,"-- ----------------------------\n",FILE_APPEND);
			
			$offset = 0;
			$pageSize = 1000;
			$list = array();
			$hasData = false;
			do{
				$line = array();
				$list = $connector->query("select * from $table limit $offset,$pageSize")->select();
				if($list){
					$hasData = true;
					foreach($list as $k=>$val){
						$line[] = "INSERT INTO `$table` VALUES ('".implode("','",array_values($val))."');";
					}
					file_put_contents($filename,implode("\n",$line)."\n",FILE_APPEND);
					unset($line,$list);
					$offset = $offset + $pageSize;
				}else{
					$hasData = false;
				}
				
			}while($hasData == true);
			$end = time();
			$time = $end - $start;
			
			file_put_contents($filename,"\n\n",FILE_APPEND);
			
			file_put_contents("run.log","Create table: $table $time(s) \n",FILE_APPEND);
		}else{
			exit;
		}
	}
	
	sleep(1);
}
?>