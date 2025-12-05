<?php
/**
 * Suite de Pruebas Completa - Akibara SII Plugin
 *
 * Ejecutar: php tests/test-plugin.php
 */

// Colores para output
define('GREEN', "\033[32m");
define('RED', "\033[31m");
define('YELLOW', "\033[33m");
define('RESET', "\033[0m");

// Mock classes - must be defined before test class
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

if (!class_exists('WooCommerce')) {
    class WooCommerce {}
}

class AkibaraTestSuite {

    private $passed = 0;
    private $failed = 0;
    private $warnings = 0;

    public function run() {
        echo "\n" . str_repeat("=", 60) . "\n";
        echo "   AKIBARA SII - SUITE DE PRUEBAS COMPLETA\n";
        echo str_repeat("=", 60) . "\n\n";

        $this->setupWordPressMocks();

        // Grupo 1: Pruebas de Sintaxis
        $this->runGroup("SINTAXIS PHP", [$this, 'testSyntax']);

        // Grupo 2: Pruebas de Carga de Clases
        $this->runGroup("CARGA DE CLASES", [$this, 'testClassLoading']);

        // Grupo 3: Pruebas de Métodos
        $this->runGroup("METODOS Y DEPENDENCIAS", [$this, 'testMethods']);

        // Grupo 4: Pruebas de Base de Datos
        $this->runGroup("ESTRUCTURA BASE DE DATOS", [$this, 'testDatabaseStructure']);

        // Grupo 5: Pruebas de Lógica de Negocio
        $this->runGroup("LOGICA DE NEGOCIO", [$this, 'testBusinessLogic']);

        // Grupo 6: Pruebas de Seguridad
        $this->runGroup("SEGURIDAD", [$this, 'testSecurity']);

        // Grupo 7: Pruebas de Vistas
        $this->runGroup("VISTAS Y URLS", [$this, 'testViews']);

        // Grupo 8: Pruebas de WooCommerce
        $this->runGroup("INTEGRACION WOOCOMMERCE", [$this, 'testWooCommerce']);

        // Grupo 9: Pruebas de XML/Firma
        $this->runGroup("GENERACION XML", [$this, 'testXmlGeneration']);

        // Grupo 10: Pruebas de Notificaciones de Folios
        $this->runGroup("NOTIFICACIONES FOLIOS", [$this, 'testFolioNotifications']);

        // Resumen final
        $this->printSummary();
    }

    private function setupWordPressMocks() {
        // Simular constantes y funciones de WordPress
        if (!defined('ABSPATH')) define('ABSPATH', '/tmp/wordpress/');
        if (!defined('AKIBARA_SII_PATH')) define('AKIBARA_SII_PATH', dirname(__DIR__) . '/');
        if (!defined('AKIBARA_SII_URL')) define('AKIBARA_SII_URL', 'http://example.com/wp-content/plugins/akibara-sii/');
        if (!defined('AKIBARA_SII_UPLOADS')) define('AKIBARA_SII_UPLOADS', '/tmp/uploads/akibara-sii/');

        // Mock functions
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
        if (!function_exists('wp_parse_args')) { function wp_parse_args($a, $d) { return array_merge($d, $a); } }
        if (!function_exists('get_option')) {
            function get_option($k, $d = '') {
                static $opts = [];
                return $opts[$k] ?? $d;
            }
        }
        if (!function_exists('update_option')) { function update_option($k, $v) { return true; } }
        if (!function_exists('get_post_meta')) { function get_post_meta($id, $k, $s = false) { return ''; } }
        if (!function_exists('update_post_meta')) { function update_post_meta($id, $k, $v) { return true; } }
        if (!function_exists('wp_upload_dir')) {
            function wp_upload_dir() {
                return ['basedir' => '/tmp/uploads', 'baseurl' => 'http://example.com/uploads'];
            }
        }
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
        if (!function_exists('sanitize_email')) { function sanitize_email($e) { return filter_var($e, FILTER_SANITIZE_EMAIL); } }
        if (!function_exists('checked')) { function checked($a, $b = 1, $e = true) { if ($a == $b) { if ($e) echo 'checked'; return 'checked'; } return ''; } }

    }

