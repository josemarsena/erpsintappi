<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Contasbancarias_model extends App_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Exclui um conta Bancaria
     * @param  mixed $id
     * @return boolean
     */
    public function delete($id)
    {
        $this->db->where('id', $id);
        $this->db->delete(db_prefix().'fin_contabancaria');

        return true;
    }

    /**
     * Adiciona uma nova conta Bancaria
     * @param  mixed $id
     * @return boolean
     */
    public function add($data)
    {
        $data['datacriacao'] = date('Y-m-d H:i:s');
        $data['criadopor'] = get_staff_user_id();
        $this->db->insert(db_prefix().'fin_contabancaria', $data);
        $insert_id = $this->db->insert_id();
        if ($insert_id)
        {
            log_activity('Conta Bancária [ ID:'.$insert_id.' ID Equipe '.get_staff_user_id().' ]');
            return $insert_id;
        }

        return false;
    }

    /**
     * Atualiza dos dados de uma conta Bancaria
     * @param  mixed $id
     * @return boolean
     */
    public function update($data, $id)
    {

        $data = hooks()->apply_filters('antes_contasbancarias_atualizar', $data, $id);
        $data['datasaldoinicial'] = to_sql_date($data['datasaldoinicial']);
        $this->db->where('id', $id);
        $this->db->update(db_prefix() . 'fin_contabancaria', $data);

        $affectedRows = $this->db->affected_rows();

        if ($this->db->affected_rows() > 0) {

            hooks()->do_action('apos_contasbancarias_atualizar', $id);
            log_activity('Cadastro da Conta foi atualizado [' .  $id . ']');

            return true;
        }

        return $affectedRows > 0;
    }

    /**
     * obtem os dados de uma/todas conta(s) Bancaria(s))
     * @param  mixed $id
     * @return boolean
     */
    public function get($id = '')
    {

        if ($id != '')
        {
            $this->db->where('id', $id);
            return $this->db->get(db_prefix() . 'fin_contabancaria')->row();
        } else
        {
            return $this->db->get(db_prefix() . 'fin_contabancaria')->result_array();
        }
    }


    /**
     * @param  integer ID
     * @param  integer Status ID
     * @return boolean
     * Muda Status da Conta Bancária
     */
    public function muda_status_contabancaria($id, $status)
    {
        $this->db->where('id', $id);
        $this->db->update('fin_contabancaria', [
            'ativo' => $status,
        ]);

        if ($this->db->affected_rows() > 0) {
            hooks()->do_action('status_conta_mudado', [
                'id'     => $id,
                'status' => $status,
            ]);

            log_activity('Status da Conta mudou [ID: ' . $id . ' Status(Ativa/Inativa): ' . $status . ']');

            return true;
        }

        return false;
    }

    /**
     * Atualiza o Saldo de uma Conta Bancária
     * @param  mixed $id
     * @return boolean
     */
    public function atualiza_saldo($id = '')
    {

        if ($id != '')
        {
            $this->db->where('id', $id);
            return $this->db->get(db_prefix() . 'fin_contabancaria')->row();
        } else
        {
            return $this->db->get(db_prefix() . 'fin_contabancaria')->result_array();
        }
    }


    /**
     * Registra o Movimento da conta Bancária após baixa do Contas a Pagar e Receber
     */

    /**
     * Adiciona um novo Movimento pago/recebido
     * @param  mixed $id
     * @return boolean
     */
    public function add_movimento($data)
    {
        $data['datacriacao'] = date('Y-m-d H:i:s');
        $data['criadopor'] = get_staff_user_id();
        $this->db->insert(db_prefix().'fin_contabancaria', $data);
        $insert_id = $this->db->insert_id();
        if ($insert_id)
        {
            log_activity('Movimento da Conta Efetuado com sucesso [ ID:'.$insert_id.' ID Equipe '.get_staff_user_id().' ]');
            return $insert_id;
        }

        return false;
    }

    /**
     * Exclui um Movimento na Conta
     * @param  mixed $id
     * @return boolean
     */
    public function delete_movto($id)
    {
        $this->db->where('id', $id);
        $this->db->delete(db_prefix().'fin_contabancaria');

        return true;
    }


    /**
     * Atualiza os Dados do Movimento após modificações
     * @param  mixed $id
     * @return boolean
     */
    public function update_movto($data, $id)
    {

        $data = hooks()->apply_filters('antes_contasbancarias_atualizar', $data, $id);
        $data['datasaldoinicial'] = to_sql_date($data['datasaldoinicial']);
        $this->db->where('id', $id);
        $this->db->update(db_prefix() . 'fin_contabancaria', $data);

        $affectedRows = $this->db->affected_rows();

        if ($this->db->affected_rows() > 0) {

            hooks()->do_action('apos_contasbancarias_atualizar', $id);
            log_activity('Cadastro da Conta foi atualizado [' .  $id . ']');

            return true;
        }

        return $affectedRows > 0;
    }

    /**
     * Obter os dados do Movimento conforme o Id da Conta Bancaria
     * @param  mixed $id
     * @return boolean
     */
    public function get_movto($id = '')
    {

        if ($id != '')
        {
            $this->db->where('id', $id);
            return $this->db->get(db_prefix() . 'fin_contabancaria')->row();
        } else
        {
            return $this->db->get(db_prefix() . 'fin_contabancaria')->result_array();
        }
    }

}

