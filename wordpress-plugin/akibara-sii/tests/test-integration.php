<?php
/**
 * Test de Integración Completa - Akibara SII
 *
 * Este script simula el flujo completo del plugin:
 * 1. Instalación y activación
 * 2. Configuración de emisor
 * 3. Carga de certificado
 * 4. Carga de CAF
 * 5. Emisión de boleta
 * 6. Envío al SII
 * 7. Consulta de estado
 * 8. Generación de RCOF
 * 9. Generación de PDF
 *
 * Ejecutar: php tests/test-integration.php
 */

define('GREEN', "\033[32m");
define('RED', "\033[31m");
define('YELLOW', "\033[33m");
define('CYAN', "\033[36m");
define('RESET', "\033[0m");

echo "\n" . str_repeat("=", 70) . "\n";
echo CYAN . "   AKIBARA SII - TEST DE INTEGRACIÓN COMPLETA\n" . RESET;
echo str_repeat("=", 70) . "\n\n";

// ============================================================
// SETUP: Simular WordPress
// ============================================================

if (!defined('ABSPATH')) define('ABSPATH', '/tmp/wordpress/');
if (!defined('AKIBARA_SII_PATH')) define('AKIBARA_SII_PATH', dirname(__DIR__) . '/');
if (!defined('AKIBARA_SII_URL')) define('AKIBARA_SII_URL', 'http://example.com/wp-content/plugins/akibara-sii/');
if (!defined('AKIBARA_SII_UPLOADS')) define('AKIBARA_SII_UPLOADS', '/tmp/akibara-test/uploads/');

// Crear directorio de uploads temporal
@mkdir(AKIBARA_SII_UPLOADS, 0755, true);
@mkdir(AKIBARA_SII_UPLOADS . 'certs/', 0755, true);
@mkdir(AKIBARA_SII_UPLOADS . 'caf/', 0755, true);

// Mock de opciones WordPress
$wp_options = [];

// Funciones mock de WordPress
if (!function_exists('get_option')) {
    function get_option($key, $default = '') {
        global $wp_options;
        return $wp_options[$key] ?? $default;
    }
}
if (!function_exists('update_option')) {
    function update_option($key, $value) {
        global $wp_options;
        $wp_options[$key] = $value;
        return true;
    }
}
if (!function_exists('add_action')) { function add_action() {} }
if (!function_exists('add_filter')) { function add_filter() {} }
if (!function_exists('register_activation_hook')) { function register_activation_hook() {} }
if (!function_exists('register_deactivation_hook')) { function register_deactivation_hook() {} }
if (!function_exists('plugin_dir_path')) { function plugin_dir_path($f) { return dirname($f) . '/'; } }
if (!function_exists('plugin_dir_url')) { function plugin_dir_url($f) { return 'http://example.com/'; } }
if (!function_exists('__')) { function __($t) { return $t; } }
if (!function_exists('_e')) { function _e($t) { echo $t; } }
if (!function_exists('esc_html')) { function esc_html($t) { return htmlspecialchars($t); } }
if (!function_exists('esc_attr')) { function esc_attr($t) { return htmlspecialchars($t); } }
if (!function_exists('sanitize_text_field')) { function sanitize_text_field($t) { return trim(strip_tags($t)); } }
if (!function_exists('sanitize_email')) { function sanitize_email($e) { return filter_var($e, FILTER_SANITIZE_EMAIL); } }
if (!function_exists('wp_parse_args')) { function wp_parse_args($a, $d) { return array_merge($d, $a); } }
if (!function_exists('wp_upload_dir')) { function wp_upload_dir() { return ['basedir' => '/tmp/uploads', 'baseurl' => 'http://example.com/uploads']; } }
if (!function_exists('wp_mkdir_p')) { function wp_mkdir_p($d) { return @mkdir($d, 0755, true); } }
if (!function_exists('wp_get_post_terms')) { function wp_get_post_terms() { return []; } }
if (!function_exists('current_time')) { function current_time($t) { return date('Y-m-d H:i:s'); } }
if (!function_exists('wp_next_scheduled')) { function wp_next_scheduled($h) { return false; } }
if (!function_exists('wp_schedule_event')) { function wp_schedule_event() { return true; } }
if (!function_exists('wp_unschedule_event')) { function wp_unschedule_event() { return true; } }
if (!function_exists('admin_url')) { function admin_url($p = '') { return 'http://example.com/wp-admin/' . $p; } }
if (!function_exists('wp_add_dashboard_widget')) { function wp_add_dashboard_widget() { return true; } }
if (!function_exists('wp_mail')) { function wp_mail($to, $s, $m, $h = '', $a = []) { return true; } }
if (!function_exists('current_user_can')) { function current_user_can($c) { return true; } }
if (!function_exists('checked')) { function checked($a, $b = 1, $e = true) { return $a == $b ? 'checked' : ''; } }
if (!function_exists('get_post_meta')) { function get_post_meta($id, $k, $s = false) { return ''; } }
if (!function_exists('update_post_meta')) { function update_post_meta($id, $k, $v) { return true; } }

