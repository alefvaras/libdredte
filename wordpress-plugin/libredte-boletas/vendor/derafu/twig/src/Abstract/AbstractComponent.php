<?php

declare(strict_types=1);

/**
 * Derafu: Twig - UI Component and Extension Library.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\Twig\Abstract;

use Derafu\Twig\Exception\TwigComponentException;
use ReflectionClass;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

/**
 * Abstract base class for Twig components.
 *
 * This class provides common functionality for all Twig components.
 */
abstract class AbstractComponent
{
    /**
     * Unique identifier for the component instance.
     *
     * @var string
     */
    private string $id;

    /**
     * Gets the component's unique identifier.
     *
     * Automatically generated if not set.
     *
     * @return string
     */
    public function getId(): string
    {
        if (!isset($this->id)) {
            $this->id = uniqid($this->getComponentName() . '--');
        }

        return $this->id;
    }

    /**
     * Sets the component's unique identifier.
     *
     * @param string $id The component's unique identifier.
     * @return static For method chaining.
     */
    public function setId(string $id): static
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Gets the component name.
     *
     * Extracts the name from the AsTwigComponent attribute,
     *
     * Falls back to the lowercase class name if no attribute is found.
     *
     * @return string
     */
    protected function getComponentName(): string
    {
        $reflection = new ReflectionClass($this);
        $attributes = $reflection->getAttributes();

        foreach ($attributes as $attribute) {
            if ($attribute->getName() === AsTwigComponent::class) {
                $args = $attribute->getArguments();
                return $args[0];
            }
        }

        return strtolower(basename(str_replace('\\', '__', get_class($this))));
    }

    /**
     * Throws a component exception with the component name and message.
     *
     * @param string $message The error message.
     * @return void
     */
    protected function error(string $message): void
    {
        throw new TwigComponentException(sprintf(
            'Component %s: %s',
            $this->getComponentName(),
            $message
        ));
    }

    /**
     * Gets the default icon for a given type.
     *
     * @param string|null $type The icon type.
     * @return string The default icon.
     */
    protected function getDefaultIcon(?string $type = null): string
    {
        return match($type) {
            'primary' => 'fa-solid fa-star text-primary fa-fw',
            'secondary' => 'fa-solid fa-info text-secondary fa-fw',
            'success' => 'fa-solid fa-check-circle text-success fa-fw',
            'danger' => 'fa-solid fa-triangle-exclamation text-danger fa-fw',
            'warning' => 'fa-solid fa-exclamation-circle text-warning fa-fw',
            'info' => 'fa-solid fa-info-circle text-info fa-fw',
            'light' => 'fa-solid fa-sun text-dark fa-fw',
            'dark' => 'fa-solid fa-moon text-dark fa-fw',
            default => 'fa-solid fa-exclamation fa-fw',
        };
    }
}
