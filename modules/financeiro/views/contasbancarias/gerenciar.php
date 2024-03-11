<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">
                <div class="_buttons">
                    <?php if (staff_can('create',  'contasbancarias')) { ?>
                        <a href="<?php echo admin_url('financeiro/adicionar_contabancaria'); ?>" class="btn btn-primary">
                            <i class="fa-regular fa-plus tw-mr-1"></i>
                            <?php echo 'Nova Conta'; ?>
                        </a>
                    <?php } ?>
                    <div class="clearfix"></div>
                </div>

                <div class="panel_s tw-mt-2 sm:tw-mt-4">
                    <?php echo form_hidden('custom_view'); ?>
                    <div class="panel-body">
                        <div class="panel-table-full tw-mt-10">
                            <?php $this->load->view('financeiro/contasbancarias/tabelacontasbancarias_html'); ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="modal fade" id="contasbancarias_modal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                            aria-hidden="true">&times;</span></button>
                <h4 class="modal-title" id="myModalLabel">
                    <span class="edit-title"><?php echo 'Adicionar novo Banco'; ?></span>
                </h4>
            </div>
            <?php echo form_open('financeiro/bancos', ['id' => 'bancos_form']); ?>
            <?php echo form_hidden('id'); ?>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-12">
                        <?php echo render_input('banco_id', 'Nome do Banco', '', 'text'); ?>
                        <?php echo render_input('agencia', 'Nome da Agência', '', 'text'); ?>
                        <?php echo render_input('conta', 'Nro. da Conta', '', 'text'); ?>
                        <?php echo render_input('gerente', 'Gerente', '', 'text'); ?>
                        <?php echo render_input('endereco', 'Endereço', '', 'longtext'); ?>
                        <?php echo render_input('telefone', 'Telefone', '', 'text'); ?>
                        <?php echo render_input('saldoinicial', 'Saldo Inicial', '', 'decimal'); ?>
                        <?php echo render_input('datasaldoinicial', 'Data do Saldo Inicial', '', 'date'); ?>
                        <?php echo render_input('ativo', 'Ativo?', '', 'text'); ?>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo _l('close'); ?></button>
                <button type="submit" class="btn btn-primary"><?php echo _l('submit'); ?></button>
                <?php echo form_close(); ?>
            </div>
        </div>
    </div>
</div>
<?php init_tail(); ?>
<script>
    $(function() {
        initDataTable('.table-contasbancarias', admin_url + 'financeiro/table_contasbancarias', undefined, undefined,
            'undefined',
            <?php echo hooks()->apply_filters('contasbancarias_table_default_order', json_encode([0, 'asc'])); ?>);


    });
</script>
</body>

</html>