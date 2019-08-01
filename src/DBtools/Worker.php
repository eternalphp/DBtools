<?php

namespace DBtools;

class Worker
{
    /**
     * 创建任务进程
	 * @param string $cmdFile
	 * @param array $data 传递参数
     * @return void
     */
	public static $taskProcessList = array();
	public static $total = 0;
	 
	public static function createTaskProcess($cmdFile){
		$pid = 1;
		while($pid <= 6){
		
			$start_file = dirname(dirname(__DIR__)) ."/". $cmdFile;
			$start_file = str_replace('/',DIRECTORY_SEPARATOR,$start_file);
			
			if(file_exists($start_file)){
				$std_file = $start_file . ".out.txt";

				$descriptorspec = array(
					0 => array('pipe', 'a'), // stdin
					1 => array('pipe','w'), // stdout
					2 => array('file', $std_file, 'w') // stderr
				);

				$pipes       = array();
				$process     = proc_open("php \"$start_file\" -q", $descriptorspec, $pipes); //创建任务进程
				$std_handler = fopen($std_file, 'a+');
				stream_set_blocking($std_handler, 0);
				Worker::$taskProcessList[] = array($process,$pipes);
			}
			
			$pid++;
		}
		
	}
	
	public static function loop(){
		while(true){
			if(Worker::$taskProcessList){
				foreach(Worker::$taskProcessList as $k=>$val){
					$process = $val[0];
					$pipes = $val[1];
					$status = proc_get_status($process);
					if(!$status['running']){
						unset(Worker::$taskProcessList[$k]);
					}
				}
			}
			
			Worker::output();
			
			sleep(1);
		}
	}
	
	public static function output(){
		
		$complate = 0;
		if(file_exists("run.log")){
			$count = 100;
			$lines = file("run.log");
			$complate = count($lines);
			$msg = end($lines);
			$msg = str_replace("\n","",$msg);
			$num = (100*$complate)/Worker::$total;
			
			printf("\r [%-100s] (%2d%%/%2d%%) [%-50s]", str_repeat("=", $num) . ">", ($num / $count) * 100, $count,$msg);
		}
		
		if($complate >= Worker::$total){
			
			$list = glob("*.sql");
		
			$header = array();
			$header[] = "/*";
			$header[] = "Navicat MySQL Data Transfer";
			$header[] = "Source Server         : 47.100.167.98";
			$header[] = "Source Server Version : 50719";
			$header[] = "Source Host           : 47.100.167.98:3306";
			$header[] = "Source Database       : school";
			$header[] = "Target Server Type    : MYSQL";
			$header[] = "Target Server Version : 50719";
			$header[] = "File Encoding         : 65001";
			$header[] = "Date: 2019-07-05 11:16:40";
			$header[] = "*/";
			$header[] = "";
			$header[] = "SET FOREIGN_KEY_CHECKS=0;";
			file_put_contents("school.sql",implode("\n",$header));
			file_put_contents("school.sql","\n\n",FILE_APPEND);
			
			if($list){
				foreach($list as $file){
					file_put_contents("school.sql",file_get_contents($file)."\n\n",FILE_APPEND);
					unlink($file);
				}
			}

			exit;
		}
	}
}	
?>