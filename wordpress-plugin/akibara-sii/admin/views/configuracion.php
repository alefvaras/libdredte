<?php
if (!defined('ABSPATH')) exit;

// Procesar formulario
if (isset($_POST['akibara_save_config']) && wp_verify_nonce($_POST['_wpnonce'], 'akibara_config')) {
    // Datos emisor
    update_option('akibara_emisor_rut', sanitize_text_field($_POST['emisor_rut']));
    update_option('akibara_emisor_razon_social', sanitize_text_field($_POST['emisor_razon_social']));
    update_option('akibara_emisor_giro', sanitize_text_field($_POST['emisor_giro']));
    update_option('akibara_emisor_acteco', sanitize_text_field($_POST['emisor_acteco']));
    update_option('akibara_emisor_direccion', sanitize_text_field($_POST['emisor_direccion']));
    update_option('akibara_emisor_comuna', sanitize_text_field($_POST['emisor_comuna']));

    // Configuracion SII
    update_option('akibara_ambiente', sanitize_text_field($_POST['ambiente']));
    update_option('akibara_resolucion_fecha', sanitize_text_field($_POST['resolucion_fecha']));
    update_option('akibara_resolucion_numero', sanitize_text_field($_POST['resolucion_numero']));
    update_option('akibara_rut_envia', sanitize_text_field($_POST['rut_envia']));

    // Opciones
    update_option('akibara_envio_automatico', isset($_POST['envio_automatico']) ? 1 : 0);
    update_option('akibara_rcof_automatico', isset($_POST['rcof_automatico']) ? 1 : 0);

    // Notificaciones de folios
    update_option('akibara_folio_notifications', isset($_POST['folio_notifications']) ? 1 : 0);
    update_option('akibara_folio_alert_threshold', intval($_POST['folio_alert_threshold'] ?? 50));
    update_option('akibara_folio_notification_email', sanitize_email($_POST['folio_notification_email'] ?? ''));

    echo '<div class="notice notice-success"><p>Configuracion guardada correctamente.</p></div>';
}

// Procesar certificado
if (isset($_POST['akibara_upload_cert']) && wp_verify_nonce($_POST['_wpnonce'], 'akibara_config')) {
    if (!empty($_FILES['certificado']['tmp_name'])) {
        $cert_ambiente = sanitize_text_field($_POST['cert_ambiente'] ?? 'certificacion');
        // Usar AKIBARA_SII_UPLOADS para consistencia con class-sii-client.php
        $cert_dir = AKIBARA_SII_UPLOADS . 'certs/';

        if (!file_exists($cert_dir)) {
            wp_mkdir_p($cert_dir);
            // Proteger directorio
            file_put_contents($cert_dir . '.htaccess', 'deny from all');
        }

        // Nombre del archivo incluye ambiente para permitir certificados separados
        $cert_filename = 'certificado_' . $cert_ambiente . '.p12';
        $cert_file = $cert_dir . $cert_filename;

        if (move_uploaded_file($_FILES['certificado']['tmp_name'], $cert_file)) {
            // Guardar con nombres que espera class-sii-client.php
            update_option("akibara_cert_{$cert_ambiente}_file", $cert_filename);
            update_option("akibara_cert_{$cert_ambiente}_password", base64_encode(sanitize_text_field($_POST['cert_password'])));

            // Mantener compatibilidad con nombre antiguo
            update_option('akibara_cert_path', $cert_file);

            echo '<div class="notice notice-success"><p>Certificado para ambiente <strong>' . strtoupper($cert_ambiente) . '</strong> subido correctamente.</p></div>';
        } else {
            echo '<div class="notice notice-error"><p>Error al subir el certificado.</p></div>';
        }
    }
}

