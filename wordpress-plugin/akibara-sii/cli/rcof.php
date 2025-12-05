<?php
/**
 * CLI: Enviar RCOF desde plugin Akibara-SII
 * Con soporte de ambientes (certificacion/produccion)
 *
 * Uso: php rcof.php [certificacion|produccion]
 * Por defecto usa el ambiente del archivo rcof_data generado
 */

require_once dirname(__DIR__) . '/vendor/autoload.php';
require_once dirname(__DIR__) . '/includes/class-folio-manager.php';

use Derafu\Certificate\Service\CertificateLoader;
use libredte\lib\Core\Application;
use libredte\lib\Core\Package\Billing\Component\Integration\Enum\SiiAmbiente;
use libredte\lib\Core\Package\Billing\Component\Integration\Support\SiiRequest;

// Configuracion
$emisor = [
    'RUTEmisor' => '78274225-6',
    'RznSoc' => 'AKIBARA SPA',
];

$autorizacionConfig = [
    'fechaResolucion' => '2014-08-22',
    'numeroResolucion' => 80,
];

// Paths
$certPath = '/home/user/libdredte/app/credentials/certificado.p12';
$certPassword = '5605';
$outputDir = dirname(__DIR__) . '/uploads/output/';

// Cargar datos del RCOF
$rcofDataFile = $outputDir . "rcof_data_" . date('Ymd') . ".json";
if (!file_exists($rcofDataFile)) {
    echo "ERROR: No se encontro archivo de datos RCOF: $rcofDataFile\n";
    echo "Ejecute primero: php set-pruebas.php\n";
    exit(1);
}

$rcofData = json_decode(file_get_contents($rcofDataFile), true);

// Obtener ambiente del archivo de datos o desde argumento CLI
$ambienteArg = $argv[1] ?? ($rcofData['ambiente'] ?? 'certificacion');
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

$siiUrl = $ambiente === Akibara_Folio_Manager::AMBIENTE_CERTIFICACION
    ? 'maullin.sii.cl'
    : 'palena.sii.cl';

echo "╔══════════════════════════════════════════════════════════════════════╗\n";
echo "║              RCOF - AKIBARA SII - Plugin WordPress                  ║\n";
printf("║                    AMBIENTE: %-12s                           ║\n", $ambienteNombre);
printf("║                    SII: %-20s                      ║\n", $siiUrl);
echo "╚══════════════════════════════════════════════════════════════════════╝\n\n";

echo "Datos RCOF:\n";
echo "  Ambiente: $ambienteNombre\n";
echo "  Fecha: {$rcofData['fecha']}\n";
echo "  Folios: {$rcofData['folio_inicial']} - {$rcofData['folio_final']}\n";
echo "  Cantidad: {$rcofData['cantidad']}\n";
echo "  Total: $" . number_format($rcofData['total']) . "\n\n";

