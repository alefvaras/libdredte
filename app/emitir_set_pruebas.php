<?php

/**
 * Script para generar el Set de Pruebas de Boletas Electrónicas para certificación SII
 *
 * Este script genera las 5 boletas requeridas por el SII para la certificación:
 * - CASO-1: Cambio de aceite + Alineación y balanceo
 * - CASO-2: Papel de regalo (17 unidades)
 * - CASO-3: Sandwich + Bebida
 * - CASO-4: Item afecto + Item exento (mixto)
 * - CASO-5: Arroz con unidad de medida Kg
 *
 * Uso:
 *   php emitir_set_pruebas.php              # Genera y envía al SII
 *   php emitir_set_pruebas.php --no-enviar  # Solo genera los XML
 */

declare(strict_types=1);

use Derafu\Certificate\Service\CertificateLoader;
use libredte\lib\Core\Application;
use libredte\lib\Core\Package\Billing\Component\Integration\Enum\SiiAmbiente;
use libredte\lib\Core\Package\Billing\Component\Integration\Support\SiiRequest;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Entity\AutorizacionDte;

// Cargar autoloader
require_once __DIR__ . '/../libredte-lib-core-master/vendor/autoload.php';

// Cargar configuración
$config = require __DIR__ . '/config/config.php';
$emisorConfig = require __DIR__ . '/config/emisor.php';

// Configurar timezone
date_default_timezone_set('America/Santiago');

// Parsear argumentos
$noEnviar = in_array('--no-enviar', $argv);

echo "╔══════════════════════════════════════════════════════════════════════╗\n";
echo "║     SET DE PRUEBAS - BOLETAS ELECTRONICAS - LibreDTE                ║\n";
echo "║                   Certificación SII Chile                           ║\n";
echo "╚══════════════════════════════════════════════════════════════════════╝\n\n";

// Definir los 5 casos del Set de Pruebas
// Nota: Los precios incluyen IVA (MntBruto = 1 para boletas)
$setDePruebas = [
    'CASO-1' => [
        'descripcion' => 'Cambio de aceite + Alineación y balanceo',
        'items' => [
            ['NmbItem' => 'Cambio de aceite', 'QtyItem' => 1, 'PrcItem' => 19900],
            ['NmbItem' => 'Alineacion y balanceo', 'QtyItem' => 1, 'PrcItem' => 9900],
        ],
    ],
    'CASO-2' => [
        'descripcion' => 'Papel de regalo (17 unidades)',
        'items' => [
            ['NmbItem' => 'Papel de regalo', 'QtyItem' => 17, 'PrcItem' => 120],
        ],
    ],
    'CASO-3' => [
        'descripcion' => 'Sandwich + Bebida',
        'items' => [
            ['NmbItem' => 'Sandwic', 'QtyItem' => 2, 'PrcItem' => 1500],
            ['NmbItem' => 'Bebida', 'QtyItem' => 2, 'PrcItem' => 550],
        ],
    ],
    'CASO-4' => [
        'descripcion' => 'Mixto: Item afecto + Item exento',
        'items' => [
            // Item afecto (servicio)
            ['NmbItem' => 'item afecto 1', 'QtyItem' => 8, 'PrcItem' => 1590, 'IndExe' => false],
            // Item exento (servicio exento)
            ['NmbItem' => 'item exento 2', 'QtyItem' => 2, 'PrcItem' => 1000, 'IndExe' => 1],
        ],
    ],
    'CASO-5' => [
        'descripcion' => 'Arroz con unidad de medida Kg',
        'items' => [
            ['NmbItem' => 'Arroz', 'QtyItem' => 5, 'PrcItem' => 700, 'UnmdItem' => 'Kg'],
        ],
    ],
];

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
    exit(1);
}

