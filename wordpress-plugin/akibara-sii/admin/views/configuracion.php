<?php
if (!defined('ABSPATH')) exit;

// Procesar formulario de configuración
if (isset($_POST['akibara_save_config']) && wp_verify_nonce($_POST['_wpnonce'], 'akibara_config')) {
    // Datos emisor
    update_option('akibara_emisor_rut', sanitize_text_field($_POST['emisor_rut'] ?? ''));
    update_option('akibara_emisor_razon_social', sanitize_text_field($_POST['emisor_razon_social'] ?? ''));
    update_option('akibara_emisor_giro', sanitize_text_field($_POST['emisor_giro'] ?? ''));
    update_option('akibara_emisor_acteco', sanitize_text_field($_POST['emisor_acteco'] ?? ''));
    update_option('akibara_emisor_direccion', sanitize_text_field($_POST['emisor_direccion'] ?? ''));
    update_option('akibara_emisor_comuna', sanitize_text_field($_POST['emisor_comuna'] ?? ''));

    // Configuracion SII
    update_option('akibara_ambiente', sanitize_text_field($_POST['ambiente'] ?? 'certificacion'));
    update_option('akibara_resolucion_fecha', sanitize_text_field($_POST['resolucion_fecha'] ?? ''));
    update_option('akibara_resolucion_numero', sanitize_text_field($_POST['resolucion_numero'] ?? '0'));
    update_option('akibara_rut_envia', sanitize_text_field($_POST['rut_envia'] ?? ''));

    // Opciones
    update_option('akibara_envio_automatico', isset($_POST['envio_automatico']) ? 1 : 0);
    update_option('akibara_rcof_automatico', isset($_POST['rcof_automatico']) ? 1 : 0);

    // Notificaciones de folios
    update_option('akibara_folio_notifications', isset($_POST['folio_notifications']) ? 1 : 0);
    update_option('akibara_folio_alert_threshold', intval($_POST['folio_alert_threshold'] ?? 50));
    update_option('akibara_folio_notification_email', sanitize_email($_POST['folio_notification_email'] ?? ''));

    echo '<div class="notice notice-success is-dismissible"><p><strong>Configuracion guardada correctamente.</strong></p></div>';
}