    private function runGroup($name, $callback) {
        echo YELLOW . "[$name]" . RESET . "\n";
        call_user_func($callback);
        echo "\n";
    }

    private function assert($condition, $testName, $details = '') {
        if ($condition) {
            echo GREEN . "  ✓ " . RESET . $testName . "\n";
            $this->passed++;
            return true;
        } else {
            echo RED . "  ✗ " . RESET . $testName;
            if ($details) echo " - " . $details;
            echo "\n";
            $this->failed++;
            return false;
        }
    }

    private function warning($message) {
        echo YELLOW . "  ⚠ " . RESET . $message . "\n";
        $this->warnings++;
    }

    // ==================== PRUEBAS ====================

    private function testSyntax() {
        $pluginDir = dirname(__DIR__);
        $files = $this->findPhpFiles($pluginDir);

        $allPassed = true;
        foreach ($files as $file) {
            $output = [];
            $return = 0;
            exec("php -l " . escapeshellarg($file) . " 2>&1", $output, $return);

            $relativePath = str_replace($pluginDir . '/', '', $file);
            if ($return !== 0) {
                $this->assert(false, "Sintaxis: $relativePath", implode(' ', $output));
                $allPassed = false;
            }
        }

        if ($allPassed) {
            $this->assert(true, "Sintaxis PHP correcta en " . count($files) . " archivos");
        }
    }

    private function testClassLoading() {
        $classes = [
            'Akibara_Database' => 'includes/class-database.php',
            'Akibara_Admin' => 'includes/class-admin.php',
            'Akibara_SII_Client' => 'includes/class-sii-client.php',
            'Akibara_Boleta' => 'includes/class-boleta.php',
            'Akibara_RCOF' => 'includes/class-rcof.php',
            'Akibara_WooCommerce_Integration' => 'includes/class-woocommerce-integration.php',
            'Akibara_Folio_Notifications' => 'includes/class-folio-notifications.php',
        ];

        foreach ($classes as $className => $file) {
            $fullPath = dirname(__DIR__) . '/' . $file;
            if (file_exists($fullPath)) {
                try {
                    require_once $fullPath;
                    $this->assert(
                        class_exists($className),
                        "Clase $className cargada correctamente"
                    );
                } catch (Exception $e) {
                    $this->assert(false, "Cargar $className", $e->getMessage());
                }
            } else {
                $this->assert(false, "Archivo existe: $file");
            }
        }
    }

    private function testMethods() {
        // Akibara_Database - métodos estáticos
        $dbMethods = ['create_tables', 'save_boleta', 'update_boleta', 'get_boleta',
                      'get_boletas_by_date', 'get_boletas_pendientes', 'save_caf',
                      'get_caf_activo', 'get_siguiente_folio', 'incrementar_folio',
                      'save_rcof', 'get_rcof_by_date', 'log', 'get_historial'];

        foreach ($dbMethods as $method) {
            $ref = new ReflectionClass('Akibara_Database');
            $exists = $ref->hasMethod($method);
            $isStatic = $exists && $ref->getMethod($method)->isStatic();
            $this->assert($exists && $isStatic, "Akibara_Database::$method() existe y es estático");
        }

        // Akibara_Boleta - métodos públicos
        $boletaMethods = ['emitir', 'enviar_al_sii', 'consultar_estado'];
        foreach ($boletaMethods as $method) {
            $ref = new ReflectionClass('Akibara_Boleta');
            $exists = $ref->hasMethod($method);
            $isPublic = $exists && $ref->getMethod($method)->isPublic();
            $this->assert($exists && $isPublic, "Akibara_Boleta::$method() existe y es público");
        }

        // Akibara_SII_Client - métodos públicos requeridos
        $siiMethods = ['generar_rcof_xml', 'enviar_documento', 'consultar_estado'];
        foreach ($siiMethods as $method) {
            $ref = new ReflectionClass('Akibara_SII_Client');
            $exists = $ref->hasMethod($method);
            $isPublic = $exists && $ref->getMethod($method)->isPublic();
            $this->assert($exists && $isPublic, "Akibara_SII_Client::$method() existe y es público");
        }

        // Akibara_RCOF - no debe usar $this->db
        $rcofContent = file_get_contents(dirname(__DIR__) . '/includes/class-rcof.php');
        $this->assert(
            strpos($rcofContent, '$this->db->') === false,
            "Akibara_RCOF no usa \$this->db-> (métodos estáticos)"
        );
        $this->assert(
            strpos($rcofContent, 'Akibara_Database::') !== false,
            "Akibara_RCOF usa Akibara_Database:: correctamente"
        );
    }

