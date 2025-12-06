<?php

declare(strict_types=1);

/**
 * Derafu: Enum - Yet Another List of Enumerations for PHP.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\Enum;

/**
 * Enum with, mainly, currencies in ISO 4217 standard.
 *
 * This enum is based on the ISO 4217 standard, but extended with some
 * currencies that are not in the standard. Some examples are:
 *
 *   - Bitcoin (BTC).
 *   - Unidad de Fomento de Chile (CLF).
 *   - Unidad Tributaria Mensual de Chile (UTM).
 *   - Unidad Tributaria Anual de Chile (UTA).
 *
 * @see https://www.iso.org/iso-4217-currency-codes.html
 * @see https://en.wikipedia.org/wiki/ISO_4217
 */
enum Currency: string
{
    /**
     * Chilean pesos.
     */
    case CLP = 'CLP';

    /**
     * Chilean Unidad de Fomento (UF).
     */
    case CLF = 'CLF';

    /**
     * Chilean Monthly Tax Unit (UTM).
     */
    case UTM = 'UTM';

    /**
     * Chilean Annual Tax Unit (UTA).
     */
    case UTA = 'UTA';

    /**
     * United States Dollar (USD).
     */
    case USD = 'USD';

    /**
     * Euro (EUR).
     */
    case EUR = 'EUR';

    /**
     * Bitcoin (BTC).
     *
     * I love Bitcoin <3
     */
    case BTC = 'BTC';

    /**
     * Argentine Peso (ARS).
     */
    case ARS = 'ARS';

    /**
     * British Pound (GBP).
     */
    case GBP = 'GBP';

    /**
     * Swedish Krona (SEK).
     */
    case SEK = 'SEK';

    /**
     * Hong Kong Dollar (HKD).
     */
    case HKD = 'HKD';

    /**
     * South African Rand (ZAR).
     */
    case ZAR = 'ZAR';

    /**
     * Colombian Peso (COP).
     */
    case COP = 'COP';

    /**
     * Mexican Peso (MXN).
     */
    case MXN = 'MXN';

    /**
     * Venezuelan Bolívar (VES).
     */
    case VES = 'VES';

    /**
     * Singapore Dollar (SGD).
     */
    case SGD = 'SGD';

    /**
     * Indian Rupee (INR).
     */
    case INR = 'INR';

    /**
     * New Taiwan Dollar (TWD).
     */
    case TWD = 'TWD';

    /**
     * United Arab Emirates Dirham (AED).
     */
    case AED = 'AED';

    /**
     * South Korean Won (KRW).
     */
    case KRW = 'KRW';

    /**
     * Polish Zloty (PLN).
     */
    case PLN = 'PLN';

    /**
     * Czech Koruna (CZK).
     */
    case CZK = 'CZK';

    /**
     * Hungarian Forint (HUF).
     */
    case HUF = 'HUF';

    /**
     * Thai Baht (THB).
     */
    case THB = 'THB';

    /**
     * Turkish Lira (TRY).
     */
    case TRY = 'TRY';

    /**
     * Malaysian Ringgit (MYR).
     */
    case MYR = 'MYR';

    /**
     * Russian Ruble (RUB).
     */
    case RUB = 'RUB';

    /**
     * Indonesian Rupiah (IDR).
     */
    case IDR = 'IDR';

    /**
     * Ukrainian Hryvnia (UAH).
     */
    case UAH = 'UAH';

    /**
     * Israeli Shekel (ILS).
     */
    case ILS = 'ILS';

    /**
     * Philippine Peso (PHP).
     */
    case PHP = 'PHP';

    /**
     * Saudi Riyal (SAR).
     */
    case SAR = 'SAR';

    /**
     * Pakistani Rupee (PKR).
     */
    case PKR = 'PKR';

    /**
     * Vietnamese Dong (VND).
     */
    case VND = 'VND';

    /**
     * Egyptian Pound (EGP).
     */
    case EGP = 'EGP';

