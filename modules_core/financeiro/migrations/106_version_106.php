<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Migration_Version_106 extends App_module_migration
{
    public function up()
    {
        $CI = &get_instance();

        if (!$CI->db->table_exists(db_prefix() . 'fin_faturas')) {
            $CI->db->query('CREATE TABLE `' . db_prefix() . "fin_faturas` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `enviado` tinyint(1) NOT NULL DEFAULT '0',
                `dataenvio` datetime DEFAULT NULL,   
                `id_fornecedor` int(11) NOT NULL,
                `numero` int(11) NOT NULL,
                `prefixo` varchar(50) DEFAULT NULL,
                `formatonumero` int(11) NOT NULL DEFAULT 0,
                `datacriacao` datetime NOT NULL,
                `data` date NOT NULL,
                `datavencimento` date DEFAULT NULL,
                `moeda` int(11) NOT NULL,
                `subtotal` decimal(15,2) NOT NULL,
                `total_impostos` decimal(15,2) NOT NULL,
                `total` decimal(15,2) NOT NULL,
                `ajuste` decimal(15,2) DEFAULT NULL,
                `adicionado_por` int(11) NOT NULL,
                `hash` varchar(32) NOT NULL,
                `status` int(11) DEFAULT 1,      
                `nota_admin` text DEFAULT NULL,
                `ultimo_lembrete_atraso` date DEFAULT NULL,
                `ultimo_lembrete_vencido` date DEFAULT NULL,
                `cancelar_lembretes_atraso` int(11) NOT NULL DEFAULT 0,
                `modos_pagamento_permitidos` mediumtext DEFAULT NULL,
                `token` mediumtext DEFAULT NULL,
                `porcento_descto` decimal(15, 2) DEFAULT 0.00,
                `total_descto` decimal(15, 2) DEFAULT 0.00,
                `tipo_descto` varchar(30) NOT NULL,
                `recorrente` int(11) NOT NULL DEFAULT 0,
                `tipo_recorrencia` varchar(10) DEFAULT NULL,
                `custom_recorrencia` tinyint(1) NOT NULL DEFAULT 0,
                `ciclos` int(11) NOT NULL DEFAULT 0,
                `total_ciclos` int(11) NOT NULL DEFAULT 0,
                `e_recorrente_de` int(11) DEFAULT NULL,
                `ultima_data_recorrencia` date DEFAULT NULL,
                `termos` text DEFAULT NULL,
                `id_comprador` int(11) NOT NULL DEFAULT 0,
                `mostrar_quantidade_como` int(11) NOT NULL DEFAULT 1,
                `id_projeto` int(11) DEFAULT 0,
                `subscription_id`  int(11) NOT NULL DEFAULT 0,
                `link_curto` varchar(100) DEFAULT NULL,
                `id_pedidocompra` int(11) NOT NULL,   
                `provisao` tinyint(1) DEFAULT 1,
                PRIMARY KEY (`id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=" . $CI->db->char_set . ';');
        }

        if (!$CI->db->table_exists(db_prefix() . 'fin_movbancario')) {
            $CI->db->query('CREATE TABLE `' . db_prefix() . "fin_movbancario` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `id_contabancaria` int(11) NOT NULL,
                `historico` varchar(255), 
                `data_movimento` date NOT NULL,
                `origem_transacao` int(11) NOT NULL,
                `valor_transacao` decimal(15,2) NOT NULL,
                `codigo_transacao` varchar(32) NOT NULL,
                `tipo_transacao` int(11) NOT NULL,
                `criadopor` int(11) NOT NULL,
                `datacriacao` datetime DEFAULT NULL,  
                PRIMARY KEY (`id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=" . $CI->db->char_set . ';');
        }


        if (!$CI->db->table_exists(db_prefix() . 'fin_movcaixa')) {
            $CI->db->query('CREATE TABLE `' . db_prefix() . "fin_movcaixa` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `id_caixa` int(11) NOT NULL,
                `historico` varchar(255), 
                `data_movimento` date NOT NULL,
                `origem_transacao` int(11) NOT NULL,
                `valor_transacao` decimal(15,2) NOT NULL,
                `codigo_transacao` varchar(32) NOT NULL,
                `tipo_transacao` int(11) NOT NULL,
                `criadopor` int(11) NOT NULL,
                `datacriacao` datetime DEFAULT NULL,  
                PRIMARY KEY (`id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=" . $CI->db->char_set . ';');
        }


        if (!$CI->db->table_exists(db_prefix() . 'fin_movcartao')) {
            $CI->db->query('CREATE TABLE `' . db_prefix() . "fin_movcartao` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `id_cartao` int(11) NOT NULL,
                `historico` varchar(255), 
                `data_movimento` date NOT NULL,
                `origem_transacao` int(11) NOT NULL,
                `valor_transacao` decimal(15,2) NOT NULL,
                `codigo_transacao` varchar(32) NOT NULL,
                `tipo_transacao` int(11) NOT NULL,
                `criadopor` int(11) NOT NULL,
                `datacriacao` datetime DEFAULT NULL,  
                PRIMARY KEY (`id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=" . $CI->db->char_set . ';');
        }
    }
}