<?php
/**
 * Clase para emisión de boletas
 */

defined('ABSPATH') || exit;

class Akibara_Boleta {

    private $sii_client;
    private $ambiente;
    private $emisor;

    public function __construct() {
        $this->sii_client = new Akibara_SII_Client();
        $this->ambiente = Akibara_SII::get_ambiente();
        $this->emisor = Akibara_SII::get_emisor_config();
    }

    /**
     * Emitir boleta
     *
     * @param array $data Datos de la boleta
     * @return array|WP_Error
     */
    public function emitir($data) {
        // Validar datos requeridos
        $validation = $this->validar_datos($data);
        if (is_wp_error($validation)) {
            return $validation;
        }

        // Obtener siguiente folio
        $folio = Akibara_Database::get_siguiente_folio(39, $this->ambiente);
        if (is_wp_error($folio)) {
            return $folio;
        }

        // Preparar datos de la boleta
        $boleta_data = $this->preparar_datos_boleta($data, $folio);

        // Generar XML de la boleta
        $xml_result = $this->sii_client->generar_boleta_xml($boleta_data);
        if (is_wp_error($xml_result)) {
            Akibara_Database::log('error', 'Error generando XML boleta', [
                'folio' => $folio,
                'error' => $xml_result->get_error_message(),
            ]);
            return $xml_result;
        }

        // Calcular montos
        $montos = $this->calcular_montos($data['items']);

        // Guardar en base de datos
        $boleta_id = Akibara_Database::save_boleta([
            'folio' => $folio,
            'tipo_dte' => 39,
            'fecha_emision' => date('Y-m-d'),
            'rut_receptor' => sanitize_text_field($data['receptor']['rut'] ?? '66666666-6'),
            'razon_social_receptor' => sanitize_text_field($data['receptor']['razon_social'] ?? $data['receptor']['nombre'] ?? 'CLIENTE'),
            'monto_neto' => $montos['neto'],
            'monto_iva' => $montos['iva'],
            'monto_exento' => $montos['exento'],
            'monto_total' => $montos['total'],
            'estado' => 'generado',
            'ambiente' => $this->ambiente,
            'xml_documento' => $xml_result['xml'],
        ]);

        if (is_wp_error($boleta_id)) {
            return $boleta_id;
        }

        // Incrementar folio
        Akibara_Database::incrementar_folio(39, $this->ambiente);

        // Log
        Akibara_Database::log('boleta', 'Boleta generada', [
            'id' => $boleta_id,
            'folio' => $folio,
            'total' => $montos['total'],
        ]);

        $result = [
            'id' => $boleta_id,
            'folio' => $folio,
            'total' => $montos['total'],
            'estado' => 'generado',
            'mensaje' => 'Boleta generada correctamente',
        ];

        // Verificar si envío automático está habilitado
        $envio_automatico = get_option('akibara_envio_automatico', 0);
        if ($envio_automatico) {
            $envio_result = $this->enviar_al_sii($boleta_id);
            if (!is_wp_error($envio_result)) {
                $result['enviado'] = true;
                $result['track_id'] = $envio_result['track_id'];
                $result['estado'] = 'enviado';
                $result['mensaje'] = 'Boleta generada y enviada al SII';
            } else {
                $result['enviado'] = false;
                $result['error_envio'] = $envio_result->get_error_message();
            }
        }

        return $result;
    }

    /**
     * Enviar boleta al SII
     *
     * @param int $boleta_id ID de la boleta
     * @return array|WP_Error
     */
    public function enviar_al_sii($boleta_id) {
        $boleta = Akibara_Database::get_boleta($boleta_id);

        if (!$boleta) {
            return new WP_Error('not_found', 'Boleta no encontrada');
        }

        if ($boleta->enviado_sii) {
            return new WP_Error('already_sent', 'La boleta ya fue enviada al SII');
        }

        // Crear sobre de envío
        $sobre_result = $this->sii_client->crear_sobre_boleta($boleta);
        if (is_wp_error($sobre_result)) {
            return $sobre_result;
        }

        // Enviar al SII
        $envio_result = $this->sii_client->enviar_documento($sobre_result['xml'], $boleta->ambiente);
        if (is_wp_error($envio_result)) {
            Akibara_Database::log('error', 'Error enviando boleta al SII', [
                'boleta_id' => $boleta_id,
                'folio' => $boleta->folio,
                'error' => $envio_result->get_error_message(),
            ]);
            return $envio_result;
        }

        // Actualizar boleta
        Akibara_Database::update_boleta($boleta_id, [
            'xml_sobre' => $sobre_result['xml'],
            'track_id' => $envio_result['track_id'],
            'estado' => 'enviado',
            'enviado_sii' => 1,
            'fecha_envio' => current_time('mysql'),
            'respuesta_sii' => json_encode($envio_result),
        ]);

        // Log
        Akibara_Database::log('envio', 'Boleta enviada al SII', [
            'boleta_id' => $boleta_id,
            'folio' => $boleta->folio,
            'track_id' => $envio_result['track_id'],
        ]);

        return [
            'track_id' => $envio_result['track_id'],
            'estado' => $envio_result['estado'],
        ];
    }