// Procesar certificado (acepta archivo combinado o separados)
if (isset($_POST['akibara_upload_cert']) && wp_verify_nonce($_POST['_wpnonce'], 'akibara_cert_upload')) {
    $cert_ok = !empty($_FILES['certificado']['tmp_name']) && $_FILES['certificado']['error'] === UPLOAD_ERR_OK;
    $key_ok = !empty($_FILES['clave_privada']['tmp_name']) && $_FILES['clave_privada']['error'] === UPLOAD_ERR_OK;

    if ($cert_ok) {
        $cert_ambiente = sanitize_text_field($_POST['cert_ambiente'] ?? 'certificacion');
        $cert_dir = AKIBARA_SII_UPLOADS . 'certs/';

        if (!file_exists($cert_dir)) {
            wp_mkdir_p($cert_dir);
            file_put_contents($cert_dir . '.htaccess', 'deny from all');
        }

        // Leer contenido del certificado
        $cert_content = file_get_contents($_FILES['certificado']['tmp_name']);

        // Verificar si el certificado ya tiene la clave privada
        $has_cert = strpos($cert_content, '-----BEGIN CERTIFICATE-----') !== false;
        $has_key_in_cert = strpos($cert_content, '-----BEGIN PRIVATE KEY-----') !== false ||
                          strpos($cert_content, '-----BEGIN RSA PRIVATE KEY-----') !== false;

        // Si subio archivo de clave separado, leerlo
        $key_content = '';
        if ($key_ok) {
            $key_content = file_get_contents($_FILES['clave_privada']['tmp_name']);
        }

        $has_key_separate = strpos($key_content, '-----BEGIN PRIVATE KEY-----') !== false ||
                           strpos($key_content, '-----BEGIN RSA PRIVATE KEY-----') !== false;

        // Validaciones
        if (!$has_cert) {
            echo '<div class="notice notice-error is-dismissible"><p><strong>Error:</strong> El archivo no contiene un certificado valido (debe tener BEGIN CERTIFICATE)</p></div>';
        } elseif (!$has_key_in_cert && !$has_key_separate) {
            echo '<div class="notice notice-error is-dismissible"><p><strong>Error:</strong> Falta la clave privada. Suba el archivo de clave privada o use un PEM que contenga ambos.</p></div>';
        } else {
            // Combinar contenido (si la clave viene separada)
            $pem_content = trim($cert_content);
            if ($has_key_separate && !$has_key_in_cert) {
                $pem_content .= "\n" . trim($key_content);
            }

            $cert_filename = 'certificado_' . $cert_ambiente . '.pem';
            $cert_file = $cert_dir . $cert_filename;

            if (file_put_contents($cert_file, $pem_content)) {
                update_option("akibara_cert_{$cert_ambiente}_file", $cert_filename);
                update_option("akibara_cert_{$cert_ambiente}_password", '');
                update_option('akibara_cert_path', $cert_file);

                $msg = $has_key_in_cert ? ' (archivo combinado)' : ' (certificado + clave separados)';
                echo '<div class="notice notice-success is-dismissible"><p><strong>Certificado para ambiente ' . strtoupper($cert_ambiente) . ' subido correctamente' . $msg . '.</strong></p></div>';
            } else {
                echo '<div class="notice notice-error is-dismissible"><p><strong>Error al guardar el certificado.</strong> Verifica los permisos del directorio.</p></div>';
            }
        }
    } else {
        echo '<div class="notice notice-error is-dismissible"><p><strong>Error:</strong> Debe subir al menos el archivo de certificado.</p></div>';
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

// Notificaciones de folios
$folio_notifications = get_option('akibara_folio_notifications', 0);
$folio_alert_threshold = get_option('akibara_folio_alert_threshold', 50);
$folio_notification_email = get_option('akibara_folio_notification_email', get_option('admin_email'));

// Certificados por ambiente
$cert_certificacion_file = get_option('akibara_cert_certificacion_file', '');
$cert_produccion_file = get_option('akibara_cert_produccion_file', '');
?>

<div class="wrap akibara-configuracion">
    <h1>Configuracion Akibara SII</h1>

    <div class="nav-tab-wrapper">
        <a href="#tab-empresa" class="nav-tab nav-tab-active" data-tab="empresa">Empresa Emisora</a>
        <a href="#tab-sii" class="nav-tab" data-tab="sii">Configuracion SII</a>
        <a href="#tab-certificado" class="nav-tab" data-tab="certificado">Certificado Digital</a>
        <a href="#tab-opciones" class="nav-tab" data-tab="opciones">Opciones</a>
    </div>

    <!-- FORMULARIO PRINCIPAL (Empresa, SII, Opciones) -->
    <form method="post" id="form-config">
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
                                   class="regular-text" placeholder="12345678-9">
                        </td>
                    </tr>
                    <tr>
                        <th><label for="emisor_razon_social">Razon Social</label></th>
                        <td>
                            <input type="text" id="emisor_razon_social" name="emisor_razon_social"
                                   value="<?php echo esc_attr($emisor_razon); ?>"
                                   class="regular-text">
                        </td>
                    </tr>
                    <tr>
                        <th><label for="emisor_giro">Giro</label></th>
                        <td>
                            <input type="text" id="emisor_giro" name="emisor_giro"
                                   value="<?php echo esc_attr($emisor_giro); ?>"
                                   class="large-text">
                        </td>
                    </tr>
                    <tr>
                        <th><label for="emisor_acteco">Codigo Actividad Economica</label></th>
                        <td>
                            <input type="text" id="emisor_acteco" name="emisor_acteco"
                                   value="<?php echo esc_attr($emisor_acteco); ?>"
                                   class="regular-text" placeholder="Ej: 477390">
                            <p class="description">Codigo de 6 digitos del SII</p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="emisor_direccion">Direccion</label></th>
                        <td>
                            <input type="text" id="emisor_direccion" name="emisor_direccion"
                                   value="<?php echo esc_attr($emisor_direccion); ?>"
                                   class="large-text">
                        </td>
                    </tr>
                    <tr>
                        <th><label for="emisor_comuna">Comuna</label></th>
                        <td>
                            <input type="text" id="emisor_comuna" name="emisor_comuna"
                                   value="<?php echo esc_attr($emisor_comuna); ?>"
                                   class="regular-text">
                        </td>
                    </tr>
                </table>
                <p class="submit">
                    <button type="submit" name="akibara_save_config" class="button button-primary">
                        Guardar Configuracion
                    </button>
                </p>
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
                                <strong>Certificacion:</strong> maullin.sii.cl |
                                <strong>Produccion:</strong> palena.sii.cl
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="resolucion_fecha">Fecha Resolucion SII</label></th>
                        <td>
                            <input type="date" id="resolucion_fecha" name="resolucion_fecha"
                                   value="<?php echo esc_attr($resolucion_fecha); ?>"
                                   class="regular-text">
                            <p class="description">Fecha de autorizacion del SII</p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="resolucion_numero">Numero Resolucion</label></th>
                        <td>
                            <input type="number" id="resolucion_numero" name="resolucion_numero"
                                   value="<?php echo esc_attr($resolucion_numero); ?>"
                                   class="small-text" min="0">
                            <p class="description">Para certificacion usar 0</p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="rut_envia">RUT del que Envia</label></th>
                        <td>
                            <input type="text" id="rut_envia" name="rut_envia"
                                   value="<?php echo esc_attr($rut_envia); ?>"
                                   class="regular-text" placeholder="12345678-9">
                            <p class="description">RUT de la persona que firma (puede ser diferente al RUT empresa)</p>
                        </td>
                    </tr>
                </table>
                <p class="submit">
                    <button type="submit" name="akibara_save_config" class="button button-primary">
                        Guardar Configuracion
                    </button>
                </p>
            </div>
        </div>

        <!-- Tab Opciones -->
        <div id="tab-opciones" class="tab-content">
            <div class="card">
                <h2>Opciones de Envio</h2>
                <table class="form-table">
                    <tr>
                        <th>Modo de Envio</th>
                        <td>
                            <label>
                                <input type="checkbox" name="envio_automatico" value="1"
                                       <?php checked($envio_automatico, 1); ?>>
                                Envio automatico al SII
                            </label>
                            <p class="description">Las boletas se enviaran automaticamente al emitirse</p>
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
                            <p class="description">Solo disponible en produccion</p>
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
                                Notificar cuando queden pocos folios
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="folio_alert_threshold">Umbral de Alerta</label></th>
                        <td>
                            <input type="number" id="folio_alert_threshold" name="folio_alert_threshold"
                                   value="<?php echo esc_attr($folio_alert_threshold); ?>"
                                   class="small-text" min="1" max="500"> folios
                        </td>
                    </tr>
                    <tr>
                        <th><label for="folio_notification_email">Email</label></th>
                        <td>
                            <input type="email" id="folio_notification_email" name="folio_notification_email"
                                   value="<?php echo esc_attr($folio_notification_email); ?>"
                                   class="regular-text">
                        </td>
                    </tr>
                </table>
                <p class="submit">
                    <button type="submit" name="akibara_save_config" class="button button-primary">
                        Guardar Configuracion
                    </button>
                </p>
            </div>
        </div>
    </form>
    <!-- FIN FORMULARIO PRINCIPAL -->

    <!-- Tab Certificado (FORMULARIO SEPARADO) -->
    <div id="tab-certificado" class="tab-content">
        <div class="card">
            <h2>Certificado Digital</h2>

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

            <!-- FORMULARIO CERTIFICADO (separado del principal) -->
            <form method="post" enctype="multipart/form-data" id="form-certificado">
                <?php wp_nonce_field('akibara_cert_upload'); ?>
                <table class="form-table">
                    <tr>
                        <th><label for="cert_ambiente">Ambiente</label></th>
                        <td>
                            <select id="cert_ambiente" name="cert_ambiente" class="regular-text">
                                <option value="certificacion">Certificacion (Pruebas)</option>
                                <option value="produccion">Produccion</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="certificado">Certificado (.crt, .cer, .pem)</label></th>
                        <td>
                            <input type="file" id="certificado" name="certificado" accept=".crt,.cer,.pem" required>
                            <p class="description">Archivo con el certificado (BEGIN CERTIFICATE). Puede incluir la clave privada.</p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="clave_privada">Clave Privada (.key, .pem)</label></th>
                        <td>
                            <input type="file" id="clave_privada" name="clave_privada" accept=".key,.pem">
                            <p class="description">Opcional si el certificado ya incluye la clave privada</p>
                        </td>
                    </tr>
                </table>
                <p class="submit">
                    <button type="submit" name="akibara_upload_cert" class="button button-primary">
                        <span class="dashicons dashicons-upload" style="vertical-align: middle; margin-right: 5px;"></span>
                        Subir Certificado
                    </button>
                </p>
            </form>
        </div>
    </div>
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
        }
    });
});
</script>

<style>
.tab-content { display: none; }
.tab-content.active { display: block; }
.card { background: #fff; border: 1px solid #ccd0d4; padding: 20px; margin-top: 20px; }
.cert-status-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px; margin-bottom: 20px; }
.cert-status-box { padding: 15px; border-radius: 8px; display: flex; flex-direction: column; align-items: center; gap: 5px; }
.cert-status-box.ok { background: #d1fae5; color: #065f46; }
.cert-status-box.missing { background: #fef3c7; color: #92400e; }
.cert-status-box .dashicons { font-size: 24px; width: 24px; height: 24px; }
.cert-status-box span:last-child { font-size: 12px; opacity: 0.8; }
</style>
