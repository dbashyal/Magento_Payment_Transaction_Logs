Merge Payment Transaction Logs
==============================

Merge payment transaction logs so that they can be used to create magento orders programmatically.

Some of the orders went through paypal but magento didn't create order. So I had to parse log to grab address details before creating order programmatically.

##Looking for someone who can optimize this, as it takes ages to parse and generate csv.

1. Export paypal transaction to csv ( **paypal.csv** ).
2. Copy increment_ids from paypal transaction csv column and create **paypal_orders.csv**.
3. Go to Shell and run: `php salescheckorder.php --file paypal_orders.csv`.
4. You will get list of increment_ids that couldn't be found in the database (**missing_paid_orders.csv**).
5. Merge payment logs if they are in different frontend servers to **allLogs.log**.
6. Now run parser for that log data: `php parselogs.php` (currently filename hardcoded on it).
7. That will generate evil eval code on file  **allLogs.log.txt** :)
8. Now copy exported paypal transaction csv (**paypal.csv**) to shell folder where we have this parser.
9. Then run data merge script that will merge info from log to paypal csv: `php merge_paypal.php --file missing_paid_orders.csv --data paypal.csv --logfile allLogs.log.txt`.
10. That will generate a combined data csv (**parsed_missing_paid_orders.csv**).


##Fix missing orders that are not searchable through magento sales grid but available on sales flat order table.
### Check how many orders are missing from Magento orders grid ( +archive grid for EE )
#### Community version
```mysql
SELECT COUNT(a.`entity_id`) FROM sales_flat_order a LEFT JOIN sales_flat_order_grid b ON a.increment_id=b.increment_id WHERE b.increment_id IS NULL;
```
#### Enterprise version
```mysql
SELECT COUNT(a.`entity_id`) FROM sales_flat_order a 
LEFT JOIN sales_flat_order_grid b ON a.increment_id=b.increment_id
LEFT JOIN enterprise_sales_order_grid_archive c ON a.increment_id=c.increment_id 
WHERE 
c.increment_id IS NULL
AND
b.increment_id IS NULL
```

###FIX (deprecated)
```mysql
INSERT INTO sales_flat_order_grid (SELECT a.`entity_id`, a.`status`,a.`store_id`,a.`store_name`,a.`customer_id`,a.`base_grand_total`,a.`base_total_paid`,a.`grand_total`,a.`total_paid`,a.`increment_id`,a.`base_currency_code`,a.`order_currency_code`, CONCAT(a.`customer_firstname`, ' ', a.`customer_lastname`) as  shipping_name, CONCAT(a.`customer_firstname`, ' ', a.`customer_lastname`) as billing_name, a.`created_at`, a.`updated_at` FROM sales_flat_order a left join sales_flat_order_grid b on a.increment_id=b.increment_id where b.increment_id IS NULL order by a.entity_id ASC);
```