    /**
     * Romanian Leu (RON).
     */
    case RON = 'RON';

    /**
     * Icelandic Krona (ISK).
     */
    case ISK = 'ISK';

    /**
     * Iranian Rial (IRR).
     */
    case IRR = 'IRR';

    /**
     * Costa Rican Colon (CRC).
     */
    case CRC = 'CRC';

    /**
     * Panamanian Balboa (PAB).
     */
    case PAB = 'PAB';

    /**
     * Paraguayan Guaraní (PYG).
     */
    case PYG = 'PYG';

    /**
     * Peruvian Sol (PEN).
     */
    case PEN = 'PEN';

    /**
     * Uruguayan Peso (UYU).
     */
    case UYU = 'UYU';

    /**
     * Australian Dollar (AUD).
     */
    case AUD = 'AUD';

    /**
     * Bolivian Boliviano (BOB).
     */
    case BOB = 'BOB';

    /**
     * Chinese Yuan (CNY).
     */
    case CNY = 'CNY';

    /**
     * Brazilian Real (BRL).
     */
    case BRL = 'BRL';

    /**
     * Danish Krone (DKK).
     */
    case DKK = 'DKK';

    /**
     * Canadian Dollar (CAD).
     */
    case CAD = 'CAD';

    /**
     * Japanese Yen (JPY).
     */
    case JPY = 'JPY';

    /**
     * Swiss Franc (CHF).
     */
    case CHF = 'CHF';

    /**
     * Norwegian Krone (NOK).
     */
    case NOK = 'NOK';

    /**
     * New Zealand Dollar (NZD).
     */
    case NZD = 'NZD';

    /**
     * Unspecified currencies.
     *
     * In strict terms, ISO 4217 defines XXX as "No currency".
     */
    case XXX = 'XXX';

