<?php
/**
 * Sistema de notificaciones de folios
 * - Envío de email cuando quedan pocos folios
 * - Verificación periódica via WP Cron
 */

defined('ABSPATH') || exit;

class Akibara_Folio_Notifications {

    /**
     * Hook name para cron
     */
    const CRON_HOOK = 'akibara_check_folios';

    /**
     * Opción para evitar spam de emails
     */
    const LAST_NOTIFICATION_OPTION = 'akibara_last_folio_notification';

    /**
     * Inicializar hooks
     */
    public static function init() {
        // Programar cron si no existe
        add_action('init', [__CLASS__, 'schedule_cron']);

        // Hook del cron
        add_action(self::CRON_HOOK, [__CLASS__, 'check_and_notify']);

        // Hook cuando se emite una boleta (verificación inmediata)
        add_action('akibara_boleta_emitida', [__CLASS__, 'check_after_emission']);

        // Widget de dashboard de WordPress
        add_action('wp_dashboard_setup', [__CLASS__, 'add_dashboard_widget']);
    }

    /**
     * Programar verificación diaria
     */
    public static function schedule_cron() {
        if (!wp_next_scheduled(self::CRON_HOOK)) {
            wp_schedule_event(time(), 'daily', self::CRON_HOOK);
        }
    }

    /**
     * Desprogramar cron al desactivar plugin
     */
    public static function unschedule_cron() {
        $timestamp = wp_next_scheduled(self::CRON_HOOK);
        if ($timestamp) {
            wp_unschedule_event($timestamp, self::CRON_HOOK);
        }
    }

    /**
     * Verificar folios y enviar notificación si es necesario
     */
    public static function check_and_notify() {
        $folio_info = self::get_folio_status();

        if (!$folio_info['has_caf']) {
            return;
        }

        $threshold = (int) get_option('akibara_folio_alert_threshold', 50);
        $notifications_enabled = get_option('akibara_folio_notifications', 0);

        if (!$notifications_enabled) {
            return;
        }

        if ($folio_info['disponibles'] <= $threshold) {
            self::send_notification($folio_info);
        }
    }

    /**
     * Verificar después de emitir una boleta
     */
    public static function check_after_emission() {
        $folio_info = self::get_folio_status();

        if (!$folio_info['has_caf']) {
            return;
        }

        $threshold = (int) get_option('akibara_folio_alert_threshold', 50);
        $notifications_enabled = get_option('akibara_folio_notifications', 0);

        // Verificar umbrales críticos (10, 25, 50)
        $critical_thresholds = [10, 25, 50];

        foreach ($critical_thresholds as $critical) {
            if ($folio_info['disponibles'] == $critical && $notifications_enabled) {
                self::send_notification($folio_info, true);
                break;
            }
        }
    }

