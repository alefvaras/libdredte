<?php

declare(strict_types=1);

/**
 * Derafu: Backbone - The Architectural Spine for PHP Libraries.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\Backbone\Abstract;

use ArrayObject;
use Derafu\Backbone\Contract\ServiceInterface;
use Derafu\Backbone\Contract\ServiceMetadataInterface;
use Derafu\Config\Contract\ConfigurableInterface;
use Derafu\Config\Contract\OptionsInterface;
use Derafu\Config\Options;
use Derafu\Config\Trait\OptionsAwareTrait;
use Derafu\Container\Contract\VaultInterface;
use LogicException;
use ReflectionClass;

/**
 * Base class for the services of the application.
 */
abstract class AbstractService implements ServiceInterface
{
    use OptionsAwareTrait;

    /**
     * Reflection instance of the service.
     *
     * @var ReflectionClass
     */
    protected ReflectionClass $reflectionInstance;

    /**
     * The service metadata.
     *
     * @var ServiceMetadataInterface
     */
    protected ServiceMetadataInterface $serviceMetadata;

    /**
     * {@inheritDoc}
     */
    public function __toString(): string
    {
        return rtrim(sprintf(
            '%s %s (%s) %s',
            ucfirst($this->getServiceMetadata()->getType()->value),
            $this->getName(),
            $this->getId(),
            $this->getServiceMetadata()->getDescription() ?? ''
        ));
    }

    /**
     * {@inheritDoc}
     */
    public function getId(): int|string
    {
        return $this->getServiceMetadata()->getId();
    }

    /**
     * {@inheritDoc}
     */
    public function getName(): string
    {
        return $this->getServiceMetadata()->getName();
    }

    /**
     * Resolves the service options from the options schema.
     *
     * @param array|ArrayObject|VaultInterface $options
     * @return OptionsInterface
     */
    public function resolveOptions(
        array|ArrayObject|VaultInterface $options = []
    ): OptionsInterface {
        // If the options passed are a vault, extract it as an array.
        if ($options instanceof VaultInterface) {
            $options = $options->all();
        }
        // If the options passed are an array object, extract it as an array.
        elseif ($options instanceof ArrayObject) {
            $options = $options->getArrayCopy();
        }

        // If the class implements ConfigurableInterface, search if it has
        // options defined in the configuration to include when resolving the
        // options by merging between the configuration options and the ones
        // passed.
        if ($this instanceof ConfigurableInterface) {
            $optionsFromConfiguration = $this->getConfiguration()->get('options');
            if ($optionsFromConfiguration) {
                $options = array_replace_recursive(
                    $optionsFromConfiguration,
                    $options
                );
            }
        }

        // Create a new container with the resolved options and validate against
        // the options schema (if defined).
        return new Options($options, $this->getOptionsSchema());
    }

    /**
     * Gets the reflection class of the service.
     *
     * @return ReflectionClass
     */
    protected function getReflectionInstance(): ReflectionClass
    {
        if (!isset($this->reflectionInstance)) {
            $this->reflectionInstance = new ReflectionClass($this);
        }

        return $this->reflectionInstance;
    }

    /**
     * Gets the service metadata.
     *
     * @return ServiceMetadataInterface
     */
    protected function getServiceMetadata(): ServiceMetadataInterface
    {
        if (!isset($this->serviceMetadata)) {
            $attributes = $this->getReflectionInstance()->getAttributes();

            foreach ($attributes as $attribute) {
                $instance = $attribute->newInstance();
                if ($instance instanceof ServiceMetadataInterface) {
                    $this->serviceMetadata = $instance;
                    return $this->serviceMetadata;
                }
            }

            throw new LogicException(sprintf(
                'The metadata attribute of the service %s is not defined.',
                $this->getReflectionInstance()->getName()
            ));
        }

        return $this->serviceMetadata;
    }
}
