<?php
/**
 * Configuracion centralizada para CLI - Akibara SII
 *
 * Este archivo contiene la configuracion comun para todos los scripts CLI.
 * Soporta ambientes de certificacion y produccion.
 */

require_once dirname(__DIR__) . '/vendor/autoload.php';
require_once dirname(__DIR__) . '/includes/class-folio-manager.php';

use libredte\lib\Core\Package\Billing\Component\Integration\Enum\SiiAmbiente;

/**
 * Configuracion del Emisor
 */
define('AKIBARA_EMISOR', [
    'RUTEmisor' => '78274225-6',
    'RznSoc' => 'AKIBARA SPA',
    'GiroEmis' => 'VENTA AL POR MENOR DE LIBROS Y OTROS PRODUCTOS',
    'DirOrigen' => 'BARTOLO SOTO 3700 DP 1402 PISO 14',
    'CmnaOrigen' => 'San Miguel',
    'Acteco' => 476101,
]);

/**
 * Configuracion de Autorizacion DTE (Resolucion SII)
 */
define('AKIBARA_AUTORIZACION', [
    'fechaResolucion' => '2014-08-22',
    'numeroResolucion' => 80,
]);

/**
 * Paths de certificados y CAF
 */
define('AKIBARA_CERT_PATH', '/home/user/libdredte/app/credentials/certificado.p12');
define('AKIBARA_CERT_PASSWORD', '5605');
define('AKIBARA_CAF_PATH', '/home/user/libdredte/app/credentials/caf_39.xml');

/**
 * Directorios de salida
 */
define('AKIBARA_OUTPUT_DIR', dirname(__DIR__) . '/uploads/output/');
define('AKIBARA_APP_OUTPUT_DIR', '/home/user/libdredte/app/output/');

/**
 * Clase helper para configuracion de ambiente
 */
class Akibara_Config {

    /**
     * Obtener ambiente desde argumentos CLI
     *
     * @param array $argv Argumentos de linea de comandos
     * @param int $argIndex Indice del argumento (default 1)
     * @param string $default Ambiente por defecto
     * @return array Configuracion del ambiente
     */
    public static function getAmbienteFromArgs(array $argv, int $argIndex = 1, string $default = 'certificacion'): array {
        $ambienteArg = $argv[$argIndex] ?? $default;
        $ambiente = strtolower(trim($ambienteArg));

        if (!in_array($ambiente, [Akibara_Folio_Manager::AMBIENTE_CERTIFICACION, Akibara_Folio_Manager::AMBIENTE_PRODUCCION])) {
            throw new Exception("Ambiente invalido '$ambiente'. Use 'certificacion' o 'produccion'.");
        }

        return [
            'ambiente' => $ambiente,
            'sii_ambiente' => $ambiente === Akibara_Folio_Manager::AMBIENTE_CERTIFICACION
                ? SiiAmbiente::CERTIFICACION
                : SiiAmbiente::PRODUCCION,
            'nombre' => $ambiente === Akibara_Folio_Manager::AMBIENTE_CERTIFICACION
                ? 'CERTIFICACIÓN'
                : 'PRODUCCIÓN',
            'sii_url' => $ambiente === Akibara_Folio_Manager::AMBIENTE_CERTIFICACION
                ? 'maullin.sii.cl'
                : 'palena.sii.cl',
            'is_certificacion' => $ambiente === Akibara_Folio_Manager::AMBIENTE_CERTIFICACION,
            'is_produccion' => $ambiente === Akibara_Folio_Manager::AMBIENTE_PRODUCCION,
        ];
    }

    /**
     * Mostrar header con informacion del ambiente
     *
     * @param string $titulo Titulo del script
     * @param array $ambienteConfig Configuracion del ambiente
     */
    public static function printHeader(string $titulo, array $ambienteConfig): void {
        echo "╔══════════════════════════════════════════════════════════════════════╗\n";
        printf("║  %-66s  ║\n", $titulo);
        echo "║                    AKIBARA SII - Plugin WordPress                  ║\n";
        echo "╠══════════════════════════════════════════════════════════════════════╣\n";
        printf("║  Ambiente: %-15s  SII: %-25s  ║\n", $ambienteConfig['nombre'], $ambienteConfig['sii_url']);
        echo "╚══════════════════════════════════════════════════════════════════════╝\n\n";
    }

    /**
     * Obtener configuracion del emisor
     */
    public static function getEmisor(): array {
        return AKIBARA_EMISOR;
    }

    /**
     * Obtener configuracion de autorizacion
     */
    public static function getAutorizacion(): array {
        return AKIBARA_AUTORIZACION;
    }

    /**
     * Crear directorio de salida si no existe
     */
    public static function ensureOutputDir(): string {
        $dir = AKIBARA_OUTPUT_DIR;
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        return $dir;
    }

    /**
     * Obtener nombre del tipo de DTE
     */
    public static function getTipoDteNombre(int $tipoDte): string {
        $tipos = [
            33 => 'Factura Electrónica',
            34 => 'Factura No Afecta o Exenta Electrónica',
            39 => 'Boleta Electrónica',
            41 => 'Boleta Exenta Electrónica',
            43 => 'Liquidación Factura Electrónica',
            46 => 'Factura de Compra Electrónica',
            52 => 'Guía de Despacho Electrónica',
            56 => 'Nota de Débito Electrónica',
            61 => 'Nota de Crédito Electrónica',
            110 => 'Factura de Exportación Electrónica',
            111 => 'Nota de Débito de Exportación Electrónica',
            112 => 'Nota de Crédito de Exportación Electrónica',
        ];
        return $tipos[$tipoDte] ?? "DTE $tipoDte";
    }
}