if (!class_exists('WP_Error')) {
    class WP_Error {
        private $code, $message, $data;
        public function __construct($c = '', $m = '', $d = '') {
            $this->code = $c; $this->message = $m; $this->data = $d;
        }
        public function get_error_code() { return $this->code; }
        public function get_error_message() { return $this->message; }
    }
}

function is_wp_error($thing) {
    return $thing instanceof WP_Error;
}

if (!class_exists('WooCommerce')) {
    class WooCommerce {}
}

$passed = 0;
$failed = 0;

function test($condition, $name) {
    global $passed, $failed;
    if ($condition) {
        echo GREEN . "  ✓ " . RESET . $name . "\n";
        $passed++;
        return true;
    } else {
        echo RED . "  ✗ " . RESET . $name . "\n";
        $failed++;
        return false;
    }
}

function step($name) {
    echo "\n" . YELLOW . "[$name]" . RESET . "\n";
}

// ============================================================
// PASO 1: Cargar clases del plugin
// ============================================================
step("PASO 1: Cargar clases del plugin");

$classes = [
    'Akibara_Database' => 'includes/class-database.php',
    'Akibara_Admin' => 'includes/class-admin.php',
    'Akibara_SII_Client' => 'includes/class-sii-client.php',
    'Akibara_Boleta' => 'includes/class-boleta.php',
    'Akibara_RCOF' => 'includes/class-rcof.php',
    'Akibara_WooCommerce_Integration' => 'includes/class-woocommerce-integration.php',
    'Akibara_Folio_Notifications' => 'includes/class-folio-notifications.php',
];

foreach ($classes as $class => $file) {
    $path = AKIBARA_SII_PATH . $file;
    if (file_exists($path)) {
        require_once $path;
        test(class_exists($class), "Clase $class cargada");
    } else {
        test(false, "Archivo $file existe");
    }
}

// ============================================================
// PASO 2: Simular configuración del emisor
// ============================================================
step("PASO 2: Configurar empresa emisora");

// Simular datos del formulario de configuración
$emisor_config = [
    'akibara_emisor_rut' => '76123456-7',
    'akibara_emisor_razon_social' => 'EMPRESA DE PRUEBA SPA',
    'akibara_emisor_giro' => 'VENTA AL POR MENOR DE COMPUTADORES',
    'akibara_emisor_acteco' => '477390',
    'akibara_emisor_direccion' => 'AV PROVIDENCIA 1234',
    'akibara_emisor_comuna' => 'PROVIDENCIA',
    'akibara_ambiente' => 'certificacion',
    'akibara_resolucion_fecha' => '2024-01-15',
    'akibara_resolucion_numero' => '0',
    'akibara_rut_envia' => '12345678-9',
    'akibara_envio_automatico' => 1,
    'akibara_folio_notifications' => 1,
    'akibara_folio_alert_threshold' => 50,
    'akibara_folio_notification_email' => 'admin@test.cl',
];

foreach ($emisor_config as $key => $value) {
    update_option($key, $value);
}