try {
    // Determinar ambiente (siempre certificación para Set de Pruebas)
    $ambiente = SiiAmbiente::CERTIFICACION;
    $ambienteStr = 'CERTIFICACION';

    echo "Configuración:\n";
    echo "  ├─ Ambiente: $ambienteStr\n";
    echo "  ├─ Emisor: {$emisorConfig['RznSoc']}\n";
    echo "  ├─ RUT: {$emisorConfig['RUTEmisor']}\n";
    echo "  └─ Casos a generar: " . count($setDePruebas) . "\n\n";

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
    echo "  ├─ Folios disponibles: " . $caf->getFolioDesde() . " - " . $caf->getFolioHasta() . "\n";
    echo "  └─ Vence: " . $caf->getFechaVencimiento() . "\n\n";

    // Validar tipo de CAF
    if ($caf->getTipoDocumento() !== 39) {
        throw new Exception("El CAF es tipo {$caf->getTipoDocumento()}, se requiere tipo 39 (Boleta Afecta).");
    }

    // Validar vigencia del CAF
    if (!$caf->vigente()) {
        throw new Exception("El CAF ha expirado (venció el " . $caf->getFechaVencimiento() . ").");
    }

    // Determinar folios a usar (empezando desde el folio 2042)
    $folioInicial = $caf->getFolioDesde() + 3; // Usar desde folio 2042 (2039+3)
    $foliosRequeridos = count($setDePruebas);
    $folioFinal = $folioInicial + $foliosRequeridos - 1;

    if ($folioFinal > $caf->getFolioHasta()) {
        throw new Exception("No hay suficientes folios. Se requieren $foliosRequeridos folios desde $folioInicial.");
    }

    echo "Folios a utilizar: $folioInicial - $folioFinal\n\n";

    // Datos del emisor para los DTEs
    $emisorDte = [
        'RUTEmisor' => $emisorConfig['RUTEmisor'],
        'RznSoc' => $emisorConfig['RznSoc'],
        'GiroEmis' => $emisorConfig['GiroEmis'],
        'Acteco' => $emisorConfig['Acteco'],
        'DirOrigen' => $emisorConfig['DirOrigen'],
        'CmnaOrigen' => $emisorConfig['CmnaOrigen'],
        'CdgSIISucur' => false,  // No incluir código de sucursal
    ];

    // Datos del receptor (genérico para boletas)
    $receptor = [
        'RUTRecep' => '66666666-6',
        'RznSocRecep' => 'CLIENTE',
        'DirRecep' => 'Santiago',
        'CmnaRecep' => 'Santiago',
        'Contacto' => false,
        'CorreoRecep' => false,
        'GiroRecep' => false,
    ];

    // Generar cada boleta del Set de Pruebas
    $documentBags = [];
    $documentos = [];
    $folioActual = $folioInicial;

    echo "╔══════════════════════════════════════════════════════════════════════╗\n";
    echo "║                   GENERANDO BOLETAS DEL SET                         ║\n";
    echo "╚══════════════════════════════════════════════════════════════════════╝\n\n";

    foreach ($setDePruebas as $caso => $datosCaso) {
        echo "Generando $caso: {$datosCaso['descripcion']}...\n";
        echo "  ├─ Folio: $folioActual\n";

        // Preparar items
        $items = [];
        $totalCalculado = 0;
        foreach ($datosCaso['items'] as $item) {
            $itemData = [
                'NmbItem' => $item['NmbItem'],
                'QtyItem' => $item['QtyItem'],
                'PrcItem' => $item['PrcItem'],
            ];

            // Agregar unidad de medida si está especificada (CASO-5)
            if (isset($item['UnmdItem'])) {
                $itemData['UnmdItem'] = $item['UnmdItem'];
            }

            // Agregar indicador de exento si está especificado (CASO-4)
            if (isset($item['IndExe']) && $item['IndExe'] !== false) {
                $itemData['IndExe'] = $item['IndExe'];
            }

            $items[] = $itemData;
            $totalCalculado += $item['QtyItem'] * $item['PrcItem'];
        }

        echo "  ├─ Items: " . count($items) . "\n";
        echo "  ├─ Total estimado: $" . number_format($totalCalculado, 0, ',', '.') . "\n";

        // Estructura de la boleta con referencia al caso del Set
        // Nota: Para boletas los precios incluyen IVA por defecto (no se usa MntBruto ni TasaIVA)
        $datosBoleta = [
            'Encabezado' => [
                'IdDoc' => [
                    'TipoDTE' => 39,
                    'Folio' => $folioActual,
                    'FchEmis' => date('Y-m-d'),
                    'IndServicio' => 3, // Boletas de venta y servicios
                ],
                'Emisor' => $emisorDte,
                'Receptor' => $receptor,
            ],
            'Detalle' => $items,
            // Referencia al caso del Set de Pruebas (según instrucciones SII: CodRef=SET, RazonRef=CASO-X)
            'Referencia' => [
                [
                    'CodRef' => 'SET',
                    'RazonRef' => $caso,
                ],
            ],
        ];

        // Generar la boleta (timbrar y firmar)
        $documentBag = $documentComponent->bill(
            data: $datosBoleta,
            caf: $caf,
            certificate: $certificate
        );

        // Establecer autorización DTE en el emisor
        if ($documentBag->getEmisor() && isset($emisorConfig['autorizacionDte'])) {
            $autorizacionDte = new AutorizacionDte(
                $emisorConfig['autorizacionDte']['fechaResolucion'],
                (int) $emisorConfig['autorizacionDte']['numeroResolucion']
            );
            $documentBag->getEmisor()->setAutorizacionDte($autorizacionDte);
        }

        $documento = $documentBag->getDocument();
        $documentBags[] = $documentBag;
        $documentos[$caso] = [
            'bag' => $documentBag,
            'documento' => $documento,
            'folio' => $folioActual,
            'total' => $documento->getMontoTotal(),
        ];

        // Guardar XML individual
        $xmlFilename = $outputDir . "set_pruebas_{$caso}_F{$folioActual}.xml";
        file_put_contents($xmlFilename, $documento->getXml());
        echo "  └─ XML guardado: $xmlFilename\n\n";

        $folioActual++;
    }

    // Resumen de boletas generadas
    echo "╔══════════════════════════════════════════════════════════════════════╗\n";
    echo "║                    RESUMEN SET DE PRUEBAS                           ║\n";
    echo "╠══════════════════════════════════════════════════════════════════════╣\n";
    $totalGeneral = 0;
    foreach ($documentos as $caso => $info) {
        printf("║  %-10s Folio: %-6d Total: $%-30s ║\n",
            $caso,
            $info['folio'],
            number_format($info['total'], 0, ',', '.')
        );
        $totalGeneral += $info['total'];
    }
    echo "╠══════════════════════════════════════════════════════════════════════╣\n";
    printf("║  TOTAL GENERAL: $%-51s ║\n", number_format($totalGeneral, 0, ',', '.'));
    echo "╚══════════════════════════════════════════════════════════════════════╝\n\n";

    // Enviar al SII si corresponde
    if (!$noEnviar) {
        echo "╔══════════════════════════════════════════════════════════════════════╗\n";
        echo "║                      ENVIO AL SII                                   ║\n";
        echo "╚══════════════════════════════════════════════════════════════════════╝\n\n";

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

        // 2. Crear sobre de envío EnvioBOLETA con todas las boletas
        echo "Creando sobre de envío EnvioBOLETA con " . count($documentBags) . " boletas... ";
        try {
            // Crear un nuevo sobre vacío y agregar todos los documentos
            $envelope = new \libredte\lib\Core\Package\Billing\Component\Document\Support\DocumentEnvelope();
            foreach ($documentBags as $bag) {
                $envelope->addDocument($bag);
            }
            $envelope->setCertificate($certificate);
            // Normalizar para generar carátula y XML con todos los documentos
            $envelope = $dispatcherWorker->normalize($envelope);
            $sobreXml = $envelope->getXmlDocument()->saveXML();

            // Guardar sobre de envío
            $sobreFilename = $outputDir . "EnvioBOLETA_SetPruebas_" . date('Ymd_His') . ".xml";
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
                'documentos' => [],
                'emisor' => $emisorConfig['RUTEmisor'],
                'sobre_file' => $sobreFilename,
            ];

            foreach ($documentos as $caso => $info) {
                $resultadoEnvio['documentos'][$caso] = [
                    'folio' => $info['folio'],
                    'total' => $info['total'],
                ];
            }

            $resultFile = $outputDir . "set_pruebas_trackid_{$trackId}.json";
            file_put_contents($resultFile, json_encode($resultadoEnvio, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            echo "Resultado guardado: $resultFile\n\n";

            // 4. Consultar estado
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

                if (isset($data['resume'])) {
                    echo "Resumen de procesamiento:\n";
                    echo "  ├─ Informados: " . ($data['resume']['reported'] ?? 0) . "\n";
                    echo "  ├─ Aceptados: " . ($data['resume']['accepted'] ?? 0) . "\n";
                    echo "  ├─ Rechazados: " . ($data['resume']['rejected'] ?? 0) . "\n";
                    echo "  └─ Reparos: " . ($data['resume']['repairs'] ?? 0) . "\n\n";
                }

            } catch (Exception $e) {
                echo "PENDIENTE\n";
                echo "  └─ El SII aún está procesando.\n\n";
            }

            echo "╔══════════════════════════════════════════════════════════════════════╗\n";
            echo "║            SET DE PRUEBAS ENVIADO EXITOSAMENTE                      ║\n";
            echo "╠══════════════════════════════════════════════════════════════════════╣\n";
            echo "║                                                                      ║\n";
            echo "║  IMPORTANTE: Ahora debe enviar el RCOF (Reporte de Consumo de       ║\n";
            echo "║  Folios) correspondiente a estos documentos.                        ║\n";
            echo "║                                                                      ║\n";
            echo "║  Ejecute: php enviar_rcof.php                                       ║\n";
            echo "║                                                                      ║\n";
            echo "╚══════════════════════════════════════════════════════════════════════╝\n";

        } catch (Exception $e) {
            echo "ERROR\n";
            echo "  └─ " . $e->getMessage() . "\n\n";
            $errorFile = $outputDir . "error_set_pruebas_" . date('Ymd_His') . ".txt";
            file_put_contents($errorFile, $e->getMessage() . "\n\n" . $e->getTraceAsString());
            echo "Error guardado: $errorFile\n";
        }

    } else {
        echo "Envío al SII omitido (--no-enviar)\n";
        echo "\nPara enviar al SII ejecute: php emitir_set_pruebas.php\n";
    }

    // Guardar información para el RCOF
    $rcofData = [
        'fecha' => date('Y-m-d'),
        'folioInicial' => $folioInicial,
        'folioFinal' => $folioActual - 1,
        'cantidadBoletas' => count($documentos),
        'montoTotal' => $totalGeneral,
        'montoNeto' => 0,
        'montoIva' => 0,
        'montoExento' => 0,
        'documentos' => [],
    ];

    // Calcular totales para RCOF
    foreach ($documentos as $caso => $info) {
        $doc = $info['documento'];
        $rcofData['documentos'][$caso] = [
            'folio' => $info['folio'],
            'total' => $info['total'],
        ];
    }

    $rcofFile = $outputDir . "rcof_data_" . date('Ymd') . ".json";
    file_put_contents($rcofFile, json_encode($rcofData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    echo "\nDatos para RCOF guardados: $rcofFile\n";

    echo "\n¡Proceso completado!\n";

} catch (Exception $e) {
    echo "\n";
    echo "╔══════════════════════════════════════════════════════════════════════╗\n";
    echo "║                              ERROR                                  ║\n";
    echo "╚══════════════════════════════════════════════════════════════════════╝\n";
    echo "\n" . $e->getMessage() . "\n\n";

    if (isset($e->getPrevious)) {
        echo "Causa: " . $e->getPrevious()->getMessage() . "\n\n";
    }

    exit(1);
}
