<?php
/**
 * Clase para gestión del RCOF (Reporte de Consumo de Folios)
 *
 * Nota importante: Desde agosto 2022 (Resolución Ex. SII N°53),
 * el RCOF ya no es obligatorio para boletas electrónicas.
 * Esta funcionalidad se mantiene por compatibilidad y para
 * usuarios que deseen enviar el reporte voluntariamente.
 */

if (!defined('ABSPATH')) {
    exit;
}

class Akibara_RCOF {

    private $sii_client;

    public function __construct() {
        $this->sii_client = new Akibara_SII_Client();
    }

    /**
     * Obtiene el ambiente actual
     */
    public function get_ambiente() {
        return Akibara_SII::get_ambiente();
    }

    /**
     * Enviar RCOF desde datos del formulario
     */
    public function enviar($data) {
        $ambiente = $this->get_ambiente();

        // RCOF normalmente solo en producción
        // Pero permitimos en certificación para pruebas del set
        $fecha = isset($data['fecha']) ? sanitize_text_field($data['fecha']) : date('Y-m-d');

        // Obtener boletas del día
        $boletas = Akibara_Database::get_boletas_by_date($fecha, $ambiente);

        if (empty($boletas)) {
            return new WP_Error('sin_boletas', 'No hay boletas para la fecha indicada');
        }

        // Calcular totales
        $resumen = $this->calcular_resumen($boletas);

        // Obtener siguiente secuencia
        $sec_envio = $this->get_siguiente_secuencia($fecha, $ambiente);

        // Preparar datos para XML
        $rcof_data = [
            'fecha' => $fecha,
            'sec_envio' => $sec_envio,
            'neto' => $resumen['neto'],
            'iva' => $resumen['iva'],
            'exento' => $resumen['exento'],
            'total' => $resumen['total'],
            'cantidad' => $resumen['cantidad'],
            'folio_inicial' => $resumen['folio_inicial'],
            'folio_final' => $resumen['folio_final'],
        ];

        // Generar XML firmado
        $xml_result = $this->sii_client->generar_rcof_xml($rcof_data);

        if (is_wp_error($xml_result)) {
            Akibara_Database::log('error', 'Error generando RCOF XML', [
                'fecha' => $fecha,
                'error' => $xml_result->get_error_message(),
            ]);
            return $xml_result;
        }

        // Enviar al SII
        $envio_result = $this->sii_client->enviar_documento($xml_result['xml'], $ambiente);

        if (is_wp_error($envio_result)) {
            // Guardar con estado error
            Akibara_Database::save_rcof([
                'fecha' => $fecha,
                'sec_envio' => $sec_envio,
                'cantidad_boletas' => $resumen['cantidad'],
                'monto_neto' => $resumen['neto'],
                'monto_iva' => $resumen['iva'],
                'monto_exento' => $resumen['exento'],
                'monto_total' => $resumen['total'],
                'folios_emitidos' => $resumen['cantidad'],
                'rango_inicial' => $resumen['folio_inicial'],
                'rango_final' => $resumen['folio_final'],
                'estado' => 'error',
                'ambiente' => $ambiente,
                'xml_rcof' => $xml_result['xml'],
                'respuesta_sii' => $envio_result->get_error_message(),
            ]);

            return $envio_result;
        }

        // Guardar con track_id
        $rcof_id = Akibara_Database::save_rcof([
            'fecha' => $fecha,
            'sec_envio' => $sec_envio,
            'cantidad_boletas' => $resumen['cantidad'],
            'monto_neto' => $resumen['neto'],
            'monto_iva' => $resumen['iva'],
            'monto_exento' => $resumen['exento'],
            'monto_total' => $resumen['total'],
            'folios_emitidos' => $resumen['cantidad'],
            'rango_inicial' => $resumen['folio_inicial'],
            'rango_final' => $resumen['folio_final'],
            'track_id' => $envio_result['track_id'],
            'estado' => 'enviado',
            'ambiente' => $ambiente,
            'xml_rcof' => $xml_result['xml'],
            'respuesta_sii' => json_encode($envio_result),
        ]);

        // Log
        Akibara_Database::log('rcof', 'RCOF enviado al SII', [
            'rcof_id' => $rcof_id,
            'fecha' => $fecha,
            'track_id' => $envio_result['track_id'],
            'total' => $resumen['total'],
        ]);

        return [
            'id' => $rcof_id,
            'track_id' => $envio_result['track_id'],
            'estado' => 'enviado',
            'mensaje' => 'RCOF enviado correctamente al SII',
        ];
    }

