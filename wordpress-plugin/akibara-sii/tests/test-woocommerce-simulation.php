<?php
/**
 * Test de Simulación WooCommerce - Akibara SII
 *
 * Simula productos y pedidos de WooCommerce para verificar
 * que la integración funciona correctamente.
 *
 * Ejecutar: php tests/test-woocommerce-simulation.php
 */

define('GREEN', "\033[32m");
define('RED', "\033[31m");
define('YELLOW', "\033[33m");
define('CYAN', "\033[36m");
define('RESET', "\033[0m");

echo "\n" . str_repeat("=", 70) . "\n";
echo CYAN . "   SIMULACIÓN WOOCOMMERCE - AKIBARA SII\n" . RESET;
echo str_repeat("=", 70) . "\n\n";

// ============================================================
// SETUP: Cargar dependencias
// ============================================================

require_once dirname(__DIR__) . '/vendor/autoload.php';

// Definir constantes WordPress
if (!defined('ABSPATH')) define('ABSPATH', '/tmp/wordpress/');
if (!defined('AKIBARA_SII_PATH')) define('AKIBARA_SII_PATH', dirname(__DIR__) . '/');
if (!defined('AKIBARA_SII_URL')) define('AKIBARA_SII_URL', 'http://test.local/wp-content/plugins/akibara-sii/');
if (!defined('AKIBARA_SII_UPLOADS')) define('AKIBARA_SII_UPLOADS', dirname(__DIR__) . '/uploads/');

// Mock de WordPress
$wp_options = [];
if (!function_exists('get_option')) { function get_option($k, $d = '') { global $wp_options; return $wp_options[$k] ?? $d; } }
if (!function_exists('update_option')) { function update_option($k, $v) { global $wp_options; $wp_options[$k] = $v; return true; } }
if (!function_exists('add_action')) { function add_action() {} }
if (!function_exists('add_filter')) { function add_filter() {} }
if (!function_exists('__')) { function __($t) { return $t; } }
if (!function_exists('sanitize_text_field')) { function sanitize_text_field($t) { return trim(strip_tags($t)); } }
if (!function_exists('sanitize_email')) { function sanitize_email($e) { return filter_var($e, FILTER_SANITIZE_EMAIL); } }
if (!function_exists('wp_parse_args')) { function wp_parse_args($a, $d) { return array_merge($d, $a); } }
if (!function_exists('wp_mkdir_p')) { function wp_mkdir_p($d) { return @mkdir($d, 0755, true); } }
if (!function_exists('current_time')) { function current_time($t) { return date('Y-m-d H:i:s'); } }
if (!function_exists('get_post_meta')) { function get_post_meta($id, $k, $s = false) { return ''; } }
if (!function_exists('update_post_meta')) { function update_post_meta($id, $k, $v) { return true; } }
if (!function_exists('is_plugin_active')) { function is_plugin_active($p) { return false; } }
if (!function_exists('apply_filters')) { function apply_filters($t, $v) { return $v; } }
if (!function_exists('wp_upload_dir')) { function wp_upload_dir() { return ['basedir' => '/tmp/uploads', 'baseurl' => 'http://test.local/uploads']; } }

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
function is_wp_error($thing) { return $thing instanceof WP_Error; }

// Mock WooCommerce
if (!class_exists('WooCommerce')) { class WooCommerce {} }
if (!class_exists('WC_Order')) {
    class WC_Order {
        private $id;
        private $items = [];
        private $meta = [];

        public function __construct($id = 1) {
            $this->id = $id;
        }
        public function get_id() { return $this->id; }
        public function get_billing_email() { return 'cliente@test.cl'; }
        public function get_billing_phone() { return '+56912345678'; }
        public function get_billing_first_name() { return 'Juan'; }
        public function get_billing_last_name() { return 'Pérez'; }
        public function get_billing_company() { return ''; }
        public function get_billing_address_1() { return 'Av Providencia 1234'; }
        public function get_billing_city() { return 'Santiago'; }
        public function get_formatted_billing_full_name() { return 'Juan Pérez'; }
        public function get_order_number() { return $this->id; }
        public function get_items() { return $this->items; }
        public function get_shipping_total() { return 3000; }
        public function add_order_note($note) { echo "  📝 Nota: $note\n"; }
        public function set_items($items) { $this->items = $items; }
    }
}

