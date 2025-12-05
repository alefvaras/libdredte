<?php

/**
 * Script para consultar el estado de un envío al SII
 *
 * Uso:
 *   php consultar_estado.php <track_id>
 */

declare(strict_types=1);

use Derafu\Certificate\Contract\CertificateServiceInterface;
use libredte\lib\Core\Application;
use libredte\lib\Core\Package\Billing\Component\Integration\Enum\SiiAmbiente;
use libredte\lib\Core\Package\Billing\Component\Integration\Support\SiiRequest;

require_once __DIR__ . '/../libredte-lib-core-master/vendor/autoload.php';

$config = require __DIR__ . '/config/config.php';
$emisorConfig = require __DIR__ . '/config/emisor.php';

date_default_timezone_set('America/Santiago');

if (!isset($argv[1]) || !is_numeric($argv[1])) {
    echo "Uso: php consultar_estado.php <track_id>\n";
    echo "Ejemplo: php consultar_estado.php 123456789\n";
    exit(1);
}

$trackId = (int) $argv[1];

echo "╔══════════════════════════════════════════════════════════════╗\n";
echo "║          CONSULTA DE ESTADO DE ENVIO - SII                  ║\n";
echo "╚══════════════════════════════════════════════════════════════╝\n\n";

echo "Track ID: $trackId\n\n";

try {
    $esProduccion = $config['ambiente'] === 'produccion';
    $ambiente = $esProduccion ? SiiAmbiente::PRODUCCION : SiiAmbiente::CERTIFICACION;

    // Inicializar
    $app = Application::getInstance(
        environment: $esProduccion ? 'prod' : 'dev',
        debug: !$esProduccion
    );

    $certificateService = $app->getService(CertificateServiceInterface::class);
    $billingPackage = $app->getPackageRegistry()->getBillingPackage();
    $integrationComponent = $billingPackage->getIntegrationComponent();

    // Cargar certificado
    echo "Cargando certificado... ";
    $certificate = $certificateService->load(
        $config['paths']['certificado'],
        $config['certificado_password']
    );
    echo "OK\n";

    $siiWorker = $integrationComponent->getSiiLazyWorker();
    $siiRequest = new SiiRequest(
        certificate: $certificate,
        options: [
            'ambiente' => $ambiente,
            'token' => ['cache' => 'filesystem', 'ttl' => 300],
        ]
    );

    // Autenticar
    echo "Autenticando con SII... ";
    $token = $siiWorker->authenticate($siiRequest);
    echo "OK\n\n";

    // Consultar estado
    echo "Consultando estado del Track ID $trackId...\n\n";

    $estadoResponse = $siiWorker->checkXmlDocumentSentStatus(
        request: $siiRequest,
        trackId: $trackId,
        company: $emisorConfig['RUTEmisor']
    );

    echo "╔══════════════════════════════════════════════════════════════╗\n";
    echo "║                    RESULTADO                                ║\n";
    echo "╠══════════════════════════════════════════════════════════════╣\n";
    printf("║  Track ID:  %-47s  ║\n", $trackId);
    printf("║  Estado:    %-47s  ║\n", $estadoResponse->getEstado());
    printf("║  Glosa:     %-47s  ║\n", substr($estadoResponse->getGlosa(), 0, 47));
    echo "╚══════════════════════════════════════════════════════════════╝\n";

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
