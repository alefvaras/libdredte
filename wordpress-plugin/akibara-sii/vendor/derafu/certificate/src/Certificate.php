<?php

declare(strict_types=1);

/**
 * Derafu: Certificate - Library for digital certificates.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\Certificate;

use DateTime;
use Derafu\Certificate\Contract\CertificateInterface;
use Derafu\Certificate\Exception\CertificateException;
use phpseclib3\File\X509;

/**
 * Class that represents a digital certificate.
 */
final class Certificate implements CertificateInterface
{
    /**
     * Public key (certificate).
     *
     * @var string
     */
    private string $publicKey;

    /**
     * Private key.
     *
     * @var string
     */
    private string $privateKey;

    /**
     * Private key details.
     *
     * @var array
     */
    private array $privateKeyDetails;

    /**
     * Parsed X509 certificate data.
     *
     * @var array
     */
    private array $data;

    /**
     * Constructor of the digital certificate.
     *
     * @param string $publicKey Public key (certificate).
     * @param string $privateKey Private key.
     */
    public function __construct(string $publicKey, string $privateKey)
    {
        $this->publicKey = AsymmetricKeyHelper::normalizePublicKey($publicKey);
        $this->privateKey = AsymmetricKeyHelper::normalizePrivateKey($privateKey);
    }

