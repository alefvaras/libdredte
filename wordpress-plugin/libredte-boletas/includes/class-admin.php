<?php
/**
 * Clase para panel de administración
 */

defined('ABSPATH') || exit;

class LibreDTE_Admin {

    private static $instance = null;

    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('admin_init', [$this, 'register_settings']);
    }

    /**
     * Registrar configuraciones
     */
    public function register_settings() {
        // Configuración del emisor
        register_setting('libredte_emisor', 'libredte_emisor_rut');
        register_setting('libredte_emisor', 'libredte_emisor_razon_social');
        register_setting('libredte_emisor', 'libredte_emisor_giro');
        register_setting('libredte_emisor', 'libredte_emisor_acteco');
        register_setting('libredte_emisor', 'libredte_emisor_direccion');
        register_setting('libredte_emisor', 'libredte_emisor_comuna');
        register_setting('libredte_emisor', 'libredte_fecha_resolucion');
        register_setting('libredte_emisor', 'libredte_numero_resolucion');

        // Configuración general
        register_setting('libredte_general', 'libredte_ambiente');
        register_setting('libredte_general', 'libredte_envio_automatico');
        register_setting('libredte_general', 'libredte_rcof_enabled');
        register_setting('libredte_general', 'libredte_rcof_hora');
    }

    /**
     * Guardar configuración desde POST
     */
    private function save_settings() {
        if (!isset($_POST['libredte_save_settings'])) {
            return;
        }

        if (!wp_verify_nonce($_POST['_wpnonce'], 'libredte_settings')) {
            return;
        }

        // Guardar configuración del emisor
        if (isset($_POST['emisor'])) {
            $emisor = $_POST['emisor'];
            update_option('libredte_emisor_rut', sanitize_text_field($emisor['rut'] ?? ''));
            update_option('libredte_emisor_razon_social', sanitize_text_field($emisor['razon_social'] ?? ''));
            update_option('libredte_emisor_giro', sanitize_text_field($emisor['giro'] ?? ''));
            update_option('libredte_emisor_acteco', sanitize_text_field($emisor['acteco'] ?? ''));
            update_option('libredte_emisor_direccion', sanitize_text_field($emisor['direccion'] ?? ''));
            update_option('libredte_emisor_comuna', sanitize_text_field($emisor['comuna'] ?? ''));
            update_option('libredte_fecha_resolucion', sanitize_text_field($emisor['fecha_resolucion'] ?? ''));
            update_option('libredte_numero_resolucion', intval($emisor['numero_resolucion'] ?? 0));
        }

        // Guardar configuración general
        update_option('libredte_ambiente', sanitize_text_field($_POST['ambiente'] ?? 'certificacion'));
        update_option('libredte_envio_automatico', isset($_POST['envio_automatico']) ? 1 : 0);
        update_option('libredte_rcof_enabled', isset($_POST['rcof_enabled']) ? 1 : 0);
        update_option('libredte_rcof_hora', sanitize_text_field($_POST['rcof_hora'] ?? '23:00'));

        add_settings_error('libredte_messages', 'settings_saved', 'Configuración guardada correctamente.', 'success');
    }

    /**
     * Render Dashboard
     */
    public function render_dashboard() {
        $ambiente = LibreDTE_Boletas::get_ambiente();
        $caf = LibreDTE_Database::get_caf_activo(39, $ambiente);

        // Estadísticas del día
        global $wpdb;
        $table = $wpdb->prefix . 'libredte_boletas';
        $hoy = date('Y-m-d');

        $stats_hoy = $wpdb->get_row($wpdb->prepare(
            "SELECT COUNT(*) as cantidad, COALESCE(SUM(monto_total), 0) as total
             FROM $table WHERE fecha_emision = %s AND ambiente = %s",
            $hoy,
            $ambiente
        ));

        $pendientes = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table WHERE enviado_sii = 0 AND ambiente = %s",
            $ambiente
        ));

        include LIBREDTE_BOLETAS_PATH . 'admin/views/dashboard.php';
    }

    /**
     * Render Nueva Boleta
     */
    public function render_nueva_boleta() {
        $ambiente = LibreDTE_Boletas::get_ambiente();
        $emisor = LibreDTE_Boletas::get_emisor_config();
        $envio_automatico = get_option('libredte_envio_automatico', 0);

        // Obtener siguiente folio
        $siguiente_folio = LibreDTE_Database::get_siguiente_folio(39, $ambiente);

        include LIBREDTE_BOLETAS_PATH . 'admin/views/nueva-boleta.php';
    }

    /**
     * Render Historial
     */
    public function render_historial() {
        $ambiente = LibreDTE_Boletas::get_ambiente();

        $args = [
            'page' => isset($_GET['paged']) ? intval($_GET['paged']) : 1,
            'ambiente' => $ambiente,
            'fecha_desde' => isset($_GET['fecha_desde']) ? sanitize_text_field($_GET['fecha_desde']) : null,
            'fecha_hasta' => isset($_GET['fecha_hasta']) ? sanitize_text_field($_GET['fecha_hasta']) : null,
            'estado' => isset($_GET['estado']) ? sanitize_text_field($_GET['estado']) : null,
        ];

        $historial = LibreDTE_Database::get_historial($args);

        include LIBREDTE_BOLETAS_PATH . 'admin/views/historial.php';
    }

    /**
     * Render Configuración
     */
    public function render_configuracion() {
        $this->save_settings();

        $emisor = LibreDTE_Boletas::get_emisor_config();
        $ambiente = LibreDTE_Boletas::get_ambiente();
        $envio_automatico = get_option('libredte_envio_automatico', 0);
        $rcof_enabled = get_option('libredte_rcof_enabled', 0);
        $rcof_hora = get_option('libredte_rcof_hora', '23:00');

        // Info del certificado
        $cert_file = get_option("libredte_cert_{$ambiente}_file", '');
        $cert_info = null;
        if ($cert_file && file_exists(LIBREDTE_BOLETAS_UPLOADS . 'certs/' . $cert_file)) {
            $sii = new LibreDTE_SII_Client();
            $password = base64_decode(get_option("libredte_cert_{$ambiente}_password", ''));
            $cert_info = $sii->validate_certificate(
                LIBREDTE_BOLETAS_UPLOADS . 'certs/' . $cert_file,
                $password
            );
        }

        include LIBREDTE_BOLETAS_PATH . 'admin/views/configuracion.php';
    }

    /**
     * Render CAF / Folios
     */
    public function render_caf() {
        $ambiente = LibreDTE_Boletas::get_ambiente();
        $caf = LibreDTE_Database::get_caf_activo(39, $ambiente);

        // Listar todos los CAF
        global $wpdb;
        $table = $wpdb->prefix . 'libredte_caf';
        $cafs = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table WHERE ambiente = %s ORDER BY created_at DESC",
            $ambiente
        ));

        include LIBREDTE_BOLETAS_PATH . 'admin/views/caf.php';
    }

    /**
     * Render RCOF
     */
    public function render_rcof() {
        $ambiente = LibreDTE_Boletas::get_ambiente();

        // RCOF solo disponible en producción
        if ($ambiente !== 'produccion') {
            include LIBREDTE_BOLETAS_PATH . 'admin/views/rcof-no-disponible.php';
            return;
        }

        $rcof_enabled = get_option('libredte_rcof_enabled', 0);

        // Obtener boletas del día para resumen
        $hoy = date('Y-m-d');
        $boletas_hoy = LibreDTE_Database::get_boletas_by_date($hoy, 'produccion');

        // Calcular totales
        $totales = [
            'cantidad' => count($boletas_hoy),
            'neto' => 0,
            'iva' => 0,
            'exento' => 0,
            'total' => 0,
        ];

        $folio_min = null;
        $folio_max = null;

        foreach ($boletas_hoy as $boleta) {
            $totales['neto'] += $boleta->monto_neto;
            $totales['iva'] += $boleta->monto_iva;
            $totales['exento'] += $boleta->monto_exento;
            $totales['total'] += $boleta->monto_total;

            if ($folio_min === null || $boleta->folio < $folio_min) {
                $folio_min = $boleta->folio;
            }
            if ($folio_max === null || $boleta->folio > $folio_max) {
                $folio_max = $boleta->folio;
            }
        }

        // RCOF enviado hoy
        $rcof_hoy = LibreDTE_Database::get_rcof_by_date($hoy, 'produccion');

        // Historial de RCOF
        global $wpdb;
        $table = $wpdb->prefix . 'libredte_rcof';
        $rcof_historial = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table WHERE ambiente = %s ORDER BY fecha DESC LIMIT 30",
            'produccion'
        ));

        include LIBREDTE_BOLETAS_PATH . 'admin/views/rcof.php';
    }
}
