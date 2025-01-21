<?php


defined('BASEPATH') or exit('No direct script access allowed');

class Migration_Version_107 extends App_module_migration
{

    public function up()
    {
        $CI = &get_instance();

        add_option('prefixo_fatura', 'FPG-');
        add_option('formato_fatura_a_pagar', 2);
        add_option('proxima_fatura_a_pagar', 1);

    }
}