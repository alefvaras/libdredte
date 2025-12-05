<?php

declare(strict_types=1);

/**
 * Derafu: XML - Library for XML manipulation.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\Xml\Contract;

/**
 * Interface for the service that works with XML documents.
 */
interface XmlServiceInterface extends XmlEncoderInterface, XmlDecoderInterface, XmlValidatorInterface
{
}
