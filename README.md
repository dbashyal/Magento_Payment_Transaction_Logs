Merge Payment Transaction Logs
==============================

Merge payment transaction logs so that they can be used to create magento orders programmatically.

Some of the orders went through paypal but magento didn't create order. So I had to parse log to grab address details from log before creating order programmatically.

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
