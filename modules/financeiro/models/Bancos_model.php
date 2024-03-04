<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Bancos_model extends App_Model
{
    public function __construct()
    {
        parent::__construct();
    }


    public function add($data)
    {

        $this->db->insert(db_prefix().'fin_bancos', $data);
        $insert_id = $this->db->insert_id();
        if ($insert_id)
        {
            log_activity('Banco Adicionado [ ID:'.$insert_id.' ID Equipe '.get_staff_user_id().' ]');
            return $insert_id;
        }

        return false;
    }

    public function update($data, $id)
    {
        $affectedRows = 0;

        $banco = $this->db->where('id', $id)->get('fin_bancos')->row();


//        $data = hooks()->apply_filters('before_contract_updated', $data, $id);


        $this->db->where('id', $id);
        $this->db->update(db_prefix() . 'fin_bancos', $data);

        if ($this->db->affected_rows() > 0) {
            hooks()->do_action('after_contract_updated', $id);
            log_activity('Cadastro do Banco foi datualizado [' . $data['subject'] . ']');

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
        $res = $this->db->update(db_prefix().'fin_bancosr', $data);
        if ($this->db->affected_rows() > 0) {
            if (!empty($data['quantity_number']) && $product->quantity_number != $data['quantity_number']) {
                log_activity('Banco Atualizado[ ID: '.$id.', From: '.$product->quantity_number.' To: '.$data['quantity_number'].' Staff id '.get_staff_user_id().']');
            }
            log_activity('Banco Atualizado[ ID: '.$id.', '.$product->product_name.', Staff id '.get_staff_user_id().' ]');
        }
        if ($res) {
            return true;
        }

        return false;
    }

    public function delete_by_id_tdc_compra($id)
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
}

