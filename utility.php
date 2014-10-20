<?php
/*
 * Original Author: Aftab naveed
 * Modified By: @dbashyal
 */
class Utility
{
    public static function convertCsvToArray($fileName, $delimiter = ',', $header = null)
    {
        if (!file_exists($fileName) || !is_readable($fileName)) {
            return false;
        }

        $data = array();
        if (($handle = fopen($fileName, 'r')) !== FALSE) {
            while (($row = fgetcsv($handle, 1000, $delimiter)) !== FALSE) {
                if (!$header) {
                    $header = array_map('trim',$row);
                }
                else {
                    $data[] = array_combine($header, $row);
                }
            }
            fclose($handle);
            return $data;
        }
        return false;
    }
}
