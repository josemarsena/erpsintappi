<?php


defined('BASEPATH') or exit('No direct script access allowed');

class Migration_Version_108 extends App_module_migration
{

    public function up()
    {
        $CI = &get_instance();

        if (!$CI->db->field_exists('contabanco_id', db_prefix() . 'invoices')) {
            $CI->db->query('ALTER TABLE `' . db_prefix() . 'invoices`
            ADD COLUMN `contabanco_id` int(11) NOT NULL');
        }

        if (!$CI->db->field_exists('contacaixa_id', db_prefix() . 'invoices')) {
            $CI->db->query('ALTER TABLE `' . db_prefix() . 'invoices`
            ADD COLUMN `contacaixa_id` int(11) NOT NULL');
        }

        if (!$CI->db->field_exists('contacartao_id', db_prefix() . 'invoices')) {
            $CI->db->query('ALTER TABLE `' . db_prefix() . 'invoices`
            ADD COLUMN `contacartao_id` int(11) NOT NULL');
        }


        if (!$CI->db->field_exists('conta_id', db_prefix() . 'invoices')) {
            $CI->db->query('ALTER TABLE `' . db_prefix() . 'invoices`
            ADD COLUMN `conta_id` int(11) NOT NULL');
        }

        if (!$CI->db->field_exists('subconta_id', db_prefix() . 'invoices')) {
            $CI->db->query('ALTER TABLE `' . db_prefix() . 'invoices`
            ADD COLUMN `subconta_id` int(11) NOT NULL');
        }
    }
}