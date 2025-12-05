<?php
if (!defined('ABSPATH')) exit;

$ambiente = get_option('libredte_ambiente', 'certificacion');
$emisor_rut = get_option('libredte_emisor_rut', '');
$emisor_razon = get_option('libredte_emisor_razon_social', '');

// Estadísticas
global $wpdb;
$table_boletas = $wpdb->prefix . 'libredte_boletas';
$table_caf = $wpdb->prefix . 'libredte_caf';

$total_boletas = $wpdb->get_var("SELECT COUNT(*) FROM $table_boletas");
$boletas_hoy = $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM $table_boletas WHERE DATE(fecha_emision) = %s",
    date('Y-m-d')
));
$monto_hoy = $wpdb->get_var($wpdb->prepare(
    "SELECT SUM(monto_total) FROM $table_boletas WHERE DATE(fecha_emision) = %s",
    date('Y-m-d')
));

$caf_activo = $wpdb->get_row(
    "SELECT * FROM $table_caf WHERE tipo_dte = 39 AND estado = 'activo' ORDER BY folio_desde DESC LIMIT 1"
);

$folios_disponibles = 0;
if ($caf_activo) {
    $folio_actual = get_option('libredte_folio_actual_39', $caf_activo->folio_desde);
    $folios_disponibles = $caf_activo->folio_hasta - $folio_actual + 1;
}
?>

<div class="wrap libredte-dashboard">
    <h1>LibreDTE - Boletas Electronicas</h1>

    <!-- Ambiente indicator -->
    <div class="ambiente-badge <?php echo $ambiente; ?>">
        <span class="dashicons dashicons-<?php echo $ambiente === 'produccion' ? 'yes-alt' : 'info'; ?>"></span>
        Ambiente: <strong><?php echo strtoupper($ambiente); ?></strong>
    </div>

    <!-- Empresa Info -->
    <div class="empresa-info card">
        <h2>Empresa Emisora</h2>
        <?php if ($emisor_rut): ?>
            <p><strong>RUT:</strong> <?php echo esc_html($emisor_rut); ?></p>
            <p><strong>Razon Social:</strong> <?php echo esc_html($emisor_razon); ?></p>
        <?php else: ?>
            <p class="notice notice-warning">
                No hay empresa configurada.
                <a href="<?php echo admin_url('admin.php?page=libredte-configuracion'); ?>">Configurar ahora</a>
            </p>
        <?php endif; ?>
    </div>

    <!-- Estadisticas -->
    <div class="estadisticas-grid">
        <div class="stat-card">
            <div class="stat-icon"><span class="dashicons dashicons-media-document"></span></div>
            <div class="stat-content">
                <span class="stat-number"><?php echo number_format($boletas_hoy); ?></span>
                <span class="stat-label">Boletas Hoy</span>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon"><span class="dashicons dashicons-money-alt"></span></div>
            <div class="stat-content">
                <span class="stat-number">$<?php echo number_format($monto_hoy ?: 0, 0, ',', '.'); ?></span>
                <span class="stat-label">Monto Hoy</span>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon"><span class="dashicons dashicons-database"></span></div>
            <div class="stat-content">
                <span class="stat-number"><?php echo number_format($total_boletas); ?></span>
                <span class="stat-label">Total Boletas</span>
            </div>
        </div>

        <div class="stat-card <?php echo $folios_disponibles < 50 ? 'warning' : ''; ?>">
            <div class="stat-icon"><span class="dashicons dashicons-tickets-alt"></span></div>
            <div class="stat-content">
                <span class="stat-number"><?php echo number_format($folios_disponibles); ?></span>
                <span class="stat-label">Folios Disponibles</span>
            </div>
        </div>
    </div>

    <!-- Acciones Rapidas -->
    <div class="acciones-rapidas card">
        <h2>Acciones Rapidas</h2>
        <div class="button-grid">
            <a href="<?php echo admin_url('admin.php?page=libredte-nueva-boleta'); ?>" class="button button-primary button-hero">
                <span class="dashicons dashicons-plus-alt"></span> Nueva Boleta
            </a>
            <a href="<?php echo admin_url('admin.php?page=libredte-historial'); ?>" class="button button-secondary button-hero">
                <span class="dashicons dashicons-list-view"></span> Ver Historial
            </a>
            <a href="<?php echo admin_url('admin.php?page=libredte-caf'); ?>" class="button button-secondary button-hero">
                <span class="dashicons dashicons-upload"></span> Subir CAF
            </a>
            <?php if ($ambiente === 'produccion'): ?>
            <a href="<?php echo admin_url('admin.php?page=libredte-rcof'); ?>" class="button button-secondary button-hero">
                <span class="dashicons dashicons-chart-bar"></span> RCOF
            </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- CAF Activo -->
    <?php if ($caf_activo): ?>
    <div class="caf-info card">
        <h2>CAF Activo (Boleta Electronica - Tipo 39)</h2>
        <table class="widefat">
            <tr>
                <th>Rango</th>
                <td><?php echo $caf_activo->folio_desde; ?> - <?php echo $caf_activo->folio_hasta; ?></td>
            </tr>
            <tr>
                <th>Folio Actual</th>
                <td><?php echo get_option('libredte_folio_actual_39', $caf_activo->folio_desde); ?></td>
            </tr>
            <tr>
                <th>Folios Disponibles</th>
                <td><?php echo $folios_disponibles; ?></td>
            </tr>
            <tr>
                <th>Fecha Autorizacion</th>
                <td><?php echo $caf_activo->fecha_autorizacion; ?></td>
            </tr>
        </table>
    </div>
    <?php else: ?>
    <div class="notice notice-error">
        <p><strong>Sin CAF activo.</strong> Debes subir un archivo CAF para poder emitir boletas.
        <a href="<?php echo admin_url('admin.php?page=libredte-caf'); ?>">Subir CAF</a></p>
    </div>
    <?php endif; ?>

    <!-- Ultimas Boletas -->
    <div class="ultimas-boletas card">
        <h2>Ultimas Boletas Emitidas</h2>
        <?php
        $ultimas = $wpdb->get_results(
            "SELECT * FROM $table_boletas ORDER BY id DESC LIMIT 5"
        );
        if ($ultimas):
        ?>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>Folio</th>
                    <th>Fecha</th>
                    <th>Receptor</th>
                    <th>Total</th>
                    <th>Estado SII</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($ultimas as $boleta): ?>
                <tr>
                    <td><strong><?php echo $boleta->folio; ?></strong></td>
                    <td><?php echo date('d/m/Y H:i', strtotime($boleta->fecha_emision)); ?></td>
                    <td><?php echo esc_html($boleta->receptor_rut); ?></td>
                    <td>$<?php echo number_format($boleta->monto_total, 0, ',', '.'); ?></td>
                    <td>
                        <span class="estado-badge estado-<?php echo $boleta->estado_sii; ?>">
                            <?php echo ucfirst($boleta->estado_sii); ?>
                        </span>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
        <p>No hay boletas emitidas aun.</p>
        <?php endif; ?>
    </div>
</div>
