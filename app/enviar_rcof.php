<?php

/**
 * Script para generar y enviar el RCOF (Reporte de Consumo de Folios) al SII
 *
 * El RCOF es obligatorio para informar los folios de boletas consumidos en el día.
 * Debe enviarse una vez que se han emitido las boletas del Set de Pruebas.
 *
 * Uso:
 *   php enviar_rcof.php                    # Genera y envía al SII
 *   php enviar_rcof.php --no-enviar        # Solo genera el XML
 *   php enviar_rcof.php --sec-envio=2      # Especifica secuencia de envío (para correcciones)
 */

declare(strict_types=1);

use Derafu\Certificate\Service\CertificateLoader;
use Derafu\Xml\XmlDocument;
use libredte\lib\Core\Application;
use libredte\lib\Core\Package\Billing\Component\Integration\Enum\SiiAmbiente;
use libredte\lib\Core\Package\Billing\Component\Integration\Support\SiiRequest;

// Cargar autoloader
require_once __DIR__ . '/../libredte-lib-core-master/vendor/autoload.php';

// Cargar configuración
$config = require __DIR__ . '/config/config.php';
$emisorConfig = require __DIR__ . '/config/emisor.php';

// Configurar timezone
date_default_timezone_set('America/Santiago');

// Parsear argumentos
$noEnviar = in_array('--no-enviar', $argv);
$secEnvio = 1;
foreach ($argv as $arg) {
    if (str_starts_with($arg, '--sec-envio=')) {
        $secEnvio = (int) substr($arg, strlen('--sec-envio='));
    }
}

echo "╔══════════════════════════════════════════════════════════════════════╗\n";
echo "║     RCOF - REPORTE DE CONSUMO DE FOLIOS - LibreDTE                  ║\n";
echo "║                   Certificación SII Chile                           ║\n";
echo "╚══════════════════════════════════════════════════════════════════════╝\n\n";

// Verificar archivos
$certificadoPath = $config['paths']['certificado'];
$outputDir = $config['paths']['output'];

if (!file_exists($certificadoPath)) {
    echo "ERROR: Certificado no encontrado: $certificadoPath\n";
    exit(1);
}

// Buscar datos del RCOF generados por el script del Set de Pruebas
$rcofDataFile = $outputDir . "rcof_data_" . date('Ymd') . ".json";
if (!file_exists($rcofDataFile)) {
    echo "ERROR: No se encontró el archivo de datos del RCOF: $rcofDataFile\n";
    echo "       Debe ejecutar primero: php emitir_set_pruebas.php\n";
    exit(1);
}

$rcofData = json_decode(file_get_contents($rcofDataFile), true);
echo "Datos RCOF cargados desde: $rcofDataFile\n\n";

