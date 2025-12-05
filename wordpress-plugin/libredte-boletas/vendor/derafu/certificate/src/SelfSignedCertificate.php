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

use Derafu\Certificate\Exception\CertificateException;
use OpenSSLAsymmetricKey;

/**
 * Class that generates self-signed certificates and returns them as a string of
 * data, an array or an instance of Certificate.
 */
final class SelfSignedCertificate
{
    /**
     * Subject data of the certificate.
     *
     * @var array
     */
    private array $subject;

    /**
     * Issuer data of the certificate.
     *
     * @var array
     */
    private array $issuer;

    /**
     * Validity of the certificate in UNIX timestamp format.
     *
     * @var array
     */
    private array $validity;

    /**
     * Password to protect the private key in the certificate.
     *
     * @var string
     */
    private string $password;

    /**
     * Constructor that assigns the default values.
     */
    public function __construct()
    {
        $this->setSubject();
        $this->setIssuer();
        $this->setValidity();
        $this->setPassword();
    }

    /**
     * Configures the subject data of the certificate.
     *
     * @param string $C Country of the subject.
     * @param string $ST State or province of the subject.
     * @param string $L City of the subject.
     * @param string $O Organization of the subject.
     * @param string $OU Organizational unit of the subject.
     * @param string $CN Common name of the subject.
     * @param string $emailAddress Email address of the subject.
     * @param string $serialNumber Serial number of the subject.
     * @param string $title Title of the subject.
     * @return static
     */
    public function setSubject(
        string $C = 'CL',
        string $ST = 'Colchagua',
        string $L = 'Santa Cruz',
        string $O = 'Intergalactic Robots Organization',
        string $OU = 'Technology',
        string $CN = 'Daniel Bot',
        string $emailAddress = 'daniel.bot@example.com',
        string $serialNumber = '11222333-9',
        string $title = 'Bot',
    ): static {
        if (empty($CN) || empty($emailAddress) || empty($serialNumber)) {
            throw new CertificateException(
                'The CN, emailAddress and serialNumber are required.'
            );
        }

        $this->subject = [
            'C' => $C,
            'ST' => $ST,
            'L' => $L,
            'O' => $O,
            'OU' => $OU,
            'CN' => $CN,
            'emailAddress' => $emailAddress,
            'serialNumber' => strtoupper($serialNumber),
            'title' => $title,
        ];

        return $this;
    }

    /**
     * Configures the issuer data of the certificate.
     *
     * @param string $C Country of the issuer.
     * @param string $ST State or province of the issuer.
     * @param string $L City of the issuer.
     * @param string $O Organization of the issuer.
     * @param string $OU Organizational unit of the issuer.
     * @param string $CN Common name of the issuer.
     * @param string $emailAddress Email address of the issuer.
     * @param string $serialNumber Serial number of the issuer.
     * @return static
     */
    public function setIssuer(
        string $C = 'CL',
        string $ST = 'Colchagua',
        string $L = 'Santa Cruz',
        string $O = 'Derafu',
        string $OU = 'Technology',
        string $CN = 'Derafu Test Certificate Authority',
        string $emailAddress = 'fakes-certificates@derafu.org',
        string $serialNumber = '76192083-9',
    ): static {
        $this->issuer = [
            'C' => $C,
            'ST' => $ST,
            'L' => $L,
            'O' => $O,
            'OU' => $OU,
            'CN' => $CN,
            'emailAddress' => $emailAddress,
            'serialNumber' => strtoupper($serialNumber),
        ];

        return $this;
    }

    /**
     * Configures the validity of the certificate.
     *
     * @param int $days Days that the certificate will be valid from the current
     * date. If not provided, it will have a validity of 365 days.
     * @return static
     */
    public function setValidity(int $days = 365): static
    {
        $this->validity = [
            'days' => $days,
        ];

        return $this;
    }

    /**
     * Configures the password to protect the private key.
     *
     * @param string $password Password to protect the private key.
     * @return void
     */
    public function setPassword(string $password = 'i_love_derafu')
    {
        $this->password = $password;
    }

    /**
     * Generates a digital certificate in PKCS#12 format and returns it as a
     * string.
     *
     * @return string Digital certificate in PKCS#12 format.
     */
    public function toPkcs12(): string
    {
        // Days of validity of the certificate (issuer and subject).
        $days = $this->validity['days'];

        // Create private key and CSR for the issuer.
        $issuerPrivateKey = openssl_pkey_new();
        if (!$issuerPrivateKey instanceof OpenSSLAsymmetricKey) {
            throw new CertificateException(
                'It was not possible to generate the private key of the issuer of the certificate.'
            );
        }
        $issuerCsr = openssl_csr_new($this->issuer, $issuerPrivateKey);

        // Create self-signed certificate for the issuer (CA).
        $issuerCert = openssl_csr_sign(
            $issuerCsr,         // CSR of the issuer.
            null,               // Issuer certificate (null indicates that it is self-signed).
            $issuerPrivateKey,  // Private key of the issuer.
            $days,              // Validity days (same as subject).
            [],                 // Additional options.
            666                 // Serial number of the certificate.
        );

        // Validate that the issuer certificate (CA) was generated.
        if ($issuerCert === false) {
            throw new CertificateException(
                'It was not possible to generate the issuer certificate (CA).'
            );
        }

        // Create private key and CSR for the subject.
        $subjectPrivateKey = openssl_pkey_new();
        if (!$subjectPrivateKey instanceof OpenSSLAsymmetricKey) {
            throw new CertificateException(
                'It was not possible to generate the private key of the certificate.'
            );
        }
        $subjectCsr = openssl_csr_new($this->subject, $subjectPrivateKey);

        // Use the issuer certificate to sign the subject CSR.
        $subjectCert = openssl_csr_sign(
            $subjectCsr,        // The subject CSR.
            $issuerCert,        // Issuer certificate.
            $issuerPrivateKey,  // Private key of the issuer.
            $days,              // Validity days.
            [],                 // Additional options.
            69                  // Serial number of the certificate.
        );

        // Validate that the user certificate was generated.
        if ($subjectCert === false) {
            throw new CertificateException(
                'It was not possible to generate the user certificate.'
            );
        }

        // Export the final certificate in PKCS#12 format.
        openssl_pkcs12_export(
            $subjectCert,
            $data,
            $subjectPrivateKey,
            $this->password
        );

        // Return the digital certificate data.
        return $data;
    }

    /**
     * Generates a digital certificate in PKCS#12 format and returns it as an
     * array.
     *
     * @return array Digital certificate in PKCS#12 format.
     */
    public function toArray(): array
    {
        $data = $this->toPkcs12();
        $array = [];
        openssl_pkcs12_read($data, $array, $this->password);

        return $array;
    }
}
