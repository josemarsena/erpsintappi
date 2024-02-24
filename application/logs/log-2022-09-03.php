<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>

ERROR - 2022-09-03 22:11:36 --> Severity: Warning --> Use of undefined constant required - assumed 'required' (this will throw an Error in a future version of PHP) C:\xampp\htdocs\i3flexnew\application\config\database.php 108
ERROR - 2022-09-03 22:11:36 --> Severity: Warning --> mysqli::real_connect(): (HY000/1049): Unknown database 'i3flex' C:\xampp\htdocs\i3flexnew\system\database\drivers\mysqli\mysqli_driver.php 203
ERROR - 2022-09-03 22:11:36 --> Unable to connect to the database
ERROR - 2022-09-03 22:18:21 --> Severity: Warning --> failed loading cafile stream: `ca-certificate.crt' C:\xampp\htdocs\i3flexnew\system\database\drivers\mysqli\mysqli_driver.php 203
ERROR - 2022-09-03 22:18:21 --> Severity: Warning --> mysqli::real_connect(): Cannot connect to MySQL by using SSL C:\xampp\htdocs\i3flexnew\system\database\drivers\mysqli\mysqli_driver.php 203
ERROR - 2022-09-03 22:18:21 --> Severity: Warning --> mysqli::real_connect(): [2002]  (trying to connect via (null)) C:\xampp\htdocs\i3flexnew\system\database\drivers\mysqli\mysqli_driver.php 203
ERROR - 2022-09-03 22:18:21 --> Severity: Warning --> mysqli::real_connect(): (HY000/2002):  C:\xampp\htdocs\i3flexnew\system\database\drivers\mysqli\mysqli_driver.php 203
ERROR - 2022-09-03 22:18:21 --> Unable to connect to the database
ERROR - 2022-09-03 22:18:21 --> Severity: Warning --> Cannot modify header information - headers already sent by (output started at C:\xampp\htdocs\i3flexnew\system\core\Exceptions.php:271) C:\xampp\htdocs\i3flexnew\system\core\Common.php 573
ERROR - 2022-09-03 22:20:24 --> Severity: Warning --> mysqli::real_connect(): (HY000/1049): Unknown database 'i3flex' C:\xampp\htdocs\i3flexnew\system\database\drivers\mysqli\mysqli_driver.php 203
ERROR - 2022-09-03 22:20:24 --> Unable to connect to the database
ERROR - 2022-09-03 22:24:42 --> Severity: Warning --> mysqli::real_connect(): (42S22/1054): Unknown column 'STRICT_ALL_TABLES,' in 'field list' C:\xampp\htdocs\i3flexnew\system\database\drivers\mysqli\mysqli_driver.php 203
ERROR - 2022-09-03 22:24:42 --> Unable to connect to the database
ERROR - 2022-09-03 22:26:26 --> Severity: Warning --> mysqli::real_connect(): (42S22/1054): Unknown column 'STRICT_ALL_TABLES,' in 'field list' C:\xampp\htdocs\i3flexnew\system\database\drivers\mysqli\mysqli_driver.php 203
ERROR - 2022-09-03 22:26:26 --> Unable to connect to the database
ERROR - 2022-09-03 18:06:03 --> Could not find the language line "crm_dashboard"
ERROR - 2022-09-03 18:06:03 --> Could not find the language line "crm_report"
ERROR - 2022-09-03 18:06:03 --> Could not find the language line "crm_setting"
ERROR - 2022-09-03 18:06:03 --> Could not find the language line "financeiro_dashboard"
ERROR - 2022-09-03 18:06:03 --> Could not find the language line "financeiro_report"
ERROR - 2022-09-03 18:06:03 --> Could not find the language line "financeiro_setting"
ERROR - 2022-09-03 18:06:03 --> Could not find the language line "helpdesk_dashboard"
ERROR - 2022-09-03 18:06:03 --> Could not find the language line "helpdesk_report"
ERROR - 2022-09-03 18:06:03 --> Could not find the language line "helpdesk_setting"
ERROR - 2022-09-03 18:06:03 --> Could not find the language line "servicedesk_dashboard"
ERROR - 2022-09-03 18:06:03 --> Could not find the language line "servicedesk_report"
ERROR - 2022-09-03 18:06:03 --> Could not find the language line "servicedesk_setting"
ERROR - 2022-09-03 18:06:03 --> Could not find the language line "Ultimate Theme Config"
ERROR - 2022-09-03 18:06:06 --> Severity: 8192 --> Invalid characters passed for attempted conversion, these have been ignored C:\xampp\htdocs\i3flexnew\application\services\utilities\Utils.php 128
ERROR - 2022-09-03 18:06:06 --> Severity: 8192 --> Invalid characters passed for attempted conversion, these have been ignored C:\xampp\htdocs\i3flexnew\application\services\utilities\Utils.php 128
ERROR - 2022-09-03 18:06:06 --> Severity: 8192 --> Invalid characters passed for attempted conversion, these have been ignored C:\xampp\htdocs\i3flexnew\application\services\utilities\Utils.php 128
ERROR - 2022-09-03 18:06:06 --> Severity: 8192 --> Invalid characters passed for attempted conversion, these have been ignored C:\xampp\htdocs\i3flexnew\application\services\utilities\Utils.php 128
ERROR - 2022-09-03 18:06:06 --> Severity: 8192 --> Invalid characters passed for attempted conversion, these have been ignored C:\xampp\htdocs\i3flexnew\application\services\utilities\Utils.php 128
ERROR - 2022-09-03 18:06:09 --> Query error: Unknown column '2022-09-01 00:00:00' in 'where clause' - Invalid query: SELECT `staffid`, `firstname`, `lastname`, (SELECT count(ticketid) from tbltickets where assigned = staffid and tbltickets.date between "2022-09-01 00:00:00" and "2022-09-30 23:59:59") as total_assigned, (SELECT count(ticketid) from tbltickets where assigned = staffid and status = 1 and tbltickets.date between "2022-09-01 00:00:00" and "2022-09-30 23:59:59") as total_open_tickets, (SELECT count(ticketid) from tbltickets where assigned = staffid and status = 5 and tbltickets.date between "2022-09-01 00:00:00" and "2022-09-30 23:59:59") as total_closed_tickets, (SELECT count(ticketid) from tblticket_replies where tblticket_replies.admin = staffid and tblticket_replies.date between "2022-09-01 00:00:00" and "2022-09-30 23:59:59") as total_replies
FROM `tblstaff`
ERROR - 2022-09-03 18:06:09 --> Severity: Warning --> Cannot modify header information - headers already sent by (output started at C:\xampp\htdocs\i3flexnew\system\core\Exceptions.php:271) C:\xampp\htdocs\i3flexnew\system\core\Common.php 573
ERROR - 2022-09-03 21:03:20 --> Could not find the language line "crm_dashboard"
ERROR - 2022-09-03 21:03:20 --> Could not find the language line "crm_report"
ERROR - 2022-09-03 21:03:20 --> Could not find the language line "crm_setting"
ERROR - 2022-09-03 21:03:20 --> Could not find the language line "financeiro_dashboard"
ERROR - 2022-09-03 21:03:20 --> Could not find the language line "financeiro_report"
ERROR - 2022-09-03 21:03:20 --> Could not find the language line "financeiro_setting"
ERROR - 2022-09-03 21:03:20 --> Could not find the language line "helpdesk_dashboard"
ERROR - 2022-09-03 21:03:20 --> Could not find the language line "helpdesk_report"
ERROR - 2022-09-03 21:03:20 --> Could not find the language line "helpdesk_setting"
ERROR - 2022-09-03 21:03:20 --> Could not find the language line "servicedesk_dashboard"
ERROR - 2022-09-03 21:03:20 --> Could not find the language line "servicedesk_report"
ERROR - 2022-09-03 21:03:20 --> Could not find the language line "servicedesk_setting"
ERROR - 2022-09-03 21:03:21 --> Could not find the language line "Ultimate Theme Config"
ERROR - 2022-09-03 21:03:24 --> Severity: 8192 --> Invalid characters passed for attempted conversion, these have been ignored C:\xampp\htdocs\i3flexnew\application\services\utilities\Utils.php 128
ERROR - 2022-09-03 21:03:24 --> Severity: 8192 --> Invalid characters passed for attempted conversion, these have been ignored C:\xampp\htdocs\i3flexnew\application\services\utilities\Utils.php 128
ERROR - 2022-09-03 21:03:24 --> Severity: 8192 --> Invalid characters passed for attempted conversion, these have been ignored C:\xampp\htdocs\i3flexnew\application\services\utilities\Utils.php 128
ERROR - 2022-09-03 21:03:24 --> Severity: 8192 --> Invalid characters passed for attempted conversion, these have been ignored C:\xampp\htdocs\i3flexnew\application\services\utilities\Utils.php 128
ERROR - 2022-09-03 21:03:24 --> Severity: 8192 --> Invalid characters passed for attempted conversion, these have been ignored C:\xampp\htdocs\i3flexnew\application\services\utilities\Utils.php 128
ERROR - 2022-09-03 21:04:36 --> Severity: Warning --> call_user_func_array() expects parameter 1 to be a valid callback, function 'crm_load_js' not found or invalid function name C:\xampp\htdocs\i3flexnew\application\vendor\bainternet\php-hooks\php-hooks.php 362
ERROR - 2022-09-03 21:04:36 --> Severity: Warning --> call_user_func_array() expects parameter 1 to be a valid callback, function 'financeiro_load_js' not found or invalid function name C:\xampp\htdocs\i3flexnew\application\vendor\bainternet\php-hooks\php-hooks.php 362
ERROR - 2022-09-03 21:04:36 --> Severity: Warning --> call_user_func_array() expects parameter 1 to be a valid callback, function 'helpdesk_load_js' not found or invalid function name C:\xampp\htdocs\i3flexnew\application\vendor\bainternet\php-hooks\php-hooks.php 362
ERROR - 2022-09-03 21:04:36 --> Severity: Warning --> call_user_func_array() expects parameter 1 to be a valid callback, function 'servicedesk_load_js' not found or invalid function name C:\xampp\htdocs\i3flexnew\application\vendor\bainternet\php-hooks\php-hooks.php 362
ERROR - 2022-09-03 21:04:36 --> Severity: Warning --> Cannot modify header information - headers already sent by (output started at C:\xampp\htdocs\i3flexnew\system\core\Exceptions.php:271) C:\xampp\htdocs\i3flexnew\system\core\Common.php 573
ERROR - 2022-09-03 21:04:36 --> Severity: Error --> Maximum execution time of 120 seconds exceeded C:\xampp\htdocs\i3flexnew\system\database\drivers\mysqli\mysqli_driver.php 325
ERROR - 2022-09-03 21:04:45 --> Could not find the language line "crm_dashboard"
ERROR - 2022-09-03 21:04:45 --> Could not find the language line "crm_report"
ERROR - 2022-09-03 21:04:45 --> Could not find the language line "crm_setting"
ERROR - 2022-09-03 21:04:45 --> Could not find the language line "financeiro_dashboard"
ERROR - 2022-09-03 21:04:45 --> Could not find the language line "financeiro_report"
ERROR - 2022-09-03 21:04:45 --> Could not find the language line "financeiro_setting"
ERROR - 2022-09-03 21:04:45 --> Could not find the language line "helpdesk_dashboard"
ERROR - 2022-09-03 21:04:45 --> Could not find the language line "helpdesk_report"
ERROR - 2022-09-03 21:04:45 --> Could not find the language line "helpdesk_setting"
ERROR - 2022-09-03 21:04:45 --> Could not find the language line "servicedesk_dashboard"
ERROR - 2022-09-03 21:04:45 --> Could not find the language line "servicedesk_report"
ERROR - 2022-09-03 21:04:45 --> Could not find the language line "servicedesk_setting"
ERROR - 2022-09-03 21:04:45 --> Could not find the language line "Ultimate Theme Config"
ERROR - 2022-09-03 21:04:53 --> Could not find the language line "crm_dashboard"
ERROR - 2022-09-03 21:04:53 --> Could not find the language line "crm_report"
ERROR - 2022-09-03 21:04:53 --> Could not find the language line "crm_setting"
ERROR - 2022-09-03 21:04:53 --> Could not find the language line "financeiro_dashboard"
ERROR - 2022-09-03 21:04:53 --> Could not find the language line "financeiro_report"
ERROR - 2022-09-03 21:04:53 --> Could not find the language line "financeiro_setting"
ERROR - 2022-09-03 21:04:53 --> Could not find the language line "helpdesk_dashboard"
ERROR - 2022-09-03 21:04:53 --> Could not find the language line "helpdesk_report"
ERROR - 2022-09-03 21:04:53 --> Could not find the language line "helpdesk_setting"
ERROR - 2022-09-03 21:04:54 --> Could not find the language line "servicedesk_dashboard"
ERROR - 2022-09-03 21:04:54 --> Could not find the language line "servicedesk_report"
ERROR - 2022-09-03 21:04:54 --> Could not find the language line "servicedesk_setting"
ERROR - 2022-09-03 21:04:54 --> Could not find the language line "Ultimate Theme Config"
ERROR - 2022-09-03 21:05:00 --> Could not find the language line "crm_dashboard"
ERROR - 2022-09-03 21:05:00 --> Could not find the language line "crm_report"
ERROR - 2022-09-03 21:05:00 --> Could not find the language line "crm_setting"
ERROR - 2022-09-03 21:05:00 --> Could not find the language line "financeiro_dashboard"
ERROR - 2022-09-03 21:05:00 --> Could not find the language line "financeiro_report"
ERROR - 2022-09-03 21:05:00 --> Could not find the language line "financeiro_setting"
ERROR - 2022-09-03 21:05:00 --> Could not find the language line "helpdesk_dashboard"
ERROR - 2022-09-03 21:05:00 --> Could not find the language line "helpdesk_report"
ERROR - 2022-09-03 21:05:00 --> Could not find the language line "helpdesk_setting"
ERROR - 2022-09-03 21:05:00 --> Could not find the language line "servicedesk_dashboard"
ERROR - 2022-09-03 21:05:00 --> Could not find the language line "servicedesk_report"
ERROR - 2022-09-03 21:05:00 --> Could not find the language line "servicedesk_setting"
ERROR - 2022-09-03 21:05:00 --> Could not find the language line "Ultimate Theme Config"
ERROR - 2022-09-03 21:05:03 --> Severity: 8192 --> Invalid characters passed for attempted conversion, these have been ignored C:\xampp\htdocs\i3flexnew\application\services\utilities\Utils.php 128
ERROR - 2022-09-03 21:05:03 --> Severity: 8192 --> Invalid characters passed for attempted conversion, these have been ignored C:\xampp\htdocs\i3flexnew\application\services\utilities\Utils.php 128
ERROR - 2022-09-03 21:05:03 --> Severity: 8192 --> Invalid characters passed for attempted conversion, these have been ignored C:\xampp\htdocs\i3flexnew\application\services\utilities\Utils.php 128
ERROR - 2022-09-03 21:05:03 --> Severity: 8192 --> Invalid characters passed for attempted conversion, these have been ignored C:\xampp\htdocs\i3flexnew\application\services\utilities\Utils.php 128
ERROR - 2022-09-03 21:05:03 --> Severity: 8192 --> Invalid characters passed for attempted conversion, these have been ignored C:\xampp\htdocs\i3flexnew\application\services\utilities\Utils.php 128
ERROR - 2022-09-03 21:05:18 --> Severity: Warning --> call_user_func_array() expects parameter 1 to be a valid callback, function 'crm_load_js' not found or invalid function name C:\xampp\htdocs\i3flexnew\application\vendor\bainternet\php-hooks\php-hooks.php 362
ERROR - 2022-09-03 21:05:18 --> Severity: Warning --> call_user_func_array() expects parameter 1 to be a valid callback, function 'financeiro_load_js' not found or invalid function name C:\xampp\htdocs\i3flexnew\application\vendor\bainternet\php-hooks\php-hooks.php 362
ERROR - 2022-09-03 21:05:18 --> Severity: Warning --> call_user_func_array() expects parameter 1 to be a valid callback, function 'helpdesk_load_js' not found or invalid function name C:\xampp\htdocs\i3flexnew\application\vendor\bainternet\php-hooks\php-hooks.php 362
ERROR - 2022-09-03 21:05:18 --> Severity: Warning --> call_user_func_array() expects parameter 1 to be a valid callback, function 'servicedesk_load_js' not found or invalid function name C:\xampp\htdocs\i3flexnew\application\vendor\bainternet\php-hooks\php-hooks.php 362
ERROR - 2022-09-03 21:05:24 --> Could not find the language line "crm_dashboard"
ERROR - 2022-09-03 21:05:24 --> Could not find the language line "crm_report"
ERROR - 2022-09-03 21:05:24 --> Could not find the language line "crm_setting"
ERROR - 2022-09-03 21:05:24 --> Could not find the language line "financeiro_dashboard"
ERROR - 2022-09-03 21:05:24 --> Could not find the language line "financeiro_report"
ERROR - 2022-09-03 21:05:24 --> Could not find the language line "financeiro_setting"
ERROR - 2022-09-03 21:05:24 --> Could not find the language line "helpdesk_dashboard"
ERROR - 2022-09-03 21:05:24 --> Could not find the language line "helpdesk_report"
ERROR - 2022-09-03 21:05:24 --> Could not find the language line "helpdesk_setting"
ERROR - 2022-09-03 21:05:24 --> Could not find the language line "servicedesk_dashboard"
ERROR - 2022-09-03 21:05:24 --> Could not find the language line "servicedesk_report"
ERROR - 2022-09-03 21:05:24 --> Could not find the language line "servicedesk_setting"
ERROR - 2022-09-03 21:05:24 --> Could not find the language line "Ultimate Theme Config"
ERROR - 2022-09-03 21:05:27 --> Severity: 8192 --> Invalid characters passed for attempted conversion, these have been ignored C:\xampp\htdocs\i3flexnew\application\services\utilities\Utils.php 128
ERROR - 2022-09-03 21:05:27 --> Severity: 8192 --> Invalid characters passed for attempted conversion, these have been ignored C:\xampp\htdocs\i3flexnew\application\services\utilities\Utils.php 128
ERROR - 2022-09-03 21:05:27 --> Severity: 8192 --> Invalid characters passed for attempted conversion, these have been ignored C:\xampp\htdocs\i3flexnew\application\services\utilities\Utils.php 128
ERROR - 2022-09-03 21:05:27 --> Severity: 8192 --> Invalid characters passed for attempted conversion, these have been ignored C:\xampp\htdocs\i3flexnew\application\services\utilities\Utils.php 128
ERROR - 2022-09-03 21:05:27 --> Severity: 8192 --> Invalid characters passed for attempted conversion, these have been ignored C:\xampp\htdocs\i3flexnew\application\services\utilities\Utils.php 128
ERROR - 2022-09-03 21:05:42 --> Severity: Warning --> call_user_func_array() expects parameter 1 to be a valid callback, function 'crm_load_js' not found or invalid function name C:\xampp\htdocs\i3flexnew\application\vendor\bainternet\php-hooks\php-hooks.php 362
ERROR - 2022-09-03 21:05:42 --> Severity: Warning --> call_user_func_array() expects parameter 1 to be a valid callback, function 'financeiro_load_js' not found or invalid function name C:\xampp\htdocs\i3flexnew\application\vendor\bainternet\php-hooks\php-hooks.php 362
ERROR - 2022-09-03 21:05:42 --> Severity: Warning --> call_user_func_array() expects parameter 1 to be a valid callback, function 'helpdesk_load_js' not found or invalid function name C:\xampp\htdocs\i3flexnew\application\vendor\bainternet\php-hooks\php-hooks.php 362
ERROR - 2022-09-03 21:05:42 --> Severity: Warning --> call_user_func_array() expects parameter 1 to be a valid callback, function 'servicedesk_load_js' not found or invalid function name C:\xampp\htdocs\i3flexnew\application\vendor\bainternet\php-hooks\php-hooks.php 362
ERROR - 2022-09-03 21:05:47 --> Could not find the language line "crm_dashboard"
ERROR - 2022-09-03 21:05:47 --> Could not find the language line "crm_report"
ERROR - 2022-09-03 21:05:47 --> Could not find the language line "crm_setting"
ERROR - 2022-09-03 21:05:47 --> Could not find the language line "financeiro_dashboard"
ERROR - 2022-09-03 21:05:47 --> Could not find the language line "financeiro_report"
ERROR - 2022-09-03 21:05:47 --> Could not find the language line "financeiro_setting"
ERROR - 2022-09-03 21:05:47 --> Could not find the language line "helpdesk_dashboard"
ERROR - 2022-09-03 21:05:47 --> Could not find the language line "helpdesk_report"
ERROR - 2022-09-03 21:05:47 --> Could not find the language line "helpdesk_setting"
ERROR - 2022-09-03 21:05:47 --> Could not find the language line "servicedesk_dashboard"
ERROR - 2022-09-03 21:05:47 --> Could not find the language line "servicedesk_report"
ERROR - 2022-09-03 21:05:47 --> Could not find the language line "servicedesk_setting"
ERROR - 2022-09-03 21:05:48 --> Could not find the language line "Ultimate Theme Config"
ERROR - 2022-09-03 21:05:57 --> Could not find the language line "crm_dashboard"
ERROR - 2022-09-03 21:05:57 --> Could not find the language line "crm_report"
ERROR - 2022-09-03 21:05:57 --> Could not find the language line "crm_setting"
ERROR - 2022-09-03 21:05:57 --> Could not find the language line "financeiro_dashboard"
ERROR - 2022-09-03 21:05:57 --> Could not find the language line "financeiro_report"
ERROR - 2022-09-03 21:05:57 --> Could not find the language line "financeiro_setting"
ERROR - 2022-09-03 21:05:57 --> Could not find the language line "helpdesk_dashboard"
ERROR - 2022-09-03 21:05:57 --> Could not find the language line "helpdesk_report"
ERROR - 2022-09-03 21:05:57 --> Could not find the language line "helpdesk_setting"
ERROR - 2022-09-03 21:05:57 --> Could not find the language line "servicedesk_dashboard"
ERROR - 2022-09-03 21:05:57 --> Could not find the language line "servicedesk_report"
ERROR - 2022-09-03 21:05:57 --> Could not find the language line "servicedesk_setting"
ERROR - 2022-09-03 21:05:57 --> Could not find the language line "Ultimate Theme Config"
ERROR - 2022-09-03 21:06:03 --> Could not find the language line "crm_dashboard"
ERROR - 2022-09-03 21:06:03 --> Could not find the language line "crm_report"
ERROR - 2022-09-03 21:06:03 --> Could not find the language line "crm_setting"
ERROR - 2022-09-03 21:06:03 --> Could not find the language line "financeiro_dashboard"
ERROR - 2022-09-03 21:06:03 --> Could not find the language line "financeiro_report"
ERROR - 2022-09-03 21:06:03 --> Could not find the language line "financeiro_setting"
ERROR - 2022-09-03 21:06:03 --> Could not find the language line "helpdesk_dashboard"
ERROR - 2022-09-03 21:06:03 --> Could not find the language line "helpdesk_report"
ERROR - 2022-09-03 21:06:03 --> Could not find the language line "helpdesk_setting"
ERROR - 2022-09-03 21:06:04 --> Could not find the language line "servicedesk_dashboard"
ERROR - 2022-09-03 21:06:04 --> Could not find the language line "servicedesk_report"
ERROR - 2022-09-03 21:06:04 --> Could not find the language line "servicedesk_setting"
ERROR - 2022-09-03 21:06:04 --> Could not find the language line "Ultimate Theme Config"
ERROR - 2022-09-03 21:06:10 --> Could not find the language line "crm_dashboard"
ERROR - 2022-09-03 21:06:10 --> Could not find the language line "crm_report"
ERROR - 2022-09-03 21:06:10 --> Could not find the language line "crm_setting"
ERROR - 2022-09-03 21:06:10 --> Could not find the language line "financeiro_dashboard"
ERROR - 2022-09-03 21:06:10 --> Could not find the language line "financeiro_report"
ERROR - 2022-09-03 21:06:10 --> Could not find the language line "financeiro_setting"
ERROR - 2022-09-03 21:06:10 --> Could not find the language line "helpdesk_dashboard"
ERROR - 2022-09-03 21:06:10 --> Could not find the language line "helpdesk_report"
ERROR - 2022-09-03 21:06:10 --> Could not find the language line "helpdesk_setting"
ERROR - 2022-09-03 21:06:10 --> Could not find the language line "servicedesk_dashboard"
ERROR - 2022-09-03 21:06:10 --> Could not find the language line "servicedesk_report"
ERROR - 2022-09-03 21:06:10 --> Could not find the language line "servicedesk_setting"
ERROR - 2022-09-03 21:06:10 --> Could not find the language line "Ultimate Theme Config"
