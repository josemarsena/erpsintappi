<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<?php $arrAtt = array();
      $arrAtt['data-type']='currency';
      $arrAtt['pattern']= "^\d*(\.\d{0,2})?$";?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <?php
            if (isset($contabancaria)) {
                echo form_hidden('is_edit', 'true');
            }
            ?>
            <div class="col-md-8">
                <h4 class="tw-mt-0 tw-font-semibold tw-text-lg tw-text-neutral-700">
                    <?php echo $title; ?>
                </h4>
                <div class="panel_s">
                    <div class="panel-body">
                        <hr class="hr-panel-separator" />
                        <?php hooks()->do_action('before_contabancaria_form_name', isset($contabancaria) ? $contabancaria : null); ?>
                        <?php $selected = (isset($contabancaria) ? $contabancaria->banco_id : ''); ?>
                        <?php echo render_select('banco_id', $bancos, ['id', 'nomebanco'],'Nome do Banco', $selected); ?>
                        <div class="col-md-4">
                            <?php $value = (isset($contabancaria) ? $contabancaria->agencia : ''); ?>
                            <?php echo render_input('agencia', 'Nome da Agência', $value, 'text'); ?>
                        </div>
                        <div class="col-md-8">
                            <?php $value = (isset($contabancaria) ? $contabancaria->conta : ''); ?>
                            <?php echo render_input('conta', 'Nro. da Conta', $value, 'text'); ?>
                        </div>
                        <?php $value = (isset($contabancaria) ? $contabancaria->gerente : ''); ?>
                        <?php echo render_input('gerente', 'Gerente', $value, 'text'); ?>
                        <?php $value = (isset($contabancaria) ? $contabancaria->endereco : ''); ?>
                        <?php echo render_textarea('endereco', 'Endereço', $value, ['rows' => 3]); ?>
                        <?php $value = (isset($contabancaria) ? $contabancaria->telefone : ''); ?>
                        <?php echo render_input('telefone', 'Telefone', $value, 'text'); ?>
                        <?php $value = (isset($contabancaria) ? $contabancaria->saldoinicial : ''); ?>
                        <div class="col-md-6">
                            <?php echo render_input('saldoinicial', 'Saldo Inicial', $value, 'text', $arrAtt); ?></div>
                        <?php $value = (isset($contabancaria) ? $contabancaria->datasaldoinicial : ''); ?>
                        <div class="col-md-6">
                            <?php echo $value ?>
                            <?php echo render_date_input('datasaldoinicial', 'Data do Saldo Inicial', _d($value)); ?></div>
                        <?php echo _d($value) ?>
                        <?php $value = (isset($contabancaria) ? $contabancaria->ativo : ''); ?>
                        <div class="col-md-6 row">
                            <div class="row">
                                <div class="col-md-6 mtop10 border-right">
                                    <span><?php echo 'Conta Bancária Ativa?'; ?></span>
                                </div>
                                <div class="col-md-6 mtop10">
                                    <div class="onoffswitch">
                                        <input type="checkbox" id="ativo" data-perm-id="1"
                                               class="onoffswitch-checkbox" <?php if (isset($contabancaria) && $contabancaria->ativo == '1') {
                                            echo 'checked';
                                        } ?> value="ativo" name="ativo">
                                        <label class="onoffswitch-label" for="ativo"></label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="btn-bottom-toolbar text-right">
                            <button type="submit" class="btn btn-primary"><?php echo _l('submit'); ?></button>
                            <?php echo form_close(); ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php hooks()->do_action('before_contabancaria_form_template_close', $contabancaria ?? null); ?>
            <?php echo form_close(); ?>
        </div>
        <div class="btn-bottom-pusher"></div>
    </div>
</div>

<?php init_tail(); ?>

<script>

</script>
</body>

</html>
