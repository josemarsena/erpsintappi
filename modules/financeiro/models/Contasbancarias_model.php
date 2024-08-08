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

    public function add($data)
    {
        $data['datacriacao'] = date('Y-m-d H:i:s');
        $data['criadopor'] = get_staff_user_id();
     //   $myfile = fopen("erro.txt", "w") or die("Unable to open file!");
      //  fwrite($myfile, var_dump($data));
     //   fclose($myfile);
        $this->db->insert(db_prefix().'fin_contabancaria', $data);
        $insert_id = $this->db->insert_id();
        if ($insert_id)
        {
            log_activity('Conta Bancária [ ID:'.$insert_id.' ID Equipe '.get_staff_user_id().' ]');
            return $insert_id;
        }

        return false;
    }

    public function update($data, $id)
    {
       // $affectedRows = 0;

       // $contabancaria = $this->db->where('id', $id)->get('fin_contabancaria')->row();


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
     * Update client status Active/Inactive
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

}