### FIX found from magento query log :)
#### FOR Community Version
```mysql
INSERT INTO `sales_flat_order_grid` (`entity_id`, `status`, `store_id`, `customer_id`, `base_grand_total`, `base_total_paid`, `grand_total`, `total_paid`, `increment_id`, `base_currency_code`, `order_currency_code`, `store_name`, `created_at`, `updated_at`, `billing_name`, `shipping_name`) SELECT `main_table`.`entity_id`, `main_table`.`status`, `main_table`.`store_id`, `main_table`.`customer_id`, `main_table`.`base_grand_total`, `main_table`.`base_total_paid`, `main_table`.`grand_total`, `main_table`.`total_paid`, `main_table`.`increment_id`, `main_table`.`base_currency_code`, `main_table`.`order_currency_code`, `main_table`.`store_name`, `main_table`.`created_at`, `main_table`.`updated_at`, CONCAT(IFNULL(table_billing_name.firstname, ''), ' ', IFNULL(table_billing_name.lastname, '')) AS `billing_name`, CONCAT(IFNULL(table_shipping_name.firstname, ''), ' ', IFNULL(table_shipping_name.lastname, '')) AS `shipping_name` FROM `sales_flat_order` AS `main_table` LEFT JOIN `sales_flat_order_address` AS `table_billing_name` ON `main_table`.`billing_address_id`=`table_billing_name`.`entity_id` LEFT JOIN `sales_flat_order_address` AS `table_shipping_name` ON `main_table`.`shipping_address_id`=`table_shipping_name`.`entity_id` WHERE (main_table.entity_id IN(SELECT a.`entity_id` FROM sales_flat_order a LEFT JOIN sales_flat_order_grid b ON a.increment_id=b.increment_id WHERE b.increment_id IS NULL)) ON DUPLICATE KEY UPDATE `entity_id` = VALUES(`entity_id`), `status` = VALUES(`status`), `store_id` = VALUES(`store_id`), `customer_id` = VALUES(`customer_id`), `base_grand_total` = VALUES(`base_grand_total`), `base_total_paid` = VALUES(`base_total_paid`), `grand_total` = VALUES(`grand_total`), `total_paid` = VALUES(`total_paid`), `increment_id` = VALUES(`increment_id`), `base_currency_code` = VALUES(`base_currency_code`), `order_currency_code` = VALUES(`order_currency_code`), `store_name` = VALUES(`store_name`), `created_at` = VALUES(`created_at`), `updated_at` = VALUES(`updated_at`), `billing_name` = VALUES(`billing_name`), `shipping_name` = VALUES(`shipping_name`);
```
#### FOR Enterprise Version
```mysql
INSERT INTO `sales_flat_order_grid` (`entity_id`, `status`, `store_id`, `customer_id`, `base_grand_total`, `base_total_paid`, `grand_total`, `total_paid`, `increment_id`, `base_currency_code`, `order_currency_code`, `store_name`, `created_at`, `updated_at`, `billing_name`, `shipping_name`) SELECT `main_table`.`entity_id`, `main_table`.`status`, `main_table`.`store_id`, `main_table`.`customer_id`, `main_table`.`base_grand_total`, `main_table`.`base_total_paid`, `main_table`.`grand_total`, `main_table`.`total_paid`, `main_table`.`increment_id`, `main_table`.`base_currency_code`, `main_table`.`order_currency_code`, `main_table`.`store_name`, `main_table`.`created_at`, `main_table`.`updated_at`, CONCAT(IFNULL(table_billing_name.firstname, ''), ' ', IFNULL(table_billing_name.lastname, '')) AS `billing_name`, CONCAT(IFNULL(table_shipping_name.firstname, ''), ' ', IFNULL(table_shipping_name.lastname, '')) AS `shipping_name` FROM `sales_flat_order` AS `main_table` LEFT JOIN `sales_flat_order_address` AS `table_billing_name` ON `main_table`.`billing_address_id`=`table_billing_name`.`entity_id` LEFT JOIN `sales_flat_order_address` AS `table_shipping_name` ON `main_table`.`shipping_address_id`=`table_shipping_name`.`entity_id` WHERE (main_table.entity_id IN(SELECT a.entity_id FROM sales_flat_order a LEFT JOIN sales_flat_order_grid b ON a.increment_id=b.increment_id LEFT JOIN enterprise_sales_order_grid_archive c ON a.increment_id=c.increment_id WHERE c.increment_id IS NULL AND b.increment_id IS NULL)) ON DUPLICATE KEY UPDATE `entity_id` = VALUES(`entity_id`), `status` = VALUES(`status`), `store_id` = VALUES(`store_id`), `customer_id` = VALUES(`customer_id`), `base_grand_total` = VALUES(`base_grand_total`), `base_total_paid` = VALUES(`base_total_paid`), `grand_total` = VALUES(`grand_total`), `total_paid` = VALUES(`total_paid`), `increment_id` = VALUES(`increment_id`), `base_currency_code` = VALUES(`base_currency_code`), `order_currency_code` = VALUES(`order_currency_code`), `store_name` = VALUES(`store_name`), `created_at` = VALUES(`created_at`), `updated_at` = VALUES(`updated_at`), `billing_name` = VALUES(`billing_name`), `shipping_name` = VALUES(`shipping_name`);
```