    private function testDatabaseStructure() {
        $dbContent = file_get_contents(dirname(__DIR__) . '/includes/class-database.php');

        // Verificar tablas
        $tables = ['akibara_boletas', 'akibara_caf', 'akibara_rcof', 'akibara_log'];
        foreach ($tables as $table) {
            $this->assert(
                strpos($dbContent, $table) !== false,
                "Tabla $table definida"
            );
        }

        // Verificar campos importantes
        $fields = [
            'folio', 'tipo_dte', 'fecha_emision', 'rut_receptor', 'monto_total',
            'xml_documento', 'track_id', 'ambiente', 'estado'
        ];
        foreach ($fields as $field) {
            $this->assert(
                strpos($dbContent, $field) !== false,
                "Campo '$field' definido en esquema"
            );
        }
    }

    private function testBusinessLogic() {
        // Test formato RUT
        $formatRut = function($rut) {
            $rut = preg_replace('/[^0-9kK]/', '', strtoupper($rut));
            if (strlen($rut) < 2) return $rut;
            $dv = substr($rut, -1);
            $numero = substr($rut, 0, -1);
            return number_format((int)$numero, 0, '', '.') . '-' . $dv;
        };

        $this->assert(
            $formatRut('12345678-9') === '12.345.678-9',
            "Formato RUT: 12345678-9 -> 12.345.678-9"
        );
        $this->assert(
            $formatRut('123456789') === '12.345.678-9',
            "Formato RUT sin guión: 123456789 -> 12.345.678-9"
        );
        $this->assert(
            $formatRut('12.345.678-9') === '12.345.678-9',
            "Formato RUT ya formateado mantiene formato"
        );
        $this->assert(
            $formatRut('66666666-6') === '66.666.666-6',
            "Formato RUT consumidor final"
        );

        // Verificar constante RUT_META_KEY
        $this->assert(
            Akibara_WooCommerce_Integration::RUT_META_KEY === 'billing_rut',
            "RUT_META_KEY = 'billing_rut'"
        );

        // Test cálculo IVA
        $calcularIVA = function($neto) {
            return (int) round($neto * 0.19);
        };

        $this->assert($calcularIVA(10000) == 1900, "Cálculo IVA: 10000 -> 1900");
        $this->assert($calcularIVA(8403) == 1597, "Cálculo IVA redondeado: 8403 -> 1597");
    }

    private function testSecurity() {
        $files = [
            'akibara-sii.php',
            'includes/class-admin.php',
            'admin/views/configuracion.php',
            'admin/views/caf.php',
            'admin/views/rcof.php',
        ];

        foreach ($files as $file) {
            $content = file_get_contents(dirname(__DIR__) . '/' . $file);

            // Verificar protección ABSPATH
            if (strpos($file, 'views/') !== false) {
                $this->assert(
                    strpos($content, "defined('ABSPATH')") !== false ||
                    strpos($content, 'defined(\'ABSPATH\')') !== false,
                    "Protección ABSPATH en $file"
                );
            }

            // Verificar uso de nonce en forms POST
            if (strpos($content, '$_POST') !== false) {
                $hasNonce = strpos($content, 'wp_verify_nonce') !== false ||
                           strpos($content, 'check_ajax_referer') !== false;
                $this->assert($hasNonce, "Verificación nonce en $file");
            }
        }

        // Verificar sanitización
        $mainFile = file_get_contents(dirname(__DIR__) . '/akibara-sii.php');
        $this->assert(
            strpos($mainFile, 'sanitize_text_field') !== false,
            "Uso de sanitize_text_field() en archivo principal"
        );
        $this->assert(
            strpos($mainFile, 'intval') !== false,
            "Uso de intval() para enteros"
        );

        // Verificar campo Acteco (obligatorio SII)
        $configFile = file_get_contents(dirname(__DIR__) . '/admin/views/configuracion.php');
        $this->assert(
            strpos($configFile, 'emisor_acteco') !== false,
            "Campo Acteco en configuracion.php"
        );
        $this->assert(
            strpos($mainFile, 'akibara_emisor_acteco') !== false,
            "Opción Acteco en get_emisor_config()"
        );

        // Verificar certificados por ambiente
        $this->assert(
            strpos($configFile, 'cert_ambiente') !== false,
            "Selector de ambiente para certificado"
        );
        $this->assert(
            strpos($configFile, 'akibara_cert_certificacion_file') !== false ||
            strpos($configFile, "akibara_cert_{\$cert_ambiente}_file") !== false,
            "Certificados separados por ambiente"
        );

        // Verificar que CAF y certificados usen AKIBARA_SII_UPLOADS (no wp_upload_dir)
        $cafFile = file_get_contents(dirname(__DIR__) . '/admin/views/caf.php');
        $this->assert(
            strpos($cafFile, 'AKIBARA_SII_UPLOADS') !== false,
            "CAF usa AKIBARA_SII_UPLOADS"
        );
        $this->assert(
            strpos($configFile, 'AKIBARA_SII_UPLOADS') !== false,
            "Certificado usa AKIBARA_SII_UPLOADS"
        );
    }

