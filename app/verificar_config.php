<?php

/**
 * Script para verificar la configuración del sistema
 *
 * Ejecutar: php verificar_config.php
 */

declare(strict_types=1);

echo "==============================================\n";
echo "  VERIFICACION DE CONFIGURACION - LibreDTE   \n";
echo "==============================================\n\n";

$errores = [];
$advertencias = [];

// 1. Verificar PHP
echo "1. Verificando PHP...\n";
$phpVersion = PHP_VERSION;
echo "   Version: $phpVersion\n";

if (version_compare($phpVersion, '8.4.0', '<')) {
    $errores[] = "Se requiere PHP 8.4 o superior. Tienes PHP $phpVersion";
} else {
    echo "   [OK] PHP 8.4+ instalado\n";
}

// 2. Verificar extensiones
echo "\n2. Verificando extensiones PHP...\n";
$extensionesRequeridas = ['curl', 'json', 'mbstring', 'openssl', 'soap', 'xml', 'dom'];

foreach ($extensionesRequeridas as $ext) {
    if (extension_loaded($ext)) {
        echo "   [OK] $ext\n";
    } else {
        $errores[] = "Extensión PHP faltante: $ext";
        echo "   [ERROR] $ext - NO INSTALADA\n";
    }
}

// 3. Verificar archivos de configuración
echo "\n3. Verificando archivos de configuración...\n";

$configFile = __DIR__ . '/config/config.php';
$emisorFile = __DIR__ . '/config/emisor.php';

if (file_exists($configFile)) {
    echo "   [OK] config/config.php existe\n";
    $config = require $configFile;
} else {
    $errores[] = "Archivo config/config.php no encontrado";
    echo "   [ERROR] config/config.php NO EXISTE\n";
}

if (file_exists($emisorFile)) {
    echo "   [OK] config/emisor.php existe\n";
    $emisor = require $emisorFile;

    // Verificar que los datos del emisor estén completos
    $camposRequeridos = ['RUTEmisor', 'RznSoc', 'GiroEmis', 'Acteco', 'DirOrigen', 'CmnaOrigen'];
    foreach ($camposRequeridos as $campo) {
        if (empty($emisor[$campo])) {
            $advertencias[] = "Campo '$campo' del emisor está vacío en config/emisor.php";
        }
    }
} else {
    $errores[] = "Archivo config/emisor.php no encontrado";
    echo "   [ERROR] config/emisor.php NO EXISTE\n";
}

// 4. Verificar directorios
echo "\n4. Verificando directorios...\n";

$directorios = [
    __DIR__ . '/credentials' => 'credentials/',
    __DIR__ . '/output' => 'output/',
    __DIR__ . '/logs' => 'logs/',
];

foreach ($directorios as $path => $nombre) {
    if (is_dir($path)) {
        if (is_writable($path)) {
            echo "   [OK] $nombre existe y es escribible\n";
        } else {
            $advertencias[] = "Directorio $nombre no tiene permisos de escritura";
            echo "   [WARN] $nombre existe pero NO ES ESCRIBIBLE\n";
        }
    } else {
        $errores[] = "Directorio $nombre no existe";
        echo "   [ERROR] $nombre NO EXISTE\n";
    }
}

// 5. Verificar certificado
echo "\n5. Verificando certificado digital...\n";

if (isset($config['paths']['certificado'])) {
    $certPath = $config['paths']['certificado'];
    if (file_exists($certPath)) {
        echo "   [OK] Certificado encontrado: " . basename($certPath) . "\n";

        // Intentar cargar el certificado
        try {
            require_once __DIR__ . '/../libredte-lib-core-master/vendor/autoload.php';
            $certContent = file_get_contents($certPath);
            $certPassword = $config['certificado_password'] ?? '';

            if ($certPassword === 'tu_password_aqui') {
                $advertencias[] = "Debes cambiar la contraseña del certificado en config/config.php";
                echo "   [WARN] La contraseña del certificado es el valor por defecto\n";
            }
        } catch (Exception $e) {
            $advertencias[] = "No se pudo verificar el certificado: " . $e->getMessage();
        }
    } else {
        $advertencias[] = "Certificado no encontrado. Sube tu archivo .p12 a: credentials/certificado.p12";
        echo "   [PENDIENTE] Certificado no encontrado\n";
        echo "   -> Sube tu archivo .p12 a: credentials/certificado.p12\n";
    }
}

