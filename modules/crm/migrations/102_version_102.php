<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Migration_Version_102 extends App_module_migration
{
    public function up()
    {
        $CI = &get_instance();

        // Atualização da base de Dados - Campos
        if (!$CI->db->field_exists('nro_parcelas', db_prefix() . 'contracts')) {
            $CI->db->query('ALTER TABLE `' . db_prefix() . 'contracts` ADD COLUMN `nro_parcelas` NUMERIC(15,2) NOT NULL');
        }
        // Atualização da base de Dados - Campos
        if (!$CI->db->field_exists('tem_entrada', db_prefix() . 'contracts')) {
            $CI->db->query('ALTER TABLE `' . db_prefix() . 'contracts` ADD COLUMN `tem_entrada` INT(11) NOT NULL');
        }

        // Atualização da base de Dados - Campos
        if (!$CI->db->field_exists('forma_pagto', db_prefix() . 'contracts')) {
            $CI->db->query('ALTER TABLE `' . db_prefix() . 'contracts` ADD COLUMN `forma_pagto` INT(11) NOT NULL');
        }
    }
}