// Valores actuales
$emisor_rut = get_option('akibara_emisor_rut', '');
$emisor_razon = get_option('akibara_emisor_razon_social', '');
$emisor_giro = get_option('akibara_emisor_giro', '');
$emisor_acteco = get_option('akibara_emisor_acteco', '');
$emisor_direccion = get_option('akibara_emisor_direccion', '');
$emisor_comuna = get_option('akibara_emisor_comuna', '');
$ambiente = get_option('akibara_ambiente', 'certificacion');
$resolucion_fecha = get_option('akibara_resolucion_fecha', '');
$resolucion_numero = get_option('akibara_resolucion_numero', '0');
$rut_envia = get_option('akibara_rut_envia', '');
$envio_automatico = get_option('akibara_envio_automatico', 0);
$rcof_automatico = get_option('akibara_rcof_automatico', 0);
$cert_path = get_option('akibara_cert_path', '');

// Notificaciones de folios
$folio_notifications = get_option('akibara_folio_notifications', 0);
$folio_alert_threshold = get_option('akibara_folio_alert_threshold', 50);
$folio_notification_email = get_option('akibara_folio_notification_email', get_option('admin_email'));

// Certificados por ambiente
$cert_certificacion_file = get_option('akibara_cert_certificacion_file', '');
$cert_produccion_file = get_option('akibara_cert_produccion_file', '');
?>

