<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Migration_Version_104 extends App_module_migration
{
    public function up()
    {
        $CI = &get_instance();

        // Atualização da base de Dados - Campos
        if (!$CI->db->field_exists('ativo', db_prefix() . 'contracts')) {
            $CI->db->query('ALTER TABLE `' . db_prefix() . 'contracts`
            ADD COLUMN `proposta_id` int(11) NOT NULL');
            $CI->db->query('ALTER TABLE `' . db_prefix() . 'contracts`
            ADD COLUMN `orcamento_id` int(11) NOT NULL');
        }

        if (!$CI->db->field_exists('ativo', db_prefix() . 'invoices')) {
            $CI->db->query('ALTER TABLE `' . db_prefix() . 'invoices`
            ADD COLUMN `contrato_id` int(11) NOT NULL');
        }



        // Cria tabela de Conta Caixa
        if (!$CI->db->table_exists(db_prefix() . 'fin_contacaixa')) {
            $CI->db->query('CREATE TABLE `' . db_prefix() . "fin_contacaixa` (
              `id` INT(11) NOT NULL AUTO_INCREMENT,
              `ativo` INT(11) NOT NULL DEFAULT 1,    
              `nome` VARCHAR(60) NOT NULL,   
              `saldoinicial` DECIMAL(15,2) NULL,    
              `datasaldoinicial` DATETIME NULL,        
              `saldoatual` DECIMAL(15,2) NOT NULL,
              `criadopor` INT(11) NULL,
              `datacriacao` DATETIME NULL,
              PRIMARY KEY (`id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=" . $CI->db->char_set . ';');
        }

        // Cria tabela de Contas Bancarias
        if (!$CI->db->table_exists(db_prefix() . 'fin_cartaocredito')) {
            $CI->db->query('CREATE TABLE `' . db_prefix() . "fin_cartaocredito` (
              `id` INT(11) NOT NULL AUTO_INCREMENT,
              `ativo` INT(11) NOT NULL DEFAULT 1,    
              `nome` VARCHAR(60) NOT NULL, 
              `numerocartao` VARCHAR(20) NOT NULL, 
              `validade` VARCHAR(10) NOT NULL, 
              `cvv` VARCHAR(10) NOT NULL,   
              `saldoinicial` DECIMAL(15,2) NULL,    
              `datasaldoinicial` DATETIME NULL,        
              `saldoatual` DECIMAL(15,2) NOT NULL,
              `criadopor` INT(11) NULL,
              `datacriacao` DATETIME NULL,
              PRIMARY KEY (`id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=" . $CI->db->char_set . ';');

        }

        if (!$CI->db->field_exists('ativo', db_prefix() . 'fin_planocontas')) {
            $CI->db->query('ALTER TABLE `' . db_prefix() . 'fin_planocontas`
            ADD COLUMN `saldoacumulado` DECIMAL(15,2) NULL');
        }
    }
}