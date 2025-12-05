<?php
/**
 * Plugin Name: Akibara SII
 * Plugin URI: https://akibara.cl
 * Description: Emisión de Boletas Electrónicas para Chile
 * Version: 1.0.0
 * Author: AKIBARA SPA
 * License: GPL v2 or later
 * Text Domain: akibara-sii
 */

defined('ABSPATH') || exit;

// Constantes del plugin
define('AKIBARA_SII_VERSION', '1.0.0');
define('AKIBARA_SII_PATH', plugin_dir_path(__FILE__));
define('AKIBARA_SII_URL', plugin_dir_url(__FILE__));
define('AKIBARA_SII_UPLOADS', AKIBARA_SII_PATH . 'uploads/');

// Autoloader de dependencias
if (file_exists(AKIBARA_SII_PATH . 'vendor/autoload.php')) {
    require_once AKIBARA_SII_PATH . 'vendor/autoload.php';
}

/**
 * Clase principal del plugin
 */
class Akibara_SII {

    private static $instance = null;

    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->includes();
        $this->init_hooks();
    }

    private function includes() {
        require_once AKIBARA_SII_PATH . 'includes/class-database.php';
        require_once AKIBARA_SII_PATH . 'includes/class-admin.php';
        require_once AKIBARA_SII_PATH . 'includes/class-sii-client.php';
        require_once AKIBARA_SII_PATH . 'includes/class-boleta.php';
        require_once AKIBARA_SII_PATH . 'includes/class-rcof.php';

        // WooCommerce integration (carga condicional)
        if (class_exists('WooCommerce') || in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
            require_once AKIBARA_SII_PATH . 'includes/class-woocommerce-integration.php';
        }
    }

    private function init_hooks() {
        register_activation_hook(__FILE__, [$this, 'activate']);
        register_deactivation_hook(__FILE__, [$this, 'deactivate']);

        add_action('init', [$this, 'init']);
        add_action('admin_menu', [$this, 'admin_menu']);
        add_action('admin_enqueue_scripts', [$this, 'admin_scripts']);

        // AJAX handlers
        add_action('wp_ajax_akibara_emitir_boleta', [$this, 'ajax_emitir_boleta']);
        add_action('wp_ajax_akibara_enviar_boleta', [$this, 'ajax_enviar_boleta']);
        add_action('wp_ajax_akibara_detalle_boleta', [$this, 'ajax_detalle_boleta']);
        add_action('wp_ajax_akibara_consultar_estado', [$this, 'ajax_consultar_estado']);
        add_action('wp_ajax_akibara_consultar_masivo', [$this, 'ajax_consultar_masivo']);
        add_action('wp_ajax_akibara_enviar_pendientes', [$this, 'ajax_enviar_pendientes']);
        add_action('wp_ajax_akibara_enviar_rcof', [$this, 'ajax_enviar_rcof']);
        add_action('wp_ajax_akibara_consultar_rcof', [$this, 'ajax_consultar_rcof']);
        add_action('wp_ajax_akibara_upload_caf', [$this, 'ajax_upload_caf']);
        add_action('wp_ajax_akibara_upload_certificado', [$this, 'ajax_upload_certificado']);
    }

    public function activate() {
        Akibara_Database::create_tables();

        // Crear directorio de uploads si no existe
        if (!file_exists(AKIBARA_SII_UPLOADS)) {
            wp_mkdir_p(AKIBARA_SII_UPLOADS);
        }

        // Proteger directorio con .htaccess
        $htaccess = AKIBARA_SII_UPLOADS . '.htaccess';
        if (!file_exists($htaccess)) {
            file_put_contents($htaccess, "deny from all\n");
        }

        flush_rewrite_rules();
    }

    public function deactivate() {
        flush_rewrite_rules();
    }

    public function init() {
        load_plugin_textdomain('akibara-sii', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

    public function admin_menu() {
        add_menu_page(
            __('Akibara SII', 'akibara-sii'),
            __('Boletas', 'akibara-sii'),
            'manage_options',
            'akibara-sii',
            [Akibara_Admin::instance(), 'render_dashboard'],
            'dashicons-media-spreadsheet',
            30
        );

        add_submenu_page(
            'akibara-sii',
            __('Dashboard', 'akibara-sii'),
            __('Dashboard', 'akibara-sii'),
            'manage_options',
            'akibara-sii',
            [Akibara_Admin::instance(), 'render_dashboard']
        );

        add_submenu_page(
            'akibara-sii',
            __('Nueva Boleta', 'akibara-sii'),
            __('Nueva Boleta', 'akibara-sii'),
            'manage_options',
            'akibara-nueva-boleta',
            [Akibara_Admin::instance(), 'render_nueva_boleta']
        );

        add_submenu_page(
            'akibara-sii',
            __('Historial', 'akibara-sii'),
            __('Historial', 'akibara-sii'),
            'manage_options',
            'akibara-historial',
            [Akibara_Admin::instance(), 'render_historial']
        );

        add_submenu_page(
            'akibara-sii',
            __('Configuración', 'akibara-sii'),
            __('Configuración', 'akibara-sii'),
            'manage_options',
            'akibara-configuracion',
            [Akibara_Admin::instance(), 'render_configuracion']
        );

        add_submenu_page(
            'akibara-sii',
            __('CAF / Folios', 'akibara-sii'),
            __('CAF / Folios', 'akibara-sii'),
            'manage_options',
            'akibara-caf',
            [Akibara_Admin::instance(), 'render_caf']
        );

        add_submenu_page(
            'akibara-sii',
            __('RCOF', 'akibara-sii'),
            __('RCOF', 'akibara-sii'),
            'manage_options',
            'akibara-rcof',
            [Akibara_Admin::instance(), 'render_rcof']
        );
    }

    public function admin_scripts($hook) {
        if (strpos($hook, 'libredte') === false) {
            return;
        }

        wp_enqueue_style(
            'akibara-admin',
            AKIBARA_SII_URL . 'assets/css/admin.css',
            [],
            AKIBARA_SII_VERSION
        );

        wp_enqueue_script(
            'akibara-admin',
            AKIBARA_SII_URL . 'assets/js/admin.js',
            ['jquery'],
            AKIBARA_SII_VERSION,
            true
        );

        wp_localize_script('akibara-admin', 'akibaraAjax', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('akibara_nonce'),
        ]);
    }

    public function ajax_emitir_boleta() {
        check_ajax_referer('akibara_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Sin permisos']);
        }

        $boleta = new Akibara_Boleta();
        $result = $boleta->emitir($_POST);

        if (is_wp_error($result)) {
            wp_send_json_error(['message' => $result->get_error_message()]);
        }

        wp_send_json_success($result);
    }

    public function ajax_enviar_rcof() {
        check_ajax_referer('akibara_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Sin permisos']);
        }

        $rcof = new Akibara_RCOF();
        $result = $rcof->enviar($_POST);

        if (is_wp_error($result)) {
            wp_send_json_error(['message' => $result->get_error_message()]);
        }

        wp_send_json_success($result);
    }

    public function ajax_upload_caf() {
        check_ajax_referer('akibara_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Sin permisos']);
        }

        if (empty($_FILES['caf_file'])) {
            wp_send_json_error(['message' => 'No se recibió archivo']);
        }

        $file = $_FILES['caf_file'];
        $upload_dir = AKIBARA_SII_UPLOADS . 'caf/';

        if (!file_exists($upload_dir)) {
            wp_mkdir_p($upload_dir);
        }

        $filename = 'caf_' . time() . '.xml';
        $filepath = $upload_dir . $filename;

        if (move_uploaded_file($file['tmp_name'], $filepath)) {
            // Validar y procesar CAF
            $sii = new Akibara_SII_Client();
            $caf_info = $sii->parse_caf($filepath);

            if (is_wp_error($caf_info)) {
                unlink($filepath);
                wp_send_json_error(['message' => $caf_info->get_error_message()]);
            }

            // Guardar en base de datos
            Akibara_Database::save_caf([
                'tipo_dte' => $caf_info['tipo'],
                'folio_desde' => $caf_info['desde'],
                'folio_hasta' => $caf_info['hasta'],
                'folio_actual' => $caf_info['desde'],
                'fecha_vencimiento' => $caf_info['vencimiento'],
                'archivo' => $filename,
                'ambiente' => sanitize_text_field($_POST['ambiente'] ?? 'certificacion'),
            ]);

            wp_send_json_success([
                'message' => 'CAF cargado correctamente',
                'info' => $caf_info,
            ]);
        }

        wp_send_json_error(['message' => 'Error al subir archivo']);
    }

    public function ajax_upload_certificado() {
        check_ajax_referer('akibara_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Sin permisos']);
        }

        if (empty($_FILES['cert_file'])) {
            wp_send_json_error(['message' => 'No se recibió archivo']);
        }

        $password = sanitize_text_field($_POST['cert_password'] ?? '');
        $ambiente = sanitize_text_field($_POST['ambiente'] ?? 'certificacion');

        $file = $_FILES['cert_file'];
        $upload_dir = AKIBARA_SII_UPLOADS . 'certs/';

        if (!file_exists($upload_dir)) {
            wp_mkdir_p($upload_dir);
        }

        $filename = 'cert_' . $ambiente . '_' . time() . '.p12';
        $filepath = $upload_dir . $filename;

        if (move_uploaded_file($file['tmp_name'], $filepath)) {
            // Validar certificado
            $sii = new Akibara_SII_Client();
            $cert_info = $sii->validate_certificate($filepath, $password);

            if (is_wp_error($cert_info)) {
                unlink($filepath);
                wp_send_json_error(['message' => $cert_info->get_error_message()]);
            }

            // Guardar configuración
            update_option("akibara_cert_{$ambiente}_file", $filename);
            update_option("akibara_cert_{$ambiente}_password", base64_encode($password));

            wp_send_json_success([
                'message' => 'Certificado cargado correctamente',
                'info' => $cert_info,
            ]);
        }

        wp_send_json_error(['message' => 'Error al subir archivo']);
    }

    /**
     * AJAX: Enviar boleta al SII
     */
    public function ajax_enviar_boleta() {
        check_ajax_referer('akibara_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Sin permisos');
        }

        $id = intval($_POST['id']);
        $boleta = new Akibara_Boleta();
        $result = $boleta->enviar_al_sii($id);

        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }

        wp_send_json_success($result);
    }

    /**
     * AJAX: Obtener detalle de boleta
     */
    public function ajax_detalle_boleta() {
        check_ajax_referer('akibara_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Sin permisos');
        }

        global $wpdb;
        $id = intval($_POST['id']);
        $boleta = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}akibara_boletas WHERE id = %d",
            $id
        ), ARRAY_A);

        if (!$boleta) {
            wp_send_json_error('Boleta no encontrada');
        }

        wp_send_json_success($boleta);
    }

    /**
     * AJAX: Consultar estado en SII
     */
    public function ajax_consultar_estado() {
        check_ajax_referer('akibara_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Sin permisos');
        }

        $id = intval($_POST['id']);
        $boleta = new Akibara_Boleta();
        $result = $boleta->consultar_estado_sii($id);

        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }

        wp_send_json_success($result);
    }

    /**
     * AJAX: Consultar estado masivo
     */
    public function ajax_consultar_masivo() {
        check_ajax_referer('akibara_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Sin permisos');
        }

        $ids = array_map('intval', $_POST['ids']);
        $boleta = new Akibara_Boleta();

        $resultados = array('aceptadas' => 0, 'rechazadas' => 0, 'pendientes' => 0);

        foreach ($ids as $id) {
            $result = $boleta->consultar_estado_sii($id);
            if (!is_wp_error($result)) {
                if ($result['estado'] === 'aceptado') $resultados['aceptadas']++;
                elseif ($result['estado'] === 'rechazado') $resultados['rechazadas']++;
                else $resultados['pendientes']++;
            }
        }

        wp_send_json_success($resultados);
    }

    /**
     * AJAX: Enviar boletas pendientes
     */
    public function ajax_enviar_pendientes() {
        check_ajax_referer('akibara_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Sin permisos');
        }

        global $wpdb;
        $pendientes = $wpdb->get_results(
            "SELECT id FROM {$wpdb->prefix}akibara_boletas WHERE estado_sii = 'generado'"
        );

        $boleta = new Akibara_Boleta();
        $enviadas = 0;

        foreach ($pendientes as $p) {
            $result = $boleta->enviar_al_sii($p->id);
            if (!is_wp_error($result)) {
                $enviadas++;
            }
        }

        wp_send_json_success(array('enviadas' => $enviadas));
    }

    /**
     * AJAX: Consultar estado RCOF
     */
    public function ajax_consultar_rcof() {
        check_ajax_referer('akibara_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Sin permisos');
        }

        $id = intval($_POST['id']);
        $rcof = new Akibara_RCOF();
        $result = $rcof->consultar_estado($id);

        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }

        wp_send_json_success($result);
    }

    /**
     * Obtener configuración del emisor (sin autorizacionDte para XML)
     */
    public static function get_emisor_config() {
        return [
            'RUTEmisor' => get_option('akibara_emisor_rut', ''),
            'RznSoc' => get_option('akibara_emisor_razon_social', ''),
            'GiroEmis' => get_option('akibara_emisor_giro', ''),
            'Acteco' => get_option('akibara_emisor_acteco', ''),
            'DirOrigen' => get_option('akibara_emisor_direccion', ''),
            'CmnaOrigen' => get_option('akibara_emisor_comuna', ''),
        ];
    }

    /**
     * Obtener configuración de autorización DTE (para Carátula)
     */
    public static function get_autorizacion_config() {
        return [
            'fechaResolucion' => get_option('akibara_resolucion_fecha', '2014-08-22'),
            'numeroResolucion' => (int) get_option('akibara_resolucion_numero', 80),
        ];
    }

    /**
     * Obtener ambiente actual
     */
    public static function get_ambiente() {
        return get_option('akibara_ambiente', 'certificacion');
    }

    /**
     * Verificar si RCOF está habilitado (solo en producción)
     */
    public static function rcof_enabled() {
        $ambiente = self::get_ambiente();
        if ($ambiente !== 'produccion') {
            return false;
        }
        return get_option('akibara_rcof_enabled', false);
    }
}

// Inicializar plugin
function akibara_boletas() {
    return Akibara_SII::instance();
}

add_action('plugins_loaded', 'akibara_boletas');
