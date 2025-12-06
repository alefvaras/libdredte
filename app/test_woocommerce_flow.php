<?php
/**
 * Test completo del flujo WooCommerce -> Boleta -> SII -> PDF
 * Simula productos, orden, generación de boleta y PDF
 */

declare(strict_types=1);

use Derafu\Certificate\Service\CertificateLoader;
use libredte\lib\Core\Application;
use libredte\lib\Core\Package\Billing\Component\Integration\Enum\SiiAmbiente;
use libredte\lib\Core\Package\Billing\Component\Integration\Support\SiiRequest;

require_once __DIR__ . '/../libredte-lib-core-master/vendor/autoload.php';

// Cargar configuración
$config = require __DIR__ . '/config/config.php';
$emisorConfig = require __DIR__ . '/config/emisor.php';

date_default_timezone_set('America/Santiago');

echo "\n";
echo "╔══════════════════════════════════════════════════════════════════╗\n";
echo "║   TEST FLUJO COMPLETO: PRODUCTOS -> BOLETA -> SII -> PDF        ║\n";
echo "╚══════════════════════════════════════════════════════════════════╝\n\n";

// ============================================================================
// PASO 1: CREAR PRODUCTOS DE PRUEBA (Simular WooCommerce)
// ============================================================================
echo "\033[33m[PASO 1] CREANDO PRODUCTOS DE PRUEBA\033[0m\n";
echo str_repeat("-", 60) . "\n";

$productos = [
    [
        'id' => 1,
        'nombre' => 'Manga One Piece Vol. 108',
        'sku' => 'MOP-108',
        'precio' => 8990,
        'cantidad' => 2,
        'categoria' => 'Manga',
        'exento' => false,
    ],
    [
        'id' => 2,
        'nombre' => 'Figura Coleccionable Luffy Gear 5',
        'sku' => 'FIG-LUFFY-G5',
        'precio' => 45990,
        'cantidad' => 1,
        'categoria' => 'Figuras',
        'exento' => false,
    ],
    [
        'id' => 3,
        'nombre' => 'Poster Attack on Titan A3',
        'sku' => 'POST-AOT-A3',
        'precio' => 3500,
        'cantidad' => 3,
        'categoria' => 'Posters',
        'exento' => false,
    ],
    [
        'id' => 4,
        'nombre' => 'Libro Historia del Anime',
        'sku' => 'LIB-ANIME-HIST',
        'precio' => 15000,
        'cantidad' => 1,
        'categoria' => 'Libros',
        'exento' => true, // Los libros pueden ser exentos
    ],
];

echo "Productos creados:\n";
foreach ($productos as $p) {
    $subtotal = $p['precio'] * $p['cantidad'];
    $tipo = $p['exento'] ? '(EXENTO)' : '(AFECTO)';
    echo sprintf("  ✓ [%s] %s x%d @ \$%s = \$%s %s\n",
        $p['sku'],
        $p['nombre'],
        $p['cantidad'],
        number_format($p['precio'], 0, ',', '.'),
        number_format($subtotal, 0, ',', '.'),
        $tipo
    );
}

// ============================================================================
// PASO 2: CREAR ORDEN DE COMPRA
// ============================================================================
echo "\n\033[33m[PASO 2] CREANDO ORDEN DE COMPRA\033[0m\n";
echo str_repeat("-", 60) . "\n";

$cliente = [
    'rut' => '12345678-5',
    'nombre' => 'Juan Perez Garcia',
    'email' => 'juan.perez@ejemplo.cl',
    'telefono' => '+56912345678',
    'direccion' => 'Av. Providencia 1234',
    'comuna' => 'Providencia',
];

$costoEnvio = 3990;

// Calcular totales
$totalOrden = 0;
$itemsBoleta = [];

foreach ($productos as $p) {
    $subtotal = $p['precio'] * $p['cantidad'];
    $totalOrden += $subtotal;

    $item = [
        'NmbItem' => strtoupper($p['nombre']),
        'QtyItem' => $p['cantidad'],
        'PrcItem' => $p['precio'],
    ];

    if ($p['exento']) {
        $item['IndExe'] = 1;
    }

    $itemsBoleta[] = $item;
}