    /**
     * Calcula el resumen de boletas para el RCOF
     */
    private function calcular_resumen($boletas) {
        $neto = 0;
        $iva = 0;
        $exento = 0;
        $total = 0;
        $folio_min = null;
        $folio_max = null;

        foreach ($boletas as $boleta) {
            $neto += (int) $boleta->monto_neto;
            $iva += (int) $boleta->monto_iva;
            $exento += (int) $boleta->monto_exento;
            $total += (int) $boleta->monto_total;

            if ($folio_min === null || $boleta->folio < $folio_min) {
                $folio_min = $boleta->folio;
            }
            if ($folio_max === null || $boleta->folio > $folio_max) {
                $folio_max = $boleta->folio;
            }
        }

        return [
            'neto' => $neto,
            'iva' => $iva,
            'exento' => $exento,
            'total' => $total,
            'cantidad' => count($boletas),
            'folio_inicial' => $folio_min,
            'folio_final' => $folio_max,
        ];
    }

    /**
     * Obtiene la siguiente secuencia de envío para una fecha
     */
    private function get_siguiente_secuencia($fecha, $ambiente) {
        $rcof_existente = Akibara_Database::get_rcof_by_date($fecha, $ambiente);

        if ($rcof_existente) {
            return $rcof_existente->sec_envio + 1;
        }

        return 1;
    }

    /**
     * Consulta el estado de un RCOF enviado
     */
    public function consultar_estado($rcof_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'akibara_rcof';

        $rcof = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE id = %d",
            $rcof_id
        ));

        if (!$rcof) {
            return new WP_Error('not_found', 'RCOF no encontrado');
        }

        if (!$rcof->track_id) {
            return new WP_Error('no_track', 'RCOF sin Track ID');
        }

        // Consultar estado en SII
        $resultado = $this->sii_client->consultar_estado($rcof->track_id, $rcof->ambiente);

        if (!is_wp_error($resultado)) {
            // Actualizar estado en BD
            $wpdb->update(
                $table,
                ['estado' => $resultado['estado'], 'respuesta_sii' => json_encode($resultado)],
                ['id' => $rcof_id]
            );
        }

        return $resultado;
    }

    /**
     * Obtiene el historial de RCOF
     */
    public function get_historial($limit = 50) {
        global $wpdb;
        $table = $wpdb->prefix . 'akibara_rcof';
        $ambiente = $this->get_ambiente();

        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table WHERE ambiente = %s ORDER BY fecha DESC, id DESC LIMIT %d",
            $ambiente,
            $limit
        ));
    }

    /**
     * Programar envío automático de RCOF (cron)
     * Nota: Ya no es obligatorio desde agosto 2022
     */
    public static function programar_envio_automatico() {
        if (!wp_next_scheduled('akibara_rcof_diario')) {
            $hora = get_option('akibara_rcof_hora', '23:00');
            list($h, $m) = explode(':', $hora);

            $timestamp = strtotime("today {$h}:{$m}:00");
            if ($timestamp < time()) {
                $timestamp = strtotime("tomorrow {$h}:{$m}:00");
            }
            wp_schedule_event($timestamp, 'daily', 'akibara_rcof_diario');
        }
    }

    /**
     * Desprogramar envío automático
     */
    public static function desprogramar_envio_automatico() {
        $timestamp = wp_next_scheduled('akibara_rcof_diario');
        if ($timestamp) {
            wp_unschedule_event($timestamp, 'akibara_rcof_diario');
        }
    }

    /**
     * Ejecuta el envío automático de RCOF (solo producción)
     */
    public function ejecutar_envio_automatico() {
        // RCOF automático solo aplica en producción
        if ($this->get_ambiente() !== 'produccion') {
            return;
        }

        // Verificar si está habilitado
        $rcof_enabled = get_option('akibara_rcof_enabled', 0);
        if (!$rcof_enabled) {
            return;
        }

        $fecha = date('Y-m-d');

        // Verificar si ya se envió hoy
        $rcof_hoy = Akibara_Database::get_rcof_by_date($fecha, 'produccion');
        if ($rcof_hoy && $rcof_hoy->estado === 'enviado') {
            return; // Ya se envió
        }

        // Enviar RCOF
        $resultado = $this->enviar(['fecha' => $fecha]);

        // Registrar en log
        Akibara_Database::log('rcof_automatico', 'Ejecución automática de RCOF', [
            'fecha' => $fecha,
            'resultado' => is_wp_error($resultado) ? $resultado->get_error_message() : $resultado,
        ]);
    }
}

// Hook para cron
add_action('akibara_rcof_diario', function() {
    $rcof = new Akibara_RCOF();
    $rcof->ejecutar_envio_automatico();
});
