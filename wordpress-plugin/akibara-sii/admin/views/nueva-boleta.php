<?php
if (!defined('ABSPATH')) exit;

$ambiente = get_option('akibara_ambiente', 'certificacion');
$envio_automatico = get_option('akibara_envio_automatico', 0);

// Verificar CAF
global $wpdb;
$table_caf = $wpdb->prefix . 'akibara_caf';
$caf_activo = $wpdb->get_row($wpdb->prepare(
    "SELECT * FROM $table_caf WHERE tipo_dte = 39 AND ambiente = %s AND activo = 1 ORDER BY folio_desde DESC LIMIT 1",
    $ambiente
));

$siguiente_folio = 0;
if ($caf_activo) {
    $siguiente_folio = $caf_activo->folio_actual;
}
?>

<div class="wrap akibara-nueva-boleta">
    <h1>Nueva Boleta Electronica</h1>

    <div class="ambiente-badge <?php echo $ambiente; ?>">
        Ambiente: <strong><?php echo strtoupper($ambiente); ?></strong>
        <?php if ($envio_automatico): ?>
        <span class="envio-mode">| Envio Automatico</span>
        <?php else: ?>
        <span class="envio-mode">| Envio Manual</span>
        <?php endif; ?>
    </div>

    <?php if (!$caf_activo): ?>
    <div class="notice notice-error">
        <p><strong>Error:</strong> No hay CAF activo. Debes subir un CAF antes de emitir boletas.
        <a href="<?php echo admin_url('admin.php?page=akibara-caf'); ?>">Subir CAF</a></p>
    </div>
    <?php else: ?>

    <div class="folio-info">
        <p>Siguiente folio disponible: <strong><?php echo $siguiente_folio; ?></strong></p>
    </div>

    <form id="form-nueva-boleta" class="boleta-form">
        <?php wp_nonce_field('akibara_nonce', 'akibara_nonce'); ?>

        <!-- Receptor -->
        <div class="card">
            <h2>Datos del Receptor</h2>
            <table class="form-table">
                <tr>
                    <th><label for="receptor_rut">RUT Receptor</label></th>
                    <td>
                        <input type="text" id="receptor_rut" name="receptor_rut" value="66666666-6" class="regular-text">
                        <p class="description">Para boletas sin identificar usar 66666666-6</p>
                    </td>
                </tr>
                <tr>
                    <th><label for="receptor_razon">Razon Social</label></th>
                    <td>
                        <input type="text" id="receptor_razon" name="receptor_razon" value="CLIENTE" class="regular-text">
                    </td>
                </tr>
                <tr>
                    <th><label for="receptor_direccion">Direccion</label></th>
                    <td>
                        <input type="text" id="receptor_direccion" name="receptor_direccion" value="Santiago" class="regular-text">
                    </td>
                </tr>
                <tr>
                    <th><label for="receptor_comuna">Comuna</label></th>
                    <td>
                        <input type="text" id="receptor_comuna" name="receptor_comuna" value="Santiago" class="regular-text">
                    </td>
                </tr>
            </table>
        </div>

        <!-- Detalle -->
        <div class="card">
            <h2>Detalle de Items</h2>
            <table id="tabla-items" class="wp-list-table widefat">
                <thead>
                    <tr>
                        <th width="5%">#</th>
                        <th width="40%">Descripcion</th>
                        <th width="10%">Cantidad</th>
                        <th width="15%">Precio Unit.</th>
                        <th width="10%">Exento</th>
                        <th width="15%">Total</th>
                        <th width="5%"></th>
                    </tr>
                </thead>
                <tbody id="items-body">
                    <tr class="item-row" data-index="1">
                        <td class="item-num">1</td>
                        <td><input type="text" name="items[1][nombre]" class="item-nombre widefat" required></td>
                        <td><input type="number" name="items[1][cantidad]" class="item-cantidad" value="1" min="1" step="1"></td>
                        <td><input type="number" name="items[1][precio]" class="item-precio" value="0" min="0"></td>
                        <td><input type="checkbox" name="items[1][exento]" class="item-exento" value="1"></td>
                        <td class="item-total">$0</td>
                        <td><button type="button" class="button btn-remove-item">&times;</button></td>
                    </tr>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="7">
                            <button type="button" id="btn-agregar-item" class="button">
                                <span class="dashicons dashicons-plus-alt2"></span> Agregar Item
                            </button>
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <!-- Totales -->
        <div class="card totales-card">
            <h2>Totales</h2>
            <table class="totales-table">
                <tr>
                    <th>Neto:</th>
                    <td id="total-neto">$0</td>
                </tr>
                <tr>
                    <th>Exento:</th>
                    <td id="total-exento">$0</td>
                </tr>
                <tr>
                    <th>IVA (19%):</th>
                    <td id="total-iva">$0</td>
                </tr>
                <tr class="total-row">
                    <th>TOTAL:</th>
                    <td id="total-final">$0</td>
                </tr>
            </table>
        </div>

        <!-- Botones -->
        <div class="submit-area">
            <button type="submit" class="button button-primary button-hero" id="btn-emitir">
                <span class="dashicons dashicons-media-document"></span>
                <?php echo $envio_automatico ? 'Emitir y Enviar al SII' : 'Emitir Boleta'; ?>
            </button>
            <?php if (!$envio_automatico): ?>
            <p class="description">La boleta sera generada pero no enviada al SII. Podras enviarla manualmente desde el historial.</p>
            <?php endif; ?>
        </div>
    </form>

    <!-- Modal resultado -->
    <div id="modal-resultado" class="akibara-modal" style="display:none;">
        <div class="modal-content">
            <span class="modal-close">&times;</span>
            <div id="resultado-content"></div>
        </div>
    </div>

    <?php endif; ?>