    /**
     * Enviar múltiples boletas al SII
     *
     * @param array $boleta_ids IDs de boletas a enviar
     * @return array
     */
    public function enviar_multiples($boleta_ids) {
        $resultados = [];

        foreach ($boleta_ids as $id) {
            $result = $this->enviar_al_sii($id);
            $resultados[$id] = is_wp_error($result)
                ? ['error' => $result->get_error_message()]
                : $result;
        }

        return $resultados;
    }

    /**
     * Consultar estado de boleta en SII
     *
     * @param int $boleta_id
     * @return array|WP_Error
     */
    public function consultar_estado($boleta_id) {
        $boleta = Akibara_Database::get_boleta($boleta_id);

        if (!$boleta) {
            return new WP_Error('not_found', 'Boleta no encontrada');
        }

        if (empty($boleta->track_id) || $boleta->track_id === '0' || intval($boleta->track_id) <= 0) {
            return new WP_Error('no_track', 'La boleta no tiene Track ID válido');
        }

        $estado = $this->sii_client->consultar_estado($boleta->track_id, $boleta->ambiente);

        if (!is_wp_error($estado)) {
            // Actualizar estado si cambió
            if ($estado['estado'] !== $boleta->estado) {
                Akibara_Database::update_boleta($boleta_id, [
                    'estado' => $estado['estado'],
                    'respuesta_sii' => json_encode($estado),
                ]);
            }
        }

        return $estado;
    }

    /**
     * Validar datos de boleta
     */
    private function validar_datos($data) {
        // Verificar emisor configurado
        if (empty($this->emisor['RUTEmisor'])) {
            return new WP_Error('no_emisor', 'Debe configurar los datos del emisor');
        }

        // Verificar items
        if (empty($data['items']) || !is_array($data['items'])) {
            return new WP_Error('no_items', 'Debe incluir al menos un item');
        }

        // Verificar cada item
        foreach ($data['items'] as $i => $item) {
            if (empty($item['nombre'])) {
                return new WP_Error('item_sin_nombre', "El item " . ($i + 1) . " no tiene nombre");
            }
            if (!isset($item['cantidad']) || $item['cantidad'] <= 0) {
                return new WP_Error('item_sin_cantidad', "El item " . ($i + 1) . " no tiene cantidad válida");
            }
            if (!isset($item['precio']) || $item['precio'] < 0) {
                return new WP_Error('item_sin_precio', "El item " . ($i + 1) . " no tiene precio válido");
            }
        }

        // Verificar CAF
        $caf = Akibara_Database::get_caf_activo(39, $this->ambiente);
        if (!$caf) {
            return new WP_Error('no_caf', 'No hay CAF activo. Debe cargar un archivo de folios.');
        }

        // Verificar certificado
        $cert_file = get_option("akibara_cert_{$this->ambiente}_file", '');
        if (empty($cert_file)) {
            return new WP_Error('no_cert', 'No hay certificado digital configurado');
        }

        return true;
    }

