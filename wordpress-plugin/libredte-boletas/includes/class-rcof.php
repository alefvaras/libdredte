<?php
/**
 * Clase para gestión del RCOF (Reporte de Consumo de Folios)
 * Disponible en ambos ambientes:
 * - Certificación: Para enviar junto con el Set de Pruebas
 * - Producción: Para el envío diario obligatorio
 */

if (!defined('ABSPATH')) {
    exit;
}

class LibreDTE_RCOF {

    private $db;
    private $sii_client;

    public function __construct() {
        $this->db = new LibreDTE_Database();
        $this->sii_client = new LibreDTE_SII_Client();
    }

    /**
     * Obtiene el ambiente actual
     */
    public function get_ambiente() {
        return get_option('libredte_ambiente', 'certificacion');
    }

    /**
     * Genera el RCOF para una fecha específica
     */
    public function generar_rcof($fecha = null) {
        if ($fecha === null) {
            $fecha = date('Y-m-d');
        }

        // Obtener boletas del día que fueron enviadas exitosamente al SII
        global $wpdb;
        $table_boletas = $wpdb->prefix . 'libredte_boletas';

        $boletas = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_boletas
             WHERE DATE(fecha_emision) = %s
             AND estado_sii = 'aceptado'
             ORDER BY folio ASC",
            $fecha
        ));

        if (empty($boletas)) {
            return array(
                'success' => false,
                'error' => 'No hay boletas aceptadas para esta fecha'
            );
        }

        // Calcular totales y rangos
        $data = $this->calcular_resumen($boletas, $fecha);

        // Generar XML
        $xml = $this->generar_xml($data);

        if (!$xml) {
            return array(
                'success' => false,
                'error' => 'Error al generar XML del RCOF'
            );
        }

        // Guardar en base de datos
        $rcof_id = $this->db->save_rcof(array(
            'fecha' => $fecha,
            'folio_inicial' => $data['folio_inicial'],
            'folio_final' => $data['folio_final'],
            'cantidad_boletas' => $data['cantidad_boletas'],
            'monto_total' => $data['totales']['total'],
            'xml' => $xml,
            'estado' => 'generado'
        ));

        return array(
            'success' => true,
            'rcof_id' => $rcof_id,
            'data' => $data,
            'xml' => $xml
        );
    }

    /**
     * Calcula el resumen de boletas para el RCOF
     */
    private function calcular_resumen($boletas, $fecha) {
        $folios = array();
        $totales = array(
            'neto' => 0,
            'iva' => 0,
            'exento' => 0,
            'total' => 0
        );

        foreach ($boletas as $boleta) {
            $folios[] = $boleta->folio;
            $totales['neto'] += $boleta->monto_neto;
            $totales['iva'] += $boleta->monto_iva;
            $totales['exento'] += $boleta->monto_exento;
            $totales['total'] += $boleta->monto_total;
        }

        sort($folios);

        // Calcular rangos continuos
        $rangos = $this->calcular_rangos($folios);

        return array(
            'fecha' => $fecha,
            'folio_inicial' => min($folios),
            'folio_final' => max($folios),
            'cantidad_boletas' => count($boletas),
            'folios' => $folios,
            'rangos' => $rangos,
            'totales' => $totales
        );
    }

    /**
     * Calcula rangos continuos de folios
     */
    private function calcular_rangos($folios) {
        if (empty($folios)) {
            return array();
        }

        sort($folios);
        $rangos = array();
        $inicio = $folios[0];
        $fin = $folios[0];

        for ($i = 1; $i < count($folios); $i++) {
            if ($folios[$i] == $fin + 1) {
                $fin = $folios[$i];
            } else {
                $rangos[] = array('inicial' => $inicio, 'final' => $fin);
                $inicio = $folios[$i];
                $fin = $folios[$i];
            }
        }
        $rangos[] = array('inicial' => $inicio, 'final' => $fin);

        return $rangos;
    }

    /**
     * Genera el XML del RCOF
     */
    private function generar_xml($data) {
        $emisor = array(
            'rut' => get_option('libredte_emisor_rut'),
            'razon_social' => get_option('libredte_emisor_razon_social'),
            'resolucion_fecha' => get_option('libredte_resolucion_fecha'),
            'resolucion_numero' => get_option('libredte_resolucion_numero', '0')
        );

        $rut_envia = get_option('libredte_rut_envia', $emisor['rut']);
        $rut_emisor_limpio = str_replace('.', '', $emisor['rut']);
        $rut_emisor_limpio = str_replace('-', '', $rut_emisor_limpio);
        $rut_emisor_limpio = substr($rut_emisor_limpio, 0, -1);

        $sec_envio = $this->get_siguiente_secuencia($data['fecha']);
        $timestamp = date('Y-m-d\TH:i:s');

        $id = 'CF_' . $rut_emisor_limpio . '_' . str_replace('-', '', $data['fecha']);

        $xml = '<?xml version="1.0" encoding="ISO-8859-1"?>' . "\n";
        $xml .= '<ConsumoFolios xmlns="http://www.sii.cl/SiiDte" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.sii.cl/SiiDte ConsumoFolio_v10.xsd" version="1.0">' . "\n";
        $xml .= '  <DocumentoConsumoFolios ID="' . $id . '">' . "\n";
        $xml .= '    <Caratula version="1.0">' . "\n";
        $xml .= '      <RutEmisor>' . $emisor['rut'] . '</RutEmisor>' . "\n";
        $xml .= '      <RutEnvia>' . $rut_envia . '</RutEnvia>' . "\n";
        $xml .= '      <FchResol>' . $emisor['resolucion_fecha'] . '</FchResol>' . "\n";
        $xml .= '      <NroResol>' . $emisor['resolucion_numero'] . '</NroResol>' . "\n";
        $xml .= '      <FchInicio>' . $data['fecha'] . '</FchInicio>' . "\n";
        $xml .= '      <FchFinal>' . $data['fecha'] . '</FchFinal>' . "\n";
        $xml .= '      <SecEnvio>' . $sec_envio . '</SecEnvio>' . "\n";
        $xml .= '      <TmstFirmaEnv>' . $timestamp . '</TmstFirmaEnv>' . "\n";
        $xml .= '    </Caratula>' . "\n";
        $xml .= '    <Resumen>' . "\n";
        $xml .= '      <TipoDocumento>39</TipoDocumento>' . "\n";
        $xml .= '      <MntNeto>' . $data['totales']['neto'] . '</MntNeto>' . "\n";
        $xml .= '      <MntIva>' . $data['totales']['iva'] . '</MntIva>' . "\n";
        $xml .= '      <TasaIVA>19</TasaIVA>' . "\n";
        if ($data['totales']['exento'] > 0) {
            $xml .= '      <MntExento>' . $data['totales']['exento'] . '</MntExento>' . "\n";
        }
        $xml .= '      <MntTotal>' . $data['totales']['total'] . '</MntTotal>' . "\n";
        $xml .= '      <FoliosEmitidos>' . $data['cantidad_boletas'] . '</FoliosEmitidos>' . "\n";
        $xml .= '      <FoliosAnulados>0</FoliosAnulados>' . "\n";
        $xml .= '      <FoliosUtilizados>' . $data['cantidad_boletas'] . '</FoliosUtilizados>' . "\n";

        foreach ($data['rangos'] as $rango) {
            $xml .= '      <RangoUtilizados>' . "\n";
            $xml .= '        <Inicial>' . $rango['inicial'] . '</Inicial>' . "\n";
            $xml .= '        <Final>' . $rango['final'] . '</Final>' . "\n";
            $xml .= '      </RangoUtilizados>' . "\n";
        }

        $xml .= '    </Resumen>' . "\n";
        $xml .= '  </DocumentoConsumoFolios>' . "\n";
        $xml .= '</ConsumoFolios>';

        // Firmar XML
        $xml_firmado = $this->sii_client->firmar_xml($xml, $id);

        return $xml_firmado;
    }

    /**
     * Obtiene la siguiente secuencia de envío
     */
    private function get_siguiente_secuencia($fecha) {
        global $wpdb;
        $table = $wpdb->prefix . 'libredte_rcof';

        $max = $wpdb->get_var($wpdb->prepare(
            "SELECT MAX(secuencia) FROM $table WHERE fecha = %s",
            $fecha
        ));

        return ($max !== null) ? $max + 1 : 1;
    }

    /**
     * Envía el RCOF al SII
     */
    public function enviar_rcof($rcof_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'libredte_rcof';

        $rcof = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE id = %d",
            $rcof_id
        ));

        if (!$rcof) {
            return array(
                'success' => false,
                'error' => 'RCOF no encontrado'
            );
        }

        // Enviar al SII
        $resultado = $this->sii_client->enviar_rcof($rcof->xml);

        // Actualizar estado
        $nuevo_estado = $resultado['success'] ? 'enviado' : 'error';
        $wpdb->update(
            $table,
            array(
                'estado' => $nuevo_estado,
                'track_id' => isset($resultado['track_id']) ? $resultado['track_id'] : null,
                'respuesta_sii' => isset($resultado['respuesta']) ? $resultado['respuesta'] : null,
                'fecha_envio' => current_time('mysql')
            ),
            array('id' => $rcof_id)
        );

        return $resultado;
    }

    /**
     * Consulta el estado de un RCOF enviado
     */
    public function consultar_estado($rcof_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'libredte_rcof';

        $rcof = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE id = %d",
            $rcof_id
        ));

        if (!$rcof || !$rcof->track_id) {
            return array(
                'success' => false,
                'error' => 'RCOF no encontrado o sin track_id'
            );
        }

        $resultado = $this->sii_client->consultar_estado_rcof($rcof->track_id);

        if ($resultado['success'] && isset($resultado['estado'])) {
            $wpdb->update(
                $table,
                array('estado_sii' => $resultado['estado']),
                array('id' => $rcof_id)
            );
        }

        return $resultado;
    }

    /**
     * Obtiene el historial de RCOF
     */
    public function get_historial($limit = 50) {
        global $wpdb;
        $table = $wpdb->prefix . 'libredte_rcof';

        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table ORDER BY fecha DESC, id DESC LIMIT %d",
            $limit
        ));
    }

    /**
     * Programar envío automático de RCOF (cron)
     */
    public static function programar_envio_automatico() {
        if (!wp_next_scheduled('libredte_rcof_diario')) {
            // Programar para las 23:50 cada día
            $timestamp = strtotime('today 23:50:00');
            if ($timestamp < time()) {
                $timestamp = strtotime('tomorrow 23:50:00');
            }
            wp_schedule_event($timestamp, 'daily', 'libredte_rcof_diario');
        }
    }

    /**
     * Ejecuta el envío automático de RCOF (solo producción)
     */
    public function ejecutar_envio_automatico() {
        // El envío automático diario solo aplica en producción
        if ($this->get_ambiente() !== 'produccion') {
            return;
        }

        $envio_automatico = get_option('libredte_rcof_automatico', 0);
        if (!$envio_automatico) {
            return;
        }

        $fecha = date('Y-m-d');

        // Generar RCOF
        $resultado = $this->generar_rcof($fecha);

        if ($resultado['success']) {
            // Enviar al SII
            $this->enviar_rcof($resultado['rcof_id']);
        }

        // Registrar en log
        $this->db->log('rcof_automatico', 'Ejecución automática de RCOF', $resultado);
    }
}
