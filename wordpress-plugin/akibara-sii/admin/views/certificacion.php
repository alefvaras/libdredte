<?php
if (!defined('ABSPATH')) exit;

// Configuración de email para certificación
$email_certificacion = get_option('akibara_email_certificacion', 'ale.fvaras@gmail.com');

// Verificar si hay certificado y CAF
$cert_file = get_option('akibara_cert_certificacion_file', '');
$ambiente = get_option('akibara_ambiente', 'certificacion');
$emisor_rut = get_option('akibara_emisor_rut', '');
$emisor_razon = get_option('akibara_emisor_razon_social', '');

global $wpdb;
$table_caf = $wpdb->prefix . 'akibara_caf';
$caf_activo = $wpdb->get_row($wpdb->prepare(
    "SELECT * FROM $table_caf WHERE tipo_dte = 39 AND ambiente = %s AND activo = 1 ORDER BY folio_desde DESC LIMIT 1",
    $ambiente
));

$todo_configurado = !empty($cert_file) && !empty($caf_activo) && !empty($emisor_rut);

// Procesar SET de pruebas
$resultado_set = null;
$resultado_email = null;

if (isset($_POST['ejecutar_set_pruebas']) && wp_verify_nonce($_POST['_wpnonce'], 'akibara_certificacion')) {
    $email_destino = sanitize_email($_POST['email_certificacion'] ?? $email_certificacion);
    update_option('akibara_email_certificacion', $email_destino);

    // Ejecutar SET de pruebas
    $resultado_set = ejecutar_set_pruebas_certificacion($email_destino);
}

if (isset($_POST['enviar_boleta_prueba']) && wp_verify_nonce($_POST['_wpnonce'], 'akibara_certificacion')) {
    $email_destino = sanitize_email($_POST['email_certificacion'] ?? $email_certificacion);
    update_option('akibara_email_certificacion', $email_destino);

    // Emitir una boleta de prueba
    $resultado_set = emitir_boleta_prueba($email_destino);
}

/**
 * Generar PDF de boleta para certificación
 */
function generar_pdf_certificacion($boleta_id) {
    $boleta = Akibara_Database::get_boleta($boleta_id);
    if (!$boleta) {
        return false;
    }

    // Directorio de PDFs
    $pdf_dir = AKIBARA_SII_UPLOADS . 'pdf/';
    if (!file_exists($pdf_dir)) {
        wp_mkdir_p($pdf_dir);
    }

    $pdf_path = $pdf_dir . 'boleta_cert_' . $boleta->folio . '.pdf';

    // Si ya existe, retornarlo
    if (file_exists($pdf_path)) {
        return $pdf_path;
    }

    try {
        if (!class_exists('libredte\lib\Core\Application')) {
            return false;
        }

        $app = \libredte\lib\Core\Application::getInstance(environment: 'dev', debug: false);
        $billingPackage = $app->getPackageRegistry()->getBillingPackage();
        $documentComponent = $billingPackage->getDocumentComponent();
        $loaderWorker = $documentComponent->getLoaderWorker();
        $rendererWorker = $documentComponent->getRendererWorker();

        // Cargar documento desde XML
        $documentBag = $loaderWorker->loadXml($boleta->xml_documento);

        // Configurar renderer para PDF
        $documentBag->setOptions([
            'renderer' => [
                'format' => 'pdf',
                'template' => 'estandar',
            ],
        ]);

        // Generar PDF
        $pdfContent = $rendererWorker->render($documentBag);

        // Guardar archivo
        file_put_contents($pdf_path, $pdfContent);

        return $pdf_path;

    } catch (Exception $e) {
        error_log('Akibara SII Certificacion: Error generando PDF - ' . $e->getMessage());
        return false;
    }
}

/**
 * Ejecutar SET completo de pruebas de certificación
 */