    private function testViews() {
        $viewFiles = glob(dirname(__DIR__) . '/admin/views/*.php');

        foreach ($viewFiles as $file) {
            $content = file_get_contents($file);
            $filename = basename($file);

            // No debe haber referencias a libredte-
            $this->assert(
                strpos($content, 'libredte-') === false,
                "Sin referencias 'libredte-' en $filename"
            );

            // URLs deben usar akibara-
            if (strpos($content, 'admin.php?page=') !== false) {
                preg_match_all('/page=([a-z-]+)/', $content, $matches);
                $allAkibara = true;
                foreach ($matches[1] as $page) {
                    if (strpos($page, 'akibara') !== 0) {
                        $allAkibara = false;
                        break;
                    }
                }
                $this->assert($allAkibara, "URLs usan prefijo 'akibara-' en $filename");
            }
        }

        // Verificar que existen todas las vistas requeridas
        $requiredViews = [
            'dashboard.php', 'nueva-boleta.php', 'historial.php',
            'configuracion.php', 'caf.php', 'rcof.php', 'rcof-no-disponible.php'
        ];

        foreach ($requiredViews as $view) {
            $this->assert(
                file_exists(dirname(__DIR__) . '/admin/views/' . $view),
                "Vista existe: $view"
            );
        }
    }

    private function testWooCommerce() {
        $wcFile = dirname(__DIR__) . '/includes/class-woocommerce-integration.php';
        $content = file_get_contents($wcFile);

        // Verificar hooks de WooCommerce
        $hooks = [
            'woocommerce_order_status_completed',
            'woocommerce_order_status_processing',
            'woocommerce_email_attachments',
            'woocommerce_order_actions',
        ];

        foreach ($hooks as $hook) {
            $this->assert(
                strpos($content, $hook) !== false,
                "Hook WooCommerce: $hook"
            );
        }

        // Verificar meta keys para RUT
        $metaKeys = ['billing_rut', '_billing_rut'];
        foreach ($metaKeys as $key) {
            $this->assert(
                strpos($content, $key) !== false,
                "Meta key RUT: $key"
            );
        }

        // Verificar que usa wp_mail para emails
        $this->assert(
            strpos($content, 'wp_mail') !== false,
            "Usa wp_mail() para envío de emails"
        );
    }

    private function testXmlGeneration() {
        // Verificar elementos XML DTE en archivos relevantes
        $siiContent = file_get_contents(dirname(__DIR__) . '/includes/class-sii-client.php');
        $boletaContent = file_get_contents(dirname(__DIR__) . '/includes/class-boleta.php');
        $allContent = $siiContent . $boletaContent;

        // Verificar elementos XML DTE (formato SII)
        $xmlElements = [
            'Documento', 'Encabezado', 'IdDoc', 'Emisor', 'Receptor',
            'MntTotal', 'Detalle', 'Signature'
        ];

        foreach ($xmlElements as $element) {
            $this->assert(
                strpos($allContent, $element) !== false,
                "Elemento XML: <$element>"
            );
        }

        $content = $siiContent;

        // Verificar uso de DOMDocument
        $this->assert(
            strpos($content, 'DOMDocument') !== false,
            "Usa DOMDocument para XML"
        );

        // Verificar firma digital
        $this->assert(
            strpos($content, 'openssl_') !== false,
            "Usa funciones OpenSSL para firma"
        );

        // Verificar canonicalización
        $this->assert(
            strpos($content, 'C14N') !== false || strpos($content, 'canonicalize') !== false,
            "Usa canonicalización XML"
        );
    }

