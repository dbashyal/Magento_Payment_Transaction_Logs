<?php
/*
 * author: @dbashyal
 */
require_once 'abstract.php';
require_once 'utility.php';

class Merge_Paypal extends Mage_Shell_Abstract
{
    
    public function run()
    {
        $file   	= $this->getArg('file');
        $paypal		= $this->getArg('data');
        $logfile	= $this->getArg('logfile');
        if(!$file || !$paypal || !$logfile) {
                print "\nPlease specifiy file merge_paypal.php --file filename --data paypal_data_filename --logfile allLogs.log.txt";
        }

        $missingcsvData	= Utility::convertCsvToArray($file); // array(167 => array([increment_id] => 100498176))
        $paypalcsvData	= Utility::convertCsvToArray($paypal); // array(67 => array([ Invoice Number] => 100498176))
		$logData		= file_get_contents($logfile);
		eval($logData);
		$default = array('TOKEN', 'PAYERID', 'AMT', 'ITEMAMT', 'TAXAMT', 'SHIPPINGAMT', 'L_NUMBER0', 'L_NAME0', 'L_QTY0', 'L_AMT0', 'EMAIL', 'FIRSTNAME', 'LASTNAME', 'MIDDLENAME', 'COUNTRYCODE', 'STATE', 'CITY', 'STREET', 'ZIP', 'PHONENUM', 'SHIPTOCOUNTRYCODE', 'SHIPTOSTATE', 'SHIPTOCITY', 'SHIPTOSTREET', 'SHIPTOZIP', 'SHIPTOPHONENUM', 'SHIPTOSTREET2', 'STREET2', 'SHIPTONAME', 'CUST_EMAIL', 'TRANSACTIONID');
		$default_keys = array_flip($default);
		array_walk($default_keys, function(&$value, $index){$value = '';});

        $missing = array();
        foreach ($missingcsvData as $key => $value) {
            $missing[$value['increment_id']] = trim($value['increment_id']);
        }
        //print_r($missing); // array([100498176] => 100498176)

        $paypal = array();
        foreach ($paypalcsvData as $key => $value) {
			$transaction_id = trim($value['Transaction ID']);
            $id = trim($value['Invoice Number']);
            if(isset($missing[$id])){
				$log_address = array();
				echo "\nlooking for:" . $transaction_id;
				if(isset($array[$transaction_id])){
					echo "\n --- found";
					$log_address = array_intersect_key($array[$transaction_id], $default_keys);
					$log_address = array_merge($default_keys, $log_address);
				} else {
					echo "\n --- not found";
				}
				$value = array_merge($value, $log_address);
                $paypal[$id] = $value;
            }
        }

        $fh = fopen('parsed_' . $file, 'w');
        if(!$fh) {
                print "Error opening file!";
                return false;
        }

        $i = 0;
        foreach ($paypal as $key => $value) {
            if(!$i++){
                fputcsv($fh, array_keys($value));
            }
            fputcsv($fh, $value);
        }
        fclose($fh);
    }
}


$app = new Merge_Paypal();
print "\n";
$app->run();
print "\n";