<div class="wrap akibara-configuracion">
    <h1>Configuracion Akibara</h1>

    <div class="nav-tab-wrapper">
        <a href="#tab-empresa" class="nav-tab nav-tab-active" data-tab="empresa">Empresa Emisora</a>
        <a href="#tab-sii" class="nav-tab" data-tab="sii">Configuracion SII</a>
        <a href="#tab-certificado" class="nav-tab" data-tab="certificado">Certificado Digital</a>
        <a href="#tab-opciones" class="nav-tab" data-tab="opciones">Opciones</a>
    </div>

    <form method="post" enctype="multipart/form-data">
        <?php wp_nonce_field('akibara_config'); ?>

        <!-- Tab Empresa -->
        <div id="tab-empresa" class="tab-content active">
            <div class="card">
                <h2>Datos de la Empresa Emisora</h2>
                <table class="form-table">
                    <tr>
                        <th><label for="emisor_rut">RUT Empresa</label></th>
                        <td>
                            <input type="text" id="emisor_rut" name="emisor_rut"
                                   value="<?php echo esc_attr($emisor_rut); ?>"
                                   class="regular-text" placeholder="12345678-9" required>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="emisor_razon_social">Razon Social</label></th>
                        <td>
                            <input type="text" id="emisor_razon_social" name="emisor_razon_social"
                                   value="<?php echo esc_attr($emisor_razon); ?>"
                                   class="regular-text" required>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="emisor_giro">Giro</label></th>
                        <td>
                            <input type="text" id="emisor_giro" name="emisor_giro"
                                   value="<?php echo esc_attr($emisor_giro); ?>"
                                   class="large-text" required>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="emisor_acteco">Codigo Actividad Economica</label></th>
                        <td>
                            <input type="text" id="emisor_acteco" name="emisor_acteco"
                                   value="<?php echo esc_attr($emisor_acteco); ?>"
                                   class="regular-text" placeholder="Ej: 477390" required>
                            <p class="description">Codigo de 6 digitos del SII. <a href="https://www.sii.cl/ayudas/ayudas_por_servicios/1956-702-2348.html" target="_blank">Consultar codigos</a></p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="emisor_direccion">Direccion</label></th>
                        <td>
                            <input type="text" id="emisor_direccion" name="emisor_direccion"
                                   value="<?php echo esc_attr($emisor_direccion); ?>"
                                   class="large-text" required>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="emisor_comuna">Comuna</label></th>
                        <td>
                            <input type="text" id="emisor_comuna" name="emisor_comuna"
                                   value="<?php echo esc_attr($emisor_comuna); ?>"
                                   class="regular-text" required>
                        </td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- Tab SII -->
        <div id="tab-sii" class="tab-content">
            <div class="card">
                <h2>Configuracion SII</h2>
                <table class="form-table">
                    <tr>
                        <th><label for="ambiente">Ambiente</label></th>
                        <td>
                            <select id="ambiente" name="ambiente" class="regular-text">
                                <option value="certificacion" <?php selected($ambiente, 'certificacion'); ?>>Certificacion (Pruebas)</option>
                                <option value="produccion" <?php selected($ambiente, 'produccion'); ?>>Produccion</option>
                            </select>
                            <p class="description">
                                <strong>Certificacion:</strong> maullin.sii.cl (pruebas) |
                                <strong>Produccion:</strong> palena.sii.cl (real)
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="resolucion_fecha">Fecha Resolucion SII</label></th>
                        <td>
                            <input type="date" id="resolucion_fecha" name="resolucion_fecha"
                                   value="<?php echo esc_attr($resolucion_fecha); ?>"
                                   class="regular-text" required>
                            <p class="description">Fecha de la resolucion del SII que autoriza la emision</p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="resolucion_numero">Numero Resolucion</label></th>
                        <td>
                            <input type="number" id="resolucion_numero" name="resolucion_numero"
                                   value="<?php echo esc_attr($resolucion_numero); ?>"
                                   class="small-text">
                            <p class="description">Para ambiente de certificacion usar 0</p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="rut_envia">RUT del que Envia</label></th>
                        <td>
                            <input type="text" id="rut_envia" name="rut_envia"
                                   value="<?php echo esc_attr($rut_envia); ?>"
                                   class="regular-text" placeholder="12345678-9">
                            <p class="description">RUT de la persona que firma y envia los documentos (puede ser diferente al RUT empresa)</p>
                        </td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- Tab Certificado -->
        <div id="tab-certificado" class="tab-content">
            <div class="card">
                <h2>Certificado Digital</h2>

                <!-- Estado de certificados por ambiente -->
                <div class="cert-status-grid">
                    <div class="cert-status-box <?php echo $cert_certificacion_file ? 'ok' : 'missing'; ?>">
                        <span class="dashicons dashicons-<?php echo $cert_certificacion_file ? 'yes-alt' : 'warning'; ?>"></span>
                        <strong>Certificacion</strong>
                        <span><?php echo $cert_certificacion_file ? esc_html($cert_certificacion_file) : 'No cargado'; ?></span>
                    </div>
                    <div class="cert-status-box <?php echo $cert_produccion_file ? 'ok' : 'missing'; ?>">
                        <span class="dashicons dashicons-<?php echo $cert_produccion_file ? 'yes-alt' : 'warning'; ?>"></span>
                        <strong>Produccion</strong>
                        <span><?php echo $cert_produccion_file ? esc_html($cert_produccion_file) : 'No cargado'; ?></span>
                    </div>
                </div>

                <!-- Formulario separado para certificado -->
                <form method="post" enctype="multipart/form-data" class="cert-upload-form">
                    <?php wp_nonce_field('akibara_config'); ?>
                    <table class="form-table">
                        <tr>
                            <th><label for="cert_ambiente">Ambiente del Certificado</label></th>
                            <td>
                                <select id="cert_ambiente" name="cert_ambiente" class="regular-text">
                                    <option value="certificacion">Certificacion (Pruebas)</option>
                                    <option value="produccion">Produccion</option>
                                </select>
                                <p class="description">Selecciona el ambiente para el certificado que vas a subir</p>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="certificado">Archivo Certificado (.p12)</label></th>
                            <td>
                                <input type="file" id="certificado" name="certificado" accept=".p12,.pfx" required>
                                <p class="description">Sube tu certificado digital en formato .p12 o .pfx</p>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="cert_password">Contrasena del Certificado</label></th>
                            <td>
                                <input type="password" id="cert_password" name="cert_password" class="regular-text" required>
                            </td>
                        </tr>
                    </table>
                    <p class="submit">
                        <button type="submit" name="akibara_upload_cert" class="button button-primary">
                            <span class="dashicons dashicons-upload" style="vertical-align: middle;"></span>
                            Subir Certificado
                        </button>
                    </p>
                </form>
            </div>
        </div>

        <!-- Tab Opciones -->
        <div id="tab-opciones" class="tab-content">
            <div class="card">
                <h2>Opciones de Envio</h2>
                <table class="form-table">
                    <tr>
                        <th>Modo de Envio de Boletas</th>
                        <td>
                            <label>
                                <input type="checkbox" name="envio_automatico" value="1"
                                       <?php checked($envio_automatico, 1); ?>>
                                Envio automatico al SII
                            </label>
                            <p class="description">
                                Si esta activado, las boletas se enviaran automaticamente al SII al emitirse.<br>
                                Si esta desactivado, deberas enviarlas manualmente desde el historial.
                            </p>
                        </td>
                    </tr>
                    <tr id="rcof-option" style="<?php echo $ambiente === 'certificacion' ? 'display:none;' : ''; ?>">
                        <th>RCOF Automatico</th>
                        <td>
                            <label>
                                <input type="checkbox" name="rcof_automatico" value="1"
                                       <?php checked($rcof_automatico, 1); ?>>
                                Enviar RCOF automaticamente
                            </label>
                            <p class="description">
                                Si esta activado, el RCOF se enviara automaticamente cada dia a las 23:50.<br>
                                <strong>Nota:</strong> RCOF solo disponible en ambiente de produccion.
                            </p>
                        </td>
                    </tr>
                </table>
            </div>

            <div class="card">
                <h2>Notificaciones de Folios</h2>
                <table class="form-table">
                    <tr>
                        <th>Alertas por Email</th>
                        <td>
                            <label>
                                <input type="checkbox" name="folio_notifications" value="1"
                                       <?php checked($folio_notifications, 1); ?>>
                                Enviar email cuando queden pocos folios
                            </label>
                            <p class="description">
                                Recibe una alerta por email cuando los folios disponibles esten por agotarse.
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="folio_alert_threshold">Umbral de Alerta</label></th>
                        <td>
                            <input type="number" id="folio_alert_threshold" name="folio_alert_threshold"
                                   value="<?php echo esc_attr($folio_alert_threshold); ?>"
                                   class="small-text" min="1" max="500">
                            <span>folios</span>
                            <p class="description">
                                Se enviara una alerta cuando queden menos de esta cantidad de folios.<br>
                                <strong>Recomendado:</strong> 50 folios
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="folio_notification_email">Email de Notificacion</label></th>
                        <td>
                            <input type="email" id="folio_notification_email" name="folio_notification_email"
                                   value="<?php echo esc_attr($folio_notification_email); ?>"
                                   class="regular-text" placeholder="<?php echo esc_attr(get_option('admin_email')); ?>">
                            <p class="description">
                                Email donde se enviaran las alertas. Si esta vacio, se usara el email del administrador.
                            </p>
                        </td>
                    </tr>
                </table>
            </div>
        </div>

        <p class="submit">
            <button type="submit" name="akibara_save_config" class="button button-primary button-hero">
                Guardar Configuracion
            </button>
        </p>
    </form>