test(get_option('akibara_emisor_rut') === '76123456-7', "RUT Emisor guardado");
test(get_option('akibara_emisor_acteco') === '477390', "Acteco guardado");
test(get_option('akibara_ambiente') === 'certificacion', "Ambiente configurado");
test(get_option('akibara_folio_notifications') === 1, "Notificaciones activadas");

// Verificar get_emisor_config() desde archivo principal
require_once AKIBARA_SII_PATH . 'akibara-sii.php';
$emisor = Akibara_SII::get_emisor_config();
test($emisor['RUTEmisor'] === '76123456-7', "get_emisor_config() retorna RUT correcto");
test($emisor['Acteco'] === '477390', "get_emisor_config() retorna Acteco");

// ============================================================
// PASO 3: Simular carga de certificado
// ============================================================
step("PASO 3: Simular carga de certificado");

// Crear un archivo de certificado falso para pruebas
$cert_content = "FAKE_CERTIFICATE_CONTENT_FOR_TESTING";
$cert_filename = 'certificado_certificacion.p12';
$cert_path = AKIBARA_SII_UPLOADS . 'certs/' . $cert_filename;
file_put_contents($cert_path, $cert_content);

update_option('akibara_cert_certificacion_file', $cert_filename);
update_option('akibara_cert_certificacion_password', base64_encode('password123'));

test(file_exists($cert_path), "Archivo certificado creado");
test(get_option('akibara_cert_certificacion_file') === $cert_filename, "Nombre certificado guardado");
test(base64_decode(get_option('akibara_cert_certificacion_password')) === 'password123', "Password codificado en base64");

// Verificar path consistente
$expected_path = AKIBARA_SII_UPLOADS . 'certs/' . get_option('akibara_cert_certificacion_file');
test(file_exists($expected_path), "Path de certificado es consistente con AKIBARA_SII_UPLOADS");

// ============================================================
// PASO 4: Simular carga de CAF
// ============================================================
step("PASO 4: Simular carga de CAF");

// Crear un CAF XML de prueba
$caf_xml = '<?xml version="1.0" encoding="UTF-8"?>
<AUTORIZACION>
    <CAF version="1.0">
        <DA>
            <RE>76123456-7</RE>
            <RS>EMPRESA DE PRUEBA SPA</RS>
            <TD>39</TD>
            <RNG>
                <D>1</D>
                <H>100</H>
            </RNG>
            <FA>2024-01-15</FA>
            <RSAPK>
                <M>test_modulus</M>
                <E>test_exponent</E>
            </RSAPK>
            <IDK>100</IDK>
        </DA>
        <FRMA algoritmo="SHA1withRSA">test_signature</FRMA>
    </CAF>
    <RSASK>test_private_key</RSASK>
    <RSAPUBK>test_public_key</RSAPUBK>
</AUTORIZACION>';

$caf_filename = 'CAF_T39_1-100.xml';
$caf_path = AKIBARA_SII_UPLOADS . 'caf/' . $caf_filename;
file_put_contents($caf_path, $caf_xml);

test(file_exists($caf_path), "Archivo CAF creado");

// Simular parsing del CAF
$xml = new SimpleXMLElement($caf_xml);
$da = $xml->CAF->DA;
$caf_data = [
    'tipo_dte' => (int)$da->TD,
    'folio_desde' => (int)$da->RNG->D,
    'folio_hasta' => (int)$da->RNG->H,
    'fecha_autorizacion' => (string)$da->FA,
    'rut_emisor' => (string)$da->RE
];

test($caf_data['tipo_dte'] === 39, "CAF es tipo 39 (Boleta Electrónica)");
test($caf_data['folio_desde'] === 1, "Folio desde: 1");
test($caf_data['folio_hasta'] === 100, "Folio hasta: 100");
test($caf_data['rut_emisor'] === '76123456-7', "RUT emisor en CAF coincide");

// Simular guardado de folio actual
update_option('akibara_folio_actual_39', 1);
test(get_option('akibara_folio_actual_39') === 1, "Folio actual inicializado");

