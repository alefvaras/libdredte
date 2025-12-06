<?php

declare(strict_types=1);

/**
 * Derafu: Backbone - The Architectural Spine for PHP Libraries.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\Backbone\Attribute;

use Attribute;

/**
 * Attribute for the api resources of the application.
 */
#[Attribute(Attribute::TARGET_METHOD)]
final class ApiResource
{
    /**
     * Constructor.
     *
     * @param array $parametersExample The parameters example of the API resource.
     * @param array $optionsExample The options example of the API resource.
     * @param array $responses The responses of the API resource.
     */
    public function __construct(
        public readonly array $parametersExample = [],
        public readonly array $optionsExample = [],
        public readonly array $responses = [],
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function toArray(): array
    {
        return [
            'parametersExample' => $this->parametersExample,
            'optionsExample' => $this->optionsExample,
            'responses' => $this->responses,
        ];
    }
}
