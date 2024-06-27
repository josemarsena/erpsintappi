<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Migration_Version_102 extends App_module_migration
{
    public function up()
    {
        $CI = &get_instance();

        // Altera tabela de Clientes
        // Endereco_numero = endereco_nro
        // Tipo de Cliente = tipo_pessoa
        // CPF_CNPJ   = cpf_cnpj
        // Bairro = endereco_bairro

        if (!$CI->db->field_exists('endereco_nro', db_prefix() . 'clients')) {
            $CI->db->query('ALTER TABLE `' . db_prefix() . 'clients`
            ADD COLUMN `endereco_nro` VARCHAR(10) NOT NULL');
        }
        if (!$CI->db->field_exists('tipo_pessoa', db_prefix() . 'clients')) {
            $CI->db->query('ALTER TABLE `' . db_prefix() . 'clients`
            ADD COLUMN `tipo_pessoa` VARCHAR(2) NOT NULL');
        }

        if (!$CI->db->field_exists('endereco_bairro', db_prefix() . 'clients')) {
            $CI->db->query('ALTER TABLE `' . db_prefix() . 'clients`
            ADD COLUMN `endereco_bairro` VARCHAR(50) NOT NULL');
        }

        if (!$CI->db->field_exists('cpf_cnpj', db_prefix() . 'clients')) {
            $CI->db->query('ALTER TABLE `' . db_prefix() . 'clients`
                    ADD COLUMN `cpf_cnpj` VARCHAR(50) NOT NULL');
        }
        // Cria tabela de Bancos
        if (!$CI->db->table_exists(db_prefix() . 'fin_bancos')) {
            $CI->db->query('CREATE TABLE `' . db_prefix() . "fin_bancos` (
              `id` INT(11) NOT NULL AUTO_INCREMENT,
              `nomebanco` VARCHAR(100) NOT NULL,  
              `codigobanco` VARCHAR(10) NOT NULL, 
              `criadopor` INT(11) NULL,
              `datacriacao` DATETIME NULL,  
              PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=" . $CI->db->char_set . ';');
        }
        // Cria tabela de Contas Bancarias
        if (!$CI->db->table_exists(db_prefix() . 'fin_contabancaria')) {
            $CI->db->query('CREATE TABLE `' . db_prefix() . "fin_contabancaria` (
              `id` INT(11) NOT NULL AUTO_INCREMENT,
              `ativo` INT(11) NOT NULL DEFAULT 1,    
              `banco_id` INT(11) NULL,  
              `agencia` VARCHAR(20) NOT NULL,   
              `conta` VARCHAR(20) NOT NULL, 
              `gerente` VARCHAR(60) NOT NULL,   
              `endereco` VARCHAR(255) NOT NULL,   
              `telefone` VARCHAR(20) NOT NULL,
              `saldoinicial` DECIMAL(15,2) NULL,    
              `datasaldoinicial` DATETIME NULL,        
              `saldoatual` DECIMAL(15,2) NOT NULL,
              `criadopor` INT(11) NULL,
              `datacriacao` DATETIME NULL,
              PRIMARY KEY (`id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=" . $CI->db->char_set . ';');

        }


        // Cria Tabela de Plano de Contas Gerencial
        // ID da Tabela
        // NOme da Conta = Nome da Conta
        // Chave_da_Conta   = Texto que identifica a conta
        // Numero da Conta = Numero que identifica a Conta
        // Tipo_conta    = Tipo de Conta
        // Descrição    = Descrição detalhada
        // Criado Por    = Quem Criou
        // Data Criaçao   =  Data de Crição
        // Saldo = Saldo Acumulado
        // Conta Pai = Conta Origem para a Subconta

        if (!$CI->db->table_exists(db_prefix() . 'fin_planocontas')) {
            $CI->db->query('CREATE TABLE `' . db_prefix() . "fin_planocontas` (
              `id` INT(11) NOT NULL AUTO_INCREMENT,
              `nomeconta` VARCHAR(255) NOT NULL,    
              `chave_conta` VARCHAR(255) NULL,      
              `numeroconta` VARCHAR(45) NULL,       
              `conta_pai` INT(11) NULL,             
              `tipo_conta` INT(11) NOT NULL,        
              `descricao` TEXT NULL,                
              `criadopor` INT(11) NULL,             
              `datacriacao` DATETIME NULL,                     
              PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=" . $CI->db->char_set . ';');

        }


        if (!$CI->db->table_exists(db_prefix() . 'fin_historico_contas')) {
            $CI->db->query('CREATE TABLE `' . db_prefix() . "fin_historico_contas` (
              `id` INT(11) NOT NULL AUTO_INCREMENT,
              `conta_id` INT(11) NOT NULL,
              `subconta_id` INT(11) NOT NULL,
              `debito` DECIMAL(15,2) NOT NULL DEFAULT 0,
              `credito` DECIMAL(15,2) NOT NULL DEFAULT 0,
              `descricao` TEXT NULL,
              `criadopor` INT(11) NULL,             
              `datacriacao` DATETIME NULL,              
              PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=" . $CI->db->char_set . ';');
        }
    }

}