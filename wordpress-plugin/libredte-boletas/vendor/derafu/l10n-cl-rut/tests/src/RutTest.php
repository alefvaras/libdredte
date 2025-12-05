<?php

declare(strict_types=1);

/**
 * Derafu: L10n CL RUT - Chilean RUT library.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.org>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\L10n\Cl\TestsRut;

use Derafu\L10n\Cl\Rut\Rut;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use UnexpectedValueException;

#[CoversClass(Rut::class)]
class RutTest extends TestCase
{
    /**
     * Test for toArray() with valid and formatted RUTs.
     *
     * This test, like many others, is concerned with delivering the data
     * as requested, but does not perform additional validations. In this case,
     * the RUT 12.345.678-K is not a valid RUT, however the test passes
     * correctly because what the Rut::toArray() method does is only
     * return the separated and formatted values, but it does NOT validate. This
     * same happens in other tests. The validation of a real RUT (its verification digit)
     * is tested in the testValidateValid???() tests using the Rut::validate() method.
     */
    public function testToArrayValid(): void
    {
        $this->assertSame([12345678, 'K'], Rut::toArray('12.345.678-K'));
        $this->assertSame([12345678, 'K'], Rut::toArray('12345678-K'));
        $this->assertSame([9876543, '5'], Rut::toArray('9876543-5'));
    }

    /**
     * Test for format() with a valid RUT as string and as integer.
     */
    public function testFormatValid(): void
    {
        $this->assertSame('12345678-K', Rut::format('12.345.678-K'));
        $this->assertSame('12345678-5', Rut::format(12345678));
    }

    /**
     * Test for formatFull() with a valid RUT.
     */
    public function testFormatFullValid(): void
    {
        $this->assertSame('12.345.678-K', Rut::formatFull('12345678-K'));
        $this->assertSame('9.876.543-3', Rut::formatFull(9876543));
    }

    /**
     * Test for calculateDv() with several valid RUTs.
     */
    public function testCalculateDv(): void
    {
        $this->assertSame('5', Rut::calculateDv(12345678));
        $this->assertSame('3', Rut::calculateDv(9876543));
        $this->assertSame('1', Rut::calculateDv(11111111));
    }

    /**
     * Test for validate() with valid RUTs.
     */
    public function testValidateValid(): void
    {
        // Should not throw exceptions.
        Rut::validate('12.345.678-5');
        Rut::validate('9.876.543-3');

        $this->assertTrue(true);
    }

    /**
     * Test for validate() with incorrect RUTs (incorrect verification digit).
     */
    public function testValidateInvalid(): void
    {
        $this->expectException(UnexpectedValueException::class);

        // Incorrect verification digit, should throw exception.
        Rut::validate('12.345.678-K');
    }

    /**
     * Test for validate() with RUTs less than the minimum.
     */
    public function testValidateBelowMin(): void
    {
        $this->expectException(UnexpectedValueException::class);

        // Less than the minimum, should throw exception.
        Rut::validate('999.999-9');
    }

    /**
     * Test for validate() with RUTs greater than the maximum.
     */
    public function testValidateAboveMax(): void
    {
        $this->expectException(UnexpectedValueException::class);

        // Greater than the maximum, should throw exception.
        Rut::validate('100.000.000-0');
    }

    /**
     * Test for removeDv() with valid RUTs.
     */
    public function testRemoveDv(): void
    {
        $this->assertSame(12345678, Rut::removeDv('12.345.678-K'));
        $this->assertSame(9876543, Rut::removeDv('9.876.543-5'));
    }

    /**
     * Test for addDv() with valid RUTs.
     */
    public function testAddDv(): void
    {
        $this->assertSame('123456785', Rut::addDv(12345678));
        $this->assertSame('98765433', Rut::addDv(9876543));
    }
}
