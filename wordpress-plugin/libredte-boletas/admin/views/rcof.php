<?php
if (!defined('ABSPATH')) exit;

$ambiente = get_option('libredte_ambiente', 'certificacion');

// RCOF disponible en ambos ambientes:
// - Certificacion: Para enviar junto con el Set de Pruebas
// - Produccion: Para el envio diario obligatorio

global $wpdb;
$table_rcof = $wpdb->prefix . 'libredte_rcof';
$table_boletas = $wpdb->prefix . 'libredte_boletas';

// Procesar generacion de RCOF
if (isset($_POST['generar_rcof']) && wp_verify_nonce($_POST['_wpnonce'], 'libredte_rcof')) {
    $fecha = sanitize_text_field($_POST['fecha_rcof']);
    $rcof = new LibreDTE_RCOF();
    $resultado = $rcof->generar_rcof($fecha);

    if ($resultado['success']) {
        echo '<div class="notice notice-success"><p>RCOF generado correctamente para ' . $fecha . '</p></div>';
    } else {
        echo '<div class="notice notice-error"><p>Error: ' . $resultado['error'] . '</p></div>';
    }
}

// Procesar envio de RCOF
if (isset($_POST['enviar_rcof']) && wp_verify_nonce($_POST['_wpnonce'], 'libredte_rcof')) {
    $rcof_id = intval($_POST['rcof_id']);
    $rcof = new LibreDTE_RCOF();
    $resultado = $rcof->enviar_rcof($rcof_id);

    if ($resultado['success']) {
        echo '<div class="notice notice-success"><p>RCOF enviado al SII. Track ID: ' . $resultado['track_id'] . '</p></div>';
    } else {
        echo '<div class="notice notice-error"><p>Error: ' . $resultado['error'] . '</p></div>';
    }
}

// Estadisticas del dia
$hoy = date('Y-m-d');
$boletas_hoy = $wpdb->get_row($wpdb->prepare(
    "SELECT
        COUNT(*) as cantidad,
        SUM(monto_total) as total,
        SUM(monto_neto) as neto,
        SUM(monto_iva) as iva,
        SUM(monto_exento) as exento,
        MIN(folio) as folio_min,
        MAX(folio) as folio_max,
        SUM(CASE WHEN estado_sii = 'aceptado' THEN 1 ELSE 0 END) as aceptadas
    FROM $table_boletas
    WHERE DATE(fecha_emision) = %s",
    $hoy
));

// Historial RCOF
$historial = $wpdb->get_results(
    "SELECT * FROM $table_rcof ORDER BY fecha DESC, id DESC LIMIT 30"
);

$rcof_automatico = get_option('libredte_rcof_automatico', 0);
?>