    /**
     * Currency names.
     *
     * If a name is not defined, the standard ISO 4217 code will be returned.
     *
     * @var array<string, array<string, string>>
     */
    private const NAMES = [
        // Currency names in Spanish.
        'es' => [
            self::CLP->value => 'Peso chileno',
            self::CLF->value => 'Unidad de fomento de Chile',
            self::UTM->value => 'Unidad tributaria mensual de Chile',
            self::UTA->value => 'Unidad tributaria anual de Chile',
            self::USD->value => 'Dólar estadounidense',
            self::EUR->value => 'Euro',
            self::BTC->value => 'Bitcoin',
            self::ARS->value => 'Peso argentino',
            self::GBP->value => 'Libra esterlina',
            self::SEK->value => 'Corona sueca',
            self::HKD->value => 'Dólar de Hong Kong',
            self::ZAR->value => 'Rand sudafricano',
            self::COP->value => 'Peso colombiano',
            self::MXN->value => 'Peso mexicano',
            self::VES->value => 'Bolívar venezolano',
            self::SGD->value => 'Dólar de Singapur',
            self::INR->value => 'Rupia india',
            self::TWD->value => 'Nuevo dólar taiwanés',
            self::AED->value => 'Dirham de Emiratos Árabes Unidos',
            self::KRW->value => 'Won surcoreano',
            self::PLN->value => 'Zloty polaco',
            self::CZK->value => 'Corona checa',
            self::HUF->value => 'Forint húngaro',
            self::THB->value => 'Baht tailandés',
            self::TRY->value => 'Lira turca',
            self::MYR->value => 'Ringgit malayo',
            self::RUB->value => 'Rublo ruso',
            self::IDR->value => 'Rupia indonesia',
            self::UAH->value => 'Grivna ucraniana',
            self::ILS->value => 'Shekel israelí',
            self::PHP->value => 'Peso filipino',
            self::SAR->value => 'Riyal saudí',
            self::PKR->value => 'Rupia pakistaní',
            self::VND->value => 'Dong vietnamita',
            self::EGP->value => 'Libra egipcia',
            self::RON->value => 'Leu rumano',
            self::ISK->value => 'Corona islandesa',
            self::IRR->value => 'Rial iraní',
            self::CRC->value => 'Colón costarricense',
            self::PAB->value => 'Balboa panameño',
            self::PYG->value => 'Guaraní paraguayo',
            self::PEN->value => 'Sol peruano',
            self::UYU->value => 'Peso uruguayo',
            self::AUD->value => 'Dólar australiano',
            self::BOB->value => 'Boliviano',
            self::CNY->value => 'Yuan chino',
            self::BRL->value => 'Real brasileño',
            self::DKK->value => 'Corona danesa',
            self::CAD->value => 'Dólar canadiense',
            self::JPY->value => 'Yen japonés',
            self::CHF->value => 'Franco suizo',
            self::NOK->value => 'Corona noruega',
            self::NZD->value => 'Dólar neozelandés',
            self::XXX->value => 'Sin divisa',
        ],
        // Currency names in English.
        'en' => [
            self::CLP->value => 'Chilean Peso',
            self::CLF->value => 'Chilean Unit of Account (UF)',
            self::UTM->value => 'Monthly Tax Unit of Chile',
            self::UTA->value => 'Annual Tax Unit of Chile',
            self::USD->value => 'United States Dollar',
            self::EUR->value => 'Euro',
            self::BTC->value => 'Bitcoin',
            self::ARS->value => 'Argentine Peso',
            self::GBP->value => 'British Pound',
            self::SEK->value => 'Swedish Krona',
            self::HKD->value => 'Hong Kong Dollar',
            self::ZAR->value => 'South African Rand',
            self::COP->value => 'Colombian Peso',
            self::MXN->value => 'Mexican Peso',
            self::VES->value => 'Venezuelan Bolívar',
            self::SGD->value => 'Singapore Dollar',
            self::INR->value => 'Indian Rupee',
            self::TWD->value => 'New Taiwan Dollar',
            self::AED->value => 'United Arab Emirates Dirham',
            self::KRW->value => 'South Korean Won',
            self::PLN->value => 'Polish Zloty',
            self::CZK->value => 'Czech Koruna',
            self::HUF->value => 'Hungarian Forint',
            self::THB->value => 'Thai Baht',
            self::TRY->value => 'Turkish Lira',
            self::MYR->value => 'Malaysian Ringgit',
            self::RUB->value => 'Russian Ruble',
            self::IDR->value => 'Indonesian Rupiah',
            self::UAH->value => 'Ukrainian Hryvnia',
            self::ILS->value => 'Israeli Shekel',
            self::PHP->value => 'Philippine Peso',
            self::SAR->value => 'Saudi Riyal',
            self::PKR->value => 'Pakistani Rupee',
            self::VND->value => 'Vietnamese Dong',
            self::EGP->value => 'Egyptian Pound',
            self::RON->value => 'Romanian Leu',
            self::ISK->value => 'Icelandic Krona',
            self::IRR->value => 'Iranian Rial',
            self::CRC->value => 'Costa Rican Colón',
            self::PAB->value => 'Panamanian Balboa',
            self::PYG->value => 'Paraguayan Guarani',
            self::PEN->value => 'Peruvian Sol',
            self::UYU->value => 'Uruguayan Peso',
            self::AUD->value => 'Australian Dollar',
            self::BOB->value => 'Bolivian Boliviano',
            self::CNY->value => 'Chinese Yuan',
            self::BRL->value => 'Brazilian Real',
            self::DKK->value => 'Danish Krone',
            self::CAD->value => 'Canadian Dollar',
            self::JPY->value => 'Japanese Yen',
            self::CHF->value => 'Swiss Franc',
            self::NOK->value => 'Norwegian Krone',
            self::NZD->value => 'New Zealand Dollar',
            self::XXX->value => 'No Currency',
        ],
    ];