    private function testFolioNotifications() {
        // Verificar que existe la clase
        $this->assert(
            class_exists('Akibara_Folio_Notifications'),
            "Clase Akibara_Folio_Notifications existe"
        );

        // Verificar métodos estáticos
        $methods = ['init', 'schedule_cron', 'unschedule_cron', 'check_and_notify',
                    'get_folio_status', 'send_notification', 'add_dashboard_widget',
                    'render_dashboard_widget'];

        foreach ($methods as $method) {
            $ref = new ReflectionClass('Akibara_Folio_Notifications');
            $exists = $ref->hasMethod($method);
            $isStatic = $exists && $ref->getMethod($method)->isStatic();
            $this->assert($exists && $isStatic, "Akibara_Folio_Notifications::$method() existe y es estático");
        }

        // Verificar constantes
        $this->assert(
            defined('Akibara_Folio_Notifications::CRON_HOOK'),
            "Constante CRON_HOOK definida"
        );

        // Verificar contenido del archivo
        $content = file_get_contents(dirname(__DIR__) . '/includes/class-folio-notifications.php');

        // Verificar que usa wp_mail
        $this->assert(
            strpos($content, 'wp_mail') !== false,
            "Notificaciones usan wp_mail()"
        );

        // Verificar que implementa dashboard widget
        $this->assert(
            strpos($content, 'wp_add_dashboard_widget') !== false,
            "Implementa widget de dashboard"
        );

        // Verificar opciones de configuración en configuracion.php
        $configContent = file_get_contents(dirname(__DIR__) . '/admin/views/configuracion.php');

        $this->assert(
            strpos($configContent, 'folio_notifications') !== false,
            "Opción folio_notifications en configuración"
        );

        $this->assert(
            strpos($configContent, 'folio_alert_threshold') !== false,
            "Opción folio_alert_threshold en configuración"
        );

        $this->assert(
            strpos($configContent, 'folio_notification_email') !== false,
            "Opción folio_notification_email en configuración"
        );

        // Verificar que se incluye en el plugin principal
        $mainContent = file_get_contents(dirname(__DIR__) . '/akibara-sii.php');
        $this->assert(
            strpos($mainContent, 'class-folio-notifications.php') !== false,
            "class-folio-notifications.php incluido en plugin principal"
        );
    }

    private function findPhpFiles($dir) {
        $files = [];
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if ($file->getExtension() === 'php' &&
                strpos($file->getPath(), '/vendor/') === false &&
                strpos($file->getPath(), '/tests/') === false) {
                $files[] = $file->getPathname();
            }
        }

        return $files;
    }

    private function printSummary() {
        $total = $this->passed + $this->failed;
        $percentage = $total > 0 ? round(($this->passed / $total) * 100, 1) : 0;

        echo str_repeat("=", 60) . "\n";
        echo "   RESUMEN DE PRUEBAS\n";
        echo str_repeat("=", 60) . "\n\n";

        echo GREEN . "  Pasadas:    " . RESET . $this->passed . "\n";
        echo RED . "  Fallidas:   " . RESET . $this->failed . "\n";
        echo YELLOW . "  Advertencias: " . RESET . $this->warnings . "\n";
        echo "\n  Total:      " . $total . " pruebas\n";
        echo "  Cobertura:  " . $percentage . "%\n\n";

        if ($this->failed === 0) {
            echo GREEN . "  ✓ TODAS LAS PRUEBAS PASARON" . RESET . "\n\n";
            exit(0);
        } else {
            echo RED . "  ✗ ALGUNAS PRUEBAS FALLARON" . RESET . "\n\n";
            exit(1);
        }
    }
}

// Ejecutar pruebas
$suite = new AkibaraTestSuite();
$suite->run();
