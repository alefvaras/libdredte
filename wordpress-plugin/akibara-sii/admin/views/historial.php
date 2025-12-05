<?php
if (!defined('ABSPATH')) exit;

global $wpdb;
$table_boletas = $wpdb->prefix . 'akibara_boletas';

// Filtros
$fecha_desde = isset($_GET['fecha_desde']) ? sanitize_text_field($_GET['fecha_desde']) : date('Y-m-01');
$fecha_hasta = isset($_GET['fecha_hasta']) ? sanitize_text_field($_GET['fecha_hasta']) : date('Y-m-d');
$estado_filtro = isset($_GET['estado']) ? sanitize_text_field($_GET['estado']) : '';
$buscar = isset($_GET['buscar']) ? sanitize_text_field($_GET['buscar']) : '';

// Construir query
$where = "WHERE DATE(fecha_emision) BETWEEN %s AND %s";
$params = array($fecha_desde, $fecha_hasta);

if ($estado_filtro) {
    $where .= " AND estado_sii = %s";
    $params[] = $estado_filtro;
}

if ($buscar) {
    $where .= " AND (folio LIKE %s OR receptor_rut LIKE %s OR receptor_razon LIKE %s)";
    $params[] = '%' . $wpdb->esc_like($buscar) . '%';
    $params[] = '%' . $wpdb->esc_like($buscar) . '%';
    $params[] = '%' . $wpdb->esc_like($buscar) . '%';
}

// Paginacion
$per_page = 25;
$current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
$offset = ($current_page - 1) * $per_page;

// Total de registros
$total = $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM $table_boletas $where",
    $params
));
$total_pages = ceil($total / $per_page);

// Obtener boletas
$boletas = $wpdb->get_results($wpdb->prepare(
    "SELECT * FROM $table_boletas $where ORDER BY id DESC LIMIT %d OFFSET %d",
    array_merge($params, array($per_page, $offset))
));

// Estadisticas del periodo
$stats = $wpdb->get_row($wpdb->prepare(
    "SELECT
        COUNT(*) as total_boletas,
        SUM(monto_total) as monto_total,
        SUM(monto_neto) as monto_neto,
        SUM(monto_iva) as monto_iva,
        SUM(monto_exento) as monto_exento,
        SUM(CASE WHEN estado_sii = 'aceptado' THEN 1 ELSE 0 END) as aceptadas,
        SUM(CASE WHEN estado_sii = 'rechazado' THEN 1 ELSE 0 END) as rechazadas,
        SUM(CASE WHEN estado_sii = 'pendiente' THEN 1 ELSE 0 END) as pendientes,
        SUM(CASE WHEN estado_sii = 'generado' THEN 1 ELSE 0 END) as sin_enviar
    FROM $table_boletas $where",
    $params
));
?>