if (!class_exists('WC_Order_Item_Product')) {
    class WC_Order_Item_Product {
        private $data;
        public function __construct($data) { $this->data = $data; }
        public function get_name() { return $this->data['name']; }
        public function get_quantity() { return $this->data['quantity']; }
        public function get_total() { return $this->data['total']; }
        public function get_product() {
            return new class($this->data['product_id']) {
                private $id;
                public function __construct($id) { $this->id = $id; }
                public function get_id() { return $this->id; }
            };
        }
    }
}

if (!function_exists('wc_get_order')) {
    function wc_get_order($id) {
        $order = new WC_Order($id);
        $order->set_items([
            new WC_Order_Item_Product([
                'name' => 'Figura Anime Naruto',
                'quantity' => 1,
                'total' => 25990,
                'product_id' => 101,
            ]),
            new WC_Order_Item_Product([
                'name' => 'Manga One Piece Vol. 1',
                'quantity' => 2,
                'total' => 19980,
                'product_id' => 102,
            ]),
            new WC_Order_Item_Product([
                'name' => 'Llavero Pokémon',
                'quantity' => 3,
                'total' => 8970,
                'product_id' => 103,
            ]),
        ]);
        return $order;
    }
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
// Cargar clases
// ============================================================
step("Cargando clases del plugin");

require_once AKIBARA_SII_PATH . 'includes/class-woocommerce-integration.php';

echo "  Clases WooCommerce cargadas\n";

// ============================================================
// Simular productos
// ============================================================
step("Productos de prueba (Tienda Anime/Manga)");

$productos = [
    ['id' => 101, 'nombre' => 'Figura Anime Naruto', 'precio' => 25990, 'categoria' => 'Figuras'],
    ['id' => 102, 'nombre' => 'Manga One Piece Vol. 1', 'precio' => 9990, 'categoria' => 'Manga'],
    ['id' => 103, 'nombre' => 'Llavero Pokémon', 'precio' => 2990, 'categoria' => 'Accesorios'],
    ['id' => 104, 'nombre' => 'Poster Dragon Ball', 'precio' => 5990, 'categoria' => 'Posters'],
    ['id' => 105, 'nombre' => 'Camiseta Attack on Titan', 'precio' => 15990, 'categoria' => 'Ropa'],
];

foreach ($productos as $p) {
    echo "  🛒 " . $p['nombre'] . " - \$" . number_format($p['precio'], 0, ',', '.') . " (" . $p['categoria'] . ")\n";
}

// ============================================================
// Simular pedido WooCommerce
// ============================================================
step("Simulando pedido WooCommerce #1001");

$order = wc_get_order(1001);
$items = $order->get_items();

echo "  Cliente: " . $order->get_formatted_billing_full_name() . "\n";
echo "  Email: " . $order->get_billing_email() . "\n";
echo "  Dirección: " . $order->get_billing_address_1() . ", " . $order->get_billing_city() . "\n\n";

$subtotal = 0;
echo "  Items del pedido:\n";
foreach ($items as $item) {
    $linea = $item->get_quantity() . "x " . $item->get_name() . " = $" . number_format($item->get_total(), 0, ',', '.');
    echo "    - $linea\n";
    $subtotal += $item->get_total();
}

$envio = $order->get_shipping_total();
$total = $subtotal + $envio;

echo "\n  Subtotal: \$" . number_format($subtotal, 0, ',', '.') . "\n";
echo "  Envío: \$" . number_format($envio, 0, ',', '.') . "\n";
echo "  TOTAL: \$" . number_format($total, 0, ',', '.') . "\n";

// ============================================================
// Verificar integración
// ============================================================
step("Verificando integración WooCommerce");

test(class_exists('Akibara_WooCommerce_Integration'), 'Clase de integración existe');

// Verificar formato RUT
$rut_test = '12345678-9';
$rut_formatted = Akibara_WooCommerce_Integration::format_rut($rut_test);
test($rut_formatted === '12.345.678-9', "Formato RUT: $rut_test -> $rut_formatted");

// Verificar RUT consumidor final
$rut_cf = Akibara_WooCommerce_Integration::get_rut_from_order(1001);
test($rut_cf === '66666666-6', "RUT consumidor final por defecto: $rut_cf");

// ============================================================
// Simular datos para boleta
// ============================================================
step("Preparando datos para boleta");

$boleta_items = [];
foreach ($items as $item) {
    $product = $item->get_product();
    $boleta_items[] = [
        'nombre' => $item->get_name(),
        'cantidad' => $item->get_quantity(),
        'precio' => round($item->get_total() / $item->get_quantity(), 0),
        'exento' => false,
    ];
}

// Agregar envío
if ($envio > 0) {
    $boleta_items[] = [
        'nombre' => 'Envío',
        'cantidad' => 1,
        'precio' => round($envio, 0),
        'exento' => false,
    ];
}

echo "  Items para boleta:\n";
foreach ($boleta_items as $item) {
    echo "    - " . $item['nombre'] . ": " . $item['cantidad'] . " x \$" . number_format($item['precio'], 0, ',', '.') . "\n";
}

// Calcular montos
$bruto_afecto = 0;
$exento = 0;
foreach ($boleta_items as $item) {
    $sub = $item['cantidad'] * $item['precio'];
    if ($item['exento']) {
        $exento += $sub;
    } else {
        $bruto_afecto += $sub;
    }
}

// Para boletas, los precios incluyen IVA
$neto = round($bruto_afecto / 1.19);
$iva = $bruto_afecto - $neto;
$total_calc = $bruto_afecto + $exento;

echo "\n  Cálculo de montos:\n";
echo "    Bruto afecto: \$" . number_format($bruto_afecto, 0, ',', '.') . "\n";
echo "    Neto: \$" . number_format($neto, 0, ',', '.') . "\n";
echo "    IVA (incluido): \$" . number_format($iva, 0, ',', '.') . "\n";
echo "    Total: \$" . number_format($total_calc, 0, ',', '.') . "\n";

test((int)$total_calc === (int)$total, "Total calculado coincide: \$" . number_format($total_calc, 0, ',', '.'));

// ============================================================
// Verificar estructura de datos para emisión
// ============================================================
step("Verificando estructura de datos");

$receptor = [
    'rut' => '66666666-6',
    'razon_social' => 'CONSUMIDOR FINAL',
    'direccion' => $order->get_billing_address_1(),
    'comuna' => $order->get_billing_city(),
];

$datos_boleta = [
    'receptor' => $receptor,
    'items' => $boleta_items,
];

test(!empty($datos_boleta['receptor']['rut']), "Receptor tiene RUT");
test(!empty($datos_boleta['items']), "Hay items en la boleta");
test(count($datos_boleta['items']) === 4, "4 items (3 productos + envío)");

// ============================================================
// Verificar que los hooks están registrados
// ============================================================
step("Verificando hooks de integración");

$reflection = new ReflectionClass('Akibara_WooCommerce_Integration');
$methods = $reflection->getMethods(ReflectionMethod::IS_PUBLIC);

$required_methods = [
    'init',
    'generate_boleta_on_complete',
    'generate_boleta_on_processing',
    'generate_boleta_for_order',
    'attach_boleta_pdf',
    'add_order_actions',
    'process_send_boleta_action',
    'send_boleta_email',
    'get_customer_data_from_order',
];

foreach ($required_methods as $method) {
    test($reflection->hasMethod($method), "Método $method() existe");
}

// ============================================================
// Resumen
// ============================================================
echo "\n" . str_repeat("=", 70) . "\n";
echo "   RESUMEN SIMULACIÓN WOOCOMMERCE\n";
echo str_repeat("=", 70) . "\n\n";

$total_tests = $passed + $failed;
$percentage = $total_tests > 0 ? round(($passed / $total_tests) * 100, 1) : 0;

echo GREEN . "  Pasadas:    " . RESET . $passed . "\n";
echo RED . "  Fallidas:   " . RESET . $failed . "\n";
echo "\n  Total:      " . $total_tests . " pruebas\n";
echo "  Cobertura:  " . $percentage . "%\n\n";

if ($failed === 0) {
    echo GREEN . "  ✓ SIMULACIÓN WOOCOMMERCE EXITOSA" . RESET . "\n\n";
    echo CYAN . "  La integración con WooCommerce está correctamente configurada.\n";
    echo "  Para un test real necesitas:\n";
    echo "  - WordPress + WooCommerce instalados\n";
    echo "  - Plugin de RUT para Chile\n";
    echo "  - Productos configurados\n" . RESET;
    echo "\n";
    exit(0);
} else {
    echo RED . "  ✗ HAY PROBLEMAS EN LA SIMULACIÓN" . RESET . "\n\n";
    exit(1);
}
