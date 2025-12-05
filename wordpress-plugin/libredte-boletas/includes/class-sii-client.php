<?php
/**
 * Clase para comunicación con SII
 */

defined('ABSPATH') || exit;

use Derafu\Certificate\Service\CertificateLoader;
use libredte\lib\Core\Application;
use libredte\lib\Core\Package\Billing\Component\Integration\Enum\SiiAmbiente;
use libredte\lib\Core\Package\Billing\Component\Integration\Support\SiiRequest;

class LibreDTE_SII_Client {

    private $app;
    private $certificate_loader;

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
     * Obtener ambiente SII
     */
    private function get_sii_ambiente($ambiente) {
        return $ambiente === 'produccion'
            ? SiiAmbiente::PRODUCCION
            : SiiAmbiente::CERTIFICACION;
    }

    /**
     * Cargar certificado
     */
    private function load_certificate($ambiente) {
        $cert_file = get_option("libredte_cert_{$ambiente}_file", '');
        $cert_password = base64_decode(get_option("libredte_cert_{$ambiente}_password", ''));

        if (empty($cert_file)) {
            return new WP_Error('no_cert', 'No hay certificado configurado');
        }

        $cert_path = LIBREDTE_BOLETAS_UPLOADS . 'certs/' . $cert_file;

        if (!file_exists($cert_path)) {
            return new WP_Error('cert_not_found', 'Archivo de certificado no encontrado');
        }

        try {
            return $this->certificate_loader->loadFromFile($cert_path, $cert_password);
        } catch (Exception $e) {
            return new WP_Error('cert_error', 'Error cargando certificado: ' . $e->getMessage());
        }
    }

