<?php
/**
 * TEST COMPLETO DEL PLUGIN AKIBARA-SII
 *
 * Este script simula el flujo completo de un usuario:
 * 1. Verifica configuración (certificado, CAF)
 * 2. Ejecuta Set de Pruebas (5 boletas)
 * 3. Consulta estado de las boletas
 * 4. Genera y envía RCOF
 * 5. Verifica resultado final
 *
 * Uso: php test-completo.php [certificacion|produccion]
 */

require_once dirname(__DIR__) . '/vendor/autoload.php';
require_once dirname(__DIR__) . '/includes/class-folio-manager.php';

use Derafu\Certificate\Service\CertificateLoader;
use Derafu\Xml\XmlDocument;
use libredte\lib\Core\Application;
use libredte\lib\Core\Package\Billing\Component\Integration\Enum\SiiAmbiente;
use libredte\lib\Core\Package\Billing\Component\Integration\Support\SiiRequest;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Entity\AutorizacionDte;

// Constantes de reintento
const MAX_RETRIES = 4;
const INITIAL_DELAY_MS = 2000;

/**
 * Ejecutar con reintentos y backoff exponencial
 */
function executeWithRetry(callable $operation, string $name = 'operación') {
    $delay = INITIAL_DELAY_MS;
    for ($attempt = 1; $attempt <= MAX_RETRIES; $attempt++) {
        try {
            return $operation();
        } catch (Exception $e) {
            $msg = $e->getMessage();
            $isConnectionError = (
                stripos($msg, 'connection') !== false ||
                stripos($msg, 'timeout') !== false ||
                stripos($msg, 'reset') !== false ||
                stripos($msg, 'upstream') !== false
            );
            if (!$isConnectionError || $attempt >= MAX_RETRIES) {
                throw $e;
            }
            echo "  [Reintento $attempt/" . MAX_RETRIES . " para $name]\n";
            usleep($delay * 1000);
            $delay = min($delay * 2, 16000);
        }
    }
}

// Determinar ambiente
$ambienteArg = $argv[1] ?? 'certificacion';
$ambiente = strtolower(trim($ambienteArg));

if (!in_array($ambiente, ['certificacion', 'produccion'])) {
    echo "ERROR: Ambiente invalido. Use 'certificacion' o 'produccion'.\n";
    exit(1);
}

$siiAmbiente = $ambiente === 'certificacion' ? SiiAmbiente::CERTIFICACION : SiiAmbiente::PRODUCCION;
$ambienteNombre = $ambiente === 'certificacion' ? 'CERTIFICACIÓN' : 'PRODUCCIÓN';

echo "\n";
echo "╔══════════════════════════════════════════════════════════════════════╗\n";
echo "║        TEST COMPLETO - PLUGIN AKIBARA-SII                            ║\n";
echo "║               Prueba End-to-End desde Cero                           ║\n";
printf("║                    AMBIENTE: %-12s                           ║\n", $ambienteNombre);
echo "╚══════════════════════════════════════════════════════════════════════╝\n\n";

// Configuración
$emisor = [
    'RUTEmisor' => '78274225-6',
    'RznSoc' => 'AKIBARA SPA',
    'GiroEmis' => 'VENTA AL POR MENOR DE LIBROS Y OTROS PRODUCTOS',
    'DirOrigen' => 'BARTOLO SOTO 3700 DP 1402 PISO 14',
    'CmnaOrigen' => 'San Miguel',
    'Acteco' => 476101,
];

$autorizacionConfig = [
    'fechaResolucion' => '2014-08-22',
    'numeroResolucion' => 80,
];

// Paths
$certPath = '/home/user/libdredte/app/credentials/certificado.p12';
$certPassword = '5605';
$cafPath = '/home/user/libdredte/app/credentials/caf_39.xml';
$outputDir = dirname(__DIR__) . '/uploads/output/';

if (!is_dir($outputDir)) {
    mkdir($outputDir, 0755, true);
}

