<?php
/**
 * Clase para envío de emails con boletas PDF
 * Funciona en certificación y producción
 */

if (!defined('ABSPATH')) exit;

class Akibara_Email_Boleta {

    /**
     * Generar PDF de boleta
     *
     * Genera el PDF en formato voucher SII e-boleta (80mm) con:
     * - Código de barras PDF417 (usando TCPDF2DBarcode como LibreDTE)
     * - Formato compacto para impresión térmica
     *
     * El PDF se guarda en AKIBARA_SII_UPLOADS/pdf/ y persiste para:
     * - Adjuntar a emails
     * - Permitir descargas posteriores
     * - Reenvíos al cliente
     *
     * @param int $boleta_id ID de la boleta
     * @return string|false Ruta del PDF o false si falla
     */
    public static function generar_pdf($boleta_id) {
        $boleta = Akibara_Database::get_boleta($boleta_id);
        if (!$boleta) {
            return false;
        }

        // Directorio de PDFs
        $pdf_dir = AKIBARA_SII_UPLOADS . 'pdf/';
        if (!file_exists($pdf_dir)) {
            wp_mkdir_p($pdf_dir);
        }

        $pdf_path = $pdf_dir . 'boleta_' . $boleta->folio . '_' . $boleta->ambiente . '.pdf';

        // Si ya existe, retornarlo
        if (file_exists($pdf_path)) {
            return $pdf_path;
        }

        try {
            // Usar el método centralizado de class-sii-client que genera formato voucher
            $sii_client = new Akibara_SII_Client();
            $pdfContent = $sii_client->generar_pdf($boleta->xml_documento);

            if (is_wp_error($pdfContent)) {
                error_log('Akibara SII: Error generando PDF - ' . $pdfContent->get_error_message());
                return false;
            }

            // Guardar archivo
            file_put_contents($pdf_path, $pdfContent);

            return $pdf_path;

        } catch (Exception $e) {
            error_log('Akibara SII: Error generando PDF - ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Enviar boleta por email al cliente
     *
     * @param int $boleta_id ID de la boleta en BD
     * @param string $email Email del destinatario
     * @param array $options Opciones adicionales (subject_prefix, mensaje_extra)
     * @return bool
     */
    public static function enviar($boleta_id, $email, $options = []) {
        $boleta = Akibara_Database::get_boleta($boleta_id);
        if (!$boleta) {
            error_log('Akibara SII Email: Boleta no encontrada - ID ' . $boleta_id);
            return false;
        }

        // Generar PDF
        $pdf_path = self::generar_pdf($boleta_id);

        // Datos del emisor
        $emisor_nombre = get_option('akibara_emisor_razon_social', 'Empresa');
        $emisor_rut = get_option('akibara_emisor_rut', '');

        // Asunto del email
        $subject_prefix = $options['subject_prefix'] ?? 'Boleta Electronica';
        $subject = "$subject_prefix N° {$boleta->folio} - $emisor_nombre";

        // Construir cuerpo del email
        $body = self::construir_body_email($boleta, $emisor_nombre, $emisor_rut, $options);

        // Headers
        $headers = [
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . $emisor_nombre . ' <' . get_option('admin_email') . '>',
        ];

        // Adjuntos
        $attachments = [];
        if ($pdf_path && file_exists($pdf_path)) {
            $attachments[] = $pdf_path;
        }

        // Enviar
        $enviado = wp_mail($email, $subject, $body, $headers, $attachments);

        if ($enviado) {
            // Registrar envío en la base de datos
            self::registrar_envio($boleta_id, $email);
        }

        return $enviado;
    }

    /**
     * Construir cuerpo HTML del email
     */
    private static function construir_body_email($boleta, $emisor_nombre, $emisor_rut, $options = []) {
        $mensaje_extra = $options['mensaje_extra'] ?? '';
        $ambiente = $boleta->ambiente === 'certificacion' ? ' (CERTIFICACION)' : '';

        $body = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #2c3e50; color: white; padding: 20px; text-align: center; }
        .content { padding: 20px; background: #f9f9f9; }
        .boleta-info { background: white; padding: 15px; border-radius: 5px; margin: 15px 0; }
        .boleta-info table { width: 100%; border-collapse: collapse; }
        .boleta-info td { padding: 8px; border-bottom: 1px solid #eee; }
        .boleta-info td:first-child { font-weight: bold; width: 40%; }
        .footer { text-align: center; padding: 20px; font-size: 12px; color: #666; }
        .total { font-size: 18px; font-weight: bold; color: #27ae60; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>' . esc_html($emisor_nombre) . '</h1>
            <p>RUT: ' . esc_html($emisor_rut) . '</p>
        </div>
        <div class="content">
            <h2>Boleta Electronica N° ' . esc_html($boleta->folio) . $ambiente . '</h2>

            <div class="boleta-info">
                <table>
                    <tr>
                        <td>Folio:</td>
                        <td>' . esc_html($boleta->folio) . '</td>
                    </tr>
                    <tr>
                        <td>Fecha Emision:</td>
                        <td>' . date('d/m/Y H:i', strtotime($boleta->fecha_emision)) . '</td>
                    </tr>
                    <tr>
                        <td>Monto Neto:</td>
                        <td>$' . number_format($boleta->monto_neto, 0, ',', '.') . '</td>
                    </tr>';

        if ($boleta->monto_exento > 0) {
            $body .= '
                    <tr>
                        <td>Monto Exento:</td>
                        <td>$' . number_format($boleta->monto_exento, 0, ',', '.') . '</td>
                    </tr>';
        }

        $body .= '
                    <tr>
                        <td>IVA (19%):</td>
                        <td>$' . number_format($boleta->monto_iva, 0, ',', '.') . '</td>
                    </tr>
                    <tr>
                        <td class="total">Total:</td>
                        <td class="total">$' . number_format($boleta->monto_total, 0, ',', '.') . '</td>
                    </tr>
                </table>
            </div>';

        if ($mensaje_extra) {
            $body .= '<p>' . esc_html($mensaje_extra) . '</p>';
        }

        $body .= '
            <p>Adjunto encontrara su boleta electronica en formato PDF.</p>
            <p>Este documento tributario electronico ha sido emitido segun la normativa del SII.</p>
        </div>
        <div class="footer">
            <p>Este es un correo automatico, por favor no responda a este mensaje.</p>
            <p>' . esc_html($emisor_nombre) . ' - ' . date('Y') . '</p>
        </div>
    </div>
</body>
</html>';

        return $body;
    }

    /**
     * Registrar envío de email en la base de datos
     */
    private static function registrar_envio($boleta_id, $email) {
        global $wpdb;

        // Actualizar boleta con fecha de envío
        $wpdb->update(
            $wpdb->prefix . 'akibara_boletas',
            [
                'email_enviado' => current_time('mysql'),
                'email_destinatario' => $email,
            ],
            ['id' => $boleta_id],
            ['%s', '%s'],
            ['%d']
        );
    }

    /**
     * Enviar boleta desde emisión manual (admin)
     */
    public static function enviar_manual($boleta_id, $email) {
        return self::enviar($boleta_id, $email, [
            'subject_prefix' => 'Boleta Electronica',
            'mensaje_extra' => 'Gracias por su preferencia.',
        ]);
    }

    /**
     * Enviar boleta desde WooCommerce (compra)
     */
    public static function enviar_woocommerce($boleta_id, $order_id) {
        $order = wc_get_order($order_id);
        if (!$order) {
            return false;
        }

        $email = $order->get_billing_email();
        if (!$email) {
            return false;
        }

        return self::enviar($boleta_id, $email, [
            'subject_prefix' => 'Boleta de su compra',
            'mensaje_extra' => 'Gracias por su compra en nuestra tienda. Pedido #' . $order_id,
        ]);
    }

    /**
     * Reenviar boleta
     */
    public static function reenviar($boleta_id, $email) {
        return self::enviar($boleta_id, $email, [
            'subject_prefix' => 'Reenvio - Boleta Electronica',
        ]);
    }
}
