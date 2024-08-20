<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Migration_Version_105 extends App_module_migration
{
    public function up()
    {
        $CI = &get_instance();

        // Atualização da base de Dados - Campos
        if (!$CI->db->field_exists('ativo', db_prefix() . 'fin_contabancaria')) {
            $CI->db->query('ALTER TABLE `' . db_prefix() . 'fin_contabancaria`
             DROP COLUMN `datasaldoinicial`');
            $CI->db->query('ALTER TABLE `' . db_prefix() . 'contracts`
            ADD COLUMN `datasaldoinicial` DATE NOT NULL');
        }

    }
}