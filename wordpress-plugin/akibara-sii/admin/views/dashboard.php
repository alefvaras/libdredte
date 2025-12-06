<?php
if (!defined('ABSPATH')) exit;

$ambiente = get_option('akibara_ambiente', 'certificacion');
$emisor_rut = get_option('akibara_emisor_rut', '');
$emisor_razon = get_option('akibara_emisor_razon_social', '');
$envio_automatico = get_option('akibara_envio_automatico', 0);

// Certificados
$cert_certificacion = get_option('akibara_cert_certificacion_file', '');
$cert_produccion = get_option('akibara_cert_produccion_file', '');
$cert_actual = $ambiente === 'produccion' ? $cert_produccion : $cert_certificacion;

// Estadísticas
global $wpdb;
$table_boletas = $wpdb->prefix . 'akibara_boletas';
$table_caf = $wpdb->prefix . 'akibara_caf';
$table_log = $wpdb->prefix . 'akibara_log';

$total_boletas = $wpdb->get_var("SELECT COUNT(*) FROM $table_boletas");
$boletas_hoy = $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM $table_boletas WHERE DATE(fecha_emision) = %s",
    date('Y-m-d')
));
$monto_hoy = $wpdb->get_var($wpdb->prepare(
    "SELECT SUM(monto_total) FROM $table_boletas WHERE DATE(fecha_emision) = %s",
    date('Y-m-d')
));

// Boletas sin track_id (problema potencial)
$boletas_sin_track = $wpdb->get_var(
    "SELECT COUNT(*) FROM $table_boletas WHERE enviado_sii = 1 AND (track_id IS NULL OR track_id = '' OR track_id = '0')"
);

// Últimos errores
$ultimos_errores = $wpdb->get_results(
    "SELECT * FROM $table_log WHERE tipo = 'error' ORDER BY created_at DESC LIMIT 5"
);

$caf_activo = $wpdb->get_row($wpdb->prepare(
    "SELECT * FROM $table_caf WHERE tipo_dte = 39 AND ambiente = %s AND activo = 1 ORDER BY folio_desde DESC LIMIT 1",
    $ambiente
));

$folios_disponibles = 0;
if ($caf_activo) {
    $folios_disponibles = $caf_activo->folio_hasta - $caf_activo->folio_actual + 1;
}

// Diagnóstico de configuración
$diagnostico = [];

// Verificar ambiente
$diagnostico['ambiente'] = [
    'nombre' => 'Ambiente',
    'valor' => strtoupper($ambiente),
    'estado' => !empty($ambiente) ? 'ok' : 'error',
    'mensaje' => !empty($ambiente) ? 'Configurado: ' . $ambiente : 'No configurado',
];

// Verificar certificado
$cert_path = AKIBARA_SII_UPLOADS . 'certs/' . $cert_actual;
$cert_existe = !empty($cert_actual) && file_exists($cert_path);
$diagnostico['certificado'] = [
    'nombre' => 'Certificado (' . strtoupper($ambiente) . ')',
    'valor' => $cert_actual ?: 'No configurado',
    'estado' => $cert_existe ? 'ok' : 'error',
    'mensaje' => $cert_existe ? 'Archivo existe' : 'Archivo no encontrado o no configurado',
];

// Verificar CAF
$diagnostico['caf'] = [
    'nombre' => 'CAF (' . strtoupper($ambiente) . ')',
    'valor' => $caf_activo ? 'Folios ' . $caf_activo->folio_desde . '-' . $caf_activo->folio_hasta : 'No configurado',
    'estado' => $caf_activo ? 'ok' : 'error',
    'mensaje' => $caf_activo ? $folios_disponibles . ' folios disponibles' : 'Debe subir un CAF',
];

// Verificar emisor
$diagnostico['emisor'] = [
    'nombre' => 'Emisor',
    'valor' => $emisor_rut ?: 'No configurado',
    'estado' => !empty($emisor_rut) ? 'ok' : 'error',
    'mensaje' => !empty($emisor_rut) ? $emisor_razon : 'Debe configurar datos del emisor',
];

// Verificar envío automático
$diagnostico['envio_auto'] = [
    'nombre' => 'Envío Automático',
    'valor' => $envio_automatico ? 'Activado' : 'Desactivado',
    'estado' => 'info',
    'mensaje' => $envio_automatico ? 'Las boletas se enviarán automáticamente' : 'Debe enviar manualmente desde historial',
];