    /**
     * Obtener estado de folios
     */
    public static function get_folio_status() {
        global $wpdb;
        $table_caf = $wpdb->prefix . 'akibara_caf';
        $ambiente = get_option('akibara_ambiente', 'certificacion');

        $caf_activo = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_caf WHERE tipo_dte = 39 AND ambiente = %s AND activo = 1 ORDER BY folio_desde DESC LIMIT 1",
            $ambiente
        ));

        if (!$caf_activo) {
            return [
                'has_caf' => false,
                'disponibles' => 0,
                'folio_actual' => 0,
                'folio_hasta' => 0,
                'porcentaje_usado' => 100,
                'caf' => null,
            ];
        }

        $folio_actual = $caf_activo->folio_actual;
        $disponibles = $caf_activo->folio_hasta - $folio_actual + 1;
        $rango_total = $caf_activo->folio_hasta - $caf_activo->folio_desde + 1;
        $usados = $folio_actual - $caf_activo->folio_desde;
        $porcentaje = $rango_total > 0 ? round(($usados / $rango_total) * 100, 1) : 0;

        return [
            'has_caf' => true,
            'disponibles' => max(0, $disponibles),
            'folio_actual' => $folio_actual,
            'folio_hasta' => $caf_activo->folio_hasta,
            'folio_desde' => $caf_activo->folio_desde,
            'rango_total' => $rango_total,
            'usados' => $usados,
            'porcentaje_usado' => $porcentaje,
            'caf' => $caf_activo,
        ];
    }

    /**
     * Enviar notificación por email
     */
    public static function send_notification($folio_info, $immediate = false) {
        // Evitar spam: no enviar más de una vez cada 24 horas (salvo inmediato en umbrales críticos)
        if (!$immediate) {
            $last_notification = get_option(self::LAST_NOTIFICATION_OPTION, 0);
            $hours_since_last = (time() - $last_notification) / 3600;

            if ($hours_since_last < 24) {
                return false;
            }
        }

        $email_recipient = get_option('akibara_folio_notification_email', get_option('admin_email'));
        $emisor = Akibara_SII::get_emisor_config();
        $ambiente = get_option('akibara_ambiente', 'certificacion');

        $subject = sprintf(
            '[%s] Alerta: Quedan %d folios disponibles',
            $emisor['RznSoc'] ?: 'Akibara SII',
            $folio_info['disponibles']
        );

        $message = self::get_email_template($folio_info, $emisor, $ambiente);

        $headers = [
            'Content-Type: text/html; charset=UTF-8',
            'From: Akibara SII <' . get_option('admin_email') . '>',
        ];

        $sent = wp_mail($email_recipient, $subject, $message, $headers);

        if ($sent) {
            update_option(self::LAST_NOTIFICATION_OPTION, time());

            // Log
            Akibara_Database::log('folio_notification', sprintf(
                'Notificacion enviada: %d folios disponibles',
                $folio_info['disponibles']
            ));
        }

        return $sent;
    }

    /**
     * Template del email de notificación
     */
    private static function get_email_template($folio_info, $emisor, $ambiente) {
        $urgencia = 'normal';
        if ($folio_info['disponibles'] <= 10) {
            $urgencia = 'critica';
            $color = '#dc2626';
        } elseif ($folio_info['disponibles'] <= 25) {
            $urgencia = 'alta';
            $color = '#d97706';
        } else {
            $color = '#2563eb';
        }

        $sii_url = 'https://www.sii.cl/servicios_online/1039-1183.html';

        ob_start();
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: <?php echo $color; ?>; color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
                .content { padding: 25px; background: #f9fafb; border: 1px solid #e5e7eb; }
                .alert-box { background: white; padding: 20px; border-radius: 8px; margin: 15px 0; border-left: 4px solid <?php echo $color; ?>; }
                .stats { display: flex; justify-content: space-around; margin: 20px 0; }
                .stat { text-align: center; padding: 15px; }
                .stat-value { font-size: 32px; font-weight: bold; color: <?php echo $color; ?>; display: block; }
                .stat-label { color: #6b7280; font-size: 12px; }
                .button { display: inline-block; padding: 12px 24px; background: <?php echo $color; ?>; color: white; text-decoration: none; border-radius: 6px; margin: 10px 5px; }
                .footer { padding: 20px; text-align: center; font-size: 12px; color: #6b7280; background: #f3f4f6; border-radius: 0 0 8px 8px; }
                .ambiente { display: inline-block; padding: 4px 12px; background: <?php echo $ambiente === 'produccion' ? '#059669' : '#6366f1'; ?>; color: white; border-radius: 4px; font-size: 11px; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1 style="margin:0;">Alerta de Folios</h1>
                    <p style="margin:10px 0 0;">Prioridad: <?php echo strtoupper($urgencia); ?></p>
                </div>
                <div class="content">
                    <div class="alert-box">
                        <h2 style="margin-top:0;color:<?php echo $color; ?>;">Folios por agotarse</h2>
                        <p>Los folios CAF para <strong>Boleta Electronica (Tipo 39)</strong> estan por agotarse.</p>
                    </div>

                    <div class="stats">
                        <div class="stat">
                            <span class="stat-value"><?php echo number_format($folio_info['disponibles']); ?></span>
                            <span class="stat-label">Folios Disponibles</span>
                        </div>
                        <div class="stat">
                            <span class="stat-value"><?php echo $folio_info['porcentaje_usado']; ?>%</span>
                            <span class="stat-label">CAF Usado</span>
                        </div>
                    </div>

                    <div class="alert-box">
                        <h3 style="margin-top:0;">Detalles del CAF Activo</h3>
                        <table style="width:100%;">
                            <tr>
                                <td><strong>Empresa:</strong></td>
                                <td><?php echo esc_html($emisor['RznSoc']); ?></td>
                            </tr>
                            <tr>
                                <td><strong>RUT:</strong></td>
                                <td><?php echo esc_html($emisor['RUTEmisor']); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Ambiente:</strong></td>
                                <td><span class="ambiente"><?php echo strtoupper($ambiente); ?></span></td>
                            </tr>
                            <tr>
                                <td><strong>Rango CAF:</strong></td>
                                <td><?php echo $folio_info['folio_desde']; ?> - <?php echo $folio_info['folio_hasta']; ?></td>
                            </tr>
                            <tr>
                                <td><strong>Proximo folio:</strong></td>
                                <td><?php echo $folio_info['folio_actual']; ?></td>
                            </tr>
                        </table>
                    </div>

                    <div style="text-align:center;margin-top:25px;">
                        <p><strong>Acciones recomendadas:</strong></p>
                        <a href="<?php echo $sii_url; ?>" class="button" target="_blank">Solicitar CAF en SII</a>
                        <a href="<?php echo admin_url('admin.php?page=akibara-caf'); ?>" class="button" style="background:#6b7280;">Subir CAF</a>
                    </div>
                </div>
                <div class="footer">
                    <p>Este es un mensaje automatico de Akibara SII.</p>
                    <p>Puedes desactivar estas notificaciones en Configuracion > Opciones.</p>
                </div>
            </div>
        </body>
        </html>
        <?php
        return ob_get_clean();
    }

    /**
     * Agregar widget al dashboard de WordPress
     */
    public static function add_dashboard_widget() {
        if (!current_user_can('manage_options')) {
            return;
        }

        wp_add_dashboard_widget(
            'akibara_folios_widget',
            'Akibara SII - Estado de Folios',
            [__CLASS__, 'render_dashboard_widget']
        );
    }

    /**
     * Renderizar widget del dashboard
     */
    public static function render_dashboard_widget() {
        $folio_info = self::get_folio_status();
        $ambiente = get_option('akibara_ambiente', 'certificacion');
        $threshold = (int) get_option('akibara_folio_alert_threshold', 50);

        ?>
        <div class="akibara-widget">
            <style>
                .akibara-widget { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; }
                .akibara-widget .ambiente-tag { display: inline-block; padding: 2px 8px; border-radius: 3px; font-size: 11px; font-weight: 500; margin-bottom: 10px; }
                .akibara-widget .ambiente-tag.certificacion { background: #e0e7ff; color: #3730a3; }
                .akibara-widget .ambiente-tag.produccion { background: #d1fae5; color: #065f46; }
                .akibara-widget .folio-main { display: flex; align-items: center; gap: 15px; margin: 15px 0; }
                .akibara-widget .folio-number { font-size: 42px; font-weight: 700; line-height: 1; }
                .akibara-widget .folio-number.ok { color: #059669; }
                .akibara-widget .folio-number.warning { color: #d97706; }
                .akibara-widget .folio-number.danger { color: #dc2626; }
                .akibara-widget .folio-label { color: #6b7280; font-size: 13px; }
                .akibara-widget .progress-bar { height: 8px; background: #e5e7eb; border-radius: 4px; overflow: hidden; margin: 10px 0; }
                .akibara-widget .progress-fill { height: 100%; transition: width 0.3s; }
                .akibara-widget .progress-fill.ok { background: #059669; }
                .akibara-widget .progress-fill.warning { background: #d97706; }
                .akibara-widget .progress-fill.danger { background: #dc2626; }
                .akibara-widget .details { font-size: 12px; color: #6b7280; margin-top: 10px; }
                .akibara-widget .details span { display: inline-block; margin-right: 15px; }
                .akibara-widget .no-caf { background: #fef2f2; border: 1px solid #fecaca; padding: 15px; border-radius: 6px; text-align: center; }
                .akibara-widget .no-caf a { color: #dc2626; }
                .akibara-widget .actions { margin-top: 15px; padding-top: 15px; border-top: 1px solid #e5e7eb; }
            </style>

            <span class="ambiente-tag <?php echo $ambiente; ?>"><?php echo strtoupper($ambiente); ?></span>

            <?php if (!$folio_info['has_caf']): ?>
                <div class="no-caf">
                    <strong>Sin CAF activo</strong><br>
                    <a href="<?php echo admin_url('admin.php?page=akibara-caf'); ?>">Subir CAF para emitir boletas</a>
                </div>
            <?php else:
                $status = 'ok';
                if ($folio_info['disponibles'] <= 10) {
                    $status = 'danger';
                } elseif ($folio_info['disponibles'] <= $threshold) {
                    $status = 'warning';
                }
            ?>
                <div class="folio-main">
                    <div>
                        <span class="folio-number <?php echo $status; ?>"><?php echo number_format($folio_info['disponibles']); ?></span>
                        <span class="folio-label">folios disponibles</span>
                    </div>
                </div>

                <div class="progress-bar">
                    <div class="progress-fill <?php echo $status; ?>" style="width: <?php echo $folio_info['porcentaje_usado']; ?>%"></div>
                </div>

                <div class="details">
                    <span>Rango: <?php echo $folio_info['folio_desde']; ?> - <?php echo $folio_info['folio_hasta']; ?></span>
                    <span>Proximo: <?php echo $folio_info['folio_actual']; ?></span>
                    <span>Usado: <?php echo $folio_info['porcentaje_usado']; ?>%</span>
                </div>

                <?php if ($status !== 'ok'): ?>
                <div class="actions">
                    <a href="https://www.sii.cl/servicios_online/1039-1183.html" target="_blank" class="button button-small">Solicitar CAF en SII</a>
                    <a href="<?php echo admin_url('admin.php?page=akibara-caf'); ?>" class="button button-small">Subir CAF</a>
                </div>
                <?php endif; ?>
            <?php endif; ?>

            <div class="actions" style="text-align:right;">
                <a href="<?php echo admin_url('admin.php?page=akibara-sii'); ?>">Ir al Dashboard</a>
            </div>
        </div>
        <?php
    }

    /**
     * Test de envío de notificación (para pruebas)
     */
    public static function test_notification() {
        $folio_info = self::get_folio_status();
        if ($folio_info['has_caf']) {
            return self::send_notification($folio_info, true);
        }
        return false;
    }
}

// Inicializar
add_action('plugins_loaded', ['Akibara_Folio_Notifications', 'init']);

// Desprogramar cron al desactivar
register_deactivation_hook(AKIBARA_SII_PATH . 'akibara-sii.php', ['Akibara_Folio_Notifications', 'unschedule_cron']);
