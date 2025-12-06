<?php

declare(strict_types=1);

/**
 * Derafu: Twig - UI Component and Extension Library.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\Twig\Service;

use Derafu\Twig\Cache\CacheItemPool;
use Derafu\Twig\Contract\ComponentProviderInterface;
use Derafu\Twig\Contract\ComponentRegistrarInterface;
use Psr\Cache\CacheItemPoolInterface;
use ReflectionClass;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;
use Symfony\UX\TwigComponent\Attribute\PostMount;
use Symfony\UX\TwigComponent\Attribute\PreMount;
use Symfony\UX\TwigComponent\ComponentFactory;
use Symfony\UX\TwigComponent\ComponentProperties;
use Symfony\UX\TwigComponent\ComponentRenderer;
use Symfony\UX\TwigComponent\ComponentStack;
use Symfony\UX\TwigComponent\ComponentTemplateFinder;
use Symfony\UX\TwigComponent\Twig\ComponentExtension;
use Symfony\UX\TwigComponent\Twig\ComponentLexer;
use Symfony\UX\TwigComponent\Twig\ComponentRuntime;
use Twig\Environment;
use Twig\RuntimeLoader\RuntimeLoaderInterface;

/**
 * ComponentRegistrar is responsible for registering components with Twig.
 * It creates a service locator, a component factory, and a component renderer.
 * It also registers the Twig extension and the runtime loader.
 */
class ComponentRegistrar implements ComponentRegistrarInterface
{
    /**
     * Constructor for ComponentRegistrar.
     *
     * @param CacheItemPoolInterface|null $cache The cache item pool interface.
     * @param string $cacheKey The cache key for the component configuration.
     */
    public function __construct(
        private ?CacheItemPoolInterface $cache = null,
        private string $cacheKey = 'derafu_twig_components_config'
    ) {
        $this->cache = $cache ?? new CacheItemPool();
    }

    /**
     * Register components with Twig.
     *
     * @param Environment $twig The Twig environment.
     * @param ComponentProviderInterface|array $componentsProvider The component
     * provider or an array of components.
     * @return Environment The Twig environment.
     */
    public function register(
        Environment $twig,
        ComponentProviderInterface|array $componentsProvider
    ): Environment {
        $basicServices = $this->createBasicServices($twig);

        $components = is_array($componentsProvider)
            ? $componentsProvider
            : $componentsProvider->getComponents();

        $componentServices = $this->createComponentServices(
            $twig,
            $components,
            $basicServices
        );

        return $this->registerTwigExtensions($twig, $componentServices);
    }

    /**
     * Create basic services for the component registrar.
     *
     * @param Environment $twig The Twig environment.
     * @return array The basic services.
     */
    private function createBasicServices(Environment $twig): array
    {
        return [
            'propertyAccessor' => PropertyAccess::createPropertyAccessor(),
            'eventDispatcher' => new EventDispatcher(),
            'templateFinder' => new ComponentTemplateFinder($twig, 'components'),
        ];
    }

    /**
     * Create component services for the component registrar.
     *
     * @param Environment $twig The Twig environment.
     * @param array $components The components.
     * @param array $basicServices The basic services.
     * @return array The component services.
     */
    private function createComponentServices(
        Environment $twig,
        array $components,
        array $basicServices
    ): array {
        $serviceLocator = $this->createServiceLocator($components);
        $factory = $this->createComponentFactory(
            $twig,
            $components,
            $serviceLocator,
            $basicServices
        );

        $renderer = $this->createComponentRenderer(
            $twig,
            $factory,
            $basicServices
        );

        return [
            'serviceLocator' => $serviceLocator,
            'factory' => $factory,
            'renderer' => $renderer,
        ];
    }

    /**
     * Create a service locator for the component registrar.
     *
     * @param array $components The components.
     * @return ServiceLocator The service locator.
     */
    private function createServiceLocator(array $components): ServiceLocator
    {
        $services = [];
        foreach ($components as $componentClass) {
            $name = $this->getComponentName($componentClass);
            $services[$name] = fn () => new $componentClass();
        }

        return new ServiceLocator($services);
    }

    /**
     * Create a component factory for the component registrar.
     *
     * @param Environment $twig The Twig environment.
     * @param array $components The components.
     * @param ServiceLocator $serviceLocator The service locator.
     * @param array $basicServices The basic services.
     * @return ComponentFactory The component factory.
     */
    private function createComponentFactory(
        Environment $twig,
        array $components,
        ServiceLocator $serviceLocator,
        array $basicServices
    ): ComponentFactory {
        return new ComponentFactory(
            $basicServices['templateFinder'],
            $serviceLocator,
            $basicServices['propertyAccessor'],
            $basicServices['eventDispatcher'],
            $this->createComponentConfig($components),
            $this->createComponentClassMap($components),
            $twig
        );
    }

    /**
     * Create a component config for the component registrar.
     *
     * @param array $components The components.
     * @return array The component config.
     */
    private function createComponentConfig(array $components): array
    {
        if (!$this->cache) {
            return $this->buildComponentConfig($components);
        }

        $item = $this->cache->getItem($this->cacheKey);
        if ($item->isHit()) {
            return $item->get();
        }

        $config = $this->buildComponentConfig($components);
        $item->set($config);
        $this->cache->save($item);

        return $config;
    }