<div class="wrap libredte-historial">
    <h1>Historial de Boletas</h1>

    <!-- Estadisticas del Periodo -->
    <div class="estadisticas-periodo">
        <div class="stat-box">
            <span class="stat-value"><?php echo number_format($stats->total_boletas); ?></span>
            <span class="stat-label">Total Boletas</span>
        </div>
        <div class="stat-box aceptado">
            <span class="stat-value"><?php echo number_format($stats->aceptadas); ?></span>
            <span class="stat-label">Aceptadas SII</span>
        </div>
        <div class="stat-box rechazado">
            <span class="stat-value"><?php echo number_format($stats->rechazadas); ?></span>
            <span class="stat-label">Rechazadas</span>
        </div>
        <div class="stat-box pendiente">
            <span class="stat-value"><?php echo number_format($stats->pendientes); ?></span>
            <span class="stat-label">Pendientes</span>
        </div>
        <div class="stat-box sin-enviar">
            <span class="stat-value"><?php echo number_format($stats->sin_enviar); ?></span>
            <span class="stat-label">Sin Enviar</span>
        </div>
        <div class="stat-box monto">
            <span class="stat-value">$<?php echo number_format($stats->monto_total ?: 0, 0, ',', '.'); ?></span>
            <span class="stat-label">Monto Total</span>
        </div>
    </div>

    <!-- Filtros -->
    <div class="card filtros-card">
        <form method="get" class="filtros-form">
            <input type="hidden" name="page" value="libredte-historial">

            <div class="filtro-group">
                <label>Desde:</label>
                <input type="date" name="fecha_desde" value="<?php echo esc_attr($fecha_desde); ?>">
            </div>

            <div class="filtro-group">
                <label>Hasta:</label>
                <input type="date" name="fecha_hasta" value="<?php echo esc_attr($fecha_hasta); ?>">
            </div>

            <div class="filtro-group">
                <label>Estado:</label>
                <select name="estado">
                    <option value="">Todos</option>
                    <option value="aceptado" <?php selected($estado_filtro, 'aceptado'); ?>>Aceptado</option>
                    <option value="rechazado" <?php selected($estado_filtro, 'rechazado'); ?>>Rechazado</option>
                    <option value="pendiente" <?php selected($estado_filtro, 'pendiente'); ?>>Pendiente</option>
                    <option value="generado" <?php selected($estado_filtro, 'generado'); ?>>Sin Enviar</option>
                </select>
            </div>

            <div class="filtro-group">
                <label>Buscar:</label>
                <input type="text" name="buscar" value="<?php echo esc_attr($buscar); ?>" placeholder="Folio, RUT, Nombre...">
            </div>

            <button type="submit" class="button">Filtrar</button>
            <a href="<?php echo admin_url('admin.php?page=libredte-historial'); ?>" class="button">Limpiar</a>
        </form>
    </div>

    <!-- Acciones masivas -->
    <div class="acciones-masivas">
        <button type="button" id="btn-consultar-seleccionados" class="button">
            <span class="dashicons dashicons-update"></span> Consultar Estado SII (Seleccionados)
        </button>
        <button type="button" id="btn-enviar-pendientes" class="button button-primary">
            <span class="dashicons dashicons-upload"></span> Enviar Sin Enviar al SII
        </button>
    </div>

    <!-- Tabla de boletas -->
    <table class="wp-list-table widefat fixed striped boletas-table">
        <thead>
            <tr>
                <td class="check-column"><input type="checkbox" id="select-all"></td>
                <th>Folio</th>
                <th>Fecha</th>
                <th>Receptor</th>
                <th>Neto</th>
                <th>IVA</th>
                <th>Exento</th>
                <th>Total</th>
                <th>Estado SII</th>
                <th>Track ID</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($boletas)): ?>
            <tr>
                <td colspan="11" class="no-items">No se encontraron boletas para el periodo seleccionado.</td>
            </tr>
            <?php else: ?>
                <?php foreach ($boletas as $boleta): ?>
                <tr data-id="<?php echo $boleta->id; ?>" data-folio="<?php echo $boleta->folio; ?>">
                    <th class="check-column">
                        <input type="checkbox" class="boleta-check" value="<?php echo $boleta->id; ?>">
                    </th>
                    <td><strong><?php echo $boleta->folio; ?></strong></td>
                    <td><?php echo date('d/m/Y H:i', strtotime($boleta->fecha_emision)); ?></td>
                    <td>
                        <span title="<?php echo esc_attr($boleta->receptor_razon); ?>">
                            <?php echo esc_html($boleta->receptor_rut); ?>
                        </span>
                    </td>
                    <td class="numero">$<?php echo number_format($boleta->monto_neto, 0, ',', '.'); ?></td>
                    <td class="numero">$<?php echo number_format($boleta->monto_iva, 0, ',', '.'); ?></td>
                    <td class="numero">$<?php echo number_format($boleta->monto_exento, 0, ',', '.'); ?></td>
                    <td class="numero"><strong>$<?php echo number_format($boleta->monto_total, 0, ',', '.'); ?></strong></td>
                    <td>
                        <span class="estado-badge estado-<?php echo $boleta->estado_sii; ?>">
                            <?php echo ucfirst($boleta->estado_sii); ?>
                        </span>
                    </td>
                    <td>
                        <?php if ($boleta->track_id): ?>
                        <code><?php echo $boleta->track_id; ?></code>
                        <?php else: ?>
                        <span class="no-track">-</span>
                        <?php endif; ?>
                    </td>
                    <td class="acciones">
                        <button type="button" class="button button-small btn-ver-detalle"
                                data-id="<?php echo $boleta->id; ?>" title="Ver Detalle">
                            <span class="dashicons dashicons-visibility"></span>
                        </button>
                        <?php if ($boleta->estado_sii === 'generado'): ?>
                        <button type="button" class="button button-small btn-enviar-sii"
                                data-id="<?php echo $boleta->id; ?>" title="Enviar al SII">
                            <span class="dashicons dashicons-upload"></span>
                        </button>
                        <?php elseif ($boleta->track_id): ?>
                        <button type="button" class="button button-small btn-consultar-sii"
                                data-id="<?php echo $boleta->id; ?>" title="Consultar Estado SII">
                            <span class="dashicons dashicons-update"></span>
                        </button>
                        <?php endif; ?>
                        <a href="#" class="button button-small btn-descargar-xml"
                           data-id="<?php echo $boleta->id; ?>" title="Descargar XML">
                            <span class="dashicons dashicons-download"></span>
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <!-- Paginacion -->
    <?php if ($total_pages > 1): ?>
    <div class="tablenav bottom">
        <div class="tablenav-pages">
            <span class="displaying-num"><?php echo number_format($total); ?> elementos</span>
            <?php
            echo paginate_links(array(
                'base' => add_query_arg('paged', '%#%'),
                'format' => '',
                'prev_text' => '&laquo;',
                'next_text' => '&raquo;',
                'total' => $total_pages,
                'current' => $current_page
            ));
            ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Modal Detalle Boleta -->
