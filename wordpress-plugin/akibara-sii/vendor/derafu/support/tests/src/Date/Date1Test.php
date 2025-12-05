<?php

declare(strict_types=1);

/**
 * Derafu: Support - Essential PHP Utilities.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\TestsSupport\Date;

use Derafu\Support\Date;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Date::class)]
class Date1Test extends TestCase
{
    /**
     * Prueba para validateAndConvert() con fechas válidas en formato Y-m-d.
     */
    public function testValidateAndConvertValidDate(): void
    {
        $this->assertSame('12/03/2023', Date::validateAndConvert('2023-03-12'));
        $this->assertSame('31/12/2022', Date::validateAndConvert('2022-12-31'));
        $this->assertSame('01/01/2020', Date::validateAndConvert('2020-01-01'));
    }

    /**
     * Prueba para validateAndConvert() con una fecha en formato Y-m-d pero con
     * un formato de salida personalizado.
     */
    public function testValidateAndConvertWithCustomFormat(): void
    {
        $this->assertSame('12-03-2023', Date::validateAndConvert('2023-03-12', 'd-m-Y'));
        $this->assertSame('31.12.2022', Date::validateAndConvert('2022-12-31', 'd.m.Y'));
    }

    /**
     * Prueba para validateAndConvert() con fechas inválidas.
     */
    public function testValidateAndConvertInvalidDate(): void
    {
        // Mes inválido.
        $this->assertNull(Date::validateAndConvert('2023-13-12'));

        // Formato inválido.
        $this->assertNull(Date::validateAndConvert('not-a-date'));

        // Formato incorrecto.
        $this->assertNull(Date::validateAndConvert('2023/03/12'));
    }

    /**
     * Prueba para validateAndConvert() con fechas en otros formatos que
     * deberían fallar.
     */
    public function testValidateAndConvertWithWrongFormat(): void
    {
        // Día primero en lugar de Año.
        $this->assertNull(Date::validateAndConvert('12/03/2023'));

        // Puntos en lugar de guiones.
        $this->assertNull(Date::validateAndConvert('2023.03.12'));
    }

    public function testDateFormatSpanish(): void
    {
        $this->assertSame(
            'Lunes, 13 de Enero del 2025',
            Date::formatSpanish('2025-01-13')
        );

        $this->assertSame(
            '13 de Enero del 2025',
            Date::formatSpanish('2025-01-13', includeDay: false)
        );
    }
}