    /**
     * Preparar datos de boleta para XML
     */
    private function preparar_datos_boleta($data, $folio) {
        $items = [];
        foreach ($data['items'] as $item) {
            $item_data = [
                'NmbItem' => sanitize_text_field($item['nombre']),
                'QtyItem' => floatval($item['cantidad']),
                'PrcItem' => floatval($item['precio']),
            ];

            if (!empty($item['unidad'])) {
                $item_data['UnmdItem'] = sanitize_text_field($item['unidad']);
            }

            if (!empty($item['exento'])) {
                $item_data['IndExe'] = 1;
            }

            $items[] = $item_data;
        }

        return [
            'Encabezado' => [
                'IdDoc' => [
                    'TipoDTE' => 39,
                    'Folio' => $folio,
                    'FchEmis' => date('Y-m-d'),
                    'IndServicio' => 3,
                ],
                'Emisor' => $this->emisor,
                'Receptor' => [
                    'RUTRecep' => sanitize_text_field($data['receptor']['rut'] ?? '66666666-6'),
                    'RznSocRecep' => sanitize_text_field($data['receptor']['razon_social'] ?? $data['receptor']['nombre'] ?? 'CLIENTE'),
                    'DirRecep' => sanitize_text_field($data['receptor']['direccion'] ?? 'Santiago'),
                    'CmnaRecep' => sanitize_text_field($data['receptor']['comuna'] ?? 'Santiago'),
                ],
            ],
            'Detalle' => $items,
        ];
    }

    /**
     * Calcular montos de la boleta
     */
    private function calcular_montos($items) {
        $bruto_afecto = 0;
        $exento = 0;

        foreach ($items as $item) {
            $subtotal = floatval($item['cantidad']) * floatval($item['precio']);

            if (!empty($item['exento'])) {
                $exento += $subtotal;
            } else {
                $bruto_afecto += $subtotal;
            }
        }

        // Para boletas, los precios incluyen IVA
        // Neto = Bruto / 1.19
        $neto = round($bruto_afecto / 1.19);
        $iva = $bruto_afecto - $neto;
        $total = $bruto_afecto + $exento;

        return [
            'neto' => (int) $neto,
            'iva' => (int) $iva,
            'exento' => (int) $exento,
            'total' => (int) $total,
        ];
    }

    /**
     * Obtener PDF de boleta
     *
     * @param int $boleta_id ID de la boleta
     * @return string|WP_Error PDF binario o error
     */
    public function get_pdf($boleta_id) {
        $boleta = Akibara_Database::get_boleta($boleta_id);

        if (!$boleta) {
            return new WP_Error('not_found', 'Boleta no encontrada');
        }

        if (empty($boleta->xml_documento)) {
            return new WP_Error('no_xml', 'La boleta no tiene XML generado');
        }

        // Generar PDF usando LibreDTE
        return $this->sii_client->generar_pdf($boleta->xml_documento);
    }

    /**
     * Obtener PDF de boleta y guardarlo/descargarlo
     *
     * @param int $boleta_id ID de la boleta
     * @param bool $download Si es true, fuerza descarga. Si es false, retorna contenido
     * @return void|string|WP_Error
     */
    public function descargar_pdf($boleta_id, $download = true) {
        $boleta = Akibara_Database::get_boleta($boleta_id);

        if (!$boleta) {
            return new WP_Error('not_found', 'Boleta no encontrada');
        }

        $pdf = $this->get_pdf($boleta_id);

        if (is_wp_error($pdf)) {
            return $pdf;
        }

        $filename = sprintf('boleta_%s_%d.pdf', $boleta->folio, $boleta->tipo_dte);

        if ($download) {
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Content-Length: ' . strlen($pdf));
            header('Cache-Control: private, max-age=0, must-revalidate');
            header('Pragma: public');
            echo $pdf;
            exit;
        }

        return $pdf;
    }

    /**
     * Guardar PDF de boleta en disco
     *
     * @param int $boleta_id ID de la boleta
     * @return string|WP_Error Ruta del archivo o error
     */
    public function guardar_pdf($boleta_id) {
        $boleta = Akibara_Database::get_boleta($boleta_id);

        if (!$boleta) {
            return new WP_Error('not_found', 'Boleta no encontrada');
        }

        $pdf = $this->get_pdf($boleta_id);

        if (is_wp_error($pdf)) {
            return $pdf;
        }

        // Crear directorio si no existe
        $pdf_dir = AKIBARA_SII_UPLOADS . 'pdf/';
        if (!file_exists($pdf_dir)) {
            wp_mkdir_p($pdf_dir);
        }

        $filename = sprintf('boleta_%d_%s_%d.pdf', $boleta->id, $boleta->folio, $boleta->tipo_dte);
        $filepath = $pdf_dir . $filename;

        if (file_put_contents($filepath, $pdf) === false) {
            return new WP_Error('save_failed', 'No se pudo guardar el PDF');
        }

        // Actualizar registro con ruta del PDF
        Akibara_Database::update_boleta($boleta_id, [
            'pdf_file' => $filename,
        ]);

        return $filepath;
    }
}
