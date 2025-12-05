<?php

declare(strict_types=1);

/**
 * Derafu: L10n CL Enum - Chilean Enum Repository.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\L10n\Cl\Enum;

/**
 * Enum to represent the regions of Chile.
 */
enum Region: int
{
    case TARAPACA = 1;
    case ANTOFAGASTA = 2;
    case ATACAMA = 3;
    case COQUIMBO = 4;
    case VALPARAISO = 5;
    case O_HIGGINS = 6;
    case MAULE = 7;
    case BIOBIO = 8;
    case ARAUCANIA = 9;
    case LOS_LAGOS = 10;
    case AYSEN = 11;
    case MAGALLANES = 12;
    case METROPOLITANA = 13;
    case LOS_RIOS = 14;
    case ARICA_PARINACOTA = 15;
    case NUBLE = 16;

    private const GLOSAS = [
        self::TARAPACA->value => 'Tarapacá',
        self::ANTOFAGASTA->value => 'Antofagasta',
        self::ATACAMA->value => 'Atacama',
        self::COQUIMBO->value => 'Coquimbo',
        self::VALPARAISO->value => 'Valparaíso',
        self::O_HIGGINS->value => 'Libertador General Bernardo O’Higgins',
        self::MAULE->value => 'Maule',
        self::BIOBIO->value => 'Biobío',
        self::ARAUCANIA->value => 'Araucanía',
        self::LOS_LAGOS->value => 'Los Lagos',
        self::AYSEN->value => 'Aysén',
        self::MAGALLANES->value => 'Magallanes',
        self::METROPOLITANA->value => 'Metropolitana',
        self::LOS_RIOS->value => 'Los Ríos',
        self::ARICA_PARINACOTA->value => 'Arica y Parinacota',
        self::NUBLE->value => 'Ñuble',
    ];

    /**
     * Returns the normalized code of the region in 2 digits.
     *
     * @return string
     */
    public function getCodigo(): string
    {
        return $this->value < 10 ? '0' . $this->value : (string) $this->value;
    }

    /**
     * Returns the glosa associated with a region.
     *
     * @return string
     */
    public function getGlosa(): string
    {
        return self::GLOSAS[$this->value];
    }

    /**
     * Returns the name of the region.
     *
     * @return string
     */
    public function getNombre(): string
    {
        return $this->getGlosa();
    }

    /**
     * Returns the provinces associated with the region.
     *
     * The first 2 digits of the normalized code of the province correspond to
     * the normalized code of the region.
     *
     * @return Provincia[]
     */
    public function getProvincias(): array
    {
        return array_filter(
            Provincia::cases(),
            fn ($provincia) =>
                substr($provincia->getCodigo(), 0, 2) === $this->getCodigo()
        );
    }

    /**
     * Returns the communes associated with the region.
     *
     * The first 2 digits of the normalized code of the commune correspond to
     * the normalized code of the region.
     *
     * @return Comuna[]
     */
    public function getComunas(): array
    {
        return array_filter(
            Comuna::cases(),
            fn ($comuna) =>
                substr($comuna->getCodigo(), 0, 2) === $this->getCodigo()
        );
    }
}
