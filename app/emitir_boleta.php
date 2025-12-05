<?php

/**
 * Script para emitir Boletas Electrónicas (código 39) con envío sincronizado al SII
 *
 * Uso básico:
 *   php emitir_boleta.php
 *
 * Con datos personalizados (JSON):
 *   php emitir_boleta.php '{"receptor":{"rut":"12345678-9","razon_social":"Cliente"},"items":[{"nombre":"Producto","cantidad":1,"precio":10000}]}'
 *
 * Solo generar sin enviar al SII:
 *   php emitir_boleta.php --no-enviar
 */

declare(strict_types=1);

use Derafu\Certificate\Service\CertificateLoader;
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
$datosJson = null;
foreach ($argv as $arg) {
    if (isset($arg[0]) && $arg[0] === '{') {
        $datosJson = json_decode($arg, true);
        break;
    }
}

echo "╔══════════════════════════════════════════════════════════════╗\n";
echo "║     EMISOR DE BOLETAS ELECTRONICAS - LibreDTE v1.0          ║\n";
echo "║          Sincronizado con SII de Chile                      ║\n";
echo "╚══════════════════════════════════════════════════════════════╝\n\n";

// Verificar archivos
$certificadoPath = $config['paths']['certificado'];
$cafPath = $config['paths']['caf_boleta_afecta'];
$outputDir = $config['paths']['output'];

$errores = [];
if (!file_exists($certificadoPath)) {
    $errores[] = "Certificado no encontrado: $certificadoPath";
}
if (!file_exists($cafPath)) {
    $errores[] = "CAF no encontrado: $cafPath";
}
if (!empty($errores)) {
    echo "ERRORES DE CONFIGURACION:\n";
    foreach ($errores as $error) {
        echo "  - $error\n";
    }
    echo "\nEjecuta 'php verificar_config.php' para más detalles.\n";
    exit(1);
}