    /**
     * Build a component config for the component registrar.
     *
     * @param array $components The components.
     * @return array The component config.
     */
    private function buildComponentConfig(array $components): array
    {
        $config = [];
        foreach ($components as $componentClass) {
            $reflection = new ReflectionClass($componentClass);
            $attributes = $reflection->getAttributes(AsTwigComponent::class);

            if (empty($attributes)) {
                continue;
            }

            $attribute = $attributes[0];
            $arguments = $attribute->getArguments();
            $name = $this->getComponentName($componentClass);

            $config[$name] = [
                'key' => $name,
                'class' => $componentClass,
                'service_id' => $name,

                // Custom template or default.
                'template' => $arguments['template']
                    ?? $this->getTemplatePath($name),

                // Variable name for attributes.
                'attributes_var' => $arguments['attributesVar']
                    ?? 'attributes',

                // Expose public properties.
                'expose_public_props' => $arguments['exposePublicProps']
                    ?? false,

                // PreMount methods if exist.
                'pre_mount' => $this->getPreMountMethods($reflection),

                // PostMount methods if exist.
                'post_mount' => $this->getPostMountMethods($reflection),
            ];
        }

        return $config;
    }

    /**
     * Get the pre-mount methods for the component registrar.
     *
     * @param ReflectionClass $reflection The reflection class.
     * @return array The pre-mount methods.
     */
    private function getPreMountMethods(ReflectionClass $reflection): array
    {
        return $this->getMethodsWithAttribute($reflection, PreMount::class);
    }

    /**
     * Get the post-mount methods for the component registrar.
     *
     * @param ReflectionClass $reflection The reflection class.
     * @return array The post-mount methods.
     */
    private function getPostMountMethods(ReflectionClass $reflection): array
    {
        return $this->getMethodsWithAttribute($reflection, PostMount::class);
    }

    /**
     * Get the methods with the attribute for the component registrar.
     *
     * @param ReflectionClass $reflection The reflection class.
     * @param string $attributeClass The attribute class.
     * @return array The methods with the attribute.
     */
    private function getMethodsWithAttribute(
        ReflectionClass $reflection,
        string $attributeClass
    ): array {
        $methods = [];
        foreach ($reflection->getMethods() as $method) {
            if (!empty($method->getAttributes($attributeClass))) {
                $methods[] = $method->getName();
            }
        }
        return $methods;
    }

    /**
     * Create a component class map for the component registrar.
     *
     * @param array $components The components.
     * @return array The component class map.
     */
    private function createComponentClassMap(array $components): array
    {
        $map = [];

        foreach ($components as $componentClass) {
            $map[$componentClass] = $this->getComponentName($componentClass);
        }

        return $map;
    }

    /**
     * Create a component renderer for the component registrar.
     *
     * @param Environment $twig The Twig environment.
     * @param ComponentFactory $factory The component factory.
     * @param array $basicServices The basic services.
     * @return ComponentRenderer The component renderer.
     */
    private function createComponentRenderer(
        Environment $twig,
        ComponentFactory $factory,
        array $basicServices
    ): ComponentRenderer {
        return new ComponentRenderer(
            $twig,
            $basicServices['eventDispatcher'],
            $factory,
            new ComponentProperties($basicServices['propertyAccessor']),
            new ComponentStack()
        );
    }

    /**
     * Register Twig extensions for the component registrar.
     *
     * @param Environment $twig The Twig environment.
     * @param array $services The services.
     * @return Environment The Twig environment.
     */
    private function registerTwigExtensions(
        Environment $twig,
        array $services
    ): Environment {
        // Register ComponentExtension.
        $twig->addExtension(new ComponentExtension());

        // Register Runtime.
        $renderersLocator = new ServiceLocator([]);
        $twig->addRuntimeLoader(
            $this->createRuntimeLoader($services['renderer'], $renderersLocator)
        );

        // Register Lexer.
        $twig->setLexer(new ComponentLexer($twig));

        return $twig;
    }

    /**
     * Create a runtime loader for the component registrar.
     *
     * @param ComponentRenderer $renderer The component renderer.
     * @param ServiceLocator $renderers The renderers.
     * @return RuntimeLoaderInterface The runtime loader.
     */
    private function createRuntimeLoader(
        ComponentRenderer $renderer,
        ServiceLocator $renderers
    ): RuntimeLoaderInterface {
        return new class ($renderer, $renderers) implements RuntimeLoaderInterface {
            public function __construct(
                private ComponentRenderer $renderer,
                private ServiceLocator $renderers
            ) {
            }

            public function load($class)
            {
                if ($class === ComponentRuntime::class) {
                    return new ComponentRuntime($this->renderer, $this->renderers);
                }
                return null;
            }
        };
    }

    /**
     * Get the component name for the component registrar.
     *
     * @param string $componentClass The component class.
     * @return string The component name.
     */
    private function getComponentName(string $componentClass): string
    {
        // Extract the component name from the class using reflection or
        // attributes.
        $reflection = new ReflectionClass($componentClass);
        $attributes = $reflection->getAttributes(AsTwigComponent::class);

        if (!empty($attributes)) {
            return $attributes[0]->getArguments()[0]
                ?? strtolower(basename(str_replace('\\', '/', $componentClass)))
            ;
        }

        return strtolower(basename(str_replace('\\', '/', $componentClass)));
    }

    /**
     * Get the template path for the component registrar.
     *
     * @param string $name The component name.
     * @return string The template path.
     */
    private function getTemplatePath(string $name): string
    {
        return sprintf('components/%s.html.twig', $name);
    }
}
