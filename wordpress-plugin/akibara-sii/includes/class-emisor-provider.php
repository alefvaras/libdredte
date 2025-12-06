<?php
/**
 * Proveedor de datos del emisor para LibreDTE
 * Lee la configuración de WordPress options
 */

defined('ABSPATH') || exit;

use libredte\lib\Core\Package\Billing\Component\TradingParties\Contract\EmisorProviderInterface;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Contract\EmisorInterface;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Contract\EmisorFactoryInterface;
use Derafu\Support\Hydrator;

class Akibara_Emisor_Provider implements EmisorProviderInterface
{
    private EmisorFactoryInterface $emisorFactory;

    public function __construct(EmisorFactoryInterface $emisorFactory)
    {
        $this->emisorFactory = $emisorFactory;
    }

    /**
     * Obtener datos del emisor desde WordPress options
     */
    public function retrieve(int|string|EmisorInterface $emisor): EmisorInterface
    {
        // Si se pasó el RUT se crea una instancia del emisor usando la factory
        if (is_int($emisor) || is_string($emisor)) {
            $emisor = $this->emisorFactory->create(['rut' => $emisor]);
        }

        // Obtener datos de WordPress options
        $rut = get_option('akibara_emisor_rut', '');
        $razon_social = get_option('akibara_emisor_razon_social', '');
        $giro = get_option('akibara_emisor_giro', '');
        $actividad_economica = get_option('akibara_emisor_acteco', 726000);
        $direccion = get_option('akibara_emisor_direccion', '');
        $comuna = get_option('akibara_emisor_comuna', '');
        $telefono = get_option('akibara_emisor_telefono', '');
        $email = get_option('akibara_emisor_email', '');

        // Parsear RUT
        $rut_parts = explode('-', $rut);
        $rut_numero = isset($rut_parts[0]) ? (int) str_replace('.', '', $rut_parts[0]) : 0;
        $rut_dv = isset($rut_parts[1]) ? $rut_parts[1] : '';

        // Hidratar el emisor con los datos reales
        $emisor = Hydrator::hydrate($emisor, [
            'rut' => $rut_numero,
            'dv' => $rut_dv,
            'razon_social' => $razon_social,
            'giro' => $giro,
            'actividad_economica' => (int) $actividad_economica,
            'telefono' => $telefono,
            'email' => $email,
            'direccion' => $direccion,
            'comuna' => $comuna,
        ]);

        return $emisor;
    }
}
