<?php
/**
 * Script simplificado para emitir una boleta
 * Usa los folios 2047-2048 disponibles
 */

require_once __DIR__ . '/vendor/autoload.php';

use Derafu\Certificate\Service\CertificateLoader;
use libredte\lib\Core\Application;
use libredte\lib\Core\Package\Billing\Component\Integration\Enum\SiiAmbiente;
use libredte\lib\Core\Package\Billing\Component\Integration\Support\SiiRequest;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Entity\AutorizacionDte;

echo "=== Generador de Boleta Electrónica ===\n\n";

// Configuración
$config = require __DIR__ . '/config/config.php';
$ambiente = SiiAmbiente::CERTIFICACION;

// Datos del emisor (sin autorizacionDte - se asigna después al objeto)
$emisor = [
    'RUTEmisor' => '78274225-6',
    'RznSoc' => 'AKIBARA SPA',
    'GiroEmis' => 'VENTA AL POR MENOR DE LIBROS Y OTROS PRODUCTOS',
    'DirOrigen' => 'BARTOLO SOTO 3700 DP 1402 PISO 14',
    'CmnaOrigen' => 'San Miguel',
    'Acteco' => 476101,
    'CdgSIISucur' => false,
];

// Configuración de autorización DTE para certificación (se usa para Carátula)
$autorizacionConfig = [
    'fechaResolucion' => '2014-08-22',
    'numeroResolucion' => 80,
];

// Folio a usar (2048 - último disponible)
$folio = 2048;

// Datos de la boleta
$boleta_data = [
    'Encabezado' => [
        'IdDoc' => [
            'TipoDTE' => 39,
            'Folio' => $folio,
            'FchEmis' => date('Y-m-d'),
            'IndServicio' => 3,
        ],
        'Emisor' => $emisor,
        'Receptor' => [
            'RUTRecep' => '66666666-6',
            'RznSocRecep' => 'CLIENTE DE PRUEBA PLUGIN',
            'DirRecep' => 'Santiago',
            'CmnaRecep' => 'Santiago',
        ],
    ],
    'Detalle' => [
        [
            'NmbItem' => 'Producto de Prueba - Plugin WordPress',
            'QtyItem' => 2,
            'PrcItem' => 5000,
        ]
    ],
];

echo "Configuración:\n";
echo "  Ambiente: certificacion (maullin.sii.cl)\n";
echo "  Emisor: {$emisor['RUTEmisor']} - {$emisor['RznSoc']}\n";
echo "  Folio: $folio\n";
echo "  Receptor: 66666666-6 - CLIENTE DE PRUEBA PLUGIN\n";
echo "  Total Bruto: $" . number_format(2 * 5000) . "\n\n";

try {
    // Inicializar LibreDTE
    echo "Inicializando LibreDTE...\n";
    $app = Application::getInstance(
        environment: 'dev',
        debug: false
    );

    // Cargar certificado
    echo "Cargando certificado...\n";
    $certLoader = new CertificateLoader();
    $certificate = $certLoader->loadFromFile(
        $config['paths']['certificado'],
        $config['certificado_password']
    );
    echo "  Certificado: {$certificate->getName()}\n";
    echo "  RUT: {$certificate->getID()}\n";

    // Cargar CAF
    echo "Cargando CAF...\n";
    $cafXml = file_get_contents($config['paths']['caf_boleta_afecta']);
    $billingPackage = $app->getPackageRegistry()->getBillingPackage();
    $cafLoader = $billingPackage->getIdentifierComponent()->getCafLoaderWorker();
    $cafBag = $cafLoader->load($cafXml);
    $caf = $cafBag->getCaf();
    echo "  CAF cargado: Folios {$caf->getFolioDesde()} - {$caf->getFolioHasta()}\n";

    // Generar boleta
    echo "\nGenerando boleta DTE tipo 39...\n";
    $documentComponent = $billingPackage->getDocumentComponent();

    $documentBag = $documentComponent->bill(
        data: $boleta_data,
        caf: $caf,
        certificate: $certificate
    );

    // Asignar autorización DTE al emisor (para generar Carátula correctamente)
    if ($documentBag->getEmisor()) {
        $autorizacionDte = new AutorizacionDte(
            $autorizacionConfig['fechaResolucion'],
            (int) $autorizacionConfig['numeroResolucion']
        );
        $documentBag->getEmisor()->setAutorizacionDte($autorizacionDte);
    }

    $documento = $documentBag->getDocument();
    echo "  Boleta generada correctamente!\n";
    echo "  Monto Total: $" . number_format($documento->getMontoTotal()) . "\n";

    // Guardar XML
    $output_file = $config['paths']['output'] . "boleta_39_F{$folio}.xml";
    file_put_contents($output_file, $documento->getXml());
    echo "  XML guardado: $output_file\n";

    // Enviar al SII
    echo "\nEnviando al SII...\n";
    $integrationComponent = $billingPackage->getIntegrationComponent();
    $siiWorker = $integrationComponent->getSiiLazyWorker();

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

    // Autenticar y obtener token
    echo "  Autenticando con SII...\n";
    $token = $siiWorker->authenticate($siiRequest);
    echo "  Autenticación exitosa!\n";

    // Crear sobre manualmente (como en emitir_set_pruebas.php)
    $dispatcherWorker = $documentComponent->getDispatcherWorker();
    $envelope = new \libredte\lib\Core\Package\Billing\Component\Document\Support\DocumentEnvelope();
    $envelope->addDocument($documentBag);
    $envelope->setCertificate($certificate);

    // Normalizar para generar carátula y XML
    $envelope = $dispatcherWorker->normalize($envelope);

    // Guardar sobre
    $xmlEnvelope = $envelope->getXmlDocument();
    $sobre_file = $config['paths']['output'] . "sobre_boleta_39_F{$folio}.xml";
    file_put_contents($sobre_file, $xmlEnvelope->saveXML());
    echo "  Sobre guardado: $sobre_file\n";

    // Enviar sobre
    echo "  Enviando sobre al SII...\n";
    $trackId = $siiWorker->sendXmlDocument(
        request: $siiRequest,
        doc: $xmlEnvelope,
        company: $emisor['RUTEmisor'],
        compress: false,
        retry: 3
    );

    echo "\n=== BOLETA ENVIADA EXITOSAMENTE ===\n";
    echo "Track ID: $trackId\n";
    echo "Folio: $folio\n";
    echo "Total: $" . number_format($documento->getMontoTotal()) . "\n";

    // Guardar track ID
    file_put_contents($config['paths']['output'] . "trackid_boleta_F{$folio}.txt", $trackId);

    echo "\nPuedes consultar el estado en:\n";
    echo "https://maullin.sii.cl/cgi_dte/UPL/DTEUpload\n";

} catch (Exception $e) {
    echo "\n*** ERROR ***\n";
    echo $e->getMessage() . "\n";
    echo "\nStack trace:\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}
