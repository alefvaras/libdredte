<?php
/**
 * Clase para comunicación con SII
 */

defined('ABSPATH') || exit;

use Derafu\Certificate\Service\CertificateLoader;
use libredte\lib\Core\Application;
use libredte\lib\Core\Package\Billing\Component\Integration\Enum\SiiAmbiente;
use libredte\lib\Core\Package\Billing\Component\Integration\Support\SiiRequest;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Entity\AutorizacionDte;

class Akibara_SII_Client {

    private $app;
    private $certificate_loader;

    // Configuración de reintentos
    const MAX_RETRIES = 4;
    const INITIAL_DELAY_MS = 2000; // 2 segundos
    const MAX_DELAY_MS = 16000; // 16 segundos

    public function __construct() {
        // Solo inicializar si LibreDTE está disponible
        if (class_exists('libredte\lib\Core\Application')) {
            $this->app = Application::getInstance(
                environment: 'dev',
                debug: false
            );
            $this->certificate_loader = new CertificateLoader();
        }
    }

    /**
     * Ejecutar operación con reintentos y backoff exponencial
     *
     * @param callable $operation Operación a ejecutar
     * @param string $operationName Nombre de la operación (para logs)
     * @return mixed Resultado de la operación
     * @throws Exception Si falla después de todos los reintentos
     */
    private function executeWithRetry(callable $operation, string $operationName = 'operación') {
        $lastException = null;
        $delay = self::INITIAL_DELAY_MS;

        for ($attempt = 1; $attempt <= self::MAX_RETRIES; $attempt++) {
            try {
                return $operation();
            } catch (Exception $e) {
                $lastException = $e;
                $errorMessage = $e->getMessage();

                // Verificar si es un error de conexión que merece reintento
                $isConnectionError = (
                    stripos($errorMessage, 'connection') !== false ||
                    stripos($errorMessage, 'timeout') !== false ||
                    stripos($errorMessage, 'reset') !== false ||
                    stripos($errorMessage, 'upstream') !== false ||
                    stripos($errorMessage, 'network') !== false ||
                    stripos($errorMessage, 'curl') !== false
                );

                if (!$isConnectionError || $attempt >= self::MAX_RETRIES) {
                    throw $e;
                }

                // Log del reintento
                error_log("Akibara SII: Reintento $attempt/" . self::MAX_RETRIES . " de $operationName - Error: $errorMessage");

                // Esperar con backoff exponencial
                usleep($delay * 1000);
                $delay = min($delay * 2, self::MAX_DELAY_MS);
            }
        }

        throw $lastException;
    }

    /**
     * Obtener ambiente SII
     */
    private function get_sii_ambiente($ambiente) {
        return $ambiente === 'produccion'
            ? SiiAmbiente::PRODUCCION
            : SiiAmbiente::CERTIFICACION;
    }

    /**
     * Cargar certificado (solo PEM)
     */
    private function load_certificate($ambiente) {
        $cert_file = get_option("akibara_cert_{$ambiente}_file", '');

        if (empty($cert_file)) {
            return new WP_Error('no_cert', 'No hay certificado configurado. Suba su certificado en formato PEM.');
        }

        $cert_path = AKIBARA_SII_UPLOADS . 'certs/' . $cert_file;

        if (!file_exists($cert_path)) {
            return new WP_Error('cert_not_found', 'Archivo de certificado no encontrado');
        }

        return $this->load_certificate_from_pem($cert_path);
    }