    /**
     * Cargar CAF
     */
    private function load_caf($ambiente) {
        $caf_data = LibreDTE_Database::get_caf_activo(39, $ambiente);

        if (!$caf_data) {
            return new WP_Error('no_caf', 'No hay CAF activo');
        }

        $caf_path = LIBREDTE_BOLETAS_UPLOADS . 'caf/' . $caf_data->archivo;

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
     * Validar certificado
     */
    public function validate_certificate($filepath, $password) {
        if (!$this->certificate_loader) {
            return new WP_Error('no_libredte', 'LibreDTE no está disponible');
        }

        try {
            $cert = $this->certificate_loader->loadFromFile($filepath, $password);

            return [
                'nombre' => $cert->getName(),
                'rut' => $cert->getID(),
                'vigente' => $cert->isActive(),
                'valido_desde' => $cert->getFrom(),
                'valido_hasta' => $cert->getTo(),
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

        $ambiente = LibreDTE_Boletas::get_ambiente();

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

            $documento = $documentBag->getDocument();

            return [
                'xml' => $documento->getXml(),
                'folio' => $data['Encabezado']['IdDoc']['Folio'],
                'total' => $documento->getMontoTotal(),
            ];
        } catch (Exception $e) {
            return new WP_Error('xml_error', 'Error generando XML: ' . $e->getMessage());
        }
    }

    /**
     * Crear sobre de envío para boleta
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
            $billingPackage = $this->app->getPackageRegistry()->getBillingPackage();
            $documentComponent = $billingPackage->getDocumentComponent();
            $dispatcherWorker = $documentComponent->getDispatcherWorker();

            // Cargar documento desde XML
            $xmlDoc = new \Derafu\Xml\XmlDocument();
            $xmlDoc->loadXml($boleta->xml_documento);

            // Crear sobre
            $envelope = new \libredte\lib\Core\Package\Billing\Component\Document\Support\DocumentEnvelope();
            $envelope->setCertificate($certificate);

            // Aquí necesitaríamos reconstruir el DocumentBag desde el XML
            // Por ahora retornamos el XML individual
            return [
                'xml' => $boleta->xml_documento,
            ];
        } catch (Exception $e) {
            return new WP_Error('sobre_error', 'Error creando sobre: ' . $e->getMessage());
        }
    }

    /**
     * Enviar documento al SII
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

            // Autenticar
            $token = $siiWorker->authenticate($siiRequest);

            // Crear documento XML
            $xmlDocument = new \Derafu\Xml\XmlDocument();
            $xmlDocument->loadXml($xml);

            // Enviar
            $emisor = LibreDTE_Boletas::get_emisor_config();
            $trackId = $siiWorker->sendXmlDocument(
                request: $siiRequest,
                doc: $xmlDocument,
                company: $emisor['RUTEmisor'],
                compress: false,
                retry: 3
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
     * Consultar estado de documento
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

            $emisor = LibreDTE_Boletas::get_emisor_config();
            $response = $siiWorker->checkXmlDocumentSentStatus(
                request: $siiRequest,
                trackId: $track_id,
                company: $emisor['RUTEmisor']
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
            $emisor = LibreDTE_Boletas::get_emisor_config();
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

            $caratula->appendChild($dom->createElement('RutEmisor', $emisor['RUTEmisor']));
            $caratula->appendChild($dom->createElement('RutEnvia', $certificate->getID()));
            $caratula->appendChild($dom->createElement('FchResol', $emisor['autorizacionDte']['fechaResolucion']));
            $caratula->appendChild($dom->createElement('NroResol', (string) $emisor['autorizacionDte']['numeroResolucion']));
            $caratula->appendChild($dom->createElement('FchInicio', $data['fecha']));
            $caratula->appendChild($dom->createElement('FchFinal', $data['fecha']));
            $caratula->appendChild($dom->createElement('SecEnvio', (string) $data['sec_envio']));
            $caratula->appendChild($dom->createElement('TmstFirmaEnv', $timestamp));

            // Resumen
            $resumen = $dom->createElement('Resumen');
            $docCF->appendChild($resumen);

            $resumen->appendChild($dom->createElement('TipoDocumento', '39'));
            $resumen->appendChild($dom->createElement('MntNeto', (string) $data['neto']));
            $resumen->appendChild($dom->createElement('MntIva', (string) $data['iva']));
            $resumen->appendChild($dom->createElement('TasaIVA', '19'));
            $resumen->appendChild($dom->createElement('MntExento', (string) $data['exento']));
            $resumen->appendChild($dom->createElement('MntTotal', (string) $data['total']));
            $resumen->appendChild($dom->createElement('FoliosEmitidos', (string) $data['cantidad']));
            $resumen->appendChild($dom->createElement('FoliosAnulados', '0'));
            $resumen->appendChild($dom->createElement('FoliosUtilizados', (string) $data['cantidad']));

            // RangoUtilizados
            if ($data['folio_inicial'] && $data['folio_final']) {
                $rangoEl = $dom->createElement('RangoUtilizados');
                $rangoEl->appendChild($dom->createElement('Inicial', (string) $data['folio_inicial']));
                $rangoEl->appendChild($dom->createElement('Final', (string) $data['folio_final']));
                $resumen->appendChild($rangoEl);
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
     * Firmar XML
     */
    private function firmar_xml($dom, $certificate, $referenceId) {
        // Obtener nodo a firmar
        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('sii', 'http://www.sii.cl/SiiDte');

        $nodes = $xpath->query("//*[@ID='" . $referenceId . "']");
        if ($nodes->length == 0) {
            throw new Exception("No se encontró el nodo con ID: " . $referenceId);
        }
        $refNode = $nodes->item(0);

        // Canonicalizar
        $c14n = $refNode->C14N(false, false);
        $digestValue = base64_encode(sha1($c14n, true));

        // Obtener datos del certificado
        $x509Clean = $certificate->getCertificate(true);
        $pubKey = openssl_pkey_get_public($certificate->getPublicKey());
        $pubKeyDetails = openssl_pkey_get_details($pubKey);
        $modulus = base64_encode($pubKeyDetails['rsa']['n']);
        $exponent = base64_encode($pubKeyDetails['rsa']['e']);

        // Crear Signature
        $signatureXml = '<Signature xmlns="http://www.w3.org/2000/09/xmldsig#">'
            . '<SignedInfo xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">'
            . '<CanonicalizationMethod Algorithm="http://www.w3.org/TR/2001/REC-xml-c14n-20010315"/>'
            . '<SignatureMethod Algorithm="http://www.w3.org/2000/09/xmldsig#rsa-sha1"/>'
            . '<Reference URI="#' . $referenceId . '">'
            . '<Transforms>'
            . '<Transform Algorithm="http://www.w3.org/TR/2001/REC-xml-c14n-20010315"/>'
            . '</Transforms>'
            . '<DigestMethod Algorithm="http://www.w3.org/2000/09/xmldsig#sha1"/>'
            . '<DigestValue>' . $digestValue . '</DigestValue>'
            . '</Reference>'
            . '</SignedInfo>'
            . '<SignatureValue></SignatureValue>'
            . '<KeyInfo>'
            . '<KeyValue>'
            . '<RSAKeyValue>'
            . '<Modulus>' . $modulus . '</Modulus>'
            . '<Exponent>' . $exponent . '</Exponent>'
            . '</RSAKeyValue>'
            . '</KeyValue>'
            . '<X509Data>'
            . '<X509Certificate>' . $x509Clean . '</X509Certificate>'
            . '</X509Data>'
            . '</KeyInfo>'
            . '</Signature>';

        // Cargar Signature
        $signatureDoc = new DOMDocument('1.0', 'ISO-8859-1');
        $signatureDoc->loadXML($signatureXml);

        // Canonicalizar SignedInfo
        $signedInfoNode = $signatureDoc->getElementsByTagName('SignedInfo')->item(0);
        $signedInfoC14N = $signedInfoNode->C14N(false, false);

        // Firmar
        $signature = '';
        if (!openssl_sign($signedInfoC14N, $signature, $certificate->getPrivateKey(), OPENSSL_ALGO_SHA1)) {
            throw new Exception("Error al firmar: " . openssl_error_string());
        }
        $signatureValue = base64_encode($signature);

        // Actualizar SignatureValue
        $signatureDoc->getElementsByTagName('SignatureValue')->item(0)->nodeValue = $signatureValue;

        // Insertar firma
        $signatureNode = $dom->importNode($signatureDoc->documentElement, true);
        $dom->documentElement->appendChild($signatureNode);

        return $dom->saveXML();
    }
}