<div id="modal-detalle" class="libredte-modal" style="display:none;">
    <div class="modal-content modal-large">
        <span class="modal-close">&times;</span>
        <div id="detalle-content">
            <div class="loading">Cargando...</div>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {

    // Select all
    $('#select-all').on('change', function() {
        $('.boleta-check').prop('checked', $(this).is(':checked'));
    });

    // Ver detalle boleta
    $(document).on('click', '.btn-ver-detalle', function() {
        var id = $(this).data('id');
        $('#modal-detalle').show();
        $('#detalle-content').html('<div class="loading">Cargando...</div>');

        $.ajax({
            url: ajaxurl,
            method: 'POST',
            data: {
                action: 'akibara_detalle_boleta',
                nonce: '<?php echo wp_create_nonce('akibara_nonce'); ?>',
                id: id
            },
            success: function(response) {
                if (response.success) {
                    var b = response.data;
                    var html = '<h2>Boleta #' + b.folio + '</h2>';

                    html += '<div class="detalle-grid">';
                    html += '<div class="detalle-seccion">';
                    html += '<h3>Informacion General</h3>';
                    html += '<table class="form-table">';
                    html += '<tr><th>Folio:</th><td>' + b.folio + '</td></tr>';
                    html += '<tr><th>Fecha Emision:</th><td>' + b.fecha_emision + '</td></tr>';
                    html += '<tr><th>Tipo DTE:</th><td>39 - Boleta Electronica</td></tr>';
                    html += '</table></div>';

                    html += '<div class="detalle-seccion">';
                    html += '<h3>Receptor</h3>';
                    html += '<table class="form-table">';
                    html += '<tr><th>RUT:</th><td>' + b.receptor_rut + '</td></tr>';
                    html += '<tr><th>Razon Social:</th><td>' + (b.receptor_razon || '-') + '</td></tr>';
                    html += '</table></div>';

                    html += '<div class="detalle-seccion">';
                    html += '<h3>Montos</h3>';
                    html += '<table class="form-table">';
                    html += '<tr><th>Neto:</th><td>$' + parseInt(b.monto_neto).toLocaleString('es-CL') + '</td></tr>';
                    html += '<tr><th>IVA:</th><td>$' + parseInt(b.monto_iva).toLocaleString('es-CL') + '</td></tr>';
                    html += '<tr><th>Exento:</th><td>$' + parseInt(b.monto_exento).toLocaleString('es-CL') + '</td></tr>';
                    html += '<tr><th>Total:</th><td><strong>$' + parseInt(b.monto_total).toLocaleString('es-CL') + '</strong></td></tr>';
                    html += '</table></div>';

                    html += '<div class="detalle-seccion">';
                    html += '<h3>Estado SII</h3>';
                    html += '<table class="form-table">';
                    html += '<tr><th>Estado:</th><td><span class="estado-badge estado-' + b.estado_sii + '">' + b.estado_sii.toUpperCase() + '</span></td></tr>';
                    html += '<tr><th>Track ID:</th><td>' + (b.track_id || '-') + '</td></tr>';
                    html += '<tr><th>Fecha Envio:</th><td>' + (b.fecha_envio || '-') + '</td></tr>';
                    html += '</table></div>';
                    html += '</div>';

                    // Respuesta SII
                    if (b.respuesta_sii) {
                        html += '<div class="detalle-seccion respuesta-sii">';
                        html += '<h3>Respuesta del SII</h3>';
                        html += '<pre class="respuesta-xml">' + escapeHtml(b.respuesta_sii) + '</pre>';
                        html += '</div>';
                    }

                    // Acciones
                    html += '<div class="detalle-acciones">';
                    if (b.estado_sii === 'generado') {
                        html += '<button type="button" class="button button-primary btn-enviar-sii" data-id="' + b.id + '">Enviar al SII</button> ';
                    } else if (b.track_id) {
                        html += '<button type="button" class="button button-primary btn-consultar-sii" data-id="' + b.id + '">Consultar Estado SII</button> ';
                    }
                    html += '<button type="button" class="button btn-descargar-xml" data-id="' + b.id + '">Descargar XML</button>';
                    html += '</div>';

                    $('#detalle-content').html(html);
                } else {
                    $('#detalle-content').html('<div class="notice notice-error"><p>' + response.data + '</p></div>');
                }
            }
        });
    });

    // Enviar al SII
    $(document).on('click', '.btn-enviar-sii', function() {
        var $btn = $(this);
        var id = $btn.data('id');

        $btn.prop('disabled', true).text('Enviando...');

        $.ajax({
            url: ajaxurl,
            method: 'POST',
            data: {
                action: 'akibara_enviar_boleta',
                nonce: '<?php echo wp_create_nonce('akibara_nonce'); ?>',
                id: id
            },
            success: function(response) {
                if (response.success) {
                    alert('Boleta enviada correctamente.\nTrack ID: ' + response.data.track_id);
                    location.reload();
                } else {
                    alert('Error: ' + response.data);
                }
            },
            complete: function() {
                $btn.prop('disabled', false).html('<span class="dashicons dashicons-upload"></span>');
            }
        });
    });

    // Consultar estado SII
    $(document).on('click', '.btn-consultar-sii', function() {
        var $btn = $(this);
        var id = $btn.data('id');

        $btn.prop('disabled', true);
        $btn.find('.dashicons').addClass('spin');

        $.ajax({
            url: ajaxurl,
            method: 'POST',
            data: {
                action: 'akibara_consultar_estado',
                nonce: '<?php echo wp_create_nonce('akibara_nonce'); ?>',
                id: id
            },
            success: function(response) {
                if (response.success) {
                    var msg = 'Estado: ' + response.data.estado;
                    if (response.data.glosa) {
                        msg += '\n' + response.data.glosa;
                    }
                    alert(msg);
                    location.reload();
                } else {
                    alert('Error: ' + response.data);
                }
            },
            complete: function() {
                $btn.prop('disabled', false);
                $btn.find('.dashicons').removeClass('spin');
            }
        });
    });

    // Consultar seleccionados
    $('#btn-consultar-seleccionados').on('click', function() {
        var ids = [];
        $('.boleta-check:checked').each(function() {
            ids.push($(this).val());
        });

        if (ids.length === 0) {
            alert('Selecciona al menos una boleta');
            return;
        }

        var $btn = $(this);
        $btn.prop('disabled', true).text('Consultando...');

        $.ajax({
            url: ajaxurl,
            method: 'POST',
            data: {
                action: 'akibara_consultar_masivo',
                nonce: '<?php echo wp_create_nonce('akibara_nonce'); ?>',
                ids: ids
            },
            success: function(response) {
                if (response.success) {
                    alert('Consulta completada:\n' +
                          'Aceptadas: ' + response.data.aceptadas + '\n' +
                          'Rechazadas: ' + response.data.rechazadas + '\n' +
                          'Pendientes: ' + response.data.pendientes);
                    location.reload();
                } else {
                    alert('Error: ' + response.data);
                }
            },
            complete: function() {
                $btn.prop('disabled', false).html('<span class="dashicons dashicons-update"></span> Consultar Estado SII (Seleccionados)');
            }
        });
    });

    // Enviar pendientes
    $('#btn-enviar-pendientes').on('click', function() {
        if (!confirm('Esto enviara todas las boletas sin enviar al SII. Continuar?')) {
            return;
        }

        var $btn = $(this);
        $btn.prop('disabled', true).text('Enviando...');

        $.ajax({
            url: ajaxurl,
            method: 'POST',
            data: {
                action: 'akibara_enviar_pendientes',
                nonce: '<?php echo wp_create_nonce('akibara_nonce'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    alert('Enviadas: ' + response.data.enviadas + ' boletas');
                    location.reload();
                } else {
                    alert('Error: ' + response.data);
                }
            },
            complete: function() {
                $btn.prop('disabled', false).html('<span class="dashicons dashicons-upload"></span> Enviar Sin Enviar al SII');
            }
        });
    });

    // Cerrar modal
    $('.modal-close, .libredte-modal').on('click', function(e) {
        if (e.target === this) {
            $('.libredte-modal').hide();
        }
    });

    function escapeHtml(text) {
        var div = document.createElement('div');
        div.appendChild(document.createTextNode(text));
        return div.innerHTML;
    }
});
</script>