    /**
     * {@inheritDoc}
     */
    public function getKeys(bool $clean = false): array
    {
        return [
            'cert' => $this->getPublicKey($clean),
            'pkey' => $this->getPrivateKey($clean),
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function getPublicKey(bool $clean = false): string
    {
        if ($clean) {
            return trim(str_replace(
                ['-----BEGIN CERTIFICATE-----', '-----END CERTIFICATE-----'],
                '',
                $this->publicKey
            ));
        }

        return $this->publicKey;
    }

    /**
     * {@inheritDoc}
     */
    public function getCertificate(bool $clean = false): string
    {
        return $this->getPublicKey($clean);
    }

    /**
     * {@inheritDoc}
     */
    public function getPrivateKey(bool $clean = false): string
    {
        if ($clean) {
            return trim(str_replace(
                ['-----BEGIN PRIVATE KEY-----', '-----END PRIVATE KEY-----'],
                '',
                $this->privateKey
            ));
        }

        return $this->privateKey;
    }

    /**
     * {@inheritDoc}
     */
    public function getPrivateKeyDetails(): array
    {
        if (!isset($this->privateKeyDetails)) {
            $this->privateKeyDetails = openssl_pkey_get_details(
                openssl_pkey_get_private($this->privateKey)
            );
        }

        return $this->privateKeyDetails;
    }

    /**
     * {@inheritDoc}
     */
    public function getData(): array
    {
        if (!isset($this->data)) {
            $this->data = openssl_x509_parse($this->publicKey);
        }

        return $this->data;
    }

    /**
     * {@inheritDoc}
     */
    public function getPkcs12(string $password): string
    {
        // Export the final certificate in PKCS#12 format.
        openssl_pkcs12_export(
            $this->getPublicKey(),
            $data,
            $this->getPrivateKey(),
            $password
        );

        // Return the digital certificate data.
        return $data;
    }

    /**
     * {@inheritDoc}
     */
    public function getId(bool $forceUpper = true): string
    {
        // Verify the serialNumber in the subject of the certificate.
        $serialNumber = $this->getData()['subject']['serialNumber'] ?? null;
        if ($serialNumber !== null) {
            $serialNumber = ltrim(trim($serialNumber), '0');
            return $forceUpper ? strtoupper($serialNumber) : $serialNumber;
        }

        // Get the extensions of the certificate.
        $x509 = new X509();
        $cert = $x509->loadX509($this->publicKey);
        if (isset($cert['tbsCertificate']['extensions'])) {
            foreach ($cert['tbsCertificate']['extensions'] as $extension) {
                if (
                    $extension['extnId'] === 'id-ce-subjectAltName'
                    && isset($extension['extnValue'][0]['otherName']['value']['ia5String'])
                ) {
                    $id = ltrim(
                        trim($extension['extnValue'][0]['otherName']['value']['ia5String']),
                        '0'
                    );
                    return $forceUpper ? strtoupper($id) : $id;
                }
            }
        }

        // The ID was not found, throw an exception.
        throw new CertificateException(
            'Cannot get the ID of the digital certificate (electronic signature). It is recommended to verify the format and password of the certificate.'
        );
    }

    /**
     * {@inheritDoc}
     */
    public function getName(): string
    {
        $name = $this->getData()['subject']['CN'] ?? null;
        if ($name === null) {
            throw new CertificateException(
                'Cannot get the name of the digital certificate (electronic signature).'
            );
        }

        return $name;
    }

    /**
     * {@inheritDoc}
     */
    public function getEmail(): string
    {
        $email = $this->getData()['subject']['emailAddress'] ?? null;
        if ($email === null) {
            throw new CertificateException(
                'Cannot get the email of the digital certificate (electronic signature).'
            );
        }

        return $email;
    }

    /**
     * {@inheritDoc}
     */
    public function getFrom(): string
    {
        return date('Y-m-d\TH:i:s', $this->getData()['validFrom_time_t']);
    }

    /**
     * {@inheritDoc}
     */
    public function getTo(): string
    {
        return date('Y-m-d\TH:i:s', $this->getData()['validTo_time_t']);
    }

    /**
     * {@inheritDoc}
     */
    public function getTotalDays(): int
    {
        $start = new DateTime($this->getFrom());
        $end = new DateTime($this->getTo());
        $diff = $start->diff($end);
        return (int) $diff->format('%a');
    }

    /**
     * {@inheritDoc}
     */
    public function getExpirationDays(?string $from = null): int
    {
        if ($from === null) {
            $from = date('Y-m-d\TH:i:s');
        }
        $start = new DateTime($from);
        $end = new DateTime($this->getTo());
        $diff = $start->diff($end);
        return (int) $diff->format('%a');
    }

    /**
     * {@inheritDoc}
     */
    public function isActive(?string $when = null): bool
    {
        if ($when === null) {
            $when = date('Y-m-d');
        }

        if (!isset($when[10])) {
            $when .= 'T23:59:59';
        }

        return $when >= $this->getFrom() && $when <= $this->getTo();
    }

    /**
     * {@inheritDoc}
     */
    public function getIssuer(): string
    {
        return $this->getData()['issuer']['CN'];
    }

    /**
     * {@inheritDoc}
     */
    public function getModulus(int $wordwrap = 64): string
    {
        $modulus = $this->getPrivateKeyDetails()['rsa']['n'] ?? null;

        if ($modulus === null) {
            throw new CertificateException(
                'Cannot get the modulus of the private key.'
            );
        }

        return wordwrap(base64_encode($modulus), $wordwrap, "\n", true);
    }

    /**
     * {@inheritDoc}
     */
    public function getExponent(int $wordwrap = 64): string
    {
        $exponent = $this->getPrivateKeyDetails()['rsa']['e'] ?? null;

        if ($exponent === null) {
            throw new CertificateException(
                'Cannot get the exponent of the private key.'
            );
        }

        return wordwrap(base64_encode($exponent), $wordwrap, "\n", true);
    }

    /**
     * {@inheritDoc}
     */
    public function toArray(): array
    {
        return [
            'id' => $this->getId(),
            'name' => $this->getName(),
            'email' => $this->getEmail(),
            'from' => $this->getFrom(),
            'to' => $this->getTo(),
            'totalDays' => $this->getTotalDays(),
            'expirationDays' => $this->getExpirationDays(),
            'isActive' => $this->isActive(),
            'issuer' => $this->getIssuer(),
            'modulus' => $this->getModulus(),
            'exponent' => $this->getExponent(),
            'publicKey' => $this->getPublicKey(),
            'privateKey' => $this->getPrivateKey(),
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
