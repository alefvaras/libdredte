<?php
/**
 * Integración con WooCommerce
 * - Lectura de RUT desde plugin existente
 * - Generación automática de boletas
 * - Envío de PDF por email con WooCommerce mailer (wp_mail)
 */

defined('ABSPATH') || exit;

class Akibara_WooCommerce_Integration {

    /**
     * Meta key donde el plugin de RUT guarda el valor
     * Configurar según el plugin instalado
     */
    const RUT_META_KEY = '_billing_rut'; // Cambiar si tu plugin usa otro meta key

    /**
     * Inicializar hooks
     */
    public static function init() {
        // Solo si WooCommerce está activo
        if (!class_exists('WooCommerce')) {
            return;
        }

        // Generar boleta automáticamente al completar pedido
        add_action('woocommerce_order_status_completed', [__CLASS__, 'generate_boleta_on_complete'], 10, 1);
        add_action('woocommerce_order_status_processing', [__CLASS__, 'generate_boleta_on_processing'], 10, 1);

        // Adjuntar PDF a email de pedido completado
        add_filter('woocommerce_email_attachments', [__CLASS__, 'attach_boleta_pdf'], 10, 4);

        // Agregar acción manual en admin
        add_action('woocommerce_order_actions', [__CLASS__, 'add_order_actions']);
        add_action('woocommerce_order_action_send_boleta_email', [__CLASS__, 'process_send_boleta_action']);
    }

    /**
     * Obtener RUT del pedido (desde plugin externo)
     */
    public static function get_rut_from_order($order_id) {
        // Intentar varios meta keys comunes
        $meta_keys = [
            self::RUT_META_KEY,
            '_billing_rut',
            'billing_rut',
            '_rut',
            'rut',
            '_billing_rut_number',
            '_wooccm11', // Checkout Field Editor
        ];

        foreach ($meta_keys as $key) {
            $rut = get_post_meta($order_id, $key, true);
            if (!empty($rut)) {
                return self::format_rut($rut);
            }
        }

        return '66666666-6'; // Consumidor Final por defecto
    }

    /**
     * Formatear RUT chileno
     */
    public static function format_rut($rut) {
        $rut = preg_replace('/[^0-9kK]/', '', strtoupper($rut));

        if (strlen($rut) < 2) {
            return $rut;
        }

        $dv = substr($rut, -1);
        $numero = substr($rut, 0, -1);

        return number_format((int)$numero, 0, '', '.') . '-' . $dv;
    }

    /**
     * Generar boleta al completar pedido
     */
    public static function generate_boleta_on_complete($order_id) {
        self::generate_boleta_for_order($order_id);
    }

    /**
     * Generar boleta al procesar pedido (opcional)
     */
    public static function generate_boleta_on_processing($order_id) {
        $auto_generate = get_option('akibara_auto_boleta_processing', 0);
        if ($auto_generate) {
            self::generate_boleta_for_order($order_id);
        }
    }

    /**
     * Generar boleta para un pedido
     */
    public static function generate_boleta_for_order($order_id) {
        // Verificar si ya tiene boleta
        $boleta_id = get_post_meta($order_id, '_akibara_boleta_id', true);
        if ($boleta_id) {
            return $boleta_id; // Ya tiene boleta
        }

        // Verificar si generación automática está habilitada
        $auto_generate = get_option('akibara_auto_boleta', 1);
        if (!$auto_generate) {
            return false;
        }

        $order = wc_get_order($order_id);
        if (!$order) {
            return false;
        }

        // Obtener RUT del cliente
        $rut = get_post_meta($order_id, '_billing_rut', true);

        // Preparar datos del receptor
        $receptor = [
            'rut' => $rut ?: '66666666-6',
            'razon_social' => $rut ? $order->get_billing_company() ?: $order->get_formatted_billing_full_name() : 'CONSUMIDOR FINAL',
            'direccion' => $order->get_billing_address_1(),
            'comuna' => $order->get_billing_city(),
        ];

        // Preparar items
        $items = [];
        foreach ($order->get_items() as $item) {
            $product = $item->get_product();
            $items[] = [
                'nombre' => $item->get_name(),
                'cantidad' => $item->get_quantity(),
                'precio' => round($item->get_total() / $item->get_quantity(), 0),
                'exento' => self::is_product_exempt($product),
            ];
        }

        // Agregar envío si tiene costo
        $shipping_total = $order->get_shipping_total();
        if ($shipping_total > 0) {
            $items[] = [
                'nombre' => 'Envío',
                'cantidad' => 1,
                'precio' => round($shipping_total, 0),
                'exento' => false,
            ];
        }

        // Generar boleta
        $boleta = new Akibara_Boleta();
        $result = $boleta->emitir([
            'receptor' => $receptor,
            'items' => $items,
        ]);

        if (is_wp_error($result)) {
            // Log error
            $order->add_order_note(
                sprintf(__('Error generando boleta: %s', 'akibara-sii'), $result->get_error_message())
            );
            return false;
        }

        // Guardar referencia
        update_post_meta($order_id, '_akibara_boleta_id', $result['id']);
        update_post_meta($order_id, '_akibara_boleta_folio', $result['folio']);

        // Agregar nota al pedido
        $order->add_order_note(
            sprintf(__('Boleta electrónica generada - Folio: %d', 'akibara-sii'), $result['folio'])
        );

        return $result['id'];
    }