// Agregar envío
$totalOrden += $costoEnvio;
$itemsBoleta[] = [
    'NmbItem' => 'ENVIO A DOMICILIO',
    'QtyItem' => 1,
    'PrcItem' => $costoEnvio,
];

$ordenId = 'WC-' . date('Ymd') . '-' . rand(1000, 9999);

echo "Orden ID: $ordenId\n";
echo "Cliente: {$cliente['nombre']} (RUT: {$cliente['rut']})\n";
echo "Email: {$cliente['email']}\n";
echo "Direccion: {$cliente['direccion']}, {$cliente['comuna']}\n\n";

echo "Items de la orden:\n";
foreach ($itemsBoleta as $i => $item) {
    $num = $i + 1;
    $tipo = isset($item['IndExe']) ? '[E]' : '[A]';
    $subtotal = $item['PrcItem'] * $item['QtyItem'];
    echo sprintf("  %d. %s %s x%d = \$%s\n",
        $num,
        $tipo,
        $item['NmbItem'],
        $item['QtyItem'],
        number_format($subtotal, 0, ',', '.')
    );
}

echo "\n";
echo "┌─────────────────────────────────────┐\n";
echo sprintf("│ TOTAL ORDEN:         \$%12s │\n", number_format($totalOrden, 0, ',', '.'));
echo "└─────────────────────────────────────┘\n";

// ============================================================================
// PASO 3: GENERAR BOLETA ELECTRÓNICA
// ============================================================================
echo "\n\033[33m[PASO 3] GENERANDO BOLETA ELECTRONICA\033[0m\n";
echo str_repeat("-", 60) . "\n";