</div>

<script>
jQuery(document).ready(function($) {
    // Tabs
    $('.nav-tab').on('click', function(e) {
        e.preventDefault();
        var tab = $(this).data('tab');

        $('.nav-tab').removeClass('nav-tab-active');
        $(this).addClass('nav-tab-active');

        $('.tab-content').removeClass('active');
        $('#tab-' + tab).addClass('active');
    });

    // Show/hide RCOF option based on ambiente
    $('#ambiente').on('change', function() {
        if ($(this).val() === 'produccion') {
            $('#rcof-option').show();
        } else {
            $('#rcof-option').hide();
            $('input[name="rcof_automatico"]').prop('checked', false);
        }
    });
});
</script>

<style>
.tab-content { display: none; }
.tab-content.active { display: block; }
.notice.inline { margin: 15px 0; }

/* Certificados grid */
.cert-status-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 15px;
    margin-bottom: 20px;
}
.cert-status-box {
    padding: 15px;
    border-radius: 8px;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 5px;
}
.cert-status-box.ok {
    background: #d1fae5;
    color: #065f46;
}
.cert-status-box.missing {
    background: #fef3c7;
    color: #92400e;
}
.cert-status-box .dashicons {
    font-size: 24px;
    width: 24px;
    height: 24px;
}
.cert-status-box span:last-child {
    font-size: 12px;
    opacity: 0.8;
}
</style>
