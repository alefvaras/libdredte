# Derafu: L10n CL RUT - Chilean RUT library

[![CI Workflow](https://github.com/derafu/l10n-cl-rut/actions/workflows/ci.yml/badge.svg?branch=main&event=push)](https://github.com/derafu/l10n-cl-rut/actions/workflows/ci.yml?query=branch%3Amain)
[![License](https://img.shields.io/badge/license-MIT-blue.svg)](https://opensource.org/licenses/MIT)

A PHP library for working with Chilean RUT and RUN identifiers.

## Description

The Chilean RUT (Rol Único Tributario) and RUN (Rol Único Nacional) are unique identification numbers assigned to individuals and businesses in Chile. This library provides utility functions to work with these identifiers, including:

- Validation of RUT/RUN numbers.
- Formatting with and without thousands separators.
- Calculation of verification digits.
- Parsing and handling different input formats.

## Installation

You can install the package via composer:

```bash
composer require derafu/l10n-cl-rut
```

## Usage

### Basic Examples

```php
use Derafu\L10n\Cl\Rut\Rut;

// Format a RUT.
echo Rut::format(12345678);              // Outputs: 12345678-5
echo Rut::formatFull(12345678);          // Outputs: 12.345.678-5

// Validate a RUT.
try {
    Rut::validate('12.345.678-5');       // Valid RUT.
    echo "RUT is valid\n";
} catch (UnexpectedValueException $e) {
    echo "RUT is invalid: " . $e->getMessage() . "\n";
}

// Calculate the verification digit.
$dv = Rut::calculateDv(12345678);        // Returns: '5'

// Convert a RUT to array [number, verification digit]
$parts = Rut::toArray('12.345.678-5');   // Returns: [12345678, '5']

// Extract only the numeric part.
$number = Rut::removeDv('12.345.678-5'); // Returns: 12345678

// Add verification digit to a number.
$withDv = Rut::addDv(12345678);          // Returns: '123456785'
```

### Validation Constraints

This library enforces some practical constraints on RUT numbers:

- The RUT number must be between 1,000,000 and 99,999,999.
- The verification digit must be a number between 0-9 or the uppercase letter 'K'.
- The verification digit must be valid for the given RUT number.

## Method Reference

- `toArray(string $rut)`: Extracts the number and verification digit from a RUT.
- `format(string|int $rut)`: Formats a RUT as "12345678-5".
- `formatFull(string|int $rut)`: Formats a RUT as "12.345.678-5".
- `calculateDv(int $rut)`: Calculates the verification digit for a RUT.
- `validate(string $rut)`: Validates a RUT and throws an exception if invalid.
- `removeDv(string $rut)`: Extracts just the numeric part of a RUT.
- `addDv(int $rut)`: Adds the verification digit to a RUT number.

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request. For major changes, please open an issue first to discuss what you would like to change.

## License

This package is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