    /**
     * Currency symbols.
     *
     * @var array<string, string>
     */
    private const SYMBOLS = [
        self::CLP->value => '$',
        self::CLF->value => 'UF',
        self::UTM->value => 'UTM',
        self::UTA->value => 'UTA',
        self::USD->value => '$',
        self::EUR->value => '€',
        self::BTC->value => '₿',
        self::ARS->value => '$',
        self::GBP->value => '£',
        self::SEK->value => 'kr',
        self::HKD->value => 'HK$',
        self::ZAR->value => 'R',
        self::COP->value => '$',
        self::MXN->value => '$',
        self::VES->value => 'Bs.',
        self::SGD->value => 'S$',
        self::INR->value => '₹',
        self::TWD->value => 'NT$',
        self::AED->value => 'د.إ',
        self::KRW->value => '₩',
        self::PLN->value => 'zł',
        self::CZK->value => 'Kč',
        self::HUF->value => 'Ft',
        self::THB->value => '฿',
        self::TRY->value => '₺',
        self::MYR->value => 'RM',
        self::RUB->value => '₽',
        self::IDR->value => 'Rp',
        self::UAH->value => '₴',
        self::ILS->value => '₪',
        self::PHP->value => '₱',
        self::SAR->value => '﷼',
        self::PKR->value => '₨',
        self::VND->value => '₫',
        self::EGP->value => '£',
        self::RON->value => 'lei',
        self::ISK->value => 'kr',
        self::IRR->value => '﷼',
        self::CRC->value => '₡',
        self::PAB->value => 'B/.',
        self::PYG->value => '₲',
        self::PEN->value => 'S/',
        self::UYU->value => '$U',
        self::AUD->value => 'A$',
        self::BOB->value => 'Bs.',
        self::CNY->value => '¥',
        self::BRL->value => 'R$',
        self::DKK->value => 'kr',
        self::CAD->value => 'C$',
        self::JPY->value => '¥',
        self::CHF->value => 'CHF',
        self::NOK->value => 'kr',
        self::NZD->value => 'NZ$',
        self::XXX->value => '',
    ];

    /**
     * Number of decimals that each currency can have.
     *
     * If a decimal is not defined, "2" will be returned by default.
     *
     * @var array<string, int>
     */
    private const DECIMALS = [
        self::CLP->value => 0,
        self::UTM->value => 0,
        self::UTA->value => 0,
        self::BTC->value => 8,
        self::KRW->value => 0,
        self::VND->value => 0,
        self::ISK->value => 0,
        self::PYG->value => 0,
        self::JPY->value => 0,
    ];

    /**
     * Decimal separators of the currencies.
     *
     * If a decimal separator is not defined, "." will be returned by default.
     *
     * @var array <string, string>
     */
    private const DECIMAL_SEPARATORS = [
        self::CLP->value => ',',
        self::CLF->value => ',',
        self::UTM->value => ',',
        self::UTA->value => ',',
    ];

    /**
     * Thousands separators of the currencies.
     *
     * If a thousands separator is not defined, "," will be returned by default.
     *
     * @var array <string, string>
     */
    private const THOUSANDS_SEPARATORS = [
        self::CLP->value => '.',
        self::CLF->value => '.',
        self::UTM->value => '.',
        self::UTA->value => '.',
    ];

    /**
     * Templates for rendering an amount of the currencies.
     *
     * If a template is not defined, the standard template will be returned:
     *
     *   `{{symbol}} {{amount}}`
     *
     * @var array <string, string>
     */
    private const TEMPLATES = [
        self::CLF->value => '{{amount}} {{symbol}}',
        self::UTM->value => '{{amount}} {{symbol}}',
        self::UTA->value => '{{amount}} {{symbol}}',
        self::EUR->value => '{{amount}} {{symbol}}',
        self::JPY->value => '{{symbol}}{{amount}}',
        self::GBP->value => '{{symbol}}{{amount}}',
        self::CHF->value => '{{amount}} {{symbol}}',
        self::CNY->value => '{{symbol}}{{amount}}',
        self::SEK->value => '{{amount}} {{symbol}}',
        self::DKK->value => '{{amount}} {{symbol}}',
        self::PLN->value => '{{amount}} {{symbol}}',
        self::CZK->value => '{{amount}} {{symbol}}',
        self::HUF->value => '{{amount}} {{symbol}}',
        self::THB->value => '{{symbol}}{{amount}}',
        self::VND->value => '{{amount}}{{symbol}}',
        self::KRW->value => '{{symbol}}{{amount}}',
    ];

