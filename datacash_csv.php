<?php
/*
 * author: @dbashyal
 */
require_once 'abstract.php';
require_once 'utility.php';

class Datacash_CSV extends Mage_Shell_Abstract
{
    
    public function run()
    {
        $file   	= $this->getArg('file');
        $logfile	= $this->getArg('logfile');
        if(!$file || !$logfile) {
            print "\nPlease specifiy file datacashToCsv.php --file datacash-missing.csv --logfile datacashRequestxmlAll.log";
            return;
        }

        $missingIds	    = Dse_Utility::convertCsvToArray($file); // array(167 => array([increment_id] => 100498176))
        $missing = array();
        foreach ($missingIds as $key => $value) {
            $missing[$value['increment_id']] = trim($value['increment_id']);
        }

        $xmls           = explode("\n", preg_replace("/\>\s*\n\s*\</", '><', file_get_contents($logfile)));
        $xmlData        = array();
        foreach ($xmls as $key => &$value) {
            if(strpos($value, '[0]')){
                $value = substr(trim($value), 7);
                $xml = simplexml_load_string($value);
                $json = json_encode($xml);
                $array = json_decode($json,TRUE);

                if(isset($array['Transaction']) && isset($array['Transaction']['TxnDetails'])){
                    $increment_id = $array['Transaction']['TxnDetails']['merchantreference'];
                    $xmlData[$increment_id]['INCREMENT_ID'] = $increment_id;
                    $xmlData[$increment_id]['GRAND_TOTAL'] = $array['Transaction']['TxnDetails']['amount'];
                    if(isset($array['Transaction']['TxnDetails']['The3rdMan'])){
                        $order_info = $array['Transaction']['TxnDetails']['The3rdMan'];
                        if(isset($order_info['CustomerInformation'])){
                            $xmlData[$increment_id]['FIRSTNAME'] = $order_info['CustomerInformation']['forename'];
                            $xmlData[$increment_id]['LASTNAME']  = $order_info['CustomerInformation']['surname'];
                            $tel = $order_info['CustomerInformation']['telephone'];
                            $xmlData[$increment_id]['PHONENUM']  = (is_array($tel) ? implode(',', $tel) : $tel);
                            $xmlData[$increment_id]['EMAIL']     = $order_info['CustomerInformation']['email'];
                            $xmlData[$increment_id]['IP']        = $order_info['CustomerInformation']['ip_address'];
                        }
                        if(isset($order_info['DeliveryAddress'])){
                            $xmlData[$increment_id]['SHIPTOSTREET'] = $order_info['DeliveryAddress']['street_address_1'];
                            $xmlData[$increment_id]['SHIPTOCITY']   = $order_info['DeliveryAddress']['city'];
                            $xmlData[$increment_id]['SHIPTOSTATE']  = $order_info['DeliveryAddress']['county'];
                            $xmlData[$increment_id]['SHIPTOZIP']    = $order_info['DeliveryAddress']['postcode'];
                        }
                        if(isset($order_info['BillingAddress'])){
                            $xmlData[$increment_id]['BILLTOSTREET'] = $order_info['BillingAddress']['street_address_1'];
                            $xmlData[$increment_id]['BILLTOCITY']   = $order_info['BillingAddress']['city'];
                            $xmlData[$increment_id]['BILLTOSTATE']  = $order_info['BillingAddress']['county'];
                            $xmlData[$increment_id]['BILLTOZIP']    = $order_info['BillingAddress']['postcode'];
                        }

                        if(isset($order_info['OrderInformation']) && isset($order_info['OrderInformation']['Products'])){
                            $sku = array();
                            $qty = 0;
                            $price = 0;
                            $order_break_down = array();

                            foreach ($order_info['OrderInformation']['Products'] as $order_key => $order_value) {
                                if($order_key != 'Product'){
                                    continue;
                                }
                                $sku[] = $order_value['code'];
                                $qty += $order_value['quantity'];
                                $price += $order_value['price'];

                                $order_break_down[] = $order_value['code'] . ' (qty:' . $order_value['quantity'] . ', $' . $order_value['price'] . ') ';
                            }
                            $xmlData[$increment_id]['SKU'] = implode(',', $sku);
                            $xmlData[$increment_id]['QTY'] = $qty;
                            $xmlData[$increment_id]['PRICE'] = $price;
                            $xmlData[$increment_id]['PAID'] = $xmlData[$increment_id]['GRAND_TOTAL'];
                            $xmlData[$increment_id]['SKUQTYPRICE'] = implode(',', $order_break_down);
                        }
                    }
                }
            }
        }

        $datacash = array();
        foreach ($xmlData as $key => $value) {
            $id = trim($value['INCREMENT_ID']);
            if(isset($missing[$id])){
                $datacash[$id] = $value;
            }
        }

        $fh = fopen('parsed_' . $file, 'w');
        if(!$fh) {
            print "Error opening file!";
            return false;
        }

        $i = 0;
        foreach ($datacash as $key => $value) {
            if(!$i++){
                fputcsv($fh, array_keys($value));
            }
            fputcsv($fh, $value);
        }
        fclose($fh);
    }
}


$app = new Datacash_CSV();
print "\n";
$app->run();
print "\n";
