<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Formata o Status da Conta Bancária
 * @param  integer  $status
 * @param  string  $classes additional classes
 * @param  boolean $label   To include in html label or not
 * @return mixed
 */
function format_contabancaria_status($status, $classes = '', $label = true)
{
    $id          = $status;
    $label_class = financeiro_status_color_class($status);
    $status      = financeiro_status_by_id($status);
    if ($label == true) {
        return '<span class="label label-' . $label_class . ' ' . $classes . ' s-status financeiro-status-' . $id . ' financeiro-status-' . $label_class . '">' . $status . '</span>';
    }

    return $status;
}



/**
 * Return estimate status translated by passed status id
 * @param  mixed $id estimate status id
 * @return string
 */
function financeiro_status_by_id($id)
{
    $status = '';
    if ($id == 0) {
        $status = 'Sim';
    } elseif ($id == 1) {
        $status = 'Não';
    } else {
        if (!is_numeric($id)) {
            if ($id == 'not_sent') {
                $status = _l('not_sent_indicator');
            }
        }
    }

    return hooks()->apply_filters('financeiro_status_label', $status, $id);
}

/**
 * Return estimate status color class based on twitter bootstrap
 * @param  mixed  $id
 * @param  boolean $replace_default_by_muted
 * @return string
 */
function financeiro_status_color_class($id, $replace_default_by_muted = false)
{
    $class = '';
    if ($id == 1) {
        $class = 'default';
        if ($replace_default_by_muted == true) {
            $class = 'muted';
        }
    } elseif ($id == 2) {
        $class = 'info';
    } elseif ($id == 3) {
        $class = 'danger';
    } elseif ($id == 4) {
        $class = 'success';
    } elseif ($id == 5) {
        // status 5
        $class = 'warning';
    } else {
        if (!is_numeric($id)) {
            if ($id == 'not_sent') {
                $class = 'default';
                if ($replace_default_by_muted == true) {
                    $class = 'muted';
                }
            }
        }
    }

    return hooks()->apply_filters('financeiro_status_color_class', $class, $id);
}
