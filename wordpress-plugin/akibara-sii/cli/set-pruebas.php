<?php
/**
 * CLI: Ejecutar Set de Pruebas desde plugin Akibara-SII
 * Con gestion inteligente de folios y soporte de ambientes
 *
 * Uso: php set-pruebas.php [certificacion|produccion]
 * Por defecto usa 'certificacion'
 */

require_once dirname(__DIR__) . '/vendor/autoload.php';
require_once dirname(__DIR__) . '/includes/class-folio-manager.php';

use Derafu\Certificate\Service\CertificateLoader;
use libredte\lib\Core\Application;
use libredte\lib\Core\Package\Billing\Component\Integration\Enum\SiiAmbiente;
use libredte\lib\Core\Package\Billing\Component\Integration\Support\SiiRequest;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Entity\AutorizacionDte;

// Determinar ambiente desde argumento CLI o usar certificacion por defecto
$ambienteArg = $argv[1] ?? 'certificacion';
$ambiente = strtolower(trim($ambienteArg));

if (!in_array($ambiente, [Akibara_Folio_Manager::AMBIENTE_CERTIFICACION, Akibara_Folio_Manager::AMBIENTE_PRODUCCION])) {
    echo "ERROR: Ambiente invalido '$ambiente'. Use 'certificacion' o 'produccion'.\n";
    exit(1);
}

$siiAmbiente = $ambiente === Akibara_Folio_Manager::AMBIENTE_CERTIFICACION
    ? SiiAmbiente::CERTIFICACION
    : SiiAmbiente::PRODUCCION;

$ambienteNombre = $ambiente === Akibara_Folio_Manager::AMBIENTE_CERTIFICACION
    ? 'CERTIFICACIÓN'
    : 'PRODUCCIÓN';

echo "╔══════════════════════════════════════════════════════════════════════╗\n";
echo "║     SET DE PRUEBAS - AKIBARA SII - Plugin WordPress                 ║\n";
echo "║           Con Gestion Inteligente de Folios                         ║\n";
printf("║                    AMBIENTE: %-12s                           ║\n", $ambienteNombre);
echo "╚══════════════════════════════════════════════════════════════════════╝\n\n";

