<?php

declare(strict_types=1);

/**
 * Derafu: Certificate - Library for digital certificates.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\Certificate\Exception;

use Exception;
use Throwable;

/**
 * Custom exception class for electronic signature.
 */
class CertificateException extends Exception
{
    /**
     * Errors specific to electronic signature.
     *
     * @var array
     */
    private array $errors;

    /**
     * List of errors that OpenSSL could deliver translated to a human
     * understandable message in English.
     *
     * NOTE: The translations end without a period on purpose because the error
     * code will be concatenated between parentheses at the end and then the
     * period will be added at the end of the error.
     *
     * @var array
     */
    private $defaultOpensslTranslations = [
        '0308010C' => 'Unsupported encryption algorithm or method',
        '11800071' => 'MAC verification failed in PKCS12, certificate or password is incorrect',
        '0906D06C' => 'Failed to load X.509 certificate',
        '0B080074' => 'Invalid PEM format',
        '0A000086' => 'Key length not allowed',
        '06065064' => 'Private key error: incorrect password',
        '14094418' => 'SSL layer error: invalid certificate or CA not known',
        '14090086' => 'SSL configuration error: certificate or key problem',
        '0907B068' => 'Error in reading a certificate file',
        '1403100E' => 'SSL error: incompatible protocol',
    ];

    /**
     * Constructor of the exception.
     *
     * @param string $message Exception message.
     * @param array $errors Array with errors with the details.
     * @param int $code Exception code (optional).
     * @param Throwable|null $previous Previous exception (optional).
     */
    public function __construct(
        string $message,
        array $errors = [],
        int $code = 0,
        ?Throwable $previous = null
    ) {
        if (empty($errors)) {
            while ($error = openssl_error_string()) {
                $errors[] = $error;
            }
        }
        $errors = $this->translateOpensslErrors($errors);

        $message = trim(sprintf(
            '%s %s',
            $message,
            implode(' ', $errors)
        ));

        $this->errors = $errors;
        parent::__construct($message, $code, $previous);
    }

    /**
     * Gets the errors associated with the exception.
     *
     * @return array Array of errors.
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Translates the OpenSSL errors to simpler messages for humans.
     *
     * @param array $errors Array with the original OpenSSL errors.
     * @return array Array with the translated errors.
     */
    private function translateOpensslErrors(array $errors): array
    {
        // Define translation rules.
        $translations = $this->defaultOpensslTranslations;

        // Translate the errors.
        foreach ($errors as &$error) {
            foreach ($translations as $code => $trans) {
                if (str_contains($error, 'error:' . $code)) {
                    $error = sprintf('%s (Error #%s).', $trans, $code);
                }
            }
        }

        // Return the translated errors.
        return $errors;
    }
}
