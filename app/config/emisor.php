<?php

/**
 * Configuración del Emisor (tu empresa)
 *
 * IMPORTANTE: Completa estos datos con la información de tu empresa.
 * Estos datos deben coincidir con los del CAF que subas.
 */

return [
    // RUT del emisor (sin puntos, con guión y dígito verificador)
    'RUTEmisor' => '76192083-3', // CAMBIAR por tu RUT

    // Razón social del emisor
    'RznSoc' => 'MI EMPRESA EJEMPLO SPA', // CAMBIAR por tu razón social

    // Giro del emisor
    'GiroEmis' => 'TECNOLOGIA E INFORMATICA', // CAMBIAR por tu giro

    // Código de actividad económica principal
    'Acteco' => 620200, // CAMBIAR por tu código de actividad

    // Dirección del emisor
    'DirOrigen' => 'CALLE EJEMPLO 123', // CAMBIAR por tu dirección

    // Comuna del emisor
    'CmnaOrigen' => 'Santiago', // CAMBIAR por tu comuna

    // Ciudad del emisor
    'CdgSIISucur' => null, // Código sucursal SII (null si es casa matriz)

    // Teléfono (opcional)
    'Telefono' => null,

    // Correo electrónico (opcional pero recomendado)
    'CorreoEmisor' => null,

    // Código vendedor (opcional)
    'CdgVendedor' => null,
];