try {
    // Determinar ambiente (siempre certificación para Set de Pruebas)
    $ambiente = SiiAmbiente::CERTIFICACION;
    $ambienteStr = 'CERTIFICACION';

    echo "Configuración:\n";
    echo "  ├─ Ambiente: $ambienteStr\n";
    echo "  ├─ Emisor: {$emisorConfig['RznSoc']}\n";
    echo "  ├─ RUT: {$emisorConfig['RUTEmisor']}\n";
    echo "  └─ Secuencia de envío: $secEnvio\n\n";

    // Inicializar LibreDTE
    echo "Inicializando LibreDTE... ";
    $app = Application::getInstance(
        environment: 'dev',
        debug: true
    );
    echo "OK\n";

    // Obtener servicios
    $certificateLoader = new CertificateLoader();
    $billingPackage = $app->getPackageRegistry()->getBillingPackage();
    $integrationComponent = $billingPackage->getIntegrationComponent();

    // Cargar certificado
    echo "Cargando certificado digital... ";
    $certificate = $certificateLoader->loadFromFile(
        $certificadoPath,
        $config['certificado_password']
    );
    echo "OK\n";
    echo "  ├─ Titular: " . $certificate->getName() . "\n";
    echo "  └─ RUT: " . $certificate->getID() . "\n\n";

    // Verificar vigencia del certificado
    if (!$certificate->isActive()) {
        throw new Exception("El certificado digital no está vigente.");
    }

    // Datos para el RCOF
    $fecha = $rcofData['fecha'] ?? date('Y-m-d');
    $cantidadBoletas = $rcofData['cantidadBoletas'];

    // Usar totales pre-calculados si existen, o calcular desde documentos
    if (isset($rcofData['totales'])) {
        $montoNeto = $rcofData['totales']['neto'];
        $montoIva = $rcofData['totales']['iva'];
        $montoExento = $rcofData['totales']['exento'];
        $montoTotal = $rcofData['totales']['total'];
    } else {
        // Calcular desde documentos
        $montoNeto = 0;
        $montoIva = 0;
        $montoExento = 0;
        $montoTotal = 0;
        foreach ($rcofData['documentos'] ?? [] as $doc) {
            $montoNeto += $doc['neto'] ?? 0;
            $montoIva += $doc['iva'] ?? 0;
            $montoExento += $doc['exento'] ?? 0;
            $montoTotal += $doc['total'] ?? 0;
        }
    }
    $tasaIva = 19;

    // Obtener rangos de folios utilizados
    $rangos = $rcofData['rangos'] ?? [
        ['inicial' => $rcofData['folioInicial'], 'final' => $rcofData['folioFinal']]
    ];

    // Variables para mostrar en resumen (primer y último folio)
    $folioInicial = $rcofData['folioInicial'];
    $folioFinal = $rcofData['folioFinal'];

    echo "Datos del RCOF:\n";
    echo "  ├─ Fecha: $fecha\n";
    echo "  ├─ Cantidad boletas: $cantidadBoletas\n";
    echo "  ├─ Monto Neto: $" . number_format($montoNeto, 0, ',', '.') . "\n";
    echo "  ├─ Monto IVA: $" . number_format($montoIva, 0, ',', '.') . "\n";
    echo "  ├─ Monto Exento: $" . number_format($montoExento, 0, ',', '.') . "\n";
    echo "  ├─ Monto Total: $" . number_format($montoTotal, 0, ',', '.') . "\n";
    echo "  └─ Rangos: ";
    foreach ($rangos as $i => $rango) {
        echo ($i > 0 ? ', ' : '') . $rango['inicial'] . '-' . $rango['final'];
    }
    echo "\n\n";

    // Timestamp de firma
    $timestamp = date('Y-m-d\TH:i:s');

    // ID del documento (usado para la firma)
    $documentId = 'CF_' . str_replace('-', '', $emisorConfig['RUTEmisor']) . '_' . str_replace('-', '', $fecha);

    // Construir estructura del XML ConsumoFolios
    $consumoFoliosData = [
        'ConsumoFolios' => [
            '@attributes' => [
                'xmlns' => 'http://www.sii.cl/SiiDte',
                'xmlns:xsi' => 'http://www.w3.org/2001/XMLSchema-instance',
                'xsi:schemaLocation' => 'http://www.sii.cl/SiiDte ConsumoFolio_v10.xsd',
                'version' => '1.0',
            ],
            'DocumentoConsumoFolios' => [
                '@attributes' => [
                    'ID' => $documentId,
                ],
                'Caratula' => [
                    '@attributes' => [
                        'version' => '1.0',
                    ],
                    'RutEmisor' => $emisorConfig['RUTEmisor'],
                    'RutEnvia' => $certificate->getID(),
                    'FchResol' => $emisorConfig['autorizacionDte']['fechaResolucion'],
                    'NroResol' => $emisorConfig['autorizacionDte']['numeroResolucion'],
                    'FchInicio' => $fecha,
                    'FchFinal' => $fecha,
                    'SecEnvio' => $secEnvio,
                    'TmstFirmaEnv' => $timestamp,
                ],
                'Resumen' => [
                    'TipoDocumento' => 39, // Boleta Afecta Electrónica
                    'MntNeto' => $montoNeto,
                    'MntIva' => $montoIva,
                    'TasaIVA' => $tasaIva,
                    'MntExento' => $montoExento,
                    'MntTotal' => $montoTotal,
                    'FoliosEmitidos' => $cantidadBoletas,
                    'FoliosAnulados' => 0,
                    'FoliosUtilizados' => $cantidadBoletas,
                    'RangoUtilizados' => array_map(function($rango) {
                        return [
                            'Inicial' => $rango['inicial'],
                            'Final' => $rango['final'],
                        ];
                    }, $rangos),
                ],
            ],
        ],
    ];

    // Generar XML manualmente usando DOMDocument
    echo "Generando XML del RCOF... ";

    $dom = new DOMDocument('1.0', 'ISO-8859-1');
    $dom->formatOutput = true;

    // Crear elemento raíz ConsumoFolios
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

    $caratula->appendChild($dom->createElement('RutEmisor', $emisorConfig['RUTEmisor']));
    $caratula->appendChild($dom->createElement('RutEnvia', $certificate->getID()));
    $caratula->appendChild($dom->createElement('FchResol', $emisorConfig['autorizacionDte']['fechaResolucion']));
    $caratula->appendChild($dom->createElement('NroResol', (string)$emisorConfig['autorizacionDte']['numeroResolucion']));
    $caratula->appendChild($dom->createElement('FchInicio', $fecha));
    $caratula->appendChild($dom->createElement('FchFinal', $fecha));
    $caratula->appendChild($dom->createElement('SecEnvio', (string)$secEnvio));
    $caratula->appendChild($dom->createElement('TmstFirmaEnv', $timestamp));

    // Resumen
    $resumen = $dom->createElement('Resumen');
    $docCF->appendChild($resumen);

    $resumen->appendChild($dom->createElement('TipoDocumento', '39'));
    $resumen->appendChild($dom->createElement('MntNeto', (string)$montoNeto));
    $resumen->appendChild($dom->createElement('MntIva', (string)$montoIva));
    $resumen->appendChild($dom->createElement('TasaIVA', (string)$tasaIva));
    $resumen->appendChild($dom->createElement('MntExento', (string)$montoExento));
    $resumen->appendChild($dom->createElement('MntTotal', (string)$montoTotal));
    $resumen->appendChild($dom->createElement('FoliosEmitidos', (string)$cantidadBoletas));
    $resumen->appendChild($dom->createElement('FoliosAnulados', '0'));
    $resumen->appendChild($dom->createElement('FoliosUtilizados', (string)$cantidadBoletas));

    // RangoUtilizados - puede haber múltiples
    foreach ($rangos as $rango) {
        $rangoEl = $dom->createElement('RangoUtilizados');
        $rangoEl->appendChild($dom->createElement('Inicial', (string)$rango['inicial']));
        $rangoEl->appendChild($dom->createElement('Final', (string)$rango['final']));
        $resumen->appendChild($rangoEl);
    }

    echo "OK\n";

    // Firmar el documento XML manualmente (corregido)
    echo "Firmando documento... ";

    // Función para firmar XML usando XMLDsig
    $signXml = function(DOMDocument $doc, $certificate, string $referenceId) {
        // Obtener el nodo que será firmado
        $xpath = new DOMXPath($doc);
        $xpath->registerNamespace('sii', 'http://www.sii.cl/SiiDte');

        // Canonicalizar el nodo referenciado
        $nodes = $xpath->query("//*[@ID='" . $referenceId . "']");
        if ($nodes->length == 0) {
            throw new Exception("No se encontró el nodo con ID: " . $referenceId);
        }
        $refNode = $nodes->item(0);

        // Canonicalización C14N (con exclusive=false para incluir namespaces heredados)
        $c14n = $refNode->C14N(false, false);

        // Calcular DigestValue (SHA1)
        $digestValue = base64_encode(sha1($c14n, true));

        // Obtener datos del certificado X509
        $x509Clean = $certificate->getCertificate(true);

        // Obtener datos de la clave pública RSA
        $pubKey = openssl_pkey_get_public($certificate->getPublicKey());
        $pubKeyDetails = openssl_pkey_get_details($pubKey);
        $modulus = base64_encode($pubKeyDetails['rsa']['n']);
        $exponent = base64_encode($pubKeyDetails['rsa']['e']);

        // Construir el XML de la firma
        $signatureNS = 'http://www.w3.org/2000/09/xmldsig#';

        // Crear SignedInfo como fragmento para poder canonicalizar correctamente
        $signedInfoXml = '<SignedInfo xmlns="' . $signatureNS . '" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">'
            . '<CanonicalizationMethod Algorithm="http://www.w3.org/TR/2001/REC-xml-c14n-20010315"/>'
            . '<SignatureMethod Algorithm="http://www.w3.org/2000/09/xmldsig#rsa-sha1"/>'
            . '<Reference URI="#' . $referenceId . '">'
            . '<Transforms>'
            . '<Transform Algorithm="http://www.w3.org/TR/2001/REC-xml-c14n-20010315"/>'
            . '</Transforms>'
            . '<DigestMethod Algorithm="http://www.w3.org/2000/09/xmldsig#sha1"/>'
            . '<DigestValue>' . $digestValue . '</DigestValue>'
            . '</Reference>'
            . '</SignedInfo>';

        // Canonicalizar SignedInfo para firmar
        $signedInfoDoc = new DOMDocument('1.0', 'UTF-8');
        $signedInfoDoc->loadXML($signedInfoXml);
        $signedInfoC14N = $signedInfoDoc->documentElement->C14N(false, false);

        // Firmar con la clave privada
        $privateKey = $certificate->getPrivateKey();
        $signature = '';
        if (!openssl_sign($signedInfoC14N, $signature, $privateKey, OPENSSL_ALGO_SHA1)) {
            throw new Exception("Error al firmar: " . openssl_error_string());
        }
        $signatureValue = base64_encode($signature);

        // Crear el elemento Signature completo
        $signatureXml = '<Signature xmlns="' . $signatureNS . '">'
            . $signedInfoXml
            . '<SignatureValue>' . $signatureValue . '</SignatureValue>'
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

        // Insertar la firma en el documento original
        $signatureDoc = new DOMDocument('1.0', 'ISO-8859-1');
        $signatureDoc->loadXML($signatureXml);
        $signatureNode = $doc->importNode($signatureDoc->documentElement, true);
        $doc->documentElement->appendChild($signatureNode);

        return $doc->saveXML();
    };

    $xmlSigned = $signXml($dom, $certificate, $documentId);

    // Crear XmlDocument desde el XML firmado
    $xmlDocumentSigned = new XmlDocument();
    $xmlDocumentSigned->loadXml($xmlSigned);
    echo "OK\n";

    // Guardar XML del RCOF
    $rcofFilename = $outputDir . "RCOF_" . date('Ymd_His') . ".xml";
    file_put_contents($rcofFilename, $xmlDocumentSigned->saveXML());
    echo "  └─ XML guardado: $rcofFilename\n\n";

    // Mostrar resumen
    echo "╔══════════════════════════════════════════════════════════════════════╗\n";
    echo "║                    RCOF GENERADO                                    ║\n";
    echo "╠══════════════════════════════════════════════════════════════════════╣\n";
    printf("║  ID:            %-52s  ║\n", $documentId);
    printf("║  Tipo Doc:      %-52s  ║\n", "39 (Boleta Afecta)");
    printf("║  Folios:        %-52s  ║\n", "$folioInicial - $folioFinal ($cantidadBoletas docs)");
    printf("║  Monto Total:   $%-51s  ║\n", number_format($montoTotal, 0, ',', '.'));
    echo "╚══════════════════════════════════════════════════════════════════════╝\n\n";

    // Enviar al SII si corresponde
    if (!$noEnviar) {
        echo "╔══════════════════════════════════════════════════════════════════════╗\n";
        echo "║                      ENVIO AL SII                                   ║\n";
        echo "╚══════════════════════════════════════════════════════════════════════╝\n\n";

        $siiWorker = $integrationComponent->getSiiLazyWorker();

        // Crear request para el SII
        $siiRequest = new SiiRequest(
            certificate: $certificate,
            options: [
                'ambiente' => $ambiente,
                'token' => [
                    'cache' => 'filesystem',
                    'ttl' => 300,
                ],
            ]
        );

        // 1. Autenticar
        echo "Autenticando con SII... ";
        try {
            $token = $siiWorker->authenticate($siiRequest);
            echo "OK\n";
            echo "  └─ Token: " . substr($token, 0, 30) . "...\n\n";
        } catch (Exception $e) {
            echo "ERROR\n";
            throw new Exception("Error de autenticación SII: " . $e->getMessage());
        }

        // 2. Enviar RCOF al SII
        echo "Enviando RCOF al SII... ";
        try {
            $rutEmisor = $emisorConfig['RUTEmisor'];

            $trackId = $siiWorker->sendXmlDocument(
                request: $siiRequest,
                doc: $xmlDocumentSigned,
                company: $rutEmisor,
                compress: false,
                retry: 3
            );

            echo "OK\n";
            echo "  └─ Track ID: $trackId\n\n";

            // Guardar resultado del envío
            $resultadoEnvio = [
                'fecha_envio' => date('Y-m-d H:i:s'),
                'ambiente' => $ambienteStr,
                'track_id' => $trackId,
                'tipo' => 'RCOF',
                'documento_id' => $documentId,
                'fecha_rcof' => $fecha,
                'folios' => "$folioInicial - $folioFinal",
                'cantidad' => $cantidadBoletas,
                'monto_total' => $montoTotal,
                'emisor' => $emisorConfig['RUTEmisor'],
                'xml_file' => $rcofFilename,
            ];

            $resultFile = $outputDir . "rcof_trackid_{$trackId}.json";
            file_put_contents($resultFile, json_encode($resultadoEnvio, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            echo "Resultado guardado: $resultFile\n\n";

            // 3. Consultar estado
            echo "Consultando estado del envío... ";
            sleep(3);

            try {
                $estadoResponse = $siiWorker->checkXmlDocumentSentStatus(
                    request: $siiRequest,
                    trackId: $trackId,
                    company: $rutEmisor
                );

                $data = $estadoResponse->getData();
                $estado = $data['status'] ?? 'N/A';
                $glosa = $data['description'] ?? $estadoResponse->getReviewStatus();

                echo "OK\n";
                echo "  ├─ Estado: $estado\n";
                echo "  └─ Glosa: $glosa\n\n";

            } catch (Exception $e) {
                echo "PENDIENTE\n";
                echo "  └─ El SII aún está procesando.\n\n";
            }

            echo "╔══════════════════════════════════════════════════════════════════════╗\n";
            echo "║               RCOF ENVIADO EXITOSAMENTE                             ║\n";
            echo "╠══════════════════════════════════════════════════════════════════════╣\n";
            echo "║                                                                      ║\n";
            echo "║  El Set de Pruebas y el RCOF han sido enviados al SII.             ║\n";
            echo "║                                                                      ║\n";
            echo "║  Puede consultar el estado en:                                      ║\n";
            echo "║  https://maullin.sii.cl/cvc_cgi/dte/rf_SolicitudCertificacion       ║\n";
            echo "║                                                                      ║\n";
            echo "╚══════════════════════════════════════════════════════════════════════╝\n";

        } catch (Exception $e) {
            echo "ERROR\n";
            echo "  └─ " . $e->getMessage() . "\n\n";
            $errorFile = $outputDir . "error_rcof_" . date('Ymd_His') . ".txt";
            file_put_contents($errorFile, $e->getMessage() . "\n\n" . $e->getTraceAsString());
            echo "Error guardado: $errorFile\n";
        }

    } else {
        echo "Envío al SII omitido (--no-enviar)\n";
        echo "\nPara enviar al SII ejecute: php enviar_rcof.php\n";
    }

    echo "\n¡Proceso completado!\n";

} catch (Exception $e) {
    echo "\n";
    echo "╔══════════════════════════════════════════════════════════════════════╗\n";
    echo "║                              ERROR                                  ║\n";
    echo "╚══════════════════════════════════════════════════════════════════════╝\n";
    echo "\n" . $e->getMessage() . "\n\n";
    exit(1);
}
