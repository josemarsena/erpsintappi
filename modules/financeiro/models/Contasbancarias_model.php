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
        $affectedRows = 0;

        $contasbancarias = $this->db->where('id', $id)->get('fin_contasbancarias')->row();


        $data = hooks()->apply_filters('antes_banco_atualizar', $data, $id);


        $this->db->where('id', $id);
        $this->db->update(db_prefix() . 'fin_contasbancarias', $data);

        if ($this->db->affected_rows() > 0) {
            hooks()->do_action('apos_banco_atualizar', $id);
            log_activity('Cadastro da Conta foi datualizado [' . $data['subject'] . ']');

            return true;
        }

        return $affectedRows > 0;
    }

    public function get($id = '')
    {

        if ($id != '')
        {
            $this->db->where('id', $id);
            return $this->db->get(db_prefix() . 'fin_bancos')->row();
        } else
        {
            return $this->db->get(db_prefix() . 'fin_bancos')->result_array();
        }
    }

    public function get_category_filter($p_category_id)
    {
        $this->db->where_in('p_category_id', $p_category_id);
        $this->db->order_by('product_master.product_category_id', 'ASC');

        return $this->get_by_id_product();
    }

    public function edit_banco($data, $id)
    {
        $banco = $this->get_by_id_banco($id);
        $this->db->where('id', $id);
        $res = $this->db->update(db_prefix().'fin_bancos', $data);
        if ($this->db->affected_rows() > 0) {
            log_activity('Banco Atualizado[ ID: '.$id.', '.$product->product_name.', Staff id '.get_staff_user_id().' ]');
        }
        if ($res) {
            return true;
        }

        return false;
    }

    public function delete_by_id_fin_bancos($id)
    {
        $product  = $this->get_by_id_product($id);
        $relPath  = get_upload_path_by_type('products').'/';
        $fullPath = $relPath.$product->product_image;
        unlink($fullPath);
        if (!empty($id)) {
            $this->db->where('id', $id);
        }
        $result = $this->db->delete(db_prefix().'product_master');
        log_activity('Product Deleted[ ID: '.$id.', '.$product->product_name.', Staff id '.get_staff_user_id().' ]');

        return $result;
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

            log_activity('Customer Status Changed [ID: ' . $id . ' Status(Active/Inactive): ' . $status . ']');

            return true;
        }

        return false;
    }

}