    /**
     * Verificar si un producto es exento de IVA
     */
    private static function is_product_exempt($product) {
        if (!$product) {
            return false;
        }

        // Verificar meta personalizado
        $exempt = get_post_meta($product->get_id(), '_akibara_exento', true);
        if ($exempt === 'yes') {
            return true;
        }

        // Verificar categoría exenta
        $exempt_categories = get_option('akibara_exempt_categories', []);
        if (!empty($exempt_categories)) {
            $product_cats = wp_get_post_terms($product->get_id(), 'product_cat', ['fields' => 'ids']);
            if (array_intersect($product_cats, $exempt_categories)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Adjuntar PDF de boleta a emails de WooCommerce
     */
    public static function attach_boleta_pdf($attachments, $email_id, $order, $email = null) {
        // Solo adjuntar en ciertos emails
        $allowed_emails = ['customer_completed_order', 'customer_invoice', 'customer_processing_order'];

        if (!in_array($email_id, $allowed_emails)) {
            return $attachments;
        }

        if (!$order || !is_a($order, 'WC_Order')) {
            return $attachments;
        }

        // Verificar si tiene boleta
        $boleta_id = get_post_meta($order->get_id(), '_akibara_boleta_id', true);
        if (!$boleta_id) {
            return $attachments;
        }

        // Generar PDF
        $pdf_path = self::generate_pdf_file($boleta_id);
        if ($pdf_path && file_exists($pdf_path)) {
            $attachments[] = $pdf_path;
        }

        return $attachments;
    }

    /**
     * Generar archivo PDF de boleta
     */
    public static function generate_pdf_file($boleta_id) {
        $boleta = Akibara_Database::get_boleta($boleta_id);
        if (!$boleta) {
            return false;
        }

        // Directorio de PDFs
        $upload_dir = wp_upload_dir();
        $pdf_dir = $upload_dir['basedir'] . '/akibara-sii/pdf/';

        if (!file_exists($pdf_dir)) {
            wp_mkdir_p($pdf_dir);
        }

        $pdf_path = $pdf_dir . 'boleta_' . $boleta->folio . '.pdf';

        // Si ya existe, retornarlo
        if (file_exists($pdf_path)) {
            return $pdf_path;
        }

        // Generar PDF usando LibreDTE
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
            error_log('Akibara SII: Error generando PDF - ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Agregar acciones personalizadas en pedidos
     */
    public static function add_order_actions($actions) {
        $actions['send_boleta_email'] = __('Enviar boleta por email', 'akibara-sii');
        return $actions;
    }

    /**
     * Procesar acción de enviar boleta por email
     */
    public static function process_send_boleta_action($order) {
        $order_id = $order->get_id();
        $boleta_id = get_post_meta($order_id, '_akibara_boleta_id', true);

        if (!$boleta_id) {
            // Intentar generar boleta primero
            $boleta_id = self::generate_boleta_for_order($order_id);
        }

        if (!$boleta_id) {
            $order->add_order_note(__('No se pudo generar la boleta para enviar.', 'akibara-sii'));
            return;
        }

        // Enviar email con boleta
        $sent = self::send_boleta_email($order_id, $boleta_id);

        if ($sent) {
            $order->add_order_note(__('Boleta enviada por email al cliente.', 'akibara-sii'));
        } else {
            $order->add_order_note(__('Error al enviar boleta por email.', 'akibara-sii'));
        }
    }

    /**
     * Enviar boleta por email usando wp_mail (WooCommerce)
     */
    public static function send_boleta_email($order_id, $boleta_id) {
        $order = wc_get_order($order_id);
        $boleta = Akibara_Database::get_boleta($boleta_id);

        if (!$order || !$boleta) {
            return false;
        }

        $email = $order->get_billing_email();
        if (!$email) {
            return false;
        }

        // Generar PDF
        $pdf_path = self::generate_pdf_file($boleta_id);
        $attachments = [];
        if ($pdf_path && file_exists($pdf_path)) {
            $attachments[] = $pdf_path;
        }

        // Preparar email
        $emisor = Akibara_Boletas::get_emisor_config();
        $subject = sprintf(
            __('Boleta Electrónica N° %d - %s', 'akibara-sii'),
            $boleta->folio,
            $emisor['RznSoc'] ?? 'AKIBARA SPA'
        );

        $message = self::get_email_template($order, $boleta);

        // Headers para HTML
        $headers = [
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . ($emisor['RznSoc'] ?? 'AKIBARA SPA') . ' <' . get_option('admin_email') . '>',
        ];

        // Enviar usando wp_mail (WooCommerce)
        $sent = wp_mail($email, $subject, $message, $headers, $attachments);

        if ($sent) {
            // Marcar como enviado
            update_post_meta($order_id, '_akibara_boleta_email_sent', current_time('mysql'));
        }

        return $sent;
    }

    /**
     * Obtener template de email
     */
    private static function get_email_template($order, $boleta) {
        $emisor = Akibara_Boletas::get_emisor_config();

        ob_start();
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #0073aa; color: white; padding: 20px; text-align: center; }
                .content { padding: 20px; background: #f9f9f9; }
                .footer { padding: 20px; text-align: center; font-size: 12px; color: #666; }
                .details { background: white; padding: 15px; margin: 15px 0; border-radius: 5px; }
                .details table { width: 100%; }
                .details td { padding: 5px 0; }
                .details td:first-child { font-weight: bold; width: 40%; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1><?php echo esc_html($emisor['RznSoc'] ?? 'AKIBARA SPA'); ?></h1>
                </div>
                <div class="content">
                    <p>Estimado/a <?php echo esc_html($order->get_formatted_billing_full_name()); ?>,</p>

                    <p>Adjunto encontrará su Boleta Electrónica correspondiente a su compra.</p>

                    <div class="details">
                        <h3>Detalles de la Boleta</h3>
                        <table>
                            <tr>
                                <td>Tipo de Documento:</td>
                                <td>Boleta Electrónica</td>
                            </tr>
                            <tr>
                                <td>Folio:</td>
                                <td><?php echo esc_html($boleta->folio); ?></td>
                            </tr>
                            <tr>
                                <td>Fecha de Emisión:</td>
                                <td><?php echo esc_html(date('d/m/Y', strtotime($boleta->fecha_emision))); ?></td>
                            </tr>
                            <tr>
                                <td>Total:</td>
                                <td>$<?php echo number_format($boleta->monto_total, 0, ',', '.'); ?></td>
                            </tr>
                            <tr>
                                <td>N° Pedido:</td>
                                <td>#<?php echo esc_html($order->get_order_number()); ?></td>
                            </tr>
                        </table>
                    </div>

                    <p>Puede verificar la validez de este documento en <a href="https://www.sii.cl">www.sii.cl</a></p>

                    <p>Gracias por su preferencia.</p>

                    <p>Saludos cordiales,<br>
                    <strong><?php echo esc_html($emisor['RznSoc'] ?? 'AKIBARA SPA'); ?></strong></p>
                </div>
                <div class="footer">
                    <p><?php echo esc_html($emisor['DirOrigen'] ?? ''); ?>, <?php echo esc_html($emisor['CmnaOrigen'] ?? ''); ?></p>
                    <p>RUT: <?php echo esc_html($emisor['RUTEmisor'] ?? ''); ?></p>
                </div>
            </div>
        </body>
        </html>
        <?php
        return ob_get_clean();
    }

    /**
     * Obtener datos del cliente desde un pedido
     */
    public static function get_customer_data_from_order($order_id) {
        $order = wc_get_order($order_id);
        if (!$order) {
            return null;
        }

        $rut = get_post_meta($order_id, '_billing_rut', true);

        return [
            'rut' => $rut ?: '66666666-6',
            'razon_social' => $rut ? ($order->get_billing_company() ?: $order->get_formatted_billing_full_name()) : 'CONSUMIDOR FINAL',
            'direccion' => $order->get_billing_address_1(),
            'comuna' => $order->get_billing_city(),
            'email' => $order->get_billing_email(),
            'telefono' => $order->get_billing_phone(),
        ];
    }
}

// Inicializar
add_action('plugins_loaded', ['Akibara_WooCommerce_Integration', 'init']);
