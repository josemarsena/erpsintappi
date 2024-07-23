<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">
                <div class="_buttons">
                    <?php if (staff_can('create',  'bancos')) { ?>
                        <a href="#" class="btn btn-primary" data-toggle="modal" data-target="#bancos_modal">
                            <i class="fa-regular fa-plus tw-mr-1"></i>
                            <?php echo 'Novo Banco'; ?>
                        </a>
                    <?php } ?>
                        <div class="clearfix"></div>
                </div>

                <div class="panel_s tw-mt-2 sm:tw-mt-4">
                    <?php echo form_hidden('custom_view'); ?>
                    <div class="panel-body">
                        <div class="panel-table-full tw-mt-10">
                            <?php $this->load->view('financeiro/bancos/tabelabancos_html'); ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="modal fade" id="bancos_modal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
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
                        <?php echo render_input('codigobanco', 'Código do Banco', '', 'text'); ?>
                        <?php echo render_input('nomebanco', 'Nome do Banco', '', 'text'); ?>
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
        initDataTable('.table-bancos', admin_url + 'financeiro/table_bancos', undefined, undefined,
            'undefined',
            <?php echo hooks()->apply_filters('bancos_table_default_order', json_encode([0, 'asc'])); ?>);


    });
</script>

<script>
 $(function() {
    appValidateForm($('form'), {
        codigobanco: {
            required: true,
            maxlength: 3
        },
        nomebanco: 'required',
    });

    $('#bancos_modal').on('show.bs.modal', function(event) {

        var button = $(event.relatedTarget)
        var id = button.data('id');

        $('#bancos_modal input[name="codigobanco"]').val('');
        $('#bancos_modal input[name="nomebanco"]').val('');

        $('#bancos_modal .add-title').removeClass('hide');
        $('#bancos_modal .edit-title').addClass('hide');

        if (typeof(id) !== 'undefined') {

            var codigobanco = $(button).parents('tr').find('td').eq(0).find('span.name').text();
            var nomebanco = $(button).parents('tr').find('td').eq(1).text();

            $('#bancos_modal .add-title').addClass('hide');
            $('#bancos_modal .edit-title').removeClass('hide');
            $('#bancos_modal input[name="codigobanco"]').val(codigobanco);
            $('#bancos_modal input[name="nomebanco"]').val(nomebanco);
        }
    });

 });

</script>

</body>

</html>