try {
    // Determinar ambiente
    $esProduccion = $config['ambiente'] === 'produccion';
    $ambiente = $esProduccion ? SiiAmbiente::PRODUCCION : SiiAmbiente::CERTIFICACION;
    $ambienteStr = $esProduccion ? 'PRODUCCION' : 'CERTIFICACION';

    echo "Configuración:\n";
    echo "  ├─ Ambiente: $ambienteStr\n";
    echo "  ├─ Emisor: {$emisorConfig['RznSoc']}\n";
    echo "  └─ RUT: {$emisorConfig['RUTEmisor']}\n\n";

    // Inicializar LibreDTE
    echo "Inicializando LibreDTE... ";
    $app = Application::getInstance(
        environment: $esProduccion ? 'prod' : 'dev',
        debug: !$esProduccion
    );
    echo "OK\n";

    // Obtener servicios
    $certificateLoader = new CertificateLoader();
    $billingPackage = $app->getPackageRegistry()->getBillingPackage();
    $documentComponent = $billingPackage->getDocumentComponent();
    $identifierComponent = $billingPackage->getIdentifierComponent();
    $integrationComponent = $billingPackage->getIntegrationComponent();

    // Cargar certificado
    echo "Cargando certificado digital... ";
    $certificate = $certificateLoader->loadFromFile(
        $certificadoPath,
        $config['certificado_password']
    );
    echo "OK\n";
    echo "  ├─ Titular: " . $certificate->getName() . "\n";
    echo "  ├─ RUT: " . $certificate->getID() . "\n";
    echo "  └─ Válido hasta: " . $certificate->getTo() . "\n\n";

    // Verificar vigencia del certificado
    if (!$certificate->isActive()) {
        throw new Exception("El certificado digital no está vigente.");
    }

    // Cargar CAF
    echo "Cargando CAF de boletas... ";
    $cafXml = file_get_contents($cafPath);
    $cafLoader = $identifierComponent->getCafLoaderWorker();
    $cafBag = $cafLoader->load($cafXml);
    $caf = $cafBag->getCaf();
    echo "OK\n";
    echo "  ├─ Tipo: " . $caf->getTipoDocumento() . " (Boleta Afecta)\n";
    echo "  ├─ Folios: " . $caf->getFolioDesde() . " - " . $caf->getFolioHasta() . "\n";
    echo "  └─ Vence: " . $caf->getFechaVencimiento() . "\n\n";

    // Validar tipo de CAF
    if ($caf->getTipoDocumento() !== 39) {
        throw new Exception("El CAF es tipo {$caf->getTipoDocumento()}, se requiere tipo 39 (Boleta Afecta).");
    }

    // Validar vigencia del CAF
    if (!$caf->vigente()) {
        throw new Exception("El CAF ha expirado (venció el " . $caf->getFechaVencimiento() . ").");
    }

    // Preparar datos de la boleta
    $folio = $caf->getFolioDesde(); // Usar primer folio disponible

    // Datos del receptor
    $receptor = [
        'RUTRecep' => $datosJson['receptor']['rut'] ?? '66666666-6',
        'RznSocRecep' => $datosJson['receptor']['razon_social'] ?? 'CLIENTE',
        'DirRecep' => $datosJson['receptor']['direccion'] ?? 'Santiago',
        'CmnaRecep' => $datosJson['receptor']['comuna'] ?? 'Santiago',
    ];

    // Items de la boleta
    $items = [];
    if (isset($datosJson['items']) && !empty($datosJson['items'])) {
        foreach ($datosJson['items'] as $item) {
            $items[] = [
                'NmbItem' => $item['nombre'] ?? 'Producto',
                'QtyItem' => $item['cantidad'] ?? 1,
                'PrcItem' => $item['precio'] ?? 0,
            ];
        }
    } else {
        $items[] = [
            'NmbItem' => 'Producto de prueba',
            'QtyItem' => 1,
            'PrcItem' => 10000,
        ];
    }

    // Limpiar datos del emisor para el DTE (sin campos extra)
    $emisorDte = [
        'RUTEmisor' => $emisorConfig['RUTEmisor'],
        'RznSoc' => $emisorConfig['RznSoc'],
        'GiroEmis' => $emisorConfig['GiroEmis'],
        'Acteco' => $emisorConfig['Acteco'],
        'DirOrigen' => $emisorConfig['DirOrigen'],
        'CmnaOrigen' => $emisorConfig['CmnaOrigen'],
    ];

    // Estructura completa de la boleta
    $datosBoleta = [
        'Encabezado' => [
            'IdDoc' => [
                'TipoDTE' => 39,
                'Folio' => $folio,
                'FchEmis' => date('Y-m-d'),
                'IndServicio' => 3,
            ],
            'Emisor' => $emisorDte,
            'Receptor' => $receptor,
        ],
        'Detalle' => $items,
    ];

    echo "Generando boleta electrónica...\n";
    echo "  ├─ Folio: $folio\n";
    echo "  ├─ Fecha: " . date('Y-m-d') . "\n";
    echo "  ├─ Receptor: {$receptor['RznSocRecep']} ({$receptor['RUTRecep']})\n";
    echo "  └─ Items: " . count($items) . "\n\n";

    // Generar la boleta (timbrar y firmar)
    echo "Timbrado y firmado... ";
    $documentBag = $documentComponent->bill(
        data: $datosBoleta,
        caf: $caf,
        certificate: $certificate
    );
    $documento = $documentBag->getDocument();

    // Establecer autorización DTE en el emisor (requerido para el sobre de envío)
    if ($documentBag->getEmisor() && isset($emisorConfig['autorizacionDte'])) {
        $autorizacionDte = new \libredte\lib\Core\Package\Billing\Component\TradingParties\Entity\AutorizacionDte(
            $emisorConfig['autorizacionDte']['fechaResolucion'],
            (int) $emisorConfig['autorizacionDte']['numeroResolucion']
        );
        $documentBag->getEmisor()->setAutorizacionDte($autorizacionDte);
    }
    echo "OK\n\n";

    // Mostrar resumen
    echo "╔══════════════════════════════════════════════════════════════╗\n";
    echo "║                    BOLETA GENERADA                          ║\n";
    echo "╠══════════════════════════════════════════════════════════════╣\n";
    printf("║  ID:     %-50s  ║\n", $documento->getId());
    printf("║  Folio:  %-50s  ║\n", $documento->getFolio());
    printf("║  Fecha:  %-50s  ║\n", $documento->getFechaEmision());
    printf("║  Total:  $%-49s  ║\n", number_format($documento->getMontoTotal(), 0, ',', '.'));
    echo "╚══════════════════════════════════════════════════════════════╝\n\n";

    // Guardar XML
    $xmlFilename = $outputDir . "boleta_39_F" . $documento->getFolio() . "_" . date('Ymd_His') . ".xml";
    file_put_contents($xmlFilename, $documento->getXml());
    echo "XML guardado: $xmlFilename\n\n";

    // Enviar al SII si corresponde
    if (!$noEnviar) {
        echo "╔══════════════════════════════════════════════════════════════╗\n";
        echo "║                  ENVIO AL SII                               ║\n";
        echo "╚══════════════════════════════════════════════════════════════╝\n\n";

        $siiWorker = $integrationComponent->getSiiLazyWorker();
        $dispatcherWorker = $documentComponent->getDispatcherWorker();

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

        // 2. Crear sobre de envío EnvioBOLETA
        echo "Creando sobre de envío EnvioBOLETA... ";
        try {
            $envelope = $dispatcherWorker->create($documentBag);
            $sobreXml = $envelope->getXmlDocument()->saveXML();

            // Guardar sobre de envío
            $sobreFilename = $outputDir . "EnvioBOLETA_F" . $documento->getFolio() . "_" . date('Ymd_His') . ".xml";
            file_put_contents($sobreFilename, $sobreXml);
            echo "OK\n";
            echo "  └─ Sobre guardado: $sobreFilename\n\n";
        } catch (Exception $e) {
            echo "ERROR\n";
            throw new Exception("Error creando sobre: " . $e->getMessage());
        }

        // 3. Enviar sobre al SII
        echo "Enviando sobre al SII... ";
        try {
            $rutEmisor = $emisorConfig['RUTEmisor'];

            $trackId = $siiWorker->sendXmlDocument(
                request: $siiRequest,
                doc: $envelope->getXmlDocument(),
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
                'documento' => [
                    'id' => $documento->getId(),
                    'tipo' => 39,
                    'folio' => $documento->getFolio(),
                    'total' => $documento->getMontoTotal(),
                ],
                'emisor' => $emisorConfig['RUTEmisor'],
                'xml_file' => $xmlFilename,
                'sobre_file' => $sobreFilename,
            ];

            $resultFile = $outputDir . "envio_trackid_{$trackId}.json";
            file_put_contents($resultFile, json_encode($resultadoEnvio, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            echo "Resultado guardado: $resultFile\n\n";

            // 4. Consultar estado
            echo "Consultando estado del envío... ";
            sleep(2);

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

            echo "╔══════════════════════════════════════════════════════════════╗\n";
            echo "║               ENVIO COMPLETADO EXITOSAMENTE                 ║\n";
            echo "╚══════════════════════════════════════════════════════════════╝\n";

        } catch (Exception $e) {
            echo "ERROR\n";
            echo "  └─ " . $e->getMessage() . "\n\n";
            $errorFile = $outputDir . "error_envio_F{$documento->getFolio()}_" . date('Ymd_His') . ".txt";
            file_put_contents($errorFile, $e->getMessage() . "\n\n" . $e->getTraceAsString());
            echo "Error guardado: $errorFile\n";
        }

    } else {
        echo "Envío al SII omitido (--no-enviar)\n";
    }

    echo "\n¡Proceso completado!\n";

} catch (Exception $e) {
    echo "\n";
    echo "╔══════════════════════════════════════════════════════════════╗\n";
    echo "║                         ERROR                               ║\n";
    echo "╚══════════════════════════════════════════════════════════════╝\n";
    echo "\n" . $e->getMessage() . "\n\n";
    exit(1);
}