function ejecutar_set_pruebas_certificacion($email) {
    $resultados = [
        'exito' => true,
        'casos' => [],
        'errores' => [],
        'track_ids' => [],
        'pdf_paths' => [],
        'email_enviado' => false,
    ];

    // Casos de prueba según SII
    $casos_prueba = [
        [
            'nombre' => 'CASO 1: Boleta con 3 items afectos',
            'receptor' => ['rut' => '66666666-6', 'nombre' => 'CLIENTE PRUEBA UNO'],
            'items' => [
                ['nombre' => 'PRODUCTO PRUEBA 1', 'cantidad' => 2, 'precio' => 1000],
                ['nombre' => 'PRODUCTO PRUEBA 2', 'cantidad' => 1, 'precio' => 2500],
                ['nombre' => 'SERVICIO PRUEBA 1', 'cantidad' => 1, 'precio' => 3000],
            ],
        ],
        [
            'nombre' => 'CASO 2: Boleta con item exento',
            'receptor' => ['rut' => '66666666-6', 'nombre' => 'CLIENTE PRUEBA DOS'],
            'items' => [
                ['nombre' => 'PRODUCTO AFECTO', 'cantidad' => 1, 'precio' => 5000],
                ['nombre' => 'LIBRO EXENTO', 'cantidad' => 1, 'precio' => 8000, 'exento' => true],
            ],
        ],
        [
            'nombre' => 'CASO 3: Boleta monto alto',
            'receptor' => ['rut' => '66666666-6', 'nombre' => 'CLIENTE PRUEBA TRES'],
            'items' => [
                ['nombre' => 'EQUIPO ELECTRONICO', 'cantidad' => 1, 'precio' => 150000],
            ],
        ],
    ];

    try {
        $boleta_emisor = new Akibara_Boleta();

        foreach ($casos_prueba as $i => $caso) {
            $caso_num = $i + 1;
            $resultado_caso = [
                'nombre' => $caso['nombre'],
                'estado' => 'pendiente',
                'folio' => null,
                'track_id' => null,
                'error' => null,
            ];

            try {
                // Crear datos de la boleta
                $boleta_data = [
                    'receptor' => $caso['receptor'],
                    'items' => $caso['items'],
                    'referencia' => 'CASO-' . $caso_num,
                ];

                // Emitir boleta usando Akibara_Boleta
                $resultado = $boleta_emisor->emitir($boleta_data);

                if (is_wp_error($resultado)) {
                    throw new Exception($resultado->get_error_message());
                }

                $resultado_caso['estado'] = 'emitida';
                $resultado_caso['folio'] = $resultado['folio'] ?? null;
                $resultado_caso['track_id'] = $resultado['track_id'] ?? null;
                $resultado_caso['boleta_id'] = $resultado['id'] ?? null;

                // Si no se envió automáticamente, enviar ahora
                if (empty($resultado['track_id']) && !empty($resultado_caso['boleta_id'])) {
                    $envio = $boleta_emisor->enviar_al_sii($resultado_caso['boleta_id']);
                    if (is_wp_error($envio)) {
                        $resultado_caso['error_envio'] = $envio->get_error_message();
                        $resultados['errores'][] = "Caso $caso_num envío: " . $envio->get_error_message();
                    } else {
                        $resultado_caso['track_id'] = $envio['track_id'];
                    }
                }

                if ($resultado_caso['track_id']) {
                    $resultados['track_ids'][] = $resultado_caso['track_id'];
                }

                // Generar PDF
                if ($resultado_caso['boleta_id']) {
                    $pdf_path = generar_pdf_certificacion($resultado_caso['boleta_id']);
                    if ($pdf_path && file_exists($pdf_path)) {
                        $resultado_caso['pdf_path'] = $pdf_path;
                        $resultados['pdf_paths'][] = $pdf_path;
                    }
                }

            } catch (Exception $e) {
                $resultado_caso['estado'] = 'error';
                $resultado_caso['error'] = $e->getMessage();
                $resultados['exito'] = false;
                $resultados['errores'][] = "Caso $caso_num: " . $e->getMessage();
            }

            $resultados['casos'][] = $resultado_caso;

            // Pequeña pausa entre casos
            usleep(500000); // 0.5 segundos
        }

        // Enviar email con resultados y PDFs adjuntos
        if (!empty($email)) {
            $email_enviado = enviar_email_certificacion($email, $resultados, $resultados['pdf_paths']);
            $resultados['email_enviado'] = $email_enviado;
        }

    } catch (Exception $e) {
        $resultados['exito'] = false;
        $resultados['errores'][] = 'Error general: ' . $e->getMessage();
    }

    return $resultados;
}

/**
 * Emitir una boleta de prueba simple
 */