// Configuracion del emisor
$emisor = [
    'RUTEmisor' => '78274225-6',
    'RznSoc' => 'AKIBARA SPA',
    'GiroEmis' => 'VENTA AL POR MENOR DE LIBROS Y OTROS PRODUCTOS',
    'DirOrigen' => 'BARTOLO SOTO 3700 DP 1402 PISO 14',
    'CmnaOrigen' => 'San Miguel',
    'Acteco' => 476101,
    'CdgSIISucur' => false,
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
$appOutputDir = '/home/user/libdredte/app/output/';

if (!is_dir($outputDir)) {
    mkdir($outputDir, 0755, true);
}

// Casos del Set de Pruebas SII
$casos = [
    'CASO-1' => [
        'items' => [
            ['NmbItem' => 'Cambio de aceite', 'QtyItem' => 1, 'PrcItem' => 19900],
            ['NmbItem' => 'Alineacion y balanceo', 'QtyItem' => 1, 'PrcItem' => 9900],
        ],
    ],
    'CASO-2' => [
        'items' => [
            ['NmbItem' => 'Papel de regalo', 'QtyItem' => 17, 'PrcItem' => 120],
        ],
    ],
    'CASO-3' => [
        'items' => [
            ['NmbItem' => 'Sandwic', 'QtyItem' => 2, 'PrcItem' => 1500],
            ['NmbItem' => 'Bebida', 'QtyItem' => 2, 'PrcItem' => 550],
        ],
    ],
    'CASO-4' => [
        'items' => [
            ['NmbItem' => 'item afecto 1', 'QtyItem' => 8, 'PrcItem' => 1590, 'IndExe' => 0],
            ['NmbItem' => 'item exento 2', 'QtyItem' => 2, 'PrcItem' => 1000, 'IndExe' => 1],
        ],
        'observacion' => 'El item 1 es un servicio afecto. El item 2 es un servicio exento.',
    ],
    'CASO-5' => [
        'items' => [
            ['NmbItem' => 'Arroz', 'QtyItem' => 5, 'PrcItem' => 700, 'UnmdItem' => 'Kg'],
        ],
        'observacion' => 'Se debe informar en el XML Unidad de medida en Kg.',
    ],
];

try {
    // Inicializar Gestor de Folios con ambiente
    echo "Inicializando Gestor de Folios ($ambienteNombre)... ";
    $folioManager = new Akibara_Folio_Manager($cafPath, $ambiente);

    // Importar folios usados de los directorios de output
    $folioManager->importFromOutputDir($outputDir);
    $folioManager->importFromOutputDir($appOutputDir);
    echo "OK\n";

    $stats = $folioManager->getStats();
    echo "╔══════════════════════════════════════════════════════════════════════╗\n";
    echo "║                    ESTADO DE FOLIOS                                 ║\n";
    echo "╠══════════════════════════════════════════════════════════════════════╣\n";
    printf("║  Ambiente: %-15s  SII: %-25s  ║\n", $stats['ambiente_nombre'], $stats['sii_url']);
    printf("║  Tipo DTE: %-5d                                                   ║\n", $stats['tipo_dte']);
    printf("║  CAF: %d - %d                                                   ║\n", $stats['caf_desde'], $stats['caf_hasta']);
    printf("║  Total: %d | Usados: %d | Disponibles: %d                           ║\n", $stats['total'], $stats['used'], $stats['available']);
    if ($stats['available'] > 0) {
        printf("║  Siguiente folio disponible: %d                                    ║\n", $stats['next_available']);
    }
    echo "╚══════════════════════════════════════════════════════════════════════╝\n\n";

    if ($stats['available'] < count($casos)) {
        throw new Exception("No hay suficientes folios disponibles para el Set de Pruebas. Necesarios: " . count($casos) . ", Disponibles: " . $stats['available']);
    }

    // Obtener folios para el Set de Pruebas
    $foliosAsignados = $folioManager->getNextFolios(count($casos));
    echo "Folios asignados para Set de Pruebas: " . implode(', ', $foliosAsignados) . "\n\n";

    // Inicializar LibreDTE
    echo "Inicializando LibreDTE... ";
    $app = Application::getInstance(environment: 'dev', debug: false);
    echo "OK\n";

    // Cargar certificado
    echo "Cargando certificado... ";
    $certLoader = new CertificateLoader();
    $certificate = $certLoader->loadFromFile($certPath, $certPassword);
    echo "OK\n";
    echo "  Titular: {$certificate->getName()}\n";

    // Cargar CAF
    echo "Cargando CAF... ";
    $cafXml = file_get_contents($cafPath);
    $billingPackage = $app->getPackageRegistry()->getBillingPackage();
    $cafLoader = $billingPackage->getIdentifierComponent()->getCafLoaderWorker();
    $cafBag = $cafLoader->load($cafXml);
    $caf = $cafBag->getCaf();
    echo "OK\n";

    $documentBags = [];
    $documentos = [];

    echo "\n=== GENERANDO BOLETAS DEL SET ===\n\n";

    $documentComponent = $billingPackage->getDocumentComponent();
    $i = 0;

    foreach ($casos as $caso => $dataCaso) {
        $folioActual = $foliosAsignados[$i];
        echo "Generando $caso (Folio: $folioActual)... ";

        $detalles = [];
        foreach ($dataCaso['items'] as $item) {
            $detalle = [
                'NmbItem' => $item['NmbItem'],
                'QtyItem' => $item['QtyItem'],
                'PrcItem' => $item['PrcItem'],
            ];
            if (isset($item['IndExe'])) {
                $detalle['IndExe'] = $item['IndExe'];
            }
            if (isset($item['UnmdItem'])) {
                $detalle['UnmdItem'] = $item['UnmdItem'];
            }
            $detalles[] = $detalle;
        }

        $datosBoleta = [
            'Encabezado' => [
                'IdDoc' => [
                    'TipoDTE' => 39,
                    'Folio' => $folioActual,
                    'FchEmis' => date('Y-m-d'),
                    'IndServicio' => 3,
                ],
                'Emisor' => $emisor,
                'Receptor' => [
                    'RUTRecep' => '66666666-6',
                    'RznSocRecep' => 'CONSUMIDOR FINAL',
                    'DirRecep' => 'Santiago',
                    'CmnaRecep' => 'Santiago',
                ],
            ],
            'Detalle' => $detalles,
            'Referencia' => [
                [
                    'TpoDocRef' => 'SET',
                    'FolioRef' => $folioActual,
                    'FchRef' => date('Y-m-d'),
                    'RazonRef' => $caso,
                ],
            ],
        ];

        $documentBag = $documentComponent->bill(
            data: $datosBoleta,
            caf: $caf,
            certificate: $certificate
        );

        // Asignar autorizacionDte
        if ($documentBag->getEmisor()) {
            $autorizacionDte = new AutorizacionDte(
                $autorizacionConfig['fechaResolucion'],
                (int) $autorizacionConfig['numeroResolucion']
            );
            $documentBag->getEmisor()->setAutorizacionDte($autorizacionDte);
        }

        $documento = $documentBag->getDocument();
        $documentBags[] = $documentBag;
        $documentos[$caso] = [
            'folio' => $folioActual,
            'total' => $documento->getMontoTotal(),
        ];

        // Guardar XML individual
        file_put_contents($outputDir . "set_{$caso}_F{$folioActual}.xml", $documento->getXml());

        // Marcar folio como usado
        $folioManager->markAsUsed($folioActual);

        echo "Total: $" . number_format($documento->getMontoTotal()) . "\n";
        $i++;
    }

    echo "\n=== ENVIANDO AL SII ===\n\n";

    // Autenticar
    echo "Autenticando con SII... ";
    $integrationComponent = $billingPackage->getIntegrationComponent();
    $siiWorker = $integrationComponent->getSiiLazyWorker();

    $siiRequest = new SiiRequest(
        certificate: $certificate,
        options: [
            'ambiente' => $siiAmbiente,
            'token' => ['cache' => 'filesystem', 'ttl' => 300],
        ]
    );

    $token = $siiWorker->authenticate($siiRequest);
    echo "OK\n";

    // Crear sobre
    echo "Creando sobre EnvioBOLETA con " . count($documentBags) . " boletas... ";
    $dispatcherWorker = $documentComponent->getDispatcherWorker();
    $envelope = new \libredte\lib\Core\Package\Billing\Component\Document\Support\DocumentEnvelope();

    foreach ($documentBags as $bag) {
        $envelope->addDocument($bag);
    }
    $envelope->setCertificate($certificate);
    $envelope = $dispatcherWorker->normalize($envelope);

    $xmlEnvelope = $envelope->getXmlDocument();
    $sobreFile = $outputDir . "EnvioBOLETA_SetPruebas_" . date('Ymd_His') . ".xml";
    file_put_contents($sobreFile, $xmlEnvelope->saveXML());
    echo "OK\n";
    echo "  Guardado: $sobreFile\n";

    // Enviar
    echo "Enviando al SII... ";
    $trackId = $siiWorker->sendXmlDocument(
        request: $siiRequest,
        doc: $xmlEnvelope,
        company: $emisor['RUTEmisor'],
        compress: false,
        retry: 3
    );
    echo "OK\n";

    echo "\n╔══════════════════════════════════════════════════════════════════════╗\n";
    echo "║  TRACK ID: $trackId                                          ║\n";
    echo "╚══════════════════════════════════════════════════════════════════════╝\n";

    // Consultar estado
    echo "\nConsultando estado del envio... ";
    sleep(3);
    try {
        $estadoResponse = $siiWorker->checkXmlDocumentSentStatus(
            request: $siiRequest,
            trackId: $trackId,
            company: $emisor['RUTEmisor']
        );
        $data = $estadoResponse->getData();
        $estado = $data['status'] ?? 'N/A';
        $glosa = $data['description'] ?? '';
        echo "OK\n";
        echo "  Estado: $estado - $glosa\n";

        if (isset($data['resume'])) {
            echo "  Informados: {$data['resume']['reported']}\n";
            echo "  Aceptados: {$data['resume']['accepted']}\n";
            echo "  Rechazados: {$data['resume']['rejected']}\n";
        }
    } catch (Exception $e) {
        echo "PENDIENTE (consultar mas tarde)\n";
    }

    // Guardar datos para RCOF
    $folioInicial = min($foliosAsignados);
    $folioFinal = max($foliosAsignados);

    $rcofData = [
        'ambiente' => $ambiente,
        'ambiente_nombre' => $ambienteNombre,
        'sii_url' => $folioManager->getSiiUrl(),
        'fecha' => date('Y-m-d'),
        'folios' => $foliosAsignados,
        'folio_inicial' => $folioInicial,
        'folio_final' => $folioFinal,
        'cantidad' => count($documentos),
        'total' => array_sum(array_column($documentos, 'total')),
        'track_id' => $trackId,
        'documentos' => $documentos,
    ];

    file_put_contents($outputDir . "rcof_data_" . date('Ymd') . ".json", json_encode($rcofData, JSON_PRETTY_PRINT));

    echo "\n╔══════════════════════════════════════════════════════════════════════╗\n";
    echo "║                    RESUMEN SET DE PRUEBAS                           ║\n";
    echo "╠══════════════════════════════════════════════════════════════════════╣\n";
    foreach ($documentos as $caso => $data) {
        printf("║  %-10s Folio: %-5d  Total: $%s                       ║\n", $caso, $data['folio'], number_format($data['total']));
    }
    echo "╠══════════════════════════════════════════════════════════════════════╣\n";
    printf("║  TOTAL GENERAL: $%s                                          ║\n", number_format(array_sum(array_column($documentos, 'total'))));
    echo "╚══════════════════════════════════════════════════════════════════════╝\n";

    // Mostrar estado actualizado de folios
    $stats = $folioManager->getStats();
    echo "\nFolios restantes disponibles: {$stats['available']}\n";
    echo "Datos RCOF guardados. Ejecutar: php rcof.php\n";

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}
