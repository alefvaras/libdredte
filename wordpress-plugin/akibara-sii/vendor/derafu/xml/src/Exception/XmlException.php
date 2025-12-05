<?php

declare(strict_types=1);

/**
 * Derafu: XML - Library for XML manipulation.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\Xml\Exception;

use Exception;
use LibXMLError;
use Throwable;

/**
 * Custom exception for XML errors.
 */
class XmlException extends Exception
{
    /**
     * Array with the errors.
     *
     * @var array
     */
    private array $errors;

    /**
     * Constructor of the exception.
     *
     * @param string $message The exception message.
     * @param array $errors The array with the errors.
     * @param int $code The exception code (optional).
     * @param Throwable|null $previous The previous exception (optional).
     */
    public function __construct(
        string $message,
        array $errors = [],
        int $code = 0,
        ?Throwable $previous = null
    ) {
        $message = trim(sprintf(
            '%s %s',
            $message,
            implode(' ', $this->libXmlErrorToString($errors))
        ));

        $this->errors = $errors;
        parent::__construct($message, $code, $previous);
    }

    /**
     * Gets the array with the errors.
     *
     * @return array The array with the errors.
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Processes an array of errors, probably of LibXMLError, and returns it as
     * an array of strings.
     *
     * @param array $errors
     * @return array
     */
    private function libXmlErrorToString(array $errors): array
    {
        return array_map(function ($error) {
            if ($error instanceof LibXMLError) {
                return sprintf(
                    'Error %s: %s in line %d, column %d (Code: %d).',
                    $error->level === LIBXML_ERR_WARNING ? 'Warning' :
                    ($error->level === LIBXML_ERR_ERROR ? 'Error' : 'Fatal'),
                    trim($error->message),
                    $error->line,
                    $error->column,
                    $error->code
                );
            }

            return $error;
        }, $errors);
    }
}
