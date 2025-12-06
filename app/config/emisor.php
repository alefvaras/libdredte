<?php

/**
 * Configuración del Emisor - AKIBARA SPA
 */

return [
    // RUT del emisor
    'RUTEmisor' => '78274225-6',

    // Razón social del emisor
    'RznSoc' => 'AKIBARA SPA',

    // Giro del emisor
    'GiroEmis' => 'VENTA AL POR MENOR DE LIBROS Y OTROS PRODUCTOS',

    // Código de actividad económica principal
    'Acteco' => 476101,

    // Dirección del emisor
    'DirOrigen' => 'BARTOLO SOTO 3700 DP 1402 PISO 14',

    // Comuna del emisor
    'CmnaOrigen' => 'San Miguel',

    // Código sucursal SII (null si es casa matriz)
    'CdgSIISucur' => null,

    // Teléfono
    'Telefono' => '942806106',

    // Correo electrónico
    'CorreoEmisor' => 'contacto@akibara.cl',

    // Código vendedor (opcional)
    'CdgVendedor' => null,

    // Autorización DTE del SII (requerido para envío)
    // Para CERTIFICACION: usar fecha de autorización y número 0
    // Para PRODUCCION: usar fecha/numero de resolución real del SII
    'autorizacionDte' => [
        'fechaResolucion' => '2014-08-22',
        'numeroResolucion' => 0,
    ],
];
