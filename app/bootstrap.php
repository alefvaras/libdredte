<?php

/**
 * Bootstrap de la aplicación LibreDTE
 */

declare(strict_types=1);

// Autoloader de Composer
require_once __DIR__ . '/../libredte-lib-core-master/vendor/autoload.php';

// Cargar configuración
$config = require __DIR__ . '/config/config.php';
$emisorConfig = require __DIR__ . '/config/emisor.php';

// Definir constantes útiles
define('APP_ROOT', __DIR__);
define('AMBIENTE', $config['ambiente']);
define('ES_PRODUCCION', $config['ambiente'] === 'produccion');

// Configurar zona horaria de Chile
date_default_timezone_set('America/Santiago');

// Función helper para obtener la aplicación LibreDTE
function getLibreDTEApp(): \libredte\lib\Core\Application
{
    static $app = null;

    if ($app === null) {
        $app = \libredte\lib\Core\Application::getInstance(
            environment: ES_PRODUCCION ? 'prod' : 'dev',
            debug: !ES_PRODUCCION
        );
    }

    return $app;
}

// Función helper para cargar el certificado
function loadCertificate(string $path, string $password): \Derafu\Certificate\Contract\CertificateInterface
{
    $app = getLibreDTEApp();
    $certificateService = $app->getService(\Derafu\Certificate\Contract\CertificateServiceInterface::class);

    return $certificateService->load($path, $password);
}

// Función helper para cargar el CAF
function loadCaf(string $path): \libredte\lib\Core\Package\Billing\Component\Identifier\Contract\CafBagInterface
{
    $app = getLibreDTEApp();
    $billingPackage = $app->getPackageRegistry()->getBillingPackage();
    $cafLoader = $billingPackage->getIdentifierComponent()->getCafLoaderWorker();

    $cafXml = file_get_contents($path);
    return $cafLoader->load($cafXml);
}

return [
    'config' => $config,
    'emisor' => $emisorConfig,
];
