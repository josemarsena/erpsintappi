<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Migration_Version_103 extends App_module_migration
{
    public function up()
    {
        $CI = &get_instance();


        if (!$CI->db->field_exists('ativo', db_prefix() . 'fin_planocontas')) {
            $CI->db->query('ALTER TABLE `' . db_prefix() . 'fin_planocontas`
            ADD COLUMN `ativo` int(11) NOT NULL');
        }

    }

}