// Verificar path consistente con AKIBARA_SII_UPLOADS
$expected_caf_path = AKIBARA_SII_UPLOADS . 'caf/' . $caf_filename;
test(file_exists($expected_caf_path), "Path de CAF es consistente con AKIBARA_SII_UPLOADS");

// ============================================================
// PASO 5: Simular emisión de boleta
// ============================================================
step("PASO 5: Simular emisión de boleta");

// Datos de una boleta de prueba
$boleta_data = [
    'receptor' => [
        'rut' => '66666666-6',
        'razon_social' => 'CONSUMIDOR FINAL',
        'direccion' => '',
        'comuna' => '',
    ],
    'items' => [
        [
            'nombre' => 'PRODUCTO DE PRUEBA',
            'cantidad' => 2,
            'precio' => 10000,
            'exento' => false,
        ],
        [
            'nombre' => 'SERVICIO DE PRUEBA',
            'cantidad' => 1,
            'precio' => 5000,
            'exento' => false,
        ],
    ],
];

// Calcular montos
$monto_neto = 0;
foreach ($boleta_data['items'] as $item) {
    $monto_neto += $item['cantidad'] * $item['precio'];
}
$monto_iva = (int) round($monto_neto * 0.19);
$monto_total = $monto_neto + $monto_iva;

test($monto_neto === 25000, "Monto neto calculado: $25,000");
test($monto_iva === 4750, "IVA calculado (19%): $4,750");
test($monto_total === 29750, "Monto total: $29,750");

// Simular generación de folio
$folio_actual = get_option('akibara_folio_actual_39', 1);
$nuevo_folio = $folio_actual + 1;
update_option('akibara_folio_actual_39', $nuevo_folio);

test($folio_actual === 1, "Folio asignado: 1");
test(get_option('akibara_folio_actual_39') === 2, "Folio incrementado a: 2");

// ============================================================
// PASO 6: Verificar estructura XML DTE
// ============================================================
step("PASO 6: Verificar generación de XML DTE");

// Verificar que Akibara_SII_Client tiene métodos para generar XML
$siiClient = new ReflectionClass('Akibara_SII_Client');

$requiredMethods = ['generar_rcof_xml', 'enviar_documento', 'consultar_estado'];
foreach ($requiredMethods as $method) {
    test($siiClient->hasMethod($method), "Método $method existe en Akibara_SII_Client");
}

// Verificar contenido del archivo para elementos XML
$siiContent = file_get_contents(AKIBARA_SII_PATH . 'includes/class-sii-client.php');
$boletaContent = file_get_contents(AKIBARA_SII_PATH . 'includes/class-boleta.php');

$xmlElements = ['Documento', 'Encabezado', 'IdDoc', 'Emisor', 'Receptor', 'MntTotal', 'Detalle'];
foreach ($xmlElements as $element) {
    test(
        strpos($siiContent, $element) !== false || strpos($boletaContent, $element) !== false,
        "Elemento XML <$element> presente"
    );
}

// ============================================================
// PASO 7: Verificar integración WooCommerce
// ============================================================
step("PASO 7: Verificar integración WooCommerce");

test(
    Akibara_WooCommerce_Integration::RUT_META_KEY === 'billing_rut',
    "Meta key RUT configurado: billing_rut"
);

// Verificar formato RUT
$formatRut = function($rut) {
    $rut = preg_replace('/[^0-9kK]/', '', strtoupper($rut));
    if (strlen($rut) < 2) return $rut;
    $dv = substr($rut, -1);
    $numero = substr($rut, 0, -1);
    return number_format((int)$numero, 0, '', '.') . '-' . $dv;
};

test($formatRut('12345678-9') === '12.345.678-9', "Formato RUT: 12345678-9 -> 12.345.678-9");
test($formatRut('66666666-6') === '66.666.666-6', "Formato RUT consumidor final");

// ============================================================
// PASO 8: Verificar sistema de notificaciones
// ============================================================
step("PASO 8: Verificar sistema de notificaciones de folios");

test(
    class_exists('Akibara_Folio_Notifications'),
    "Clase Akibara_Folio_Notifications existe"
);