function emitir_boleta_prueba($email) {
    $resultados = [
        'exito' => true,
        'casos' => [],
        'errores' => [],
        'track_ids' => [],
        'pdf_paths' => [],
        'email_enviado' => false,
    ];

    try {
        $boleta_emisor = new Akibara_Boleta();

        $boleta_data = [
            'receptor' => [
                'rut' => '66666666-6',
                'nombre' => 'CLIENTE PRUEBA',
            ],
            'items' => [
                ['nombre' => 'PRODUCTO DE PRUEBA', 'cantidad' => 1, 'precio' => 10000],
            ],
            'referencia' => 'TEST-' . date('His'),
        ];

        $resultado = $boleta_emisor->emitir($boleta_data);

        if (is_wp_error($resultado)) {
            throw new Exception($resultado->get_error_message());
        }

        $caso = [
            'nombre' => 'Boleta de Prueba',
            'estado' => 'emitida',
            'folio' => $resultado['folio'] ?? null,
            'track_id' => $resultado['track_id'] ?? null,
            'boleta_id' => $resultado['id'] ?? null,
        ];

        // Si no se envió automáticamente, enviar ahora
        if (empty($caso['track_id']) && !empty($caso['boleta_id'])) {
            $envio = $boleta_emisor->enviar_al_sii($caso['boleta_id']);
            if (is_wp_error($envio)) {
                $caso['error_envio'] = $envio->get_error_message();
                $resultados['errores'][] = "Error envío: " . $envio->get_error_message();
            } else {
                $caso['track_id'] = $envio['track_id'];
            }
        }

        // Generar PDF
        if (!empty($caso['boleta_id'])) {
            $pdf_path = generar_pdf_certificacion($caso['boleta_id']);
            if ($pdf_path && file_exists($pdf_path)) {
                $caso['pdf_path'] = $pdf_path;
                $resultados['pdf_paths'][] = $pdf_path;
            }
        }

        $resultados['casos'][] = $caso;

        if (!empty($caso['track_id'])) {
            $resultados['track_ids'][] = $caso['track_id'];
        }

        // Enviar email con PDF adjunto
        if (!empty($email)) {
            $resultados['email_enviado'] = enviar_email_certificacion($email, $resultados, $resultados['pdf_paths']);
        }

    } catch (Exception $e) {
        $resultados['exito'] = false;
        $resultados['errores'][] = $e->getMessage();
    }

    return $resultados;
}

/**
 * Enviar email con resultados de certificación y PDFs adjuntos
 */
function enviar_email_certificacion($email, $resultados, $pdf_paths = []) {
    $emisor = get_option('akibara_emisor_razon_social', 'Empresa');

    $subject = "[Certificacion SII] Resultados de pruebas - $emisor";

    $body = "<html><body>";
    $body .= "<h2>Resultados de Certificacion SII</h2>";
    $body .= "<p><strong>Empresa:</strong> $emisor</p>";
    $body .= "<p><strong>Fecha:</strong> " . date('d/m/Y H:i:s') . "</p>";
    $body .= "<hr>";

    // Resumen
    $total_casos = count($resultados['casos']);
    $casos_ok = count(array_filter($resultados['casos'], fn($c) => $c['estado'] === 'emitida'));

    $body .= "<h3>Resumen</h3>";
    $body .= "<p>Casos ejecutados: $total_casos</p>";
    $body .= "<p>Casos exitosos: $casos_ok</p>";
    $body .= "<p>Estado: " . ($resultados['exito'] ? '<span style="color:green">EXITOSO</span>' : '<span style="color:red">CON ERRORES</span>') . "</p>";

    // Detalle de casos
    $body .= "<h3>Detalle de Casos</h3>";
    $body .= "<table border='1' cellpadding='5' cellspacing='0' style='border-collapse: collapse;'>";
    $body .= "<tr><th>Caso</th><th>Estado</th><th>Folio</th><th>Track ID</th><th>PDF</th></tr>";

    foreach ($resultados['casos'] as $caso) {
        $color = $caso['estado'] === 'emitida' ? 'green' : 'red';
        $tiene_pdf = !empty($caso['pdf_path']) ? 'Adjunto' : '-';
        $body .= "<tr>";
        $body .= "<td>{$caso['nombre']}</td>";
        $body .= "<td style='color:$color'>{$caso['estado']}</td>";
        $body .= "<td>" . ($caso['folio'] ?? '-') . "</td>";
        $body .= "<td>" . ($caso['track_id'] ?? '-') . "</td>";
        $body .= "<td>$tiene_pdf</td>";
        $body .= "</tr>";
    }
    $body .= "</table>";

    // Indicar PDFs adjuntos
    if (!empty($pdf_paths)) {
        $body .= "<h3>Boletas PDF Adjuntas</h3>";
        $body .= "<p>Se adjuntan " . count($pdf_paths) . " boleta(s) en formato PDF.</p>";
    }

    // Track IDs para seguimiento
    if (!empty($resultados['track_ids'])) {
        $body .= "<h3>Track IDs para Seguimiento</h3>";
        $body .= "<p>Puede consultar el estado en el portal del SII (maullin.sii.cl para certificacion):</p>";
        $body .= "<ul>";
        foreach ($resultados['track_ids'] as $tid) {
            $body .= "<li>$tid</li>";
        }
        $body .= "</ul>";
    }

    // Errores
    if (!empty($resultados['errores'])) {
        $body .= "<h3 style='color:red'>Errores</h3>";
        $body .= "<ul>";
        foreach ($resultados['errores'] as $error) {
            $body .= "<li>$error</li>";
        }
        $body .= "</ul>";
    }

    $body .= "<hr>";
    $body .= "<p><small>Email generado automaticamente por Akibara SII para WordPress</small></p>";
    $body .= "</body></html>";

    $headers = ['Content-Type: text/html; charset=UTF-8'];

    // Preparar adjuntos (PDFs)
    $attachments = [];
    if (!empty($pdf_paths)) {
        foreach ($pdf_paths as $pdf) {
            if (file_exists($pdf)) {
                $attachments[] = $pdf;
            }
        }
    }

    return wp_mail($email, $subject, $body, $headers, $attachments);
}
?>

