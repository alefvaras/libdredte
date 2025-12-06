<?php

declare(strict_types=1);

/**
 * Derafu: Certificate - Library for digital certificates.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\Certificate\Contract;

use JsonSerializable;

/**
 * Interface for the certificate entity.
 */
interface CertificateInterface extends JsonSerializable
{
    /**
     * Returns the public and private keys.
     *
     * @param boolean $clean If the certificate content is cleaned.
     * @return array Array with the indices: cert and pkey.
     */
    public function getKeys(bool $clean = false): array;

    /**
     * Returns the public key (certificate).
     *
     * @param bool $clean If the certificate content is cleaned.
     * @return string Certificate content, public key of the certificate, in
     * base64.
     */
    public function getPublicKey(bool $clean = false): string;

    /**
     * Returns the public key (certificate).
     *
     * @param bool $clean If the certificate content is cleaned.
     * @return string Certificate content, public key of the certificate, in
     * base64.
     */
    public function getCertificate(bool $clean = false): string;

    /**
     * Returns the private key.
     *
     * @param bool $clean If the private key content is cleaned.
     * @return string Private key content, in base64.
     */
    public function getPrivateKey(bool $clean = false): string;

    /**
     * Returns the private key details.
     *
     * @return array
     */
    public function getPrivateKeyDetails(): array;

    /**
     * Returns the certificate data as an array.
     *
     * Alias of getCertX509().
     *
     * @return array Array with all the certificate data.
     */
    public function getData(): array;

    /**
     * Returns the certificate data as a string in PKCS #12 format.
     *
     * @param string $password
     * @return string
     */
    public function getPkcs12(string $password): string;

    /**
     * Returns the ID associated with the certificate.
     *
     * The ID is the RUN that must be in an extension, this is the standard.
     * Also, it could be in the `serialNumber` field, some providers place it in
     * this field, it is also easier for tests.
     *
     * @param bool $forceUpper If uppercase is forced.
     * @return string ID associated with the certificate in the format:
     * 11222333-4.
     */
    public function getId(bool $forceUpper = true): string;

    /**
     * Returns the CN of the subject.
     *
     * @return string CN of the subject.
     */
    public function getName(): string;

    /**
     * Returns the email address of the subject.
     *
     * @return string Email address of the subject.
     */
    public function getEmail(): string;

    /**
     * Returns from when the signature is valid.
     *
     * @return string Date and time from when the signature is valid.
     */
    public function getFrom(): string;

    /**
     * Returns until when the signature is valid.
     *
     * @return string Date and time until when the signature is valid.
     */
    public function getTo(): string;

    /**
     * Returns the total days that the signature is valid.
     *
     * @return int Total days that the signature is valid.
     */
    public function getTotalDays(): int;

    /**
     * Returns the days that are left to expire the signature.
     *
     * @param string|null $from Date from which the calculation is made.
     * @return int Days that are left to expire the signature.
     */
    public function getExpirationDays(?string $from = null): int;

    /**
     * Indicates if the signature is active or expired.
     *
     * NOTE: This method will also validate that the signature is not active in
     * the future. That is, the date from when it is active must be in the past.
     *
     * @param string|null $when Date from which the validation is made.
     * @return bool `true` if the signature is active, `false` if it is expired.
     */
    public function isActive(?string $when = null): bool;

    /**
     * Returns the name of the issuer of the signature.
     *
     * @return string CN of the issuer.
     */
    public function getIssuer(): string;

    /**
     * Returns the modulus of the private key.
     *
     * @return string Modulus in base64.
     */
    public function getModulus(int $wordwrap = 64): string;

    /**
     * Returns the public exponent of the private key.
     *
     * @return string Public exponent in base64.
     */
    public function getExponent(int $wordwrap = 64): string;

    /**
     * Returns the certificate data as an array.
     *
     * @return array Array with all the certificate data.
     */
    public function toArray(): array;
}
