<?php
ini_set('memory_limit', '1024M');
function remove_new_line($matches)
{
	$str = '=> ';
	if(isset($matches[1])){
		$val = preg_replace("/\n|\r/", ' ', $matches[1]);
		$val = preg_replace("/\(\s*([^\)]+)\s*\)/i", "$1", $val);
		$val = trim(preg_replace("/\s+/", " ", $val));
		
		if($val != 'Array ('){
			if(preg_match("/\)/", $val, $match)){
				$str .= '\'' . trim(str_replace(')', '\')', $val));
			} else {
				$str .= '\'' . $val . '\',';
			}
		} else {
			$str .= 'array(';
		}
	}
	return ($str .= "\n[");
}

$file = 'allLogs.log';
$log = file_get_contents($file);

$log = preg_replace('/.*: |\'|"/', '', $log);
$log = preg_replace_callback("/\=\>\s*([^\[]+)\[/s", "remove_new_line", $log);
$log = preg_replace('/\[|\]/', "'", $log);
$log = preg_replace('/Array/sm', 'array', $log);
$log = preg_replace('/\)/', '),', $log);
$string = '$array = array(' . trim($log, ',') . ');';
eval($string);

$tokens = array();
$token = 0;
foreach ($array as $row) {
	foreach ($row as $method => $data) {
		if(in_array($method, array('DoExpressCheckoutPayment','response'))){
			if(isset($data['TOKEN'])){
				$token = $data['TOKEN'];
			}
			if($method == 'DoExpressCheckoutPayment' && isset($data['EMAIL'])){
				$data['CUST_EMAIL'] = $data['EMAIL'];
			}
			if(!isset($tokens[$token]) || !is_array($tokens[$token])){
				$tokens[$token] = array();
			}
			$tokens[$token] = array_merge($tokens[$token], (array)$data);
		}
	}
}
$new_eval = '$array=array(';
foreach($tokens as $k => $v){
	if(!isset($v['TRANSACTIONID'])){
		continue;
	}
	$new_eval .= "\n'" . $v['TRANSACTIONID'] . "'=>array(\n";
	foreach($v as $key => $val){
		$new_eval .= "'{$key}'=>'{$val}',";
	}
	$new_eval .= "\n),";
}
$new_eval .= "\n);";

file_put_contents($file . '.txt', $new_eval);
