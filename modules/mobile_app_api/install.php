<?php defined('BASEPATH') or exit('No direct script access allowed');
/**
 * This file is responsible for handling the perfex mobile app api installation
 */
$CI = &get_instance();
if (!option_exists(PERFEX_MOBILE_APP_API . '_enabled'))
    add_option(PERFEX_MOBILE_APP_API . '_enabled', 1);

if (addExceptionForCSRF()) {
    $CI->db->query('CREATE TABLE IF NOT EXISTS `' . db_prefix() . 'api_logs` ( `id` INT(255) NOT NULL AUTO_INCREMENT , `url` VARCHAR(255) NOT NULL , `data` MEDIUMTEXT NULL , `response` MEDIUMTEXT NOT NULL , `origin_from` VARCHAR(255) NOT NULL , `headers` MEDIUMTEXT NULL , `ip_address` VARCHAR(255) NOT NULL , `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP , PRIMARY KEY (`id`)) ENGINE = InnoDB;');
}

function addExceptionForCSRF()
{
    $exceptionAdded = false;
    $filePath = APPPATH . 'config/config.php';
    if (file_exists($filePath) && is_writable($filePath)) {
        if (!strpos(file_get_contents($filePath), "perfex_mobile_app_api") !== false) {
            $configFile = fopen($filePath, "a") or die("Unable to open $filePath file!");
            $codeToWrite = 'if ($config["csrf_protection"] == true ' . PHP_EOL . ' && isset($_SERVER["REQUEST_URI"]) ' . PHP_EOL . ' && strpos($_SERVER["REQUEST_URI"], "perfex_mobile_app_api") !== false) { ' . PHP_EOL . ' $config["csrf_protection"] = false; ' . PHP_EOL . ' }';
            fwrite($configFile, PHP_EOL . $codeToWrite);
            fclose($configFile);
        }
        $exceptionAdded = true;
    }
    return $exceptionAdded;
}