<style>
.estadisticas-periodo {
    display: flex;
    gap: 15px;
    margin-bottom: 20px;
    flex-wrap: wrap;
}
.stat-box {
    background: #fff;
    padding: 15px 25px;
    border-radius: 4px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    text-align: center;
    min-width: 120px;
}
.stat-box .stat-value {
    display: block;
    font-size: 24px;
    font-weight: bold;
    color: #2271b1;
}
.stat-box .stat-label {
    color: #666;
    font-size: 12px;
}
.stat-box.aceptado .stat-value { color: #00a32a; }
.stat-box.rechazado .stat-value { color: #d63638; }
.stat-box.pendiente .stat-value { color: #dba617; }
.stat-box.sin-enviar .stat-value { color: #666; }
.stat-box.monto .stat-value { color: #2271b1; }

.filtros-form {
    display: flex;
    gap: 15px;
    align-items: flex-end;
    flex-wrap: wrap;
}
.filtro-group label {
    display: block;
    font-weight: 500;
    margin-bottom: 5px;
}

.acciones-masivas {
    margin: 15px 0;
}

.estado-badge {
    display: inline-block;
    padding: 3px 8px;
    border-radius: 3px;
    font-size: 11px;
    font-weight: 500;
    text-transform: uppercase;
}
.estado-aceptado { background: #d1fae5; color: #065f46; }
.estado-rechazado { background: #fee2e2; color: #991b1b; }
.estado-pendiente { background: #fef3c7; color: #92400e; }
.estado-generado { background: #e5e7eb; color: #374151; }

.numero { text-align: right; }
.acciones .button-small { padding: 0 5px; }
.acciones .dashicons { vertical-align: middle; }

.spin { animation: spin 1s linear infinite; }
@keyframes spin { 100% { transform: rotate(360deg); } }

.modal-large { max-width: 900px; }
.detalle-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 20px;
}
.detalle-seccion {
    background: #f9fafb;
    padding: 15px;
    border-radius: 4px;
}
.detalle-seccion h3 { margin-top: 0; }
.respuesta-xml {
    background: #1e293b;
    color: #e2e8f0;
    padding: 15px;
    border-radius: 4px;
    overflow-x: auto;
    font-size: 12px;
    max-height: 300px;
    overflow-y: auto;
}
.detalle-acciones {
    margin-top: 20px;
    padding-top: 20px;
    border-top: 1px solid #e5e7eb;
}
</style>
