<?php

declare(strict_types=1);

/**
 * Derafu: Backbone - The Architectural Spine for PHP Libraries.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\Backbone\DependencyInjection;

use Derafu\Backbone\Attribute\Component;
use Derafu\Backbone\Attribute\Handler;
use Derafu\Backbone\Attribute\Job;
use Derafu\Backbone\Attribute\Package;
use Derafu\Backbone\Attribute\Strategy;
use Derafu\Backbone\Attribute\Worker;
use Derafu\Backbone\Contract\ServiceInterface;
use Derafu\Backbone\Contract\ServiceMetadataInterface;
use ReflectionClass;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

/**
 * Compiler pass that processes services based on attributes.
 */
final class ServiceProcessingCompilerPass implements CompilerPassInterface
{
    /**
     * Attribute classes to process.
     *
     * @var array<class-string<ServiceMetadataInterface>>
     */
    private const ATTRIBUTE_CLASSES = [
        Package::class,
        Component::class,
        Worker::class,
        Job::class,
        Handler::class,
        Strategy::class,
    ];

    /**
     * Constructor of the compiler pass.
     *
     * @param string $servicesPrefix The prefix for the services in the container.
     */
    public function __construct(private readonly string $servicesPrefix = '')
    {
    }

    /**
     * {@inheritDoc}
     */
    public function process(ContainerBuilder $container): void
    {
        foreach ($container->getDefinitions() as $id => $definition) {
            // Skip synthetic and abstract services.
            if ($definition->isSynthetic() || $definition->isAbstract()) {
                continue;
            }

            // Process service definition.
            if ($this->processServiceDefinition($id, $definition, $container)) {
                // Assign services as lazy.
                $definition->setLazy(true);
            }
        }
    }

    /**
     * Processes a service based on its attributes.
     *
     * @param string $id The id of the service.
     * @param Definition $definition The definition of the service.
     * @param ContainerBuilder $container The container builder.
     * @return bool True if the service was processed, false otherwise.
     */
    private function processServiceDefinition(
        string $id,
        Definition $definition,
        ContainerBuilder $container
    ): bool {
        // Only process classes that exist and are services.
        $class = $definition->getClass() ?? $id;
        if (
            !class_exists($class) ||
            !is_subclass_of($class, ServiceInterface::class)
        ) {
            return false;
        }

        // Get the reflection of the class.
        $reflection = new ReflectionClass($class);

        // Process all supported attributes.
        foreach (self::ATTRIBUTE_CLASSES as $attributeClass) {
            $processed = $this->processAttribute(
                $reflection,
                $attributeClass,
                $id,
                $definition,
                $container
            );
            if ($processed) {
                return true;
            }
        }

        return false;
    }

    /**
     * Processes an attribute of the specified type.
     *
     * @param ReflectionClass $reflection The reflection of the class.
     * @param class-string $attributeClass The class of the attribute.
     * @param string $id The id of the service.
     * @param Definition $definition The definition of the service.
     * @param ContainerBuilder $container The container builder.
     * @return bool True if the attribute was processed, false otherwise.
     */
    private function processAttribute(
        ReflectionClass $reflection,
        string $attributeClass,
        string $id,
        Definition $definition,
        ContainerBuilder $container
    ): bool {
        // Get the attributes of the class.
        $attributes = $reflection->getAttributes($attributeClass);
        if (empty($attributes)) {
            return false;
        }

        // Find the attribute that implements the interface.
        $found = false;
        foreach ($attributes as $attribute) {
            // Get the attribute instance.
            $attribute = $attribute->newInstance();

            // Ensure attribute implements interface with required methods.
            if ($attribute instanceof $attributeClass) {
                $found = true;
                break;
            }
        }

        // If no attribute was found, return false.
        if (!$found) {
            return false;
        }

        // Create alias.
        $alias = $this->servicesPrefix . $attribute->id;
        $aliasDefinition = $container->setAlias($alias, $id);

        // Make Package aliases public and private otherwise.
        $aliasDefinition->setPublic($attribute instanceof Package);

        // Add tags.
        $tagAttributes = $attribute->toArray();
        $definition->addTag($attribute->getGroupId(), $tagAttributes);
        $definition->addTag($attribute->getCategoryId(), $tagAttributes);

        return true;
    }
}