try {
    // Determinar ambiente
    $esProduccion = $config['ambiente'] === 'produccion';
    $ambiente = $esProduccion ? SiiAmbiente::PRODUCCION : SiiAmbiente::CERTIFICACION;
    $ambienteStr = $esProduccion ? 'PRODUCCION' : 'CERTIFICACION';

    echo "Configuracion:\n";
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
    $certificadoPath = $config['paths']['certificado'];
    echo "Cargando certificado digital... ";
    $certificate = $certificateLoader->loadFromFile(
        $certificadoPath,
        $config['certificado_password']
    );
    echo "OK\n";
    echo "  ├─ Titular: " . $certificate->getName() . "\n";
    echo "  ├─ RUT: " . $certificate->getID() . "\n";
    echo "  └─ Valido hasta: " . $certificate->getTo() . "\n\n";

    // Cargar CAF
    $cafPath = $config['paths']['caf_boleta_afecta'];
    echo "Cargando CAF de boletas... ";
    $cafXml = file_get_contents($cafPath);
    $cafLoader = $identifierComponent->getCafLoaderWorker();
    $cafBag = $cafLoader->load($cafXml);
    $caf = $cafBag->getCaf();
    echo "OK\n";
    echo "  ├─ Tipo: " . $caf->getTipoDocumento() . " (Boleta Afecta)\n";
    echo "  ├─ Folios: " . $caf->getFolioDesde() . " - " . $caf->getFolioHasta() . "\n";
    echo "  └─ Vence: " . $caf->getFechaVencimiento() . "\n\n";

    // Obtener siguiente folio
    $folioRegistry = __DIR__ . '/credentials/folio_registry_wc_test.json';
    if (file_exists($folioRegistry)) {
        $registry = json_decode(file_get_contents($folioRegistry), true);
        $folio = ($registry['last_folio'] ?? $caf->getFolioDesde() - 1) + 1;
    } else {
        $folio = $caf->getFolioDesde() + 10; // Empezar en folio 2059
    }

    // Verificar rango
    if ($folio > $caf->getFolioHasta()) {
        throw new Exception("No hay folios disponibles. Rango: {$caf->getFolioDesde()}-{$caf->getFolioHasta()}");
    }

    // Datos del receptor
    $receptor = [
        'RUTRecep' => $cliente['rut'],
        'RznSocRecep' => strtoupper($cliente['nombre']),
        'DirRecep' => $cliente['direccion'],
        'CmnaRecep' => $cliente['comuna'],
        'Contacto' => false,
        'CorreoRecep' => false,
        'GiroRecep' => false,
    ];

    // Estructura de la boleta
    $datosBoleta = [
        'Encabezado' => [
            'IdDoc' => [
                'TipoDTE' => 39,
                'Folio' => $folio,
                'FchEmis' => date('Y-m-d'),
                'IndServicio' => 3,
            ],
            'Emisor' => [
                'RUTEmisor' => $emisorConfig['RUTEmisor'],
                'RznSoc' => $emisorConfig['RznSoc'],
                'GiroEmis' => $emisorConfig['GiroEmis'],
                'Acteco' => $emisorConfig['Acteco'],
                'DirOrigen' => $emisorConfig['DirOrigen'],
                'CmnaOrigen' => $emisorConfig['CmnaOrigen'],
                'CdgSIISucur' => false,
            ],
            'Receptor' => $receptor,
        ],
        'Detalle' => $itemsBoleta,
    ];

    echo "Generando boleta electronica...\n";
    echo "  ├─ Folio: $folio\n";
    echo "  ├─ Fecha: " . date('Y-m-d') . "\n";
    echo "  ├─ Receptor: {$receptor['RznSocRecep']} ({$receptor['RUTRecep']})\n";
    echo "  └─ Items: " . count($itemsBoleta) . "\n\n";

    // Generar la boleta (timbrar y firmar)
    echo "Timbrado y firmado... ";
    $documentBag = $documentComponent->bill(
        data: $datosBoleta,
        caf: $caf,
        certificate: $certificate
    );
    $documento = $documentBag->getDocument();

    // Establecer autorizacionDte
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
    printf("║  Total:  \$%-49s  ║\n", number_format($documento->getMontoTotal(), 0, ',', '.'));
    echo "╚══════════════════════════════════════════════════════════════╝\n\n";

    // Guardar XML
    $outputDir = $config['paths']['output'];
    $xmlFilename = "boleta_39_F" . $documento->getFolio() . "_" . date('Ymd_His') . ".xml";
    file_put_contents($outputDir . $xmlFilename, $documento->getXml());
    echo "XML guardado: $xmlFilename\n\n";

    // Guardar folio usado
    file_put_contents($folioRegistry, json_encode([
        'last_folio' => $folio,
        'updated_at' => date('Y-m-d H:i:s'),
    ], JSON_PRETTY_PRINT));

    // ============================================================================
    // PASO 4: ENVIAR AL SII
    // ============================================================================
    echo "\033[33m[PASO 4] ENVIANDO BOLETA AL SII\033[0m\n";
    echo str_repeat("-", 60) . "\n";

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
    $token = $siiWorker->authenticate($siiRequest);
    echo "OK\n";
    echo "  └─ Token: " . substr($token, 0, 30) . "...\n\n";

    // 2. Crear sobre de envío
    echo "Creando sobre de envio EnvioBOLETA... ";
    $envelope = $dispatcherWorker->create($documentBag);
    $sobreXml = $envelope->getXmlDocument()->saveXML();

    $sobreFilename = "EnvioBOLETA_F" . $documento->getFolio() . "_" . date('Ymd_His') . ".xml";
    file_put_contents($outputDir . $sobreFilename, $sobreXml);
    echo "OK\n";
    echo "  └─ Guardado: $sobreFilename\n\n";

    // 3. Enviar al SII
    echo "Enviando al SII... ";
    $rutEmisor = $emisorConfig['RUTEmisor'];

    $trackId = $siiWorker->sendXmlDocument(
        request: $siiRequest,
        doc: $envelope->getXmlDocument(),
        company: $rutEmisor,
        compress: false,
        retry: 3
    );
    echo "OK\n";
    echo "  └─ \033[32mTrack ID: $trackId\033[0m\n\n";

    // ============================================================================
    // PASO 5: CONSULTAR ESTADO
    // ============================================================================
    echo "\033[33m[PASO 5] CONSULTANDO ESTADO EN SII\033[0m\n";
    echo str_repeat("-", 60) . "\n";

    echo "Esperando 3 segundos para que el SII procese...\n";
    sleep(3);

    echo "Consultando estado del envio... ";
    try {
        $estadoResponse = $siiWorker->checkXmlDocumentSentStatus(
            request: $siiRequest,
            trackId: $trackId,
            company: $rutEmisor
        );

        $data = $estadoResponse->getData();
        $estado = $data['status'] ?? 'N/A';
        $glosa = $data['description'] ?? $estadoResponse->getReviewStatus();

        echo "OK\n\n";
        echo "Estado del envio:\n";
        echo "  ├─ Estado: $estado\n";
        echo "  └─ Glosa: $glosa\n";
    } catch (Exception $e) {
        echo "PENDIENTE\n";
        echo "  └─ " . $e->getMessage() . "\n";
        echo "  (El SII aun esta procesando. Consulte con Track ID: $trackId)\n";
    }

    // ============================================================================
    // PASO 6: GENERAR PDF
    // ============================================================================
    echo "\n\033[33m[PASO 6] GENERANDO PDF\033[0m\n";
    echo str_repeat("-", 60) . "\n";

    try {
        echo "Intentando generar PDF...\n";
        $rendererWorker = $documentComponent->getRendererWorker();

        // Verificar si el renderer soporta PDF
        echo "  → Verificando renderer disponible...\n";

        $pdfContent = $rendererWorker->render($documentBag, [
            'format' => 'pdf',
        ]);

        $pdfFilename = "boleta_39_F" . $documento->getFolio() . "_" . date('Ymd_His') . ".pdf";
        file_put_contents($outputDir . $pdfFilename, $pdfContent);

        $pdfSize = filesize($outputDir . $pdfFilename);
        echo "  ✓ \033[32mPDF generado exitosamente\033[0m\n";
        echo "  ✓ Archivo: $pdfFilename\n";
        echo "  ✓ Tamano: " . number_format($pdfSize / 1024, 2) . " KB\n";

    } catch (Exception $e) {
        echo "  ⚠ PDF no disponible: " . $e->getMessage() . "\n";
        echo "  (Requiere TCPDF u otra libreria de PDF)\n";
        $pdfFilename = null;
    }

    // ============================================================================
    // RESUMEN FINAL
    // ============================================================================
    echo "\n";
    echo "╔══════════════════════════════════════════════════════════════════╗\n";
    echo "║                        RESUMEN DEL FLUJO                        ║\n";
    echo "╠══════════════════════════════════════════════════════════════════╣\n";
    printf("║  Orden:              %-43s ║\n", $ordenId);
    printf("║  Cliente:            %-43s ║\n", substr($cliente['nombre'], 0, 43));
    printf("║  RUT Cliente:        %-43s ║\n", $cliente['rut']);
    printf("║  Total:              \$%-42s ║\n", number_format($totalOrden, 0, ',', '.'));
    echo "╠──────────────────────────────────────────────────────────────────╣\n";
    printf("║  Folio Boleta:       %-43s ║\n", $folio);
    printf("║  Tipo DTE:           %-43s ║\n", "39 - Boleta Electronica");
    printf("║  Fecha Emision:      %-43s ║\n", date('d/m/Y H:i:s'));
    echo "╠──────────────────────────────────────────────────────────────────╣\n";
    printf("║  Track ID SII:       %-43s ║\n", $trackId ?? 'N/A');
    printf("║  Ambiente:           %-43s ║\n", "CERTIFICACION (maullin.sii.cl)");
    echo "╠══════════════════════════════════════════════════════════════════╣\n";
    echo "║  Archivos generados:                                            ║\n";
    printf("║    • %-61s ║\n", $xmlFilename);
    printf("║    • %-61s ║\n", $sobreFilename);
    if (isset($pdfFilename) && $pdfFilename) {
        printf("║    • %-61s ║\n", $pdfFilename);
    }
    echo "╚══════════════════════════════════════════════════════════════════╝\n";

    echo "\n\033[32m✓ FLUJO COMPLETADO EXITOSAMENTE\033[0m\n\n";

} catch (Exception $e) {
    echo "\n\033[31m✗ ERROR: " . $e->getMessage() . "\033[0m\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
    exit(1);
}