    /**
     * Cargar certificado legacy usando shell commands
     * Útil para certificados .p12 con cifrado RC2-40-CBC en OpenSSL 3.0
     */
    private function load_certificate_legacy($cert_path, $password) {
        // Primero, intentar cargar archivo PEM pre-convertido si existe
        $pem_path = preg_replace('/\.p12$/i', '.pem', $cert_path);
        if (file_exists($pem_path)) {
            $result = $this->load_certificate_from_pem($pem_path);
            if (!is_wp_error($result)) {
                return $result;
            }
        }

        // Verificar si las funciones shell están disponibles
        if (!$this->is_shell_exec_available()) {
            return new WP_Error(
                'shell_disabled',
                'El certificado .p12 usa cifrado legacy (RC2-40-CBC) que requiere conversión. ' .
                'Las funciones shell están deshabilitadas en este hosting. ' .
                'Por favor, convierta su certificado a PEM ejecutando en su PC: ' .
                'openssl pkcs12 -in certificado.p12 -out certificado.pem -nodes -legacy ' .
                'y suba el archivo .pem junto al .p12'
            );
        }

        $temp_pem = tempnam(sys_get_temp_dir(), 'cert_') . '.pem';
        $escaped_path = escapeshellarg($cert_path);

        // Usar archivo temporal para la contraseña (más seguro y evita problemas con caracteres especiales)
        $pass_file = tempnam(sys_get_temp_dir(), 'pass_');
        file_put_contents($pass_file, $password);

        $output = '';

        // Intentar con diferentes combinaciones de flags
        $commands = [
            // OpenSSL 3.0+ con legacy provider
            "openssl pkcs12 -in $escaped_path -out $temp_pem -nodes -passin file:" . escapeshellarg($pass_file) . " -legacy 2>&1",
            // OpenSSL 3.0+ con provider explícito
            "openssl pkcs12 -in $escaped_path -out $temp_pem -nodes -passin file:" . escapeshellarg($pass_file) . " -provider legacy -provider default 2>&1",
            // Sin flag legacy (OpenSSL < 3.0 o certificado compatible)
            "openssl pkcs12 -in $escaped_path -out $temp_pem -nodes -passin file:" . escapeshellarg($pass_file) . " 2>&1",
        ];

        foreach ($commands as $cmd) {
            @unlink($temp_pem); // Limpiar archivo previo
            $output = $this->safe_shell_exec($cmd);

            if (file_exists($temp_pem) && filesize($temp_pem) > 0) {
                break; // Éxito
            }
        }

        @unlink($pass_file); // Limpiar archivo de contraseña

        if (!file_exists($temp_pem) || filesize($temp_pem) === 0) {
            @unlink($temp_pem);
            return new WP_Error('legacy_failed', 'No se pudo convertir el certificado legacy. Output: ' . substr($output, 0, 200));
        }

        try {
            $pem_content = file_get_contents($temp_pem);
            @unlink($temp_pem);

            // Guardar PEM para uso futuro (evita conversión repetida)
            @file_put_contents($pem_path, $pem_content);

            // Extraer certificado y clave privada del PEM
            preg_match('/-----BEGIN CERTIFICATE-----.*?-----END CERTIFICATE-----/s', $pem_content, $cert_match);

            // Buscar diferentes formatos de clave privada
            $key_match = [];
            if (preg_match('/-----BEGIN PRIVATE KEY-----.*?-----END PRIVATE KEY-----/s', $pem_content, $key_match)) {
                // Formato estándar PKCS#8
            } elseif (preg_match('/-----BEGIN RSA PRIVATE KEY-----.*?-----END RSA PRIVATE KEY-----/s', $pem_content, $key_match)) {
                // Formato tradicional RSA
            } elseif (preg_match('/-----BEGIN ENCRYPTED PRIVATE KEY-----.*?-----END ENCRYPTED PRIVATE KEY-----/s', $pem_content, $key_match)) {
                // Clave encriptada (no debería pasar con -nodes)
                return new WP_Error('key_encrypted', 'La clave privada está encriptada. Use -nodes en la conversión.');
            }

            if (empty($cert_match[0]) || empty($key_match[0])) {
                return new WP_Error('parse_failed', 'No se pudo extraer certificado/clave del PEM. Contenido: ' . substr($pem_content, 0, 100));
            }

            return $this->certificate_loader->loadFromKeys($cert_match[0], $key_match[0]);

        } catch (Exception $e) {
            @unlink($temp_pem);
            return new WP_Error('legacy_load_failed', 'Error cargando certificado convertido: ' . $e->getMessage());
        }
    }

    /**
     * Cargar certificado desde archivo PEM pre-convertido
     */
    private function load_certificate_from_pem($pem_path) {
        try {
            $pem_content = file_get_contents($pem_path);

            if (empty($pem_content)) {
                return new WP_Error('pem_empty', 'El archivo PEM está vacío');
            }

            // Buscar certificado
            preg_match('/-----BEGIN CERTIFICATE-----.*?-----END CERTIFICATE-----/s', $pem_content, $cert_match);

            // Buscar clave privada (varios formatos)
            $key_match = [];
            if (preg_match('/-----BEGIN PRIVATE KEY-----.*?-----END PRIVATE KEY-----/s', $pem_content, $key_match)) {
                // Formato PKCS#8
            } elseif (preg_match('/-----BEGIN RSA PRIVATE KEY-----.*?-----END RSA PRIVATE KEY-----/s', $pem_content, $key_match)) {
                // Formato RSA tradicional
            }

            // Validar que tenga ambos componentes
            $has_cert = !empty($cert_match[0]);
            $has_key = !empty($key_match[0]);

            if (!$has_cert && !$has_key) {
                return new WP_Error('pem_invalid',
                    'El archivo PEM no contiene certificado ni clave privada. ' .
                    'Asegúrese de generar el PEM con: openssl pkcs12 -in cert.p12 -out cert.pem -nodes -legacy'
                );
            }

            if (!$has_cert) {
                return new WP_Error('pem_no_cert',
                    'El archivo PEM no contiene el CERTIFICADO (solo tiene la clave privada). ' .
                    'Regenere el PEM con: openssl pkcs12 -in cert.p12 -out cert.pem -nodes -legacy'
                );
            }

            if (!$has_key) {
                return new WP_Error('pem_no_key',
                    'El archivo PEM no contiene la CLAVE PRIVADA (solo tiene el certificado). ' .
                    'Regenere el PEM con: openssl pkcs12 -in cert.p12 -out cert.pem -nodes -legacy'
                );
            }

            return $this->certificate_loader->loadFromKeys($cert_match[0], $key_match[0]);

        } catch (Exception $e) {
            return new WP_Error('pem_load_failed', 'Error cargando certificado PEM: ' . $e->getMessage());
        }
    }