    /**
     * Returns the currency code in ISO 4217 standard.
     *
     * @return string
     */
    public function getCode(): string
    {
        return $this->name;
    }

    /**
     * Returns the currency name.
     *
     * @param string $language
     * @return string
     */
    public function getName(string $language = 'es'): string
    {
        $name = self::NAMES[$language][$this->value] ?? null;

        if ($name !== null) {
            return $name;
        }

        if ($language !== 'en') {
            return $this->getName('en');
        }

        return $this->getCode();
    }

    /**
     * Returns the currency symbol.
     *
     * @return string
     */
    public function getSymbol(): string
    {
        return self::SYMBOLS[$this->value];
    }

    /**
     * Returns the number of decimals of the currency.
     *
     * @return int
     */
    public function getDecimals(): int
    {
        return self::DECIMALS[$this->value] ?? 2;
    }

    /**
     * Returns the decimal separator that the currency uses.
     *
     * @return string
     */
    public function getDecimalSeparator(): string
    {
        return self::DECIMAL_SEPARATORS[$this->value] ?? '.';
    }

    /**
     * Returns the thousands separator that the currency uses.
     *
     * @return string
     */
    public function getThousandsSeparator(): string
    {
        return self::THOUSANDS_SEPARATORS[$this->value] ?? ',';
    }

    /**
     * Validates if an amount is correct for the currency according to its
     * decimals.
     *
     * @param int|float $amount
     * @return bool
     */
    public function isValidAmount(int|float $amount): bool
    {
        $decimals = $this->getDecimals();
        $factor = pow(10, $decimals);

        return (floor($amount * $factor) === $amount * $factor);
    }

    /**
     * Approximates the amount according to the decimals that the currency has.
     *
     * @param int|float $amount
     * @return int|float
     */
    public function round(int|float $amount): int|float
    {
        $decimals = $this->getDecimals();

        $roundedAmount = round((float) $amount, $decimals);

        return $decimals === 0 ? (int) $roundedAmount : $roundedAmount;
    }

    /**
     * Formats the amount of a currency as a string.
     *
     * Rounds the amount to the decimals of the currency and formats it using
     * the decimal separator and thousands separator of the currency.
     *
     * @param int|float $amount
     * @return string
     */
    public function format(int|float $amount): string
    {
        $roundedAmount = $this->round($amount);

        return number_format(
            (float) $roundedAmount,
            $this->getDecimals(),
            $this->getDecimalSeparator(),
            $this->getThousandsSeparator()
        );
    }

    /**
     * Renders the amount of the currency using its template.
     *
     * Returns a string with the approximate amount to the decimals of the
     * currency and the symbol in the format that the currency uses.
     *
     * @param int|float $amount
     * @return string
     */
    public function render(int|float $amount): string
    {
        $template = $this->getTemplate();

        $formatedAmount = $this->format($amount);

        return str_replace(
            ['{{symbol}}', '{{amount}}'],
            [$this->getSymbol(), $formatedAmount],
            $template
        );
    }

    /**
     * Returns the template that should be used to render an amount of the
     * currency.
     *
     * @return string
     */
    public function getTemplate(): string
    {
        return self::TEMPLATES[$this->value] ?? '{{symbol}} {{amount}}';
    }

    /**
     * Returns an array with the complete information of the currency.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'code' => $this->getCode(),
            'name' => $this->getName(),
            'symbol' => $this->getSymbol(),
            'decimals' => $this->getDecimals(),
            'decimal_separator' => $this->getDecimalSeparator(),
            'thousands_separator' => $this->getThousandsSeparator(),
            'template' => $this->getTemplate(),
        ];
    }
}