<div class="wrap libredte-rcof">
    <h1>RCOF - Reporte de Consumo de Folios</h1>

    <div class="ambiente-badge produccion">
        <span class="dashicons dashicons-yes-alt"></span>
        Ambiente: <strong>PRODUCCION</strong>
    </div>

    <!-- Resumen del dia -->
    <div class="card resumen-dia">
        <h2>Resumen de Hoy (<?php echo date('d/m/Y'); ?>)</h2>
        <?php if ($boletas_hoy && $boletas_hoy->cantidad > 0): ?>
        <div class="resumen-grid">
            <div class="resumen-item">
                <span class="numero"><?php echo number_format($boletas_hoy->cantidad); ?></span>
                <span class="label">Boletas Emitidas</span>
            </div>
            <div class="resumen-item">
                <span class="numero"><?php echo number_format($boletas_hoy->aceptadas); ?></span>
                <span class="label">Aceptadas SII</span>
            </div>
            <div class="resumen-item">
                <span class="numero"><?php echo $boletas_hoy->folio_min; ?> - <?php echo $boletas_hoy->folio_max; ?></span>
                <span class="label">Rango Folios</span>
            </div>
            <div class="resumen-item">
                <span class="numero">$<?php echo number_format($boletas_hoy->neto, 0, ',', '.'); ?></span>
                <span class="label">Monto Neto</span>
            </div>
            <div class="resumen-item">
                <span class="numero">$<?php echo number_format($boletas_hoy->iva, 0, ',', '.'); ?></span>
                <span class="label">IVA</span>
            </div>
            <div class="resumen-item">
                <span class="numero">$<?php echo number_format($boletas_hoy->total, 0, ',', '.'); ?></span>
                <span class="label">Total</span>
            </div>
        </div>
        <?php else: ?>
        <p>No hay boletas emitidas hoy.</p>
        <?php endif; ?>
    </div>

    <!-- Generar RCOF -->
    <div class="card">
        <h2>Generar RCOF</h2>
        <form method="post" class="rcof-form">
            <?php wp_nonce_field('libredte_rcof'); ?>
            <div class="form-row">
                <label for="fecha_rcof">Fecha:</label>
                <input type="date" id="fecha_rcof" name="fecha_rcof" value="<?php echo $hoy; ?>" max="<?php echo $hoy; ?>">
                <button type="submit" name="generar_rcof" class="button button-primary">
                    <span class="dashicons dashicons-media-document"></span> Generar RCOF
                </button>
            </div>
            <p class="description">
                El RCOF agrupa todas las boletas aceptadas por el SII en la fecha indicada.
                <?php if ($rcof_automatico): ?>
                <br><span class="dashicons dashicons-yes"></span> <strong>Envio automatico activado</strong> - Se enviara automaticamente a las 23:50
                <?php endif; ?>
            </p>
        </form>
    </div>

    <!-- Historial RCOF -->
    <div class="card">
        <h2>Historial de RCOF</h2>
        <?php if (empty($historial)): ?>
        <p>No hay RCOF generados.</p>
        <?php else: ?>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Folios</th>
                    <th>Cantidad</th>
                    <th>Monto Total</th>
                    <th>Estado</th>
                    <th>Track ID</th>
                    <th>Estado SII</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($historial as $rcof): ?>
                <tr>
                    <td><strong><?php echo date('d/m/Y', strtotime($rcof->fecha)); ?></strong></td>
                    <td><?php echo $rcof->folio_inicial; ?> - <?php echo $rcof->folio_final; ?></td>
                    <td><?php echo $rcof->cantidad_boletas; ?></td>
                    <td>$<?php echo number_format($rcof->monto_total, 0, ',', '.'); ?></td>
                    <td>
                        <span class="estado-badge estado-<?php echo $rcof->estado; ?>">
                            <?php echo ucfirst($rcof->estado); ?>
                        </span>
                    </td>
                    <td><?php echo $rcof->track_id ?: '-'; ?></td>
                    <td>
                        <?php if ($rcof->estado_sii): ?>
                        <span class="estado-badge estado-<?php echo $rcof->estado_sii; ?>">
                            <?php echo ucfirst($rcof->estado_sii); ?>
                        </span>
                        <?php else: ?>
                        -
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($rcof->estado === 'generado'): ?>
                        <form method="post" style="display:inline;">
                            <?php wp_nonce_field('libredte_rcof'); ?>
                            <input type="hidden" name="rcof_id" value="<?php echo $rcof->id; ?>">
                            <button type="submit" name="enviar_rcof" class="button button-small button-primary">
                                Enviar al SII
                            </button>
                        </form>
                        <?php elseif ($rcof->track_id): ?>
                        <button type="button" class="button button-small btn-consultar-rcof" data-id="<?php echo $rcof->id; ?>">
                            Consultar Estado
                        </button>
                        <?php endif; ?>
                        <button type="button" class="button button-small btn-ver-xml" data-id="<?php echo $rcof->id; ?>">
                            Ver XML
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>

    <!-- Informacion -->
    <div class="card">
        <h2>Informacion sobre el RCOF</h2>
        <p>El <strong>RCOF (Reporte de Consumo de Folios)</strong> es un documento obligatorio que debe enviarse al SII
        al final de cada dia de operacion en ambiente de produccion.</p>
        <ul>
            <li>Agrupa todas las boletas electronicas emitidas en el dia</li>
            <li>Informa los folios utilizados y los montos totales</li>
            <li>Debe enviarse antes de la medianoche</li>
            <li>Solo se envian boletas que fueron ACEPTADAS por el SII</li>
        </ul>
        <p><strong>Nota:</strong> En ambiente de certificacion NO se envia RCOF.</p>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Consultar estado RCOF
    $('.btn-consultar-rcof').on('click', function() {
        var $btn = $(this);
        var id = $btn.data('id');

        $btn.prop('disabled', true).text('Consultando...');

        $.ajax({
            url: ajaxurl,
            method: 'POST',
            data: {
                action: 'libredte_consultar_rcof',
                nonce: '<?php echo wp_create_nonce('libredte_nonce'); ?>',
                id: id
            },
            success: function(response) {
                if (response.success) {
                    alert('Estado: ' + response.data.estado + '\n' + (response.data.glosa || ''));
                    location.reload();
                } else {
                    alert('Error: ' + response.data);
                }
            },
            complete: function() {
                $btn.prop('disabled', false).text('Consultar Estado');
            }
        });
    });

    // Ver XML
    $('.btn-ver-xml').on('click', function() {
        var id = $(this).data('id');
        window.open(ajaxurl + '?action=libredte_ver_rcof_xml&id=' + id, '_blank');
    });
});
</script>

<style>
.resumen-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 20px;
    margin-top: 15px;
}
.resumen-item {
    text-align: center;
    padding: 15px;
    background: #f8fafc;
    border-radius: 8px;
}
.resumen-item .numero {
    display: block;
    font-size: 24px;
    font-weight: bold;
    color: #2271b1;
}
.resumen-item .label {
    color: #64748b;
    font-size: 12px;
}

.rcof-form .form-row {
    display: flex;
    align-items: center;
    gap: 15px;
}
.rcof-form label {
    font-weight: 500;
}

.estado-generado { background: #e5e7eb; color: #374151; }
.estado-enviado { background: #dbeafe; color: #1e40af; }
.estado-aceptado { background: #d1fae5; color: #065f46; }
.estado-rechazado { background: #fee2e2; color: #991b1b; }
.estado-error { background: #fee2e2; color: #991b1b; }
</style>