// Verificar boletas sin track_id
if ($boletas_sin_track > 0) {
    $diagnostico['track_id'] = [
        'nombre' => 'Boletas sin Track ID',
        'valor' => $boletas_sin_track,
        'estado' => 'warning',
        'mensaje' => 'Hay boletas enviadas sin Track ID válido. Verifique los logs.',
    ];
}
?>

<div class="wrap akibara-dashboard">
    <h1>Akibara - Boletas Electronicas</h1>

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
                <a href="<?php echo admin_url('admin.php?page=akibara-configuracion'); ?>">Configurar ahora</a>
            </p>
        <?php endif; ?>
    </div>

    <!-- Panel de Diagnóstico -->
    <div class="diagnostico-card card">
        <h2>Diagnóstico de Configuración</h2>
        <table class="widefat diagnostico-table">
            <thead>
                <tr>
                    <th>Componente</th>
                    <th>Valor</th>
                    <th>Estado</th>
                    <th>Detalle</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($diagnostico as $item): ?>
                <tr class="diag-<?php echo $item['estado']; ?>">
                    <td><strong><?php echo esc_html($item['nombre']); ?></strong></td>
                    <td><code><?php echo esc_html($item['valor']); ?></code></td>
                    <td>
                        <?php if ($item['estado'] === 'ok'): ?>
                            <span class="estado-badge estado-aceptado">OK</span>
                        <?php elseif ($item['estado'] === 'error'): ?>
                            <span class="estado-badge estado-rechazado">ERROR</span>
                        <?php elseif ($item['estado'] === 'warning'): ?>
                            <span class="estado-badge estado-pendiente">ALERTA</span>
                        <?php else: ?>
                            <span class="estado-badge estado-enviado">INFO</span>
                        <?php endif; ?>
                    </td>
                    <td><?php echo esc_html($item['mensaje']); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php if (!empty($ultimos_errores)): ?>
        <h3 style="margin-top: 20px;">Últimos Errores</h3>
        <table class="widefat">
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Mensaje</th>
                    <th>Datos</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($ultimos_errores as $error): ?>
                <tr>
                    <td><?php echo date('d/m/Y H:i', strtotime($error->created_at)); ?></td>
                    <td><?php echo esc_html($error->mensaje); ?></td>
                    <td><code style="font-size: 10px;"><?php echo esc_html(substr($error->datos ?: '-', 0, 100)); ?></code></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
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
            <a href="<?php echo admin_url('admin.php?page=akibara-nueva-boleta'); ?>" class="button button-primary button-hero">
                <span class="dashicons dashicons-plus-alt"></span> Nueva Boleta
            </a>
            <a href="<?php echo admin_url('admin.php?page=akibara-historial'); ?>" class="button button-secondary button-hero">
                <span class="dashicons dashicons-list-view"></span> Ver Historial
            </a>
            <a href="<?php echo admin_url('admin.php?page=akibara-caf'); ?>" class="button button-secondary button-hero">
                <span class="dashicons dashicons-upload"></span> Subir CAF
            </a>
            <?php if ($ambiente === 'produccion'): ?>
            <a href="<?php echo admin_url('admin.php?page=akibara-rcof'); ?>" class="button button-secondary button-hero">
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
                <td><?php echo $caf_activo->folio_actual; ?></td>
            </tr>
            <tr>
                <th>Folios Disponibles</th>
                <td><?php echo $folios_disponibles; ?></td>
            </tr>
            <tr>
                <th>Fecha Carga</th>
                <td><?php echo date('d/m/Y', strtotime($caf_activo->created_at)); ?></td>
            </tr>
        </table>
    </div>
    <?php else: ?>
    <div class="notice notice-error">
        <p><strong>Sin CAF activo.</strong> Debes subir un archivo CAF para poder emitir boletas.
        <a href="<?php echo admin_url('admin.php?page=akibara-caf'); ?>">Subir CAF</a></p>
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
                    <td><?php echo esc_html($boleta->rut_receptor); ?></td>
                    <td>$<?php echo number_format($boleta->monto_total, 0, ',', '.'); ?></td>
                    <td>
                        <span class="estado-badge estado-<?php echo $boleta->estado; ?>">
                            <?php echo ucfirst($boleta->estado); ?>
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
