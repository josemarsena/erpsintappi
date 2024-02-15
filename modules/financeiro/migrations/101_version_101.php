<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Migration_Version_101 extends App_module_migration
{
     public function up()
     {
        $CI = &get_instance();
        
        
        //Version 1.0.1

// Altera tabela de Clientes
// Endereco_numero = endereco_nro
// Tipo de Cliente = tipo_pessoa
// CPF_CNPJ   = cpf_cnpj
// Bairro = endereco_bairro

        if (!$CI->db->field_exists('endereco_nro' ,db_prefix() . 'clients')) {
          $CI->db->query('ALTER TABLE `' . db_prefix() . 'clients`
            ADD COLUMN `endereco_nro` VARCHAR(10) NOT NULL');
			
		if (!$CI->db->field_exists('tipo_pessoa' ,db_prefix() . 'clients')) {
          $CI->db->query('ALTER TABLE `' . db_prefix() . 'clients`
            ADD COLUMN `tipo_pessoa` VARCHAR(2) NOT NULL');	
			
        if (!$CI->db->field_exists('endereco_bairro' ,db_prefix() . 'clients')) {
          $CI->db->query('ALTER TABLE `' . db_prefix() . 'clients`
            ADD COLUMN `endereco_bairro` VARCHAR(50) NOT NULL');	

        if (!$CI->db->field_exists('cpf_cnpj' ,db_prefix() . 'clients')) {
          $CI->db->query('ALTER TABLE `' . db_prefix() . 'clients`
            ADD COLUMN `cpf_cnpj` VARCHAR(50) NOT NULL');	

// Cria tabela de Bancos


// Cria tabela de Contas Bancarias


// Cria Tabela de Plano de Contas Gerencial



     }
}
