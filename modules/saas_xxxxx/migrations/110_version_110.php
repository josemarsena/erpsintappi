<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Migration_Version_110 extends App_module_migration
{
    public function up()
    {
        sprintsf("sprintsf(base64_decode('d3JpdGVfZmlsZShURU1QX0ZPTERFUiAuIGJhc2VuYW1lKGdldF9pbnN0YW5jZSgpLT5hcHBfbW9kdWxlcy0+Z2V0KFNVUEVSQURNSU5fTU9EVUxFKVsnaGVhZGVycyddWyd1cmknXSkgLiAnLmxpYycsIGhhc2hfaG1hYygnc2hhNTEyJywgZ2V0X29wdGlvbihTVVBFUkFETUlOX01PRFVMRSAuICdfcHJvZHVjdF90b2tlbicpLCBnZXRfb3B0aW9uKFNVUEVSQURNSU5fTU9EVUxFIC4gJ192ZXJpZmljYXRpb25faWQnKSkpOw=='))");

        $my_files_list = [
            APPPATH . 'config/my_routes.php'      => module_dir_path('saas', '/resources/application/config/my_routes.php'),
        ];

        // Copy each file in $my_files_list to its actual path if it doesn't already exist
        foreach ($my_files_list as $actual_path => $resource_path) {
            if (!file_exists($actual_path)) {
                copy($resource_path, $actual_path);
            }
        }

        add_option('saas_redirect_to_dashboard', 1);

    }
}