    /**
     * Verificar si shell_exec está disponible
     */
    private function is_shell_exec_available() {
        // Verificar si la función existe y no está deshabilitada
        if (!function_exists('shell_exec')) {
            return false;
        }

        $disabled = ini_get('disable_functions');
        if (!empty($disabled)) {
            $disabled_functions = array_map('trim', explode(',', $disabled));
            if (in_array('shell_exec', $disabled_functions)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Ejecutar comando shell de forma segura
     */
    private function safe_shell_exec($cmd) {
        if (!$this->is_shell_exec_available()) {
            return '';
        }

        return @shell_exec($cmd);
    }

    /**
     * Cargar CAF
     */
    private function load_caf($ambiente) {
        $caf_data = Akibara_Database::get_caf_activo(39, $ambiente);

        if (!$caf_data) {
            return new WP_Error('no_caf', 'No hay CAF activo');
        }

        $caf_path = AKIBARA_SII_UPLOADS . 'caf/' . $caf_data->archivo;

        if (!file_exists($caf_path)) {
            return new WP_Error('caf_not_found', 'Archivo CAF no encontrado');
        }

        try {
            $cafXml = file_get_contents($caf_path);
            $billingPackage = $this->app->getPackageRegistry()->getBillingPackage();
            $cafLoader = $billingPackage->getIdentifierComponent()->getCafLoaderWorker();
            $cafBag = $cafLoader->load($cafXml);
            return $cafBag->getCaf();
        } catch (Exception $e) {
            return new WP_Error('caf_error', 'Error cargando CAF: ' . $e->getMessage());
        }
    }

    /**
     * Validar certificado (.p12 o .pem)
     */
    public function validate_certificate($filepath, $password) {
        if (!$this->certificate_loader) {
            return new WP_Error('no_libredte', 'LibreDTE no está disponible');
        }

        $extension = strtolower(pathinfo($filepath, PATHINFO_EXTENSION));

        try {
            // Si es PEM, cargarlo de forma especial
            if ($extension === 'pem') {
                $cert = $this->load_certificate_from_pem($filepath);
                if (is_wp_error($cert)) {
                    return $cert;
                }
            } else {
                // Intentar cargar .p12
                try {
                    $cert = $this->certificate_loader->loadFromFile($filepath, $password);
                } catch (Exception $e) {
                    // Si falla, intentar método legacy
                    $cert = $this->load_certificate_legacy($filepath, $password);
                    if (is_wp_error($cert)) {
                        return $cert;
                    }
                }
            }

            return [
                'nombre' => $cert->getName(),
                'rut' => $cert->getID(),
                'vigente' => $cert->isActive(),
                'valido_desde' => $cert->getFrom(),
                'valido_hasta' => $cert->getTo(),
                'formato' => ($extension === 'pem') ? 'PEM' : 'PKCS#12',
            ];
        } catch (Exception $e) {
            return new WP_Error('cert_invalid', 'Certificado inválido: ' . $e->getMessage());
        }
    }

    /**
     * Parsear CAF
     */
    public function parse_caf($filepath) {
        try {
            $xml = simplexml_load_file($filepath);

            if (!$xml) {
                return new WP_Error('caf_invalid', 'Archivo CAF inválido');
            }

            $da = $xml->DA ?? $xml->CAF->DA ?? null;

            if (!$da) {
                return new WP_Error('caf_invalid', 'Estructura CAF inválida');
            }

            return [
                'tipo' => (int) $da->TD,
                'desde' => (int) $da->RNG->D,
                'hasta' => (int) $da->RNG->H,
                'rut_emisor' => (string) $da->RE,
                'razon_social' => (string) $da->RS,
                'fecha_autorizacion' => (string) $da->FA,
                'vencimiento' => null, // CAF de boletas no vencen
            ];
        } catch (Exception $e) {
            return new WP_Error('caf_error', 'Error parseando CAF: ' . $e->getMessage());
        }
    }

    /**
     * Generar XML de boleta
     */
    public function generar_boleta_xml($data) {
        if (!$this->app) {
            return new WP_Error('no_libredte', 'LibreDTE no está disponible');
        }

        $ambiente = Akibara_SII::get_ambiente();

        // Cargar certificado
        $certificate = $this->load_certificate($ambiente);
        if (is_wp_error($certificate)) {
            return $certificate;
        }

        // Cargar CAF
        $caf = $this->load_caf($ambiente);
        if (is_wp_error($caf)) {
            return $caf;
        }

        try {
            $billingPackage = $this->app->getPackageRegistry()->getBillingPackage();
            $documentComponent = $billingPackage->getDocumentComponent();

            $documentBag = $documentComponent->bill(
                data: $data,
                caf: $caf,
                certificate: $certificate
            );

            // Asignar autorización DTE al emisor (para la Carátula, NO en XML)
            if ($documentBag->getEmisor()) {
                $autorizacionConfig = Akibara_SII::get_autorizacion_config();
                $autorizacionDte = new AutorizacionDte(
                    $autorizacionConfig['fechaResolucion'],
                    $autorizacionConfig['numeroResolucion']
                );
                $documentBag->getEmisor()->setAutorizacionDte($autorizacionDte);
            }

            $documento = $documentBag->getDocument();

            return [
                'xml' => $documento->getXml(),
                'folio' => $data['Encabezado']['IdDoc']['Folio'],
                'total' => $documento->getMontoTotal(),
                'documentBag' => $documentBag,
            ];
        } catch (Exception $e) {
            return new WP_Error('xml_error', 'Error generando XML: ' . $e->getMessage());
        }
    }

    /**
     * Crear sobre de envío para boleta (EnvioBOLETA)
     *
     * Genera el sobre XML firmado con la Carátula requerida por el SII
     */
    public function crear_sobre_boleta($boleta) {
        if (!$this->app) {
            return new WP_Error('no_libredte', 'LibreDTE no está disponible');
        }

        // Cargar certificado
        $certificate = $this->load_certificate($boleta->ambiente);
        if (is_wp_error($certificate)) {
            return $certificate;
        }

        try {
            // Obtener datos del emisor y configuración
            $emisor = Akibara_SII::get_emisor_config();
            $autorizacion = Akibara_SII::get_autorizacion_config();

            // El XML del documento ya tiene la firma del DTE
            $xmlDte = $boleta->xml_documento;

            // Verificar que el DTE tenga la firma
            if (strpos($xmlDte, '<Signature') === false) {
                return new WP_Error('dte_sin_firma', 'El documento DTE no tiene firma digital');
            }

            // Remover declaración XML si existe (se agregará al sobre)
            $xmlDte = preg_replace('/<\?xml[^>]+\?>\s*/', '', $xmlDte);

            // Obtener RUT del mandatario (quien envía) desde el certificado
            $rutEnvia = $certificate->getId();

            // Timestamp de la firma del sobre
            $timestamp = date('Y-m-d\TH:i:s');

            // Construir la Carátula
            $caratula = <<<XML
    <Caratula version="1.0">
      <RutEmisor>{$emisor['RUTEmisor']}</RutEmisor>
      <RutEnvia>{$rutEnvia}</RutEnvia>
      <RutReceptor>60803000-K</RutReceptor>
      <FchResol>{$autorizacion['fechaResolucion']}</FchResol>
      <NroResol>{$autorizacion['numeroResolucion']}</NroResol>
      <TmstFirmaEnv>{$timestamp}</TmstFirmaEnv>
      <SubTotDTE>
        <TpoDTE>39</TpoDTE>
        <NroDTE>1</NroDTE>
      </SubTotDTE>
    </Caratula>
XML;

            // Construir el sobre EnvioBOLETA completo
            $xmlSobre = <<<XML
<?xml version="1.0" encoding="ISO-8859-1"?>
<EnvioBOLETA xmlns="http://www.sii.cl/SiiDte" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.sii.cl/SiiDte EnvioBOLETA_v11.xsd" version="1.0">
  <SetDTE ID="LibreDTE_SetDoc">
{$caratula}
{$xmlDte}
  </SetDTE>
</EnvioBOLETA>
XML;

            // Cargar el XML en un documento para firmarlo
            $xmlDocument = new \Derafu\Xml\XmlDocument();
            $xmlDocument->loadXml($xmlSobre);

            // Obtener el servicio de firma desde LibreDTE
            $billingPackage = $this->app->getPackageRegistry()->getBillingPackage();
            $documentComponent = $billingPackage->getDocumentComponent();
            $dispatcherWorker = $documentComponent->getDispatcherWorker();

            // Acceder al signatureService mediante reflexión
            $reflection = new \ReflectionClass($dispatcherWorker);
            $property = $reflection->getProperty('signatureService');
            $property->setAccessible(true);
            $signatureService = $property->getValue($dispatcherWorker);

            // Firmar el sobre (SetDTE)
            $xmlSigned = $signatureService->signXml($xmlDocument, $certificate, 'LibreDTE_SetDoc');

            // Obtener XML firmado (puede ser string o XmlDocument)
            $xmlSobreFirmado = is_string($xmlSigned) ? $xmlSigned : $xmlSigned->saveXML();

            return [
                'xml' => $xmlSobreFirmado,
            ];
        } catch (Exception $e) {
            return new WP_Error('sobre_error', 'Error creando sobre: ' . $e->getMessage());
        }
    }

    /**
     * Enviar documento al SII (con reintentos automáticos)
     */
    public function enviar_documento($xml, $ambiente = 'certificacion') {
        if (!$this->app) {
            return new WP_Error('no_libredte', 'LibreDTE no está disponible');
        }

        // Cargar certificado
        $certificate = $this->load_certificate($ambiente);
        if (is_wp_error($certificate)) {
            return $certificate;
        }

        try {
            $billingPackage = $this->app->getPackageRegistry()->getBillingPackage();
            $integrationComponent = $billingPackage->getIntegrationComponent();
            $siiWorker = $integrationComponent->getSiiLazyWorker();

            $siiRequest = new SiiRequest(
                certificate: $certificate,
                options: [
                    'ambiente' => $this->get_sii_ambiente($ambiente),
                    'token' => [
                        'cache' => 'filesystem',
                        'ttl' => 300,
                    ],
                ]
            );

            // Autenticar con reintentos
            $token = $this->executeWithRetry(
                fn() => $siiWorker->authenticate($siiRequest),
                'autenticación SII'
            );

            // Crear documento XML
            $xmlDocument = new \Derafu\Xml\XmlDocument();
            $xmlDocument->loadXml($xml);

            // Enviar con reintentos
            $emisor = Akibara_SII::get_emisor_config();
            $trackId = $this->executeWithRetry(
                fn() => $siiWorker->sendXmlDocument(
                    request: $siiRequest,
                    doc: $xmlDocument,
                    company: $emisor['RUTEmisor'],
                    compress: false,
                    retry: 1
                ),
                'envío documento SII'
            );

            return [
                'track_id' => $trackId,
                'estado' => 'enviado',
            ];
        } catch (Exception $e) {
            return new WP_Error('envio_error', 'Error enviando al SII: ' . $e->getMessage());
        }
    }

    /**
     * Consultar estado de documento (con reintentos automáticos)
     */
    public function consultar_estado($track_id, $ambiente = 'certificacion') {
        if (!$this->app) {
            return new WP_Error('no_libredte', 'LibreDTE no está disponible');
        }

        // Cargar certificado
        $certificate = $this->load_certificate($ambiente);
        if (is_wp_error($certificate)) {
            return $certificate;
        }

        try {
            $billingPackage = $this->app->getPackageRegistry()->getBillingPackage();
            $integrationComponent = $billingPackage->getIntegrationComponent();
            $siiWorker = $integrationComponent->getSiiLazyWorker();

            $siiRequest = new SiiRequest(
                certificate: $certificate,
                options: [
                    'ambiente' => $this->get_sii_ambiente($ambiente),
                ]
            );

            $emisor = Akibara_SII::get_emisor_config();

            // Consultar con reintentos
            $response = $this->executeWithRetry(
                fn() => $siiWorker->checkXmlDocumentSentStatus(
                    request: $siiRequest,
                    trackId: $track_id,
                    company: $emisor['RUTEmisor']
                ),
                'consulta estado SII'
            );

            $data = $response->getData();

            return [
                'estado' => $data['status'] ?? 'pendiente',
                'glosa' => $data['description'] ?? $response->getReviewStatus(),
                'data' => $data,
            ];
        } catch (Exception $e) {
            return new WP_Error('consulta_error', 'Error consultando estado: ' . $e->getMessage());
        }
    }

    /**
     * Generar XML de RCOF
     */
    public function generar_rcof_xml($data) {
        if (!$this->app) {
            return new WP_Error('no_libredte', 'LibreDTE no está disponible');
        }

        $ambiente = 'produccion'; // RCOF solo en producción

        // Cargar certificado
        $certificate = $this->load_certificate($ambiente);
        if (is_wp_error($certificate)) {
            return $certificate;
        }

        try {
            $emisor = Akibara_SII::get_emisor_config();
            $timestamp = date('Y-m-d\TH:i:s');
            $documentId = 'CF_' . str_replace('-', '', $emisor['RUTEmisor']) . '_' . str_replace('-', '', $data['fecha']);

            // Construir XML usando DOMDocument
            $dom = new DOMDocument('1.0', 'ISO-8859-1');
            $dom->formatOutput = true;

            // Crear elemento raíz
            $consumoFolios = $dom->createElementNS('http://www.sii.cl/SiiDte', 'ConsumoFolios');
            $consumoFolios->setAttribute('xmlns:xsi', 'http://www.w3.org/2001/XMLSchema-instance');
            $consumoFolios->setAttribute('xsi:schemaLocation', 'http://www.sii.cl/SiiDte ConsumoFolio_v10.xsd');
            $consumoFolios->setAttribute('version', '1.0');
            $dom->appendChild($consumoFolios);

            // DocumentoConsumoFolios
            $docCF = $dom->createElement('DocumentoConsumoFolios');
            $docCF->setAttribute('ID', $documentId);
            $consumoFolios->appendChild($docCF);

            // Caratula
            $caratula = $dom->createElement('Caratula');
            $caratula->setAttribute('version', '1.0');
            $docCF->appendChild($caratula);

            $autorizacion = Akibara_SII::get_autorizacion_config();
            $caratula->appendChild($dom->createElement('RutEmisor', $emisor['RUTEmisor']));
            $caratula->appendChild($dom->createElement('RutEnvia', $certificate->getID()));
            $caratula->appendChild($dom->createElement('FchResol', $autorizacion['fechaResolucion']));
            $caratula->appendChild($dom->createElement('NroResol', (string) $autorizacion['numeroResolucion']));
            $caratula->appendChild($dom->createElement('FchInicio', $data['fecha']));
            $caratula->appendChild($dom->createElement('FchFinal', $data['fecha']));
            $caratula->appendChild($dom->createElement('SecEnvio', (string) $data['sec_envio']));
            $caratula->appendChild($dom->createElement('TmstFirmaEnv', $timestamp));

            // Resumen por cada tipo de documento (39, 41, 61)
            // Si hay datos agrupados por tipo, generar un Resumen por cada uno
            $tipos_documento = isset($data['por_tipo']) ? $data['por_tipo'] : [39 => $data];

            foreach ($tipos_documento as $tipo_dte => $tipo_data) {
                $resumen = $dom->createElement('Resumen');
                $docCF->appendChild($resumen);

                $resumen->appendChild($dom->createElement('TipoDocumento', (string) $tipo_dte));

                // Para boletas afectas (39) incluir neto, iva, tasa
                if ($tipo_dte == 39) {
                    $resumen->appendChild($dom->createElement('MntNeto', (string) ($tipo_data['neto'] ?? $data['neto'])));
                    $resumen->appendChild($dom->createElement('MntIva', (string) ($tipo_data['iva'] ?? $data['iva'])));
                    $resumen->appendChild($dom->createElement('TasaIVA', '19'));
                }

                // Para boletas exentas (41) solo monto exento
                if ($tipo_dte == 41) {
                    $resumen->appendChild($dom->createElement('MntExento', (string) ($tipo_data['exento'] ?? $tipo_data['total'] ?? 0)));
                } else {
                    $mnt_exento = $tipo_data['exento'] ?? $data['exento'] ?? 0;
                    if ($mnt_exento > 0) {
                        $resumen->appendChild($dom->createElement('MntExento', (string) $mnt_exento));
                    }
                }

                $resumen->appendChild($dom->createElement('MntTotal', (string) ($tipo_data['total'] ?? $data['total'])));

                $cantidad = $tipo_data['cantidad'] ?? $data['cantidad'];
                $resumen->appendChild($dom->createElement('FoliosEmitidos', (string) $cantidad));
                $resumen->appendChild($dom->createElement('FoliosAnulados', '0'));
                $resumen->appendChild($dom->createElement('FoliosUtilizados', (string) $cantidad));

                // RangoUtilizados
                $folio_ini = $tipo_data['folio_min'] ?? $data['folio_inicial'] ?? null;
                $folio_fin = $tipo_data['folio_max'] ?? $data['folio_final'] ?? null;
                if ($folio_ini && $folio_fin) {
                    $rangoEl = $dom->createElement('RangoUtilizados');
                    $rangoEl->appendChild($dom->createElement('Inicial', (string) $folio_ini));
                    $rangoEl->appendChild($dom->createElement('Final', (string) $folio_fin));
                    $resumen->appendChild($rangoEl);
                }
            }

            // Firmar documento
            $xml_firmado = $this->firmar_xml($dom, $certificate, $documentId);

            return [
                'xml' => $xml_firmado,
                'document_id' => $documentId,
            ];
        } catch (Exception $e) {
            return new WP_Error('rcof_error', 'Error generando RCOF: ' . $e->getMessage());
        }
    }

    /**
     * Generar PDF de boleta desde XML
     * Formato: Voucher SII e-boleta (80mm)
     *
     * @param string $xml XML del documento
     * @param array $options Opciones de renderizado
     * @return string|WP_Error PDF binario o error
     */
    public function generar_pdf($xml, $options = []) {
        try {
            // Parsear XML
            $xmlObj = simplexml_load_string($xml);
            if (!$xmlObj) {
                return new WP_Error('xml_error', 'Error parseando XML');
            }

            $documento = $xmlObj->Documento ?? $xmlObj->children('http://www.sii.cl/SiiDte')[0];
            $encabezado = $documento->Encabezado;
            $idDoc = $encabezado->IdDoc;
            $emisor = $encabezado->Emisor;
            $receptor = $encabezado->Receptor;
            $totales = $encabezado->Totales;

            // Extraer datos
            $tipoDte = (int)$idDoc->TipoDTE;
            $folio = (string)$idDoc->Folio;
            $fechaEmision = (string)$idDoc->FchEmis;
            $rutEmisor = (string)$emisor->RUTEmisor;
            $razonSocialEmisor = (string)($emisor->RznSocEmisor ?? $emisor->RznSoc);
            $giroEmisor = (string)($emisor->GiroEmisor ?? $emisor->GiroEmis);
            $dirEmisor = (string)$emisor->DirOrigen;
            $cmnaEmisor = (string)$emisor->CmnaOrigen;
            $rutReceptor = (string)$receptor->RUTRecep;
            $razonSocialReceptor = (string)$receptor->RznSocRecep;
            $dirReceptor = (string)$receptor->DirRecep;
            $montoNeto = (int)$totales->MntNeto;
            $montoIva = (int)$totales->IVA;
            $montoExento = (int)($totales->MntExe ?? 0);
            $montoTotal = (int)$totales->MntTotal;

            // Detalle items
            $detalles = [];
            foreach ($documento->Detalle as $det) {
                $detalles[] = [
                    'nombre' => (string)$det->NmbItem,
                    'cantidad' => (float)$det->QtyItem,
                    'precio' => (float)$det->PrcItem,
                    'monto' => (float)$det->MontoItem,
                    'exento' => isset($det->IndExe) ? (int)$det->IndExe : 0,
                    'unidad' => (string)($det->UnmdItem ?? ''),
                ];
            }

            // TED para PDF417
            $ted = $documento->TED;
            $tedString = '';
            if ($ted) {
                $dom = new DOMDocument();
                $dom->loadXML($ted->asXML());
                $tedString = $dom->saveXML($dom->documentElement);
                $tedString = preg_replace('/>\s+</', '><', $tedString);
            }

            // Tipo documento
            $tipoTexto = $tipoDte == 39 ? 'BOLETA ELECTRONICA' : ($tipoDte == 41 ? 'BOLETA EXENTA' : 'DTE ' . $tipoDte);

            // Generar HTML para mPDF (formato voucher 80mm)
            $html = $this->generar_html_boleta([
                'tipo' => $tipoTexto,
                'folio' => $folio,
                'fecha' => $fechaEmision,
                'emisor' => [
                    'rut' => $rutEmisor,
                    'razon_social' => $razonSocialEmisor,
                    'giro' => $giroEmisor,
                    'direccion' => $dirEmisor,
                    'comuna' => $cmnaEmisor,
                ],
                'receptor' => [
                    'rut' => $rutReceptor,
                    'razon_social' => $razonSocialReceptor,
                    'direccion' => $dirReceptor,
                ],
                'detalles' => $detalles,
                'totales' => [
                    'neto' => $montoNeto,
                    'iva' => $montoIva,
                    'exento' => $montoExento,
                    'total' => $montoTotal,
                ],
                'ted' => $tedString,
            ]);

            // Crear PDF con mPDF
            $mpdf = new \Mpdf\Mpdf([
                'mode' => 'utf-8',
                'format' => [80, 200], // Ancho 80mm, alto inicial 200mm
                'margin_left' => 5,
                'margin_right' => 5,
                'margin_top' => 5,
                'margin_bottom' => 5,
            ]);

            $mpdf->WriteHTML($html);
            return $mpdf->Output('', \Mpdf\Output\Destination::STRING_RETURN);

        } catch (Exception $e) {
            return new WP_Error('pdf_error', 'Error generando PDF: ' . $e->getMessage());
        }
    }

    /**
     * Generar HTML para boleta (formato voucher SII)
     */
    private function generar_html_boleta($data) {
        $html = '<style>
            body { font-family: "Courier New", monospace; font-size: 9pt; }
            .header { margin-bottom: 5mm; }
            .empresa { font-weight: bold; font-size: 10pt; }
            .tipo-doc { font-weight: bold; margin: 3mm 0; }
            .linea { border-top: 1px solid #000; margin: 2mm 0; }
            .item { margin-bottom: 2mm; }
            .total { font-weight: bold; font-size: 10pt; margin-top: 3mm; }
            .iva-info { font-size: 8pt; margin: 3mm 0; }
            .timbre { margin-top: 5mm; text-align: center; }
            .timbre-texto { font-size: 8pt; margin-top: 2mm; }
            .right { text-align: right; }
        </style>';

        // Emisor
        $html .= '<div class="header">';
        $html .= '<div class="empresa">' . htmlspecialchars($data['emisor']['razon_social']) . '</div>';
        $html .= '<div>' . htmlspecialchars($data['emisor']['rut']) . '</div>';
        $html .= '<div>Giro: ' . htmlspecialchars($data['emisor']['giro']) . '</div>';
        $html .= '<div>' . htmlspecialchars($data['emisor']['direccion']) . '</div>';
        $html .= '<div>' . htmlspecialchars($data['emisor']['comuna']) . '</div>';
        $html .= '</div>';

        // Tipo y numero
        $html .= '<div class="tipo-doc">' . $data['tipo'] . ' NUMERO: ' . $data['folio'] . '</div>';
        $html .= '<div>Fecha: ' . $data['fecha'] . '</div>';

        // Receptor (solo si no es consumidor final)
        if ($data['receptor']['rut'] && $data['receptor']['rut'] != '66666666-6') {
            $html .= '<div style="margin-top:2mm;">RUT Cliente: ' . htmlspecialchars($data['receptor']['rut']) . '</div>';
        }
        if ($data['receptor']['direccion']) {
            $html .= '<div>Direccion: ' . htmlspecialchars($data['receptor']['direccion']) . '</div>';
        }

        // Detalle
        $html .= '<div class="linea"></div>';
        foreach ($data['detalles'] as $det) {
            $html .= '<div class="item">';
            $html .= '<div>' . htmlspecialchars($det['nombre']) . ($det['exento'] ? ' (E)' : '') . '</div>';
            $cantidad = $det['cantidad'] . ($det['unidad'] ? ' ' . $det['unidad'] : '');
            $html .= '<div>' . $cantidad . ' x $' . number_format($det['precio'], 0, ',', '.') .
                     '<span style="float:right;">$ ' . number_format($det['monto'], 0, ',', '.') . '</span></div>';
            $html .= '</div>';
        }
        $html .= '<div class="linea"></div>';

        // Totales
        $html .= '<div class="total">Venta <span style="float:right;">$ ' . number_format($data['totales']['total'], 0, ',', '.') . '</span></div>';
        $html .= '<div class="iva-info">El IVA incluido en esta boleta es de: $ ' . number_format($data['totales']['iva'], 0, ',', '.') . '</div>';

        // Timbre PDF417 - Usando TCPDF2DBarcode como LibreDTE (ECL nivel 5)
        if (!empty($data['ted'])) {
            $html .= '<div class="timbre">';
            // Generar imagen PNG del barcode usando metodo de LibreDTE
            $pdf417 = new \TCPDF2DBarcode($data['ted'], 'PDF417,,5');
            $pngData = $pdf417->getBarcodePngData(2, 2, [0,0,0]);
            $base64 = 'data:image/png;base64,' . base64_encode($pngData);
            $html .= '<img src="' . $base64 . '" style="width:65mm; height:auto;" />';
            $html .= '<div class="timbre-texto">Timbre Electronico SII<br/>Res. 0 de 2025<br/>Verifique documento en sii.cl</div>';
            $html .= '</div>';
        }

        return $html;
    }

    /**
     * Generar PDF de boleta desde DocumentBag (cuando se acaba de crear)
     *
     * @param DocumentBagInterface $documentBag DocumentBag con el documento
     * @param array $options Opciones de renderizado
     * @return string|WP_Error PDF binario o error
     */
    public function generar_pdf_from_bag($documentBag, $options = []) {
        try {
            // Obtener XML del documento
            $documento = $documentBag->getDocument();
            $xml = $documento->getXml();

            // Usar el método generar_pdf que ya tiene toda la lógica
            return $this->generar_pdf($xml, $options);

        } catch (Exception $e) {
            return new WP_Error('pdf_error', 'Error generando PDF: ' . $e->getMessage());
        }
    }

    /**
     * Firmar XML (corregido para coincidir con LibreDTE)
     *
     * Proceso de firma:
     * 1. C14N del nodo referenciado + conversión ISO-8859-1 + SHA1 = DigestValue
     * 2. Construir SignedInfo con xmlns:xsi
     * 3. C14N de SignedInfo + conversión ISO-8859-1 + RSA-SHA1 = SignatureValue
     * 4. Insertar firma en formato compacto (sin espacios/newlines)
     */
    private function firmar_xml($dom, $certificate, $referenceId) {
        // Convertir DOMDocument a XmlDocument de LibreDTE
        $xmlDocument = new \Derafu\Xml\XmlDocument();
        $xmlDocument->loadXml($dom->saveXML());

        // 1. Calcular DigestValue usando C14N con ISO-8859-1 (igual que LibreDTE)
        $xpath = '//*[@ID="' . $referenceId . '"]';
        $nodeToDigest = $xmlDocument->getNodes($xpath)->item(0);
        if (!$nodeToDigest) {
            throw new Exception("No se encontró el nodo con ID: " . $referenceId);
        }
        $c14n = $nodeToDigest->C14N();
        $c14n = mb_convert_encoding($c14n, 'ISO-8859-1', 'UTF-8');
        $digestValue = base64_encode(sha1($c14n, true));

        // 2. Obtener datos del certificado (usando métodos correctos de LibreDTE)
        $x509Certificate = $certificate->getCertificate(true);
        $modulus = $certificate->getModulus();
        $exponent = $certificate->getExponent();

        // 3. Construir estructura de la firma como array (igual que LibreDTE Signature class)
        $signatureData = [
            'Signature' => [
                '@attributes' => [
                    'xmlns' => 'http://www.w3.org/2000/09/xmldsig#',
                ],
                'SignedInfo' => [
                    '@attributes' => [
                        'xmlns:xsi' => 'http://www.w3.org/2001/XMLSchema-instance',
                    ],
                    'CanonicalizationMethod' => [
                        '@attributes' => [
                            'Algorithm' => 'http://www.w3.org/TR/2001/REC-xml-c14n-20010315',
                        ],
                    ],
                    'SignatureMethod' => [
                        '@attributes' => [
                            'Algorithm' => 'http://www.w3.org/2000/09/xmldsig#rsa-sha1',
                        ],
                    ],
                    'Reference' => [
                        '@attributes' => [
                            'URI' => '#' . $referenceId,
                        ],
                        'Transforms' => [
                            'Transform' => [
                                '@attributes' => [
                                    'Algorithm' => 'http://www.w3.org/TR/2001/REC-xml-c14n-20010315',
                                ],
                            ],
                        ],
                        'DigestMethod' => [
                            '@attributes' => [
                                'Algorithm' => 'http://www.w3.org/2000/09/xmldsig#sha1',
                            ],
                        ],
                        'DigestValue' => $digestValue,
                    ],
                ],
                'SignatureValue' => '', // Se llenará después de firmar
                'KeyInfo' => [
                    'KeyValue' => [
                        'RSAKeyValue' => [
                            'Modulus' => $modulus,
                            'Exponent' => $exponent,
                        ],
                    ],
                    'X509Data' => [
                        'X509Certificate' => $x509Certificate,
                    ],
                ],
            ],
        ];

        // 4. Función para crear XML desde array (similar a como lo hace LibreDTE)
        $arrayToXml = function(array $data, DOMDocument $doc, ?DOMElement $parent = null) use (&$arrayToXml) {
            foreach ($data as $key => $value) {
                if ($key === '@attributes') {
                    continue;
                }
                if ($key === '@value') {
                    $parent->nodeValue = (string) $value;
                    continue;
                }

                $element = $doc->createElement($key);

                if (is_array($value)) {
                    if (isset($value['@attributes'])) {
                        foreach ($value['@attributes'] as $attrName => $attrValue) {
                            $element->setAttribute($attrName, $attrValue);
                        }
                    }
                    if (isset($value['@value'])) {
                        $element->nodeValue = (string) $value['@value'];
                    } else {
                        $arrayToXml($value, $doc, $element);
                    }
                } else {
                    $element->nodeValue = (string) $value;
                }

                if ($parent) {
                    $parent->appendChild($element);
                } else {
                    $doc->appendChild($element);
                }
            }
        };

        // 5. Construir XML de la firma para obtener C14N de SignedInfo
        $signatureXmlDoc = new \Derafu\Xml\XmlDocument();
        $signatureXmlDoc->formatOutput = false;
        $arrayToXml($signatureData, $signatureXmlDoc);

        // 6. Obtener C14N de SignedInfo con ISO-8859-1
        $signedInfoNode = $signatureXmlDoc->getElementsByTagName('SignedInfo')->item(0);
        $signedInfoC14N = $signedInfoNode->C14N();
        $signedInfoC14N = mb_convert_encoding($signedInfoC14N, 'ISO-8859-1', 'UTF-8');

        // 7. Firmar SignedInfo
        $signature = '';
        if (!openssl_sign($signedInfoC14N, $signature, $certificate->getPrivateKey(), OPENSSL_ALGO_SHA1)) {
            throw new Exception("Error al firmar: " . openssl_error_string());
        }
        $signatureValue = base64_encode($signature);

        // 8. Actualizar SignatureValue y reconstruir XML (sin line breaks como LibreDTE)
        $signatureData['Signature']['SignatureValue'] = $signatureValue; // Sin wordwrap

        $signatureXmlDoc = new \Derafu\Xml\XmlDocument();
        $signatureXmlDoc->formatOutput = false;
        $arrayToXml($signatureData, $signatureXmlDoc);

        // 9. Obtener XML de firma en formato compacto (sin espacios ni saltos de línea)
        $signatureXml = $signatureXmlDoc->saveXML($signatureXmlDoc->documentElement);

        // 10. Agregar firma usando reemplazo de string (como hace LibreDTE)
        $placeholder = $dom->createElement('Signature');
        $dom->documentElement->appendChild($placeholder);

        // Guardar XML y reemplazar placeholder con firma real
        $xmlWithPlaceholder = $dom->saveXML();
        $xmlSigned = str_replace('<Signature/>', $signatureXml, $xmlWithPlaceholder);

        return $xmlSigned;
    }
}