<div class="wrap akibara-certificacion">
    <h1>Certificacion SII - Set de Pruebas</h1>

    <?php if (!$todo_configurado): ?>
    <div class="notice notice-warning">
        <p><strong>Configuracion incompleta.</strong> Para ejecutar el proceso de certificacion necesitas:</p>
        <ul style="list-style: disc; margin-left: 20px;">
            <?php if (empty($cert_file)): ?>
            <li>Subir el <a href="<?php echo admin_url('admin.php?page=akibara-configuracion'); ?>">Certificado Digital</a></li>
            <?php endif; ?>
            <?php if (empty($caf_activo)): ?>
            <li>Subir un <a href="<?php echo admin_url('admin.php?page=akibara-caf'); ?>">CAF (Folios)</a></li>
            <?php endif; ?>
            <?php if (empty($emisor_rut)): ?>
            <li>Configurar los <a href="<?php echo admin_url('admin.php?page=akibara-configuracion'); ?>">Datos del Emisor</a></li>
            <?php endif; ?>
        </ul>
    </div>
    <?php endif; ?>

    <?php if ($resultado_set): ?>
    <div class="notice notice-<?php echo $resultado_set['exito'] ? 'success' : 'error'; ?> is-dismissible">
        <p>
            <strong><?php echo $resultado_set['exito'] ? 'Proceso completado!' : 'Proceso con errores'; ?></strong>
            <?php if ($resultado_set['email_enviado']): ?>
            - Email enviado a <?php echo esc_html($email_certificacion); ?>
            <?php endif; ?>
        </p>
    </div>

    <!-- Resultados detallados -->
    <div class="card">
        <h2>Resultados del SET de Pruebas</h2>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>Caso</th>
                    <th>Estado</th>
                    <th>Folio</th>
                    <th>Track ID</th>
                    <th>Error</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($resultado_set['casos'] as $caso): ?>
                <tr>
                    <td><?php echo esc_html($caso['nombre']); ?></td>
                    <td>
                        <span class="estado-<?php echo $caso['estado']; ?>">
                            <?php echo ucfirst($caso['estado']); ?>
                        </span>
                    </td>
                    <td><?php echo $caso['folio'] ? esc_html($caso['folio']) : '-'; ?></td>
                    <td><?php echo $caso['track_id'] ? esc_html($caso['track_id']) : '<span style="color:orange">Sin enviar</span>'; ?></td>
                    <td><?php
                        $errores = [];
                        if (!empty($caso['error'])) $errores[] = $caso['error'];
                        if (!empty($caso['error_envio'])) $errores[] = 'Envío: ' . $caso['error_envio'];
                        echo $errores ? esc_html(implode(' | ', $errores)) : '-';
                    ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <?php if (!empty($resultado_set['track_ids'])): ?>
        <p style="margin-top: 15px;">
            <strong>Consulta el estado en:</strong>
            <a href="https://maullin.sii.cl" target="_blank">maullin.sii.cl</a> (Mi SII > Boletas Electronicas)
        </p>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- Estado actual -->
    <div class="card">
        <h2>Estado de Configuracion</h2>
        <table class="form-table">
            <tr>
                <th>Ambiente</th>
                <td>
                    <span class="badge badge-<?php echo $ambiente === 'certificacion' ? 'warning' : 'success'; ?>">
                        <?php echo strtoupper($ambiente); ?>
                    </span>
                    <?php if ($ambiente !== 'certificacion'): ?>
                    <p class="description" style="color: #d63638;">
                        <strong>Advertencia:</strong> Estas en ambiente de produccion. Cambia a certificacion para hacer pruebas.
                    </p>
                    <?php endif; ?>
                </td>
            </tr>
            <tr>
                <th>Certificado Digital</th>
                <td>
                    <?php if ($cert_file): ?>
                    <span class="dashicons dashicons-yes-alt" style="color: green;"></span>
                    <?php echo esc_html($cert_file); ?>
                    <?php else: ?>
                    <span class="dashicons dashicons-warning" style="color: orange;"></span>
                    No cargado
                    <?php endif; ?>
                </td>
            </tr>
            <tr>
                <th>CAF (Folios)</th>
                <td>
                    <?php if ($caf_activo): ?>
                    <span class="dashicons dashicons-yes-alt" style="color: green;"></span>
                    Folios <?php echo $caf_activo->folio_desde; ?> - <?php echo $caf_activo->folio_hasta; ?>
                    <?php else: ?>
                    <span class="dashicons dashicons-warning" style="color: orange;"></span>
                    No hay CAF activo
                    <?php endif; ?>
                </td>
            </tr>
            <tr>
                <th>Empresa Emisora</th>
                <td>
                    <?php if ($emisor_rut): ?>
                    <span class="dashicons dashicons-yes-alt" style="color: green;"></span>
                    <?php echo esc_html($emisor_razon); ?> (<?php echo esc_html($emisor_rut); ?>)
                    <?php else: ?>
                    <span class="dashicons dashicons-warning" style="color: orange;"></span>
                    No configurado
                    <?php endif; ?>
                </td>
            </tr>
        </table>
    </div>

    <!-- Ejecutar pruebas -->
    <?php if ($todo_configurado): ?>
    <div class="card">
        <h2>Ejecutar Pruebas de Certificacion</h2>
        <p>El SET de pruebas emitira 3 boletas de prueba con diferentes escenarios segun los requisitos del SII.</p>

        <form method="post">
            <?php wp_nonce_field('akibara_certificacion'); ?>
            <table class="form-table">
                <tr>
                    <th><label for="email_certificacion">Email para resultados</label></th>
                    <td>
                        <input type="email" id="email_certificacion" name="email_certificacion"
                               value="<?php echo esc_attr($email_certificacion); ?>"
                               class="regular-text" required>
                        <p class="description">Se enviara un email con los resultados de las pruebas.</p>
                    </td>
                </tr>
            </table>

            <p class="submit">
                <button type="submit" name="enviar_boleta_prueba" class="button button-secondary">
                    <span class="dashicons dashicons-tickets-alt" style="vertical-align: middle;"></span>
                    Emitir 1 Boleta de Prueba
                </button>

                <button type="submit" name="ejecutar_set_pruebas" class="button button-primary button-hero" style="margin-left: 10px;">
                    <span class="dashicons dashicons-controls-play" style="vertical-align: middle;"></span>
                    Ejecutar SET Completo (3 Casos)
                </button>
            </p>
        </form>
    </div>
    <?php endif; ?>

    <!-- Instrucciones -->
    <div class="card">
        <h2>Proceso de Certificacion SII</h2>
        <ol>
            <li><strong>Configurar ambiente:</strong> Asegurate de estar en ambiente de <strong>certificacion</strong> (maullin.sii.cl)</li>
            <li><strong>Subir certificado:</strong> Sube tu certificado digital (.p12) para el ambiente de certificacion</li>
            <li><strong>Subir CAF:</strong> Solicita y sube un CAF de pruebas desde el portal del SII</li>
            <li><strong>Ejecutar SET:</strong> Ejecuta el SET de pruebas que emitira las boletas requeridas</li>
            <li><strong>Verificar en SII:</strong> Ingresa a maullin.sii.cl y verifica que las boletas fueron recibidas</li>
            <li><strong>Obtener resolucion:</strong> Una vez aprobado, el SII te entregara la resolucion de autorizacion</li>
        </ol>

        <p>
            <a href="https://maullin.sii.cl" target="_blank" class="button">
                <span class="dashicons dashicons-external" style="vertical-align: middle;"></span>
                Ir a maullin.sii.cl (Certificacion)
            </a>
        </p>
    </div>
</div>

<style>
.akibara-certificacion .card { background: #fff; border: 1px solid #ccd0d4; padding: 20px; margin-top: 20px; border-radius: 4px; }
.estado-emitida { color: #065f46; background: #d1fae5; padding: 3px 8px; border-radius: 4px; }
.estado-error { color: #991b1b; background: #fee2e2; padding: 3px 8px; border-radius: 4px; }
.estado-pendiente { color: #92400e; background: #fef3c7; padding: 3px 8px; border-radius: 4px; }
.badge { display: inline-block; padding: 5px 10px; border-radius: 4px; font-weight: 500; }
.badge-warning { background: #fef3c7; color: #92400e; }
.badge-success { background: #d1fae5; color: #065f46; }
</style>
