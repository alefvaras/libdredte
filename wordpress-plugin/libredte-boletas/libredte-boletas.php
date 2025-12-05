<?php
/**
 * Plugin Name: LibreDTE Boletas
 * Plugin URI: https://github.com/libredte
 * Description: Emisión de Boletas Electrónicas para Chile usando LibreDTE
 * Version: 1.0.0
 * Author: AKIBARA SPA
 * License: GPL v2 or later
 * Text Domain: libredte-boletas
 */

defined('ABSPATH') || exit;

// Constantes del plugin
define('LIBREDTE_BOLETAS_VERSION', '1.0.0');
define('LIBREDTE_BOLETAS_PATH', plugin_dir_path(__FILE__));
define('LIBREDTE_BOLETAS_URL', plugin_dir_url(__FILE__));
define('LIBREDTE_BOLETAS_UPLOADS', LIBREDTE_BOLETAS_PATH . 'uploads/');

// Autoloader de LibreDTE
if (file_exists(LIBREDTE_BOLETAS_PATH . 'lib/libredte-lib-core/vendor/autoload.php')) {
    require_once LIBREDTE_BOLETAS_PATH . 'lib/libredte-lib-core/vendor/autoload.php';
}

/**
 * Clase principal del plugin
 */
class LibreDTE_Boletas {

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
        require_once LIBREDTE_BOLETAS_PATH . 'includes/class-database.php';
        require_once LIBREDTE_BOLETAS_PATH . 'includes/class-admin.php';
        require_once LIBREDTE_BOLETAS_PATH . 'includes/class-sii-client.php';
        require_once LIBREDTE_BOLETAS_PATH . 'includes/class-boleta.php';
        require_once LIBREDTE_BOLETAS_PATH . 'includes/class-rcof.php';
    }

    private function init_hooks() {
        register_activation_hook(__FILE__, [$this, 'activate']);
        register_deactivation_hook(__FILE__, [$this, 'deactivate']);

        add_action('init', [$this, 'init']);
        add_action('admin_menu', [$this, 'admin_menu']);
        add_action('admin_enqueue_scripts', [$this, 'admin_scripts']);

        // AJAX handlers
        add_action('wp_ajax_libredte_emitir_boleta', [$this, 'ajax_emitir_boleta']);
        add_action('wp_ajax_libredte_enviar_boleta', [$this, 'ajax_enviar_boleta']);
        add_action('wp_ajax_libredte_detalle_boleta', [$this, 'ajax_detalle_boleta']);
        add_action('wp_ajax_libredte_consultar_estado', [$this, 'ajax_consultar_estado']);
        add_action('wp_ajax_libredte_consultar_masivo', [$this, 'ajax_consultar_masivo']);
        add_action('wp_ajax_libredte_enviar_pendientes', [$this, 'ajax_enviar_pendientes']);
        add_action('wp_ajax_libredte_enviar_rcof', [$this, 'ajax_enviar_rcof']);
        add_action('wp_ajax_libredte_consultar_rcof', [$this, 'ajax_consultar_rcof']);
        add_action('wp_ajax_libredte_upload_caf', [$this, 'ajax_upload_caf']);
        add_action('wp_ajax_libredte_upload_certificado', [$this, 'ajax_upload_certificado']);
    }

    public function activate() {
        LibreDTE_Database::create_tables();

        // Crear directorio de uploads si no existe
        if (!file_exists(LIBREDTE_BOLETAS_UPLOADS)) {
            wp_mkdir_p(LIBREDTE_BOLETAS_UPLOADS);
        }

        // Proteger directorio con .htaccess
        $htaccess = LIBREDTE_BOLETAS_UPLOADS . '.htaccess';
        if (!file_exists($htaccess)) {
            file_put_contents($htaccess, "deny from all\n");
        }

        flush_rewrite_rules();
    }

    public function deactivate() {
        flush_rewrite_rules();
    }

    public function init() {
        load_plugin_textdomain('libredte-boletas', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

    public function admin_menu() {
        add_menu_page(
            __('LibreDTE Boletas', 'libredte-boletas'),
            __('Boletas DTE', 'libredte-boletas'),
            'manage_options',
            'libredte-boletas',
            [LibreDTE_Admin::instance(), 'render_dashboard'],
            'dashicons-media-spreadsheet',
            30
        );

        add_submenu_page(
            'libredte-boletas',
            __('Dashboard', 'libredte-boletas'),
            __('Dashboard', 'libredte-boletas'),
            'manage_options',
            'libredte-boletas',
            [LibreDTE_Admin::instance(), 'render_dashboard']
        );

        add_submenu_page(
            'libredte-boletas',
            __('Nueva Boleta', 'libredte-boletas'),
            __('Nueva Boleta', 'libredte-boletas'),
            'manage_options',
            'libredte-nueva-boleta',
            [LibreDTE_Admin::instance(), 'render_nueva_boleta']
        );

        add_submenu_page(
            'libredte-boletas',
            __('Historial', 'libredte-boletas'),
            __('Historial', 'libredte-boletas'),
            'manage_options',
            'libredte-historial',
            [LibreDTE_Admin::instance(), 'render_historial']
        );

        add_submenu_page(
            'libredte-boletas',
            __('Configuración', 'libredte-boletas'),
            __('Configuración', 'libredte-boletas'),
            'manage_options',
            'libredte-configuracion',
            [LibreDTE_Admin::instance(), 'render_configuracion']
        );

        add_submenu_page(
            'libredte-boletas',
            __('CAF / Folios', 'libredte-boletas'),
            __('CAF / Folios', 'libredte-boletas'),
            'manage_options',
            'libredte-caf',
            [LibreDTE_Admin::instance(), 'render_caf']
        );

        add_submenu_page(
            'libredte-boletas',
            __('RCOF', 'libredte-boletas'),
            __('RCOF', 'libredte-boletas'),
            'manage_options',
            'libredte-rcof',
            [LibreDTE_Admin::instance(), 'render_rcof']
        );
    }

    public function admin_scripts($hook) {
        if (strpos($hook, 'libredte') === false) {
            return;
        }

        wp_enqueue_style(
            'libredte-admin',
            LIBREDTE_BOLETAS_URL . 'assets/css/admin.css',
            [],
            LIBREDTE_BOLETAS_VERSION
        );

        wp_enqueue_script(
            'libredte-admin',
            LIBREDTE_BOLETAS_URL . 'assets/js/admin.js',
            ['jquery'],
            LIBREDTE_BOLETAS_VERSION,
            true
        );

        wp_localize_script('libredte-admin', 'libredteAjax', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('libredte_nonce'),
        ]);
    }

    public function ajax_emitir_boleta() {
        check_ajax_referer('libredte_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Sin permisos']);
        }

        $boleta = new LibreDTE_Boleta();
        $result = $boleta->emitir($_POST);

        if (is_wp_error($result)) {
            wp_send_json_error(['message' => $result->get_error_message()]);
        }

        wp_send_json_success($result);
    }

    public function ajax_enviar_rcof() {
        check_ajax_referer('libredte_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Sin permisos']);
        }

        $rcof = new LibreDTE_RCOF();
        $result = $rcof->enviar($_POST);

        if (is_wp_error($result)) {
            wp_send_json_error(['message' => $result->get_error_message()]);
        }

        wp_send_json_success($result);
    }

    public function ajax_upload_caf() {
        check_ajax_referer('libredte_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Sin permisos']);
        }

        if (empty($_FILES['caf_file'])) {
            wp_send_json_error(['message' => 'No se recibió archivo']);
        }

        $file = $_FILES['caf_file'];
        $upload_dir = LIBREDTE_BOLETAS_UPLOADS . 'caf/';

        if (!file_exists($upload_dir)) {
            wp_mkdir_p($upload_dir);
        }

        $filename = 'caf_' . time() . '.xml';
        $filepath = $upload_dir . $filename;

        if (move_uploaded_file($file['tmp_name'], $filepath)) {
            // Validar y procesar CAF
            $sii = new LibreDTE_SII_Client();
            $caf_info = $sii->parse_caf($filepath);

            if (is_wp_error($caf_info)) {
                unlink($filepath);
                wp_send_json_error(['message' => $caf_info->get_error_message()]);
            }

            // Guardar en base de datos
            LibreDTE_Database::save_caf([
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
        check_ajax_referer('libredte_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Sin permisos']);
        }

        if (empty($_FILES['cert_file'])) {
            wp_send_json_error(['message' => 'No se recibió archivo']);
        }

        $password = sanitize_text_field($_POST['cert_password'] ?? '');
        $ambiente = sanitize_text_field($_POST['ambiente'] ?? 'certificacion');

        $file = $_FILES['cert_file'];
        $upload_dir = LIBREDTE_BOLETAS_UPLOADS . 'certs/';

        if (!file_exists($upload_dir)) {
            wp_mkdir_p($upload_dir);
        }

        $filename = 'cert_' . $ambiente . '_' . time() . '.p12';
        $filepath = $upload_dir . $filename;

        if (move_uploaded_file($file['tmp_name'], $filepath)) {
            // Validar certificado
            $sii = new LibreDTE_SII_Client();
            $cert_info = $sii->validate_certificate($filepath, $password);

            if (is_wp_error($cert_info)) {
                unlink($filepath);
                wp_send_json_error(['message' => $cert_info->get_error_message()]);
            }

            // Guardar configuración
            update_option("libredte_cert_{$ambiente}_file", $filename);
            update_option("libredte_cert_{$ambiente}_password", base64_encode($password));

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
        check_ajax_referer('libredte_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Sin permisos');
        }

        $id = intval($_POST['id']);
        $boleta = new LibreDTE_Boleta();
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
        check_ajax_referer('libredte_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Sin permisos');
        }

        global $wpdb;
        $id = intval($_POST['id']);
        $boleta = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}libredte_boletas WHERE id = %d",
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
        check_ajax_referer('libredte_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Sin permisos');
        }

        $id = intval($_POST['id']);
        $boleta = new LibreDTE_Boleta();
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
        check_ajax_referer('libredte_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Sin permisos');
        }

        $ids = array_map('intval', $_POST['ids']);
        $boleta = new LibreDTE_Boleta();

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
        check_ajax_referer('libredte_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Sin permisos');
        }

        global $wpdb;
        $pendientes = $wpdb->get_results(
            "SELECT id FROM {$wpdb->prefix}libredte_boletas WHERE estado_sii = 'generado'"
        );

        $boleta = new LibreDTE_Boleta();
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
        check_ajax_referer('libredte_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Sin permisos');
        }

        $id = intval($_POST['id']);
        $rcof = new LibreDTE_RCOF();
        $result = $rcof->consultar_estado($id);

        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }

        wp_send_json_success($result);
    }

    /**
     * Obtener configuración del emisor
     */
    public static function get_emisor_config() {
        return [
            'RUTEmisor' => get_option('libredte_emisor_rut', ''),
            'RznSoc' => get_option('libredte_emisor_razon_social', ''),
            'GiroEmis' => get_option('libredte_emisor_giro', ''),
            'Acteco' => get_option('libredte_emisor_acteco', ''),
            'DirOrigen' => get_option('libredte_emisor_direccion', ''),
            'CmnaOrigen' => get_option('libredte_emisor_comuna', ''),
            'autorizacionDte' => [
                'fechaResolucion' => get_option('libredte_fecha_resolucion', ''),
                'numeroResolucion' => get_option('libredte_numero_resolucion', 0),
            ],
        ];
    }

    /**
     * Obtener ambiente actual
     */
    public static function get_ambiente() {
        return get_option('libredte_ambiente', 'certificacion');
    }

    /**
     * Verificar si RCOF está habilitado (solo en producción)
     */
    public static function rcof_enabled() {
        $ambiente = self::get_ambiente();
        if ($ambiente !== 'produccion') {
            return false;
        }
        return get_option('libredte_rcof_enabled', false);
    }
}

// Inicializar plugin
function libredte_boletas() {
    return LibreDTE_Boletas::instance();
}

add_action('plugins_loaded', 'libredte_boletas');