try {
    // Inicializar
    echo "Inicializando LibreDTE... ";
    $app = Application::getInstance(environment: 'dev', debug: false);
    echo "OK\n";

    // Cargar certificado
    echo "Cargando certificado... ";
    $certLoader = new CertificateLoader();
    $certificate = $certLoader->loadFromFile($certPath, $certPassword);
    echo "OK\n";

    // Autenticar
    echo "Autenticando con SII... ";
    $billingPackage = $app->getPackageRegistry()->getBillingPackage();
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

    // Crear XML del RCOF
    echo "Generando XML RCOF... ";

    $timestamp = date('Y-m-d\TH:i:s');
    $secEnvio = rand(1, 999999);
    $documentId = 'RCOF_' . date('Ymd') . '_' . $secEnvio;

    // Calcular montos (aproximados para el set de pruebas)
    $mntTotal = $rcofData['total'];
    $mntNeto = round($mntTotal / 1.19);
    $mntIva = $mntTotal - $mntNeto;
    $mntExento = 2000; // CASO-4 tiene items exentos

    $rcofXml = '<?xml version="1.0" encoding="ISO-8859-1"?>
<ConsumoFolios xmlns="http://www.sii.cl/SiiDte" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.sii.cl/SiiDte ConsumoFolio_v10.xsd" version="1.0">
  <DocumentoConsumoFolios ID="' . $documentId . '">
    <Caratula version="1.0">
      <RutEmisor>' . $emisor['RUTEmisor'] . '</RutEmisor>
      <RutEnvia>' . $certificate->getID() . '</RutEnvia>
      <FchResol>' . $autorizacionConfig['fechaResolucion'] . '</FchResol>
      <NroResol>' . $autorizacionConfig['numeroResolucion'] . '</NroResol>
      <FchInicio>' . $rcofData['fecha'] . '</FchInicio>
      <FchFinal>' . $rcofData['fecha'] . '</FchFinal>
      <SecEnvio>' . $secEnvio . '</SecEnvio>
      <TmstFirmaEnv>' . $timestamp . '</TmstFirmaEnv>
    </Caratula>
    <Resumen>
      <TipoDocumento>39</TipoDocumento>
      <MntNeto>' . $mntNeto . '</MntNeto>
      <MntIva>' . $mntIva . '</MntIva>
      <TasaIVA>19</TasaIVA>
      <MntExento>' . $mntExento . '</MntExento>
      <MntTotal>' . $mntTotal . '</MntTotal>
      <FoliosEmitidos>' . $rcofData['cantidad'] . '</FoliosEmitidos>
      <FoliosAnulados>0</FoliosAnulados>
      <FoliosUtilizados>' . $rcofData['cantidad'] . '</FoliosUtilizados>
      <RangoUtilizados>
        <Inicial>' . $rcofData['folio_inicial'] . '</Inicial>
        <Final>' . $rcofData['folio_final'] . '</Final>
      </RangoUtilizados>
    </Resumen>
  </DocumentoConsumoFolios>
</ConsumoFolios>';

    // Firmar RCOF
    $dom = new DOMDocument('1.0', 'ISO-8859-1');
    $dom->loadXML($rcofXml);

    // Usar el firmador de LibreDTE
    $signatureComponent = $billingPackage->getSignatureComponent();
    $signatureWorker = $signatureComponent->getXmlSignatureWorker();

    $xmlDoc = new \Derafu\Xml\XmlDocument();
    $xmlDoc->loadXml($rcofXml);

    $signedXml = $signatureWorker->signXml(
        doc: $xmlDoc,
        certificate: $certificate,
        reference: '#' . $documentId
    );

    $rcofFile = $outputDir . "RCOF_" . date('Ymd_His') . ".xml";
    file_put_contents($rcofFile, $signedXml->saveXml());
    echo "OK\n";
    echo "  Guardado: $rcofFile\n";

    // Enviar RCOF
    echo "Enviando RCOF al SII... ";
    $trackId = $siiWorker->sendXmlDocument(
        request: $siiRequest,
        doc: $signedXml,
        company: $emisor['RUTEmisor'],
        compress: false,
        retry: 3
    );
    echo "OK\n";

    echo "\n╔══════════════════════════════════════════════════════════════════════╗\n";
    echo "║  RCOF ENVIADO - TRACK ID: $trackId                           ║\n";
    echo "╚══════════════════════════════════════════════════════════════════════╝\n";

    // Consultar estado
    echo "\nConsultando estado... ";
    sleep(2);
    $estado = $siiWorker->getUploadStatus($siiRequest, $trackId, $emisor['RUTEmisor']);
    echo "OK\n";
    echo "  Estado: {$estado['estado']} - {$estado['glosa']}\n";

    // Guardar resultado
    $result = [
        'track_id' => $trackId,
        'fecha' => date('Y-m-d H:i:s'),
        'estado' => $estado,
    ];
    file_put_contents($outputDir . "rcof_result_" . date('Ymd') . ".json", json_encode($result, JSON_PRETTY_PRINT));

    echo "\nRCOF completado exitosamente!\n";

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}