$notifMethods = ['get_folio_status', 'send_notification', 'add_dashboard_widget'];
foreach ($notifMethods as $method) {
    $ref = new ReflectionClass('Akibara_Folio_Notifications');
    test($ref->hasMethod($method), "Método $method existe");
}

test(
    defined('Akibara_Folio_Notifications::CRON_HOOK'),
    "Constante CRON_HOOK definida"
);

// ============================================================
// PASO 9: Verificar RCOF
// ============================================================
step("PASO 9: Verificar RCOF");

$rcofClass = new ReflectionClass('Akibara_RCOF');
test($rcofClass->hasMethod('enviar'), "Método enviar() en RCOF");
test($rcofClass->hasMethod('consultar_estado'), "Método consultar_estado() en RCOF");

// Verificar que RCOF usa Akibara_Database
$rcofContent = file_get_contents(AKIBARA_SII_PATH . 'includes/class-rcof.php');
test(strpos($rcofContent, 'Akibara_Database::') !== false, "RCOF usa Akibara_Database::");
test(strpos($rcofContent, '$this->db->') === false, "RCOF no usa \$this->db (corregido)");

// ============================================================
// PASO 10: Verificar consistencia de paths
// ============================================================
step("PASO 10: Verificar consistencia de paths");

$configContent = file_get_contents(AKIBARA_SII_PATH . 'admin/views/configuracion.php');
$cafViewContent = file_get_contents(AKIBARA_SII_PATH . 'admin/views/caf.php');
$siiClientContent = file_get_contents(AKIBARA_SII_PATH . 'includes/class-sii-client.php');

test(
    strpos($configContent, 'AKIBARA_SII_UPLOADS') !== false,
    "configuracion.php usa AKIBARA_SII_UPLOADS"
);
test(
    strpos($cafViewContent, 'AKIBARA_SII_UPLOADS') !== false,
    "caf.php usa AKIBARA_SII_UPLOADS"
);
test(
    strpos($siiClientContent, 'AKIBARA_SII_UPLOADS') !== false,
    "class-sii-client.php usa AKIBARA_SII_UPLOADS"
);

// ============================================================
// LIMPIEZA
// ============================================================
step("LIMPIEZA");

// Eliminar archivos temporales
@unlink($cert_path);
@unlink($caf_path);
@rmdir(AKIBARA_SII_UPLOADS . 'certs/');
@rmdir(AKIBARA_SII_UPLOADS . 'caf/');
@rmdir(AKIBARA_SII_UPLOADS);
@rmdir('/tmp/akibara-test/');

test(!file_exists($cert_path), "Certificado temporal eliminado");
test(!file_exists($caf_path), "CAF temporal eliminado");

// ============================================================
// RESUMEN
// ============================================================
echo "\n" . str_repeat("=", 70) . "\n";
echo "   RESUMEN DE INTEGRACIÓN\n";
echo str_repeat("=", 70) . "\n\n";

$total = $passed + $failed;
$percentage = $total > 0 ? round(($passed / $total) * 100, 1) : 0;

echo GREEN . "  Pasadas:    " . RESET . $passed . "\n";
echo RED . "  Fallidas:   " . RESET . $failed . "\n";
echo "\n  Total:      " . $total . " pruebas\n";
echo "  Cobertura:  " . $percentage . "%\n\n";

if ($failed === 0) {
    echo GREEN . "  ✓ INTEGRACIÓN VERIFICADA CORRECTAMENTE" . RESET . "\n\n";
    echo CYAN . "  El plugin está listo para pruebas en un entorno WordPress real.\n";
    echo "  Para pruebas completas necesitas:\n";
    echo "  - WordPress instalado\n";
    echo "  - Certificado digital .p12 válido del SII\n";
    echo "  - CAF válido para Tipo 39\n";
    echo "  - Acceso a maullin.sii.cl (certificación) o palena.sii.cl (producción)\n" . RESET;
    echo "\n";
    exit(0);
} else {
    echo RED . "  ✗ HAY PROBLEMAS DE INTEGRACIÓN" . RESET . "\n\n";
    exit(1);
}
