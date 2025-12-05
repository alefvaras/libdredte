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

use Derafu\Twig\Contract\ComponentRegistrarInterface;
use Derafu\Twig\Contract\TwigCreatorInterface;
use Derafu\Twig\Extension\MarkdownExtension;
use Twig\Environment;
use Twig\Extra\CssInliner\CssInlinerExtension;
use Twig\Extra\Inky\InkyExtension;
use Twig\Extra\Intl\IntlExtension;
use Twig\Extra\String\StringExtension;
use Twig\Loader\FilesystemLoader;

class TwigCreator implements TwigCreatorInterface
{
    /**
     * Opciones para la creación de la instancia de twig.
     *
     * @var array
     */
    private array $options;

    private ComponentRegistrarInterface $componentRegistrar;

    /**
     * Constructor del servicio que crea instancia del renderizador Twig.
     *
     * @param array $options Opciones para la construcción de la instancia Twig.
     * @param ComponentRegistrarInterface|null $componentRegistrar
     */
    public function __construct(
        array $options = [],
        ?ComponentRegistrarInterface $componentRegistrar = null
    ) {
        if (!empty($options)) {
            $this->options = $this->resolveOptions($options);
        }

        $this->componentRegistrar = $componentRegistrar ?? new ComponentRegistrar();
    }

    /**
     * Crea, inicializa y retorna una nueva instancia de Twig.
     *
     * @param array $options
     * @return Environment
     */
    public function create(array $options = []): Environment
    {
        // Resolver las opciones para la creación.
        $options = $this->resolveOptions($options);

        // Crear loader con los paths donde se buscarán las plantillas.
        $loader = new FilesystemLoader($options['paths']);

        // Crear el renderizador de twig.
        $twig = new Environment($loader);

        // Agregar las extensiones que estén registradas.
        foreach ($options['extensions'] as $extension) {
            $twig->addExtension($extension);
        }

        // Agregar las extensiones extra dependiendo de si están disponibles.
        if ($options['extra']) {
            $this->addExtraExtensions($twig);
        }

        // Registrar en Twig todo lo asociado a los componentes.
        if (!empty($options['components'])) {
            $this->componentRegistrar->register($twig, $options['components']);
        }

        // Entregar la instancia de Twig.
        return $twig;
    }

    private function resolveOptions(array $options): array
    {
        $options = array_merge($this->options ?? [], $options);

        $paths = $options['paths'] ?? [realpath(__DIR__ . '/../../templates')];
        $options['paths'] = is_string($paths) ? [$paths] : $paths;
        $options['extensions'] = $options['extensions'] ?? [];
        $options['components'] = $options['components'] ?? [];
        $options['extra'] = $options['extra'] ?? true;

        return $options;
    }

    /**
     * Carga las extensiones solo si sus dependencias están instaladas.
     *
     * @param Environment $twig Instancia de Twig donde agregar las extensiones.
     * @return void
     */
    private function addExtraExtensions(Environment $twig): void
    {
        $twig->addExtension(new MarkdownExtension());

        if (class_exists(CssInlinerExtension::class)) {
            $twig->addExtension(new CssInlinerExtension());
        }

        if (class_exists(IntlExtension::class)) {
            $twig->addExtension(new IntlExtension());
        }

        if (class_exists(StringExtension::class)) {
            $twig->addExtension(new StringExtension());
        }

        if (class_exists(InkyExtension::class)) {
            $twig->addExtension(new InkyExtension());
        }
    }
}