// 6. Verificar CAF
echo "\n6. Verificando CAF de boletas (código 39)...\n";

if (isset($config['paths']['caf_boleta_afecta'])) {
    $cafPath = $config['paths']['caf_boleta_afecta'];
    if (file_exists($cafPath)) {
        echo "   [OK] CAF encontrado: " . basename($cafPath) . "\n";

        // Verificar contenido básico del CAF
        $cafContent = file_get_contents($cafPath);
        if (strpos($cafContent, '<TD>39</TD>') !== false || strpos($cafContent, '<TD>39<') !== false) {
            echo "   [OK] CAF es para boletas afectas (código 39)\n";
        } else {
            $advertencias[] = "El CAF podría no ser para boletas afectas (código 39)";
            echo "   [WARN] Verifica que el CAF sea para boletas afectas (código 39)\n";
        }
    } else {
        $advertencias[] = "CAF no encontrado. Sube tu archivo CAF a: credentials/caf_39.xml";
        echo "   [PENDIENTE] CAF no encontrado\n";
        echo "   -> Sube tu archivo CAF de boletas a: credentials/caf_39.xml\n";
    }
}

// 7. Verificar LibreDTE
echo "\n7. Verificando LibreDTE...\n";

$autoloadPath = __DIR__ . '/../libredte-lib-core-master/vendor/autoload.php';
if (file_exists($autoloadPath)) {
    echo "   [OK] Autoloader de Composer encontrado\n";

    try {
        require_once $autoloadPath;
        $app = \libredte\lib\Core\Application::getInstance('dev', true);
        echo "   [OK] LibreDTE Application inicializada correctamente\n";

        $billingPackage = $app->getPackageRegistry()->getBillingPackage();
        echo "   [OK] Paquete Billing cargado\n";

    } catch (Exception $e) {
        $errores[] = "Error al inicializar LibreDTE: " . $e->getMessage();
        echo "   [ERROR] No se pudo inicializar LibreDTE\n";
    }
} else {
    $errores[] = "No se encontró el autoloader. Ejecuta: composer install";
    echo "   [ERROR] Autoloader no encontrado\n";
}

// Resumen
echo "\n==============================================\n";
echo "  RESUMEN DE VERIFICACION\n";
echo "==============================================\n\n";

if (empty($errores) && empty($advertencias)) {
    echo "ESTADO: TODO OK - Sistema listo para emitir boletas\n\n";
} else {
    if (!empty($errores)) {
        echo "ERRORES (" . count($errores) . "):\n";
        foreach ($errores as $i => $error) {
            echo "  " . ($i + 1) . ". $error\n";
        }
        echo "\n";
    }

    if (!empty($advertencias)) {
        echo "ADVERTENCIAS (" . count($advertencias) . "):\n";
        foreach ($advertencias as $i => $adv) {
            echo "  " . ($i + 1) . ". $adv\n";
        }
        echo "\n";
    }
}

echo "==============================================\n";

// Instrucciones si faltan archivos
if (!file_exists($config['paths']['certificado'] ?? '') || !file_exists($config['paths']['caf_boleta_afecta'] ?? '')) {
    echo "\nPARA COMPLETAR LA CONFIGURACION:\n";
    echo "1. Sube tu certificado digital (.p12) a:\n";
    echo "   /home/user/libdredte/app/credentials/certificado.p12\n\n";
    echo "2. Sube tu CAF de boletas (código 39) a:\n";
    echo "   /home/user/libdredte/app/credentials/caf_39.xml\n\n";
    echo "3. Edita la contraseña del certificado en:\n";
    echo "   /home/user/libdredte/app/config/config.php\n\n";
    echo "4. Edita los datos de tu empresa en:\n";
    echo "   /home/user/libdredte/app/config/emisor.php\n\n";
}

exit(empty($errores) ? 0 : 1);
