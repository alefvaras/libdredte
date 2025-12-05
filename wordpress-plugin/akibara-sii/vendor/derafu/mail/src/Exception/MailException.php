<?php

declare(strict_types=1);

/**
 * Derafu: Mail - Elegant orchestration of email communications for PHP.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\Mail\Exception;

use Derafu\Translation\Exception\Core\TranslatableRuntimeException;

/**
 * Custom exception class for emails.
 */
class MailException extends TranslatableRuntimeException
{
}