</div>

<script>
jQuery(document).ready(function($) {
    var itemIndex = 1;

    // Agregar item
    $('#btn-agregar-item').on('click', function() {
        itemIndex++;
        var newRow = `
            <tr class="item-row" data-index="${itemIndex}">
                <td class="item-num">${itemIndex}</td>
                <td><input type="text" name="items[${itemIndex}][nombre]" class="item-nombre widefat" required></td>
                <td><input type="number" name="items[${itemIndex}][cantidad]" class="item-cantidad" value="1" min="1" step="1"></td>
                <td><input type="number" name="items[${itemIndex}][precio]" class="item-precio" value="0" min="0"></td>
                <td><input type="checkbox" name="items[${itemIndex}][exento]" class="item-exento" value="1"></td>
                <td class="item-total">$0</td>
                <td><button type="button" class="button btn-remove-item">&times;</button></td>
            </tr>
        `;
        $('#items-body').append(newRow);
        renumerarItems();
    });

    // Eliminar item
    $(document).on('click', '.btn-remove-item', function() {
        if ($('.item-row').length > 1) {
            $(this).closest('tr').remove();
            renumerarItems();
            calcularTotales();
        }
    });

    // Renumerar items
    function renumerarItems() {
        $('.item-row').each(function(i) {
            $(this).find('.item-num').text(i + 1);
        });
    }

    // Calcular totales
    function calcularTotales() {
        var neto = 0;
        var exento = 0;

        $('.item-row').each(function() {
            var cantidad = parseInt($(this).find('.item-cantidad').val()) || 0;
            var precio = parseInt($(this).find('.item-precio').val()) || 0;
            var esExento = $(this).find('.item-exento').is(':checked');
            var subtotal = cantidad * precio;

            $(this).find('.item-total').text('$' + subtotal.toLocaleString('es-CL'));

            if (esExento) {
                exento += subtotal;
            } else {
                neto += subtotal;
            }
        });

        var iva = Math.round(neto * 0.19);
        var total = neto + iva + exento;

        $('#total-neto').text('$' + neto.toLocaleString('es-CL'));
        $('#total-exento').text('$' + exento.toLocaleString('es-CL'));
        $('#total-iva').text('$' + iva.toLocaleString('es-CL'));
        $('#total-final').text('$' + total.toLocaleString('es-CL'));
    }

    // Eventos de calculo
    $(document).on('input change', '.item-cantidad, .item-precio, .item-exento', calcularTotales);

    // Enviar formulario
    $('#form-nueva-boleta').on('submit', function(e) {
        e.preventDefault();

        var $btn = $('#btn-emitir');
        $btn.prop('disabled', true).text('Procesando...');

        $.ajax({
            url: ajaxurl,
            method: 'POST',
            data: {
                action: 'akibara_emitir_boleta',
                nonce: $('#akibara_nonce').val(),
                receptor: {
                    rut: $('#receptor_rut').val(),
                    razon_social: $('#receptor_razon').val(),
                    direccion: $('#receptor_direccion').val(),
                    comuna: $('#receptor_comuna').val()
                },
                items: []
            },
            beforeSend: function() {
                // Recolectar items
                var items = [];
                $('.item-row').each(function() {
                    items.push({
                        nombre: $(this).find('.item-nombre').val(),
                        cantidad: $(this).find('.item-cantidad').val(),
                        precio: $(this).find('.item-precio').val(),
                        exento: $(this).find('.item-exento').is(':checked') ? 1 : 0
                    });
                });
                this.data = $.param({
                    action: 'akibara_emitir_boleta',
                    nonce: $('#akibara_nonce').val(),
                    receptor: {
                        rut: $('#receptor_rut').val(),
                        razon_social: $('#receptor_razon').val(),
                        direccion: $('#receptor_direccion').val(),
                        comuna: $('#receptor_comuna').val()
                    },
                    items: items
                });
            },
            success: function(response) {
                var html = '';
                if (response.success) {
                    html = '<div class="notice notice-success"><h3>Boleta Emitida Correctamente</h3>';
                    html += '<p><strong>Folio:</strong> ' + response.data.folio + '</p>';
                    html += '<p><strong>Total:</strong> $' + response.data.total.toLocaleString('es-CL') + '</p>';
                    if (response.data.track_id) {
                        html += '<p><strong>Track ID SII:</strong> ' + response.data.track_id + '</p>';
                    }
                    html += '<p><a href="' + response.data.pdf_url + '" target="_blank" class="button">Ver PDF</a>';
                    html += ' <a href="' + response.data.xml_url + '" target="_blank" class="button">Descargar XML</a></p>';
                    html += '</div>';

                    // Reset form
                    $('#form-nueva-boleta')[0].reset();
                    $('#items-body').html($('.item-row:first').clone());
                    calcularTotales();
                } else {
                    html = '<div class="notice notice-error"><h3>Error</h3><p>' + response.data + '</p></div>';
                }

                $('#resultado-content').html(html);
                $('#modal-resultado').show();
            },
            error: function() {
                $('#resultado-content').html('<div class="notice notice-error"><p>Error de conexion</p></div>');
                $('#modal-resultado').show();
            },
            complete: function() {
                $btn.prop('disabled', false).html('<span class="dashicons dashicons-media-document"></span> <?php echo $envio_automatico ? "Emitir y Enviar al SII" : "Emitir Boleta"; ?>');
            }
        });
    });

    // Cerrar modal
    $('.modal-close, #modal-resultado').on('click', function(e) {
        if (e.target === this) {
            $('#modal-resultado').hide();
        }
    });
});
</script>
