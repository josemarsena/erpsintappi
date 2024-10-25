<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Migration_Version_102 extends App_module_migration
{
    public function up()
    {
        $CI = &get_instance();
        // Cria tabela de Conta Caixa
        if (!$CI->db->table_exists(db_prefix() . 'desk_ordemservico')) {
            $CI->db->query('CREATE TABLE `' . db_prefix() . "desk_ordemservico` (
              `id` INT(11) NOT NULL AUTO_INCREMENT,
              `id_orcamento` INT(11) NOT NULL,
              `descricao` TEXT DEFAULT NULL,    
              `status` INT(11) NOT NULL DEFAULT 0, 
              `tipo_faturamento` INT(11) NOT NULL,  
              `id_cliente` INT(11) NOT NULL,    
              `datainicio` DATE NULL,        
              `datatermino` DATE DEFAULT NULL,
              `prazofinal` DATE DEFAULT NULL,              
              `criadopor` INT(11) NULL,
              `datacriacao` DATETIME NULL,
              `progresso` INT(11) DEFAULT 0,
              `progresso_de_tarefas` INT(11) NOT NULL DEFAULT 1,
              `custo_projeto` DECIMAL(15,2) DEFAULT NULL,
              `taxa_por_hora_projeto` DECIMAL(15,2) DEFAULT NULL,
              `horas_orcadas` DECIMAL(15,2) DEFAULT NULL,
              `adicionado_de` INT(11) NOT NULL,
              `contato_notificacao` INT(11) DEFAULT 1,
              `notifica_contatos` TEXT DEFAULT NULL,
              PRIMARY KEY (`id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=" . $CI->db->char_set . ';');

        }

    }
}