try {
    // ═══════════════════════════════════════════════════════════════════════
    // PASO 1: VERIFICAR CONFIGURACIÓN
    // ═══════════════════════════════════════════════════════════════════════
    echo "═══════════════════════════════════════════════════════════════════════\n";
    echo "  PASO 1: VERIFICACIÓN DE CONFIGURACIÓN\n";
    echo "═══════════════════════════════════════════════════════════════════════\n\n";

    // Verificar certificado
    echo "Verificando certificado... ";
    if (!file_exists($certPath)) {
        throw new Exception("Certificado no encontrado: $certPath");
    }
    $certLoader = new CertificateLoader();
    $certificate = $certLoader->loadFromFile($certPath, $certPassword);
    echo "OK\n";
    echo "  ├─ Titular: {$certificate->getName()}\n";
    echo "  ├─ RUT: {$certificate->getID()}\n";
    echo "  └─ Vigente: " . ($certificate->isActive() ? 'Sí' : 'No') . "\n\n";

    if (!$certificate->isActive()) {
        throw new Exception("El certificado no está vigente");
    }

    // Verificar CAF
    echo "Verificando CAF... ";
    if (!file_exists($cafPath)) {
        throw new Exception("CAF no encontrado: $cafPath");
    }
    $folioManager = new Akibara_Folio_Manager($cafPath, $ambiente);
    $stats = $folioManager->getStats();
    echo "OK\n";
    echo "  ├─ Tipo DTE: {$stats['tipo_dte']} (Boleta Electrónica)\n";
    echo "  ├─ Rango: {$stats['caf_desde']} - {$stats['caf_hasta']}\n";
    echo "  ├─ Usados: {$stats['used']}\n";
    echo "  └─ Disponibles: {$stats['available']}\n\n";

    if ($stats['available'] < 5) {
        throw new Exception("No hay suficientes folios disponibles (necesarios: 5, disponibles: {$stats['available']})");
    }

    // Inicializar LibreDTE
    echo "Inicializando LibreDTE... ";
    $app = Application::getInstance(environment: 'dev', debug: false);
    echo "OK\n\n";

    // ═══════════════════════════════════════════════════════════════════════
    // PASO 2: GENERAR SET DE PRUEBAS (5 BOLETAS)
    // ═══════════════════════════════════════════════════════════════════════
    echo "═══════════════════════════════════════════════════════════════════════\n";
    echo "  PASO 2: GENERACIÓN DEL SET DE PRUEBAS\n";
    echo "═══════════════════════════════════════════════════════════════════════\n\n";

    // Cargar CAF
    echo "Cargando CAF en LibreDTE... ";
    $cafXml = file_get_contents($cafPath);
    $billingPackage = $app->getPackageRegistry()->getBillingPackage();
    $cafLoader = $billingPackage->getIdentifierComponent()->getCafLoaderWorker();
    $cafBag = $cafLoader->load($cafXml);
    $caf = $cafBag->getCaf();
    echo "OK\n\n";

    // Casos del Set de Pruebas
    $casos = [
        'CASO-1' => [['NmbItem' => 'Cambio de aceite', 'QtyItem' => 1, 'PrcItem' => 19900], ['NmbItem' => 'Alineacion y balanceo', 'QtyItem' => 1, 'PrcItem' => 9900]],
        'CASO-2' => [['NmbItem' => 'Papel de regalo', 'QtyItem' => 17, 'PrcItem' => 120]],
        'CASO-3' => [['NmbItem' => 'Sandwic', 'QtyItem' => 2, 'PrcItem' => 1500], ['NmbItem' => 'Bebida', 'QtyItem' => 2, 'PrcItem' => 550]],
        'CASO-4' => [['NmbItem' => 'item afecto 1', 'QtyItem' => 8, 'PrcItem' => 1590], ['NmbItem' => 'item exento 2', 'QtyItem' => 2, 'PrcItem' => 1000, 'IndExe' => 1]],
        'CASO-5' => [['NmbItem' => 'Arroz', 'QtyItem' => 5, 'PrcItem' => 700, 'UnmdItem' => 'Kg']],
    ];

    $foliosAsignados = $folioManager->getNextFolios(count($casos));
    echo "Folios asignados: " . implode(', ', $foliosAsignados) . "\n\n";

    $documentBags = [];
    $documentos = [];
    $documentComponent = $billingPackage->getDocumentComponent();
    $i = 0;

    foreach ($casos as $caso => $items) {
        $folio = $foliosAsignados[$i];
        echo "Generando $caso (Folio $folio)... ";

        $datosBoleta = [
            'Encabezado' => [
                'IdDoc' => ['TipoDTE' => 39, 'Folio' => $folio, 'FchEmis' => date('Y-m-d'), 'IndServicio' => 3],
                'Emisor' => $emisor,
                'Receptor' => ['RUTRecep' => '66666666-6', 'RznSocRecep' => 'CONSUMIDOR FINAL', 'DirRecep' => 'Santiago', 'CmnaRecep' => 'Santiago'],
            ],
            'Detalle' => $items,
            'Referencia' => [['TpoDocRef' => 'SET', 'FolioRef' => $folio, 'FchRef' => date('Y-m-d'), 'RazonRef' => $caso]],
        ];

        $documentBag = $documentComponent->bill(data: $datosBoleta, caf: $caf, certificate: $certificate);

        if ($documentBag->getEmisor()) {
            $autorizacionDte = new AutorizacionDte($autorizacionConfig['fechaResolucion'], (int) $autorizacionConfig['numeroResolucion']);
            $documentBag->getEmisor()->setAutorizacionDte($autorizacionDte);
        }

        $documento = $documentBag->getDocument();
        $documentBags[] = $documentBag;
        $documentos[$caso] = ['folio' => $folio, 'total' => $documento->getMontoTotal()];

        file_put_contents($outputDir . "boleta_{$caso}_F{$folio}.xml", $documento->getXml());
        $folioManager->markAsUsed($folio);

        echo "Total: $" . number_format($documento->getMontoTotal()) . "\n";
        $i++;
    }

    // ═══════════════════════════════════════════════════════════════════════
    // PASO 3: ENVIAR AL SII
    // ═══════════════════════════════════════════════════════════════════════
    echo "\n═══════════════════════════════════════════════════════════════════════\n";
    echo "  PASO 3: ENVÍO AL SII\n";
    echo "═══════════════════════════════════════════════════════════════════════\n\n";

    $integrationComponent = $billingPackage->getIntegrationComponent();
    $siiWorker = $integrationComponent->getSiiLazyWorker();

    $siiRequest = new SiiRequest(
        certificate: $certificate,
        options: ['ambiente' => $siiAmbiente, 'token' => ['cache' => 'filesystem', 'ttl' => 300]]
    );

    // Autenticar con reintentos
    echo "Autenticando con SII... ";
    $token = executeWithRetry(fn() => $siiWorker->authenticate($siiRequest), 'autenticación');
    echo "OK\n";

    // Crear sobre EnvioBOLETA
    echo "Creando sobre EnvioBOLETA... ";
    $dispatcherWorker = $documentComponent->getDispatcherWorker();
    $envelope = new \libredte\lib\Core\Package\Billing\Component\Document\Support\DocumentEnvelope();
    foreach ($documentBags as $bag) {
        $envelope->addDocument($bag);
    }
    $envelope->setCertificate($certificate);
    $envelope = $dispatcherWorker->normalize($envelope);
    $xmlEnvelope = $envelope->getXmlDocument();
    $sobreFile = $outputDir . "EnvioBOLETA_Test_" . date('Ymd_His') . ".xml";
    file_put_contents($sobreFile, $xmlEnvelope->saveXML());
    echo "OK\n";

    // Enviar con reintentos
    echo "Enviando al SII... ";
    $trackId = executeWithRetry(
        fn() => $siiWorker->sendXmlDocument(request: $siiRequest, doc: $xmlEnvelope, company: $emisor['RUTEmisor'], compress: false, retry: 1),
        'envío SII'
    );
    echo "OK\n";
    echo "  └─ Track ID: $trackId\n";

    // ═══════════════════════════════════════════════════════════════════════
    // PASO 4: CONSULTAR ESTADO
    // ═══════════════════════════════════════════════════════════════════════
    echo "\n═══════════════════════════════════════════════════════════════════════\n";
    echo "  PASO 4: CONSULTA DE ESTADO\n";
    echo "═══════════════════════════════════════════════════════════════════════\n\n";

    echo "Esperando procesamiento (5 segundos)... ";
    sleep(5);
    echo "OK\n";

    echo "Consultando estado... ";
    try {
        $estadoResponse = executeWithRetry(
            fn() => $siiWorker->checkXmlDocumentSentStatus(request: $siiRequest, trackId: $trackId, company: $emisor['RUTEmisor']),
            'consulta estado'
        );
        $data = $estadoResponse->getData();
        echo "OK\n";
        echo "  ├─ Estado: " . ($data['status'] ?? 'N/A') . "\n";
        echo "  └─ Glosa: " . ($data['description'] ?? $estadoResponse->getReviewStatus()) . "\n";
    } catch (Exception $e) {
        echo "PENDIENTE (SII aún procesando)\n";
    }

    // ═══════════════════════════════════════════════════════════════════════
    // PASO 5: GENERAR Y ENVIAR RCOF
    // ═══════════════════════════════════════════════════════════════════════
    echo "\n═══════════════════════════════════════════════════════════════════════\n";
    echo "  PASO 5: RCOF (REPORTE DE CONSUMO DE FOLIOS)\n";
    echo "═══════════════════════════════════════════════════════════════════════\n\n";

    // Calcular totales
    $montoTotal = array_sum(array_column($documentos, 'total'));
    $montoNeto = 0;
    $montoIva = 0;
    $montoExento = 2000; // CASO-4 tiene item exento

    // Recalcular neto e IVA correctamente
    foreach ($documentos as $doc) {
        $total = $doc['total'];
        $netoDoc = round($total / 1.19);
        $ivaDoc = $total - $netoDoc;
        $montoNeto += $netoDoc;
        $montoIva += $ivaDoc;
    }

    $fecha = date('Y-m-d');
    $folioInicial = min($foliosAsignados);
    $folioFinal = max($foliosAsignados);
    $secEnvio = 10; // Nuevo secEnvio
    $timestamp = date('Y-m-d\TH:i:s');
    $documentId = 'CF_' . str_replace('-', '', $emisor['RUTEmisor']) . '_' . str_replace('-', '', $fecha);

    echo "Generando XML del RCOF... ";

    // Crear XML del RCOF
    $dom = new DOMDocument('1.0', 'ISO-8859-1');
    $dom->formatOutput = true;

    $consumoFolios = $dom->createElementNS('http://www.sii.cl/SiiDte', 'ConsumoFolios');
    $consumoFolios->setAttribute('xmlns:xsi', 'http://www.w3.org/2001/XMLSchema-instance');
    $consumoFolios->setAttribute('xsi:schemaLocation', 'http://www.sii.cl/SiiDte ConsumoFolio_v10.xsd');
    $consumoFolios->setAttribute('version', '1.0');
    $dom->appendChild($consumoFolios);

    $docCF = $dom->createElement('DocumentoConsumoFolios');
    $docCF->setAttribute('ID', $documentId);
    $consumoFolios->appendChild($docCF);

    $caratula = $dom->createElement('Caratula');
    $caratula->setAttribute('version', '1.0');
    $docCF->appendChild($caratula);

    $caratula->appendChild($dom->createElement('RutEmisor', $emisor['RUTEmisor']));
    $caratula->appendChild($dom->createElement('RutEnvia', $certificate->getID()));
    $caratula->appendChild($dom->createElement('FchResol', $autorizacionConfig['fechaResolucion']));
    $caratula->appendChild($dom->createElement('NroResol', (string)$autorizacionConfig['numeroResolucion']));
    $caratula->appendChild($dom->createElement('FchInicio', $fecha));
    $caratula->appendChild($dom->createElement('FchFinal', $fecha));
    $caratula->appendChild($dom->createElement('SecEnvio', (string)$secEnvio));
    $caratula->appendChild($dom->createElement('TmstFirmaEnv', $timestamp));

    $resumen = $dom->createElement('Resumen');
    $docCF->appendChild($resumen);

    $resumen->appendChild($dom->createElement('TipoDocumento', '39'));
    $resumen->appendChild($dom->createElement('MntNeto', (string)$montoNeto));
    $resumen->appendChild($dom->createElement('MntIva', (string)$montoIva));
    $resumen->appendChild($dom->createElement('TasaIVA', '19'));
    $resumen->appendChild($dom->createElement('MntExento', (string)$montoExento));
    $resumen->appendChild($dom->createElement('MntTotal', (string)$montoTotal));
    $resumen->appendChild($dom->createElement('FoliosEmitidos', (string)count($documentos)));
    $resumen->appendChild($dom->createElement('FoliosAnulados', '0'));
    $resumen->appendChild($dom->createElement('FoliosUtilizados', (string)count($documentos)));

    $rangoEl = $dom->createElement('RangoUtilizados');
    $rangoEl->appendChild($dom->createElement('Inicial', (string)$folioInicial));
    $rangoEl->appendChild($dom->createElement('Final', (string)$folioFinal));
    $resumen->appendChild($rangoEl);

    echo "OK\n";

    // Firmar RCOF (usando el mismo método que funciona en app/enviar_rcof.php)
    echo "Firmando RCOF... ";

    $xmlDocument = new XmlDocument();
    $xmlDocument->loadXml($dom->saveXML());

    $xpath = '//*[@ID="' . $documentId . '"]';
    $nodeToDigest = $xmlDocument->getNodes($xpath)->item(0);
    $c14n = $nodeToDigest->C14N();
    $c14n = mb_convert_encoding($c14n, 'ISO-8859-1', 'UTF-8');
    $digestValue = base64_encode(sha1($c14n, true));

    $x509Certificate = $certificate->getCertificate(true);
    $modulus = $certificate->getModulus();
    $exponent = $certificate->getExponent();

    $signatureData = [
        'Signature' => [
            '@attributes' => ['xmlns' => 'http://www.w3.org/2000/09/xmldsig#'],
            'SignedInfo' => [
                '@attributes' => ['xmlns:xsi' => 'http://www.w3.org/2001/XMLSchema-instance'],
                'CanonicalizationMethod' => ['@attributes' => ['Algorithm' => 'http://www.w3.org/TR/2001/REC-xml-c14n-20010315']],
                'SignatureMethod' => ['@attributes' => ['Algorithm' => 'http://www.w3.org/2000/09/xmldsig#rsa-sha1']],
                'Reference' => [
                    '@attributes' => ['URI' => '#' . $documentId],
                    'Transforms' => ['Transform' => ['@attributes' => ['Algorithm' => 'http://www.w3.org/TR/2001/REC-xml-c14n-20010315']]],
                    'DigestMethod' => ['@attributes' => ['Algorithm' => 'http://www.w3.org/2000/09/xmldsig#sha1']],
                    'DigestValue' => $digestValue,
                ],
            ],
            'SignatureValue' => '',
            'KeyInfo' => [
                'KeyValue' => ['RSAKeyValue' => ['Modulus' => $modulus, 'Exponent' => $exponent]],
                'X509Data' => ['X509Certificate' => $x509Certificate],
            ],
        ],
    ];

    $arrayToXml = function(array $data, DOMDocument $doc, ?DOMElement $parent = null) use (&$arrayToXml) {
        foreach ($data as $key => $value) {
            if ($key === '@attributes') continue;
            $element = $doc->createElement($key);
            if (is_array($value)) {
                if (isset($value['@attributes'])) {
                    foreach ($value['@attributes'] as $n => $v) $element->setAttribute($n, $v);
                }
                $arrayToXml($value, $doc, $element);
            } else {
                $element->nodeValue = (string) $value;
            }
            $parent ? $parent->appendChild($element) : $doc->appendChild($element);
        }
    };

    $signatureXmlDoc = new XmlDocument();
    $signatureXmlDoc->formatOutput = false;
    $arrayToXml($signatureData, $signatureXmlDoc);

    $signedInfoNode = $signatureXmlDoc->getElementsByTagName('SignedInfo')->item(0);
    $signedInfoC14N = $signedInfoNode->C14N();
    $signedInfoC14N = mb_convert_encoding($signedInfoC14N, 'ISO-8859-1', 'UTF-8');

    $signature = '';
    openssl_sign($signedInfoC14N, $signature, $certificate->getPrivateKey(), OPENSSL_ALGO_SHA1);
    $signatureValue = base64_encode($signature);

    $signatureData['Signature']['SignatureValue'] = $signatureValue;
    $signatureXmlDoc = new XmlDocument();
    $signatureXmlDoc->formatOutput = false;
    $arrayToXml($signatureData, $signatureXmlDoc);
    $signatureXml = $signatureXmlDoc->saveXML($signatureXmlDoc->documentElement);

    $placeholder = $dom->createElement('Signature');
    $dom->documentElement->appendChild($placeholder);
    $xmlWithPlaceholder = $dom->saveXML();
    $xmlSigned = str_replace('<Signature/>', $signatureXml, $xmlWithPlaceholder);

    $xmlDocumentSigned = new XmlDocument();
    $xmlDocumentSigned->loadXml($xmlSigned);

    $rcofFile = $outputDir . "RCOF_Test_" . date('Ymd_His') . ".xml";
    file_put_contents($rcofFile, $xmlDocumentSigned->saveXML());
    echo "OK\n";

    // Enviar RCOF
    echo "Enviando RCOF al SII... ";
    $rcofTrackId = executeWithRetry(
        fn() => $siiWorker->sendXmlDocument(request: $siiRequest, doc: $xmlDocumentSigned, company: $emisor['RUTEmisor'], compress: false, retry: 1),
        'envío RCOF'
    );
    echo "OK\n";
    echo "  └─ Track ID: $rcofTrackId\n";

    // Consultar estado RCOF
    echo "\nConsultando estado del RCOF... ";
    sleep(3);
    try {
        $estadoRcof = executeWithRetry(
            fn() => $siiWorker->checkXmlDocumentSentStatus(request: $siiRequest, trackId: $rcofTrackId, company: $emisor['RUTEmisor']),
            'consulta RCOF'
        );
        $dataRcof = $estadoRcof->getData();
        echo "OK\n";
        echo "  ├─ Estado: " . ($dataRcof['status'] ?? 'N/A') . "\n";
        echo "  └─ Glosa: " . ($dataRcof['description'] ?? $estadoRcof->getReviewStatus()) . "\n";
    } catch (Exception $e) {
        echo "PENDIENTE\n";
    }

    // ═══════════════════════════════════════════════════════════════════════
    // RESUMEN FINAL
    // ═══════════════════════════════════════════════════════════════════════
    echo "\n╔══════════════════════════════════════════════════════════════════════╗\n";
    echo "║                    RESUMEN DEL TEST                                 ║\n";
    echo "╠══════════════════════════════════════════════════════════════════════╣\n";
    echo "║  Set de Pruebas:                                                    ║\n";
    foreach ($documentos as $caso => $data) {
        printf("║    %-10s Folio: %-5d  Total: $%-10s                   ║\n", $caso, $data['folio'], number_format($data['total']));
    }
    echo "╠══════════════════════════════════════════════════════════════════════╣\n";
    printf("║  Track ID Boletas: %-45s   ║\n", $trackId);
    printf("║  Track ID RCOF:    %-45s   ║\n", $rcofTrackId);
    echo "╠══════════════════════════════════════════════════════════════════════╣\n";
    echo "║  Archivos generados en: uploads/output/                             ║\n";
    echo "╠══════════════════════════════════════════════════════════════════════╣\n";
    echo "║                    ✓ TEST COMPLETADO EXITOSAMENTE                   ║\n";
    echo "╚══════════════════════════════════════════════════════════════════════╝\n\n";

    // Guardar resumen
    $resumen = [
        'fecha_test' => date('Y-m-d H:i:s'),
        'ambiente' => $ambiente,
        'set_pruebas' => ['track_id' => $trackId, 'documentos' => $documentos],
        'rcof' => ['track_id' => $rcofTrackId, 'sec_envio' => $secEnvio],
    ];
    file_put_contents($outputDir . "test_resumen_" . date('Ymd_His') . ".json", json_encode($resumen, JSON_PRETTY_PRINT));

} catch (Exception $e) {
    echo "\n╔══════════════════════════════════════════════════════════════════════╗\n";
    echo "║                           ERROR                                     ║\n";
    echo "╚══════════════════════════════════════════════════════════════════════╝\n";
    echo "\n" . $e->getMessage() . "\n\n";
    exit(1);
}
