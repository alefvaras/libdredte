<?php

declare(strict_types=1);

/**
 * Derafu: Container - Flexible Data Containers for PHP.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\Container;

use ArrayAccess;
use ArrayObject;
use Derafu\Container\Abstract\AbstractContainer;
use Derafu\Container\Contract\StoreInterface;
use InvalidArgumentException;
use Opis\JsonSchema\Errors\ErrorFormatter;
use Opis\JsonSchema\Helper;
use Opis\JsonSchema\Validator;
use stdClass;

/**
 * Clase para contenedor de datos estructurados con JSON Schema.
 */
class Store extends AbstractContainer implements StoreInterface
{
    /**
     * Configuración del schema de datos.
     *
     * @var stdClass
     */
    protected ?stdClass $schema = null;

    /**
     * Instancia que representa el formateador de los errores del validador.
     *
     * @var ErrorFormatter
     */
    private ErrorFormatter $formatter;

    /**
     * Constructor del contenedor.
     *
     * @param array|ArrayAccess|ArrayObject $data Datos iniciales.
     * @param array $schema Schema inicial.
     */
    public function __construct(
        array|ArrayAccess|ArrayObject $data = [],
        array $schema = []
    ) {
        $this->formatter = new ErrorFormatter();
        $this->setSchema($schema);
        if (!is_array($data)) {
            $data = $this->createFrom($data)->toArray();
        }
        $data = $this->resolve($data, $this->schema);
        $this->data = $this->createFrom($data);
    }

    /**
     * {@inheritDoc}
     */
    public function setSchema(array $schema): static
    {
        $schema = array_merge([
            '$schema' => 'https://json-schema.org/draft/2020-12/schema',
            'type' => 'object',
        ], $schema);

        $this->schema = Helper::toJSON($schema);

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getSchema(): array
    {
        return json_decode(json_encode($this->schema), true);
    }

    /**
     * {@inheritDoc}
     */
    public function validate(): void
    {
        $this->resolve($this->toArray(), $this->schema);
    }

    /**
     * Valida y resuelve datos usando un schema JSON.
     *
     * @param array $data Datos a validar.
     * @param stdClass $schema Schema JSON a usar.
     * @return array Datos validados y con valores por defecto aplicados.
     */
    private function resolve(array $data, stdClass $schema): array
    {
        if (!isset($schema->properties)) {
            return $data;
        }

        $data = $this->applyDefaults($data, $schema);
        $json = json_decode(json_encode($data));

        $validator = new Validator();
        $result = $validator->validate($json, $schema);

        if ($result->hasError()) {
            $errors = [];
            foreach ($this->formatter->format($result->error()) as $section => $messages) {
                foreach ($messages as $message) {
                    $errors[] = $message . ' in ' . $section . '.';
                }
            }
            throw new InvalidArgumentException(sprintf(
                'Error al validar el esquema JSON de los datos. %s',
                implode(' ', $errors)
            ));
        }

        return $data;
    }

    /**
     * Aplica valores por defecto recursivamente.
     *
     * @param array $data Datos a procesar.
     * @param stdClass $schema Schema con valores por defecto.
     * @return array Datos con valores por defecto aplicados.
     */
    private function applyDefaults(array $data, stdClass $schema): array
    {
        foreach ($schema->properties as $key => $property) {
            // Aplicar valor por defecto si la propiedad no existe.
            if (!isset($data[$key]) && isset($property->default)) {
                $data[$key] = $property->default;
            }

            // Recursión para objetos anidados.
            if (
                isset($data[$key])
                && isset($property->type)
                && $property->type === 'object'
                && isset($property->properties)
            ) {
                $data[$key] = $this->applyDefaults($data[$key], $property);
            }
        }

        return $data;
    }
}
