<?php

declare(strict_types=1);

/**
 * Derafu: Signature - Library for digital signatures.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\Signature\Exception;

use Derafu\Certificate\Exception\CertificateException;

/**
 * Exception class for the process of signing or validating digital signatures.
 */
class SignatureException extends CertificateException
{
}
