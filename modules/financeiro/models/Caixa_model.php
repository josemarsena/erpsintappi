<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Caixa_model extends App_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Adiciona um novo Registro na Conta Caixa
     * @param  mixed $data
     * @return boolean
     */
    public function add($data)
    {
        $data['datacriacao'] = date('Y-m-d H:i:s');
        $data['criadopor'] = get_staff_user_id();

        $this->db->insert(db_prefix().'fin_contacaixa', $data);
        $insert_id = $this->db->insert_id();
        if ($insert_id)
        {
            log_activity('Conta Caixa Adicionada [ ID:'.$insert_id.' ID Equipe '.get_staff_user_id().' ]');
            return $insert_id;
        }

        return false;
    }

    /**
     * Exclui uma Conta Caixa. Atenção!!!!!! Excluir manterá o histórico? O ideal é ocultar a conta e deixa-la recuperavel
     * @param  mixed $id group id
     * @return boolean
     */
    public function delete($id)
    {
        $this->db->where('id', $id);
        $this->db->delete(db_prefix().'fin_bancos');

        return true;
    }

    /**
     * Atualiza os dados Editados
     * @param  mixed $id group id
     * @param $data
     * @return boolean
     */
    public function update($data, $id)
    {
        $affectedRows = 0;

           $data = hooks()->apply_filters('antes_contacaixa_atualizar', $data, $id);


        $this->db->where('id', $id);
        $this->db->update(db_prefix() . 'fin_contacaixa', $data);

        $affectedRows = $this->db->affected_rows();
        if ($affectedRows > 0) {
            hooks()->do_action('apos_contacaixa_atualizar', $id);
            log_activity('Cadastro da Conta Caixa foi atualizado [' . $data['id'] . ']');

            return true;
        }

        return $affectedRows > 0;
    }

    /**
     * Obtem um registro conforme o ID
     * @param  mixed $id group id
     * @param $data
     * @return boolean
     */
    public function get($id = '')
    {

        if ($id != '')
        {
            $this->db->where('id', $id);
            return $this->db->get(db_prefix() . 'fin_contacaixa')->row();
        } else
        {
            return $this->db->get(db_prefix() . 'fin_contacaixa')->result_array();
        }
    }


}

