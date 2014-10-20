<?php

require_once 'abstract.php';
require_once 'utility.php';

class Salescheckorder extends Mage_Shell_Abstract
{
    public function run()
    {
        $file = $this->getArg('file');
        if(!$file) {
            print "\nPlease specifiy file checkorder.php --file filename";
        }
        $csvData = Dse_Utility::convertCsvToArray($file, ',', array('increment_id'));
        $fh = fopen('missing_' . $file, 'w');
        if(!$fh) {
                print "Error opening file!";
                return false;
        }

        $ids = array();
        foreach ($csvData as $key => $value) {
            $increment_id = $value['increment_id'];
            $ids[$increment_id] = $increment_id;
        }

        #fwrite($fh, "Ref\n");
        $collection = Mage::getModel('sales/order')->getCollection();
        $collection->addAttributeToFilter('increment_id', array('in' => $ids));
        foreach($collection as $row) {
                $id             = $row->getId();
                $incrementId    = $row->getData('increment_id');
                $status         = $row->getData('status');
                $grand_total    = $row->getData('grand_total');
                $email          = $row->getData('customer_email');
                print "found: id: {$incrementId} - status: {$status} - total: {$grand_total} - email: {$email}\n";
                unset($ids[$incrementId]);
        }

        sort($ids);
        foreach ($ids as $key => $value) {
            fwrite($fh, $value);
            fwrite($fh, "\n");
        }
        fclose($fh);

        echo 'Total found: ' . $collection->count() . "\n";
    }
}


$app = new Salescheckorder();
print "\n";
$app->run();
print "\n";
