<?php

/**
 * Configuración general de la aplicación
 */

return [
    // Ambiente: 'produccion' o 'certificacion'
    // IMPORTANTE: Usa 'certificacion' para pruebas antes de ir a producción
    'ambiente' => 'certificacion',

    // Rutas de archivos
    'paths' => [
        'certificado' => __DIR__ . '/../credentials/certificado.p12',
        'caf_boleta_afecta' => __DIR__ . '/../credentials/caf_39.xml',
        'output' => __DIR__ . '/../output/',
        'logs' => __DIR__ . '/../logs/',
    ],

    // Contraseña del certificado digital (.p12)
    // IMPORTANTE: Cambia esto por la contraseña real de tu certificado
    'certificado_password' => '5605',

    // Configuración del SII
    'sii' => [
        // URLs según ambiente
        'urls' => [
            'certificacion' => [
                'auth' => 'https://maullin.sii.cl/DTEWS/',
                'dte' => 'https://maullin.sii.cl/cgi_dte/',
                'boleta' => 'https://pangal.sii.cl/recursos/v1/',
            ],
            'produccion' => [
                'auth' => 'https://palena.sii.cl/DTEWS/',
                'dte' => 'https://palena.sii.cl/cgi_dte/',
                'boleta' => 'https://rahue.sii.cl/recursos/v1/',
            ],
        ],
    ],
];
