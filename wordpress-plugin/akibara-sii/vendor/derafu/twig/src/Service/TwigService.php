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

use Derafu\Twig\Contract\TwigCreatorInterface;
use Derafu\Twig\Contract\TwigServiceInterface;
use Derafu\Twig\Provider\AllComponentProvider;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

class TwigService implements TwigServiceInterface
{
    /**
     * Renderizador de plantillas HTML con Twig.
     *
     * @var Environment
     */
    private Environment $twig;

    /**
     * Creador de instancias de Twig.
     *
     * @var TwigCreatorInterface
     */
    private TwigCreatorInterface $twigCreator;

    /**
     * Constructor del servicio.
     *
     * @param Environment|TwigCreatorInterface|array|null $twig
     */
    public function __construct(
        Environment|TwigCreatorInterface|array|null $twig = null,
    ) {
        if (is_array($twig)) {
            $this->twigCreator = new TwigCreator($twig);
        } elseif ($twig instanceof TwigCreatorInterface) {
            $this->twigCreator = $twig;
        } elseif ($twig === null) {
            $this->twigCreator = new TwigCreator();
        } else {
            $this->twig = $twig;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function render(string $template, array &$data = []): string
    {
        //$config = $data['options']['config']['html'] ?? [];

        // Resolver plantilla.
        $template = $this->resolveTemplate($template);

        // Renderizar la plantilla.
        return $this->getTwig()->render($template, $data);
    }

    /**
     * {@inheritDoc}
     */
    public function getTwig(): Environment
    {
        if (!isset($this->twig)) {
            $this->twig = $this->twigCreator->create([
                'components' => new AllComponentProvider(),
            ]);
        }

        return $this->twig;
    }

    /**
     * Resuelve la plantilla que se está solicitando.
     *
     * Se encarga de:
     *
     *   - Agregar la extensión a la plantilla.
     *   - Agregar el directorio si se pasó una ruta absoluta de la plantilla.
     *
     * @param string $template
     * @return string
     */
    private function resolveTemplate(string $template): string
    {
        // Agregar extensión.
        if (!str_ends_with($template, '.twig')) {
            $template .= '.html.twig';
        }

        // Obtener el loader.
        $loader = $this->getTwig()->getLoader();
        if (!$loader instanceof FilesystemLoader) {
            return $template;
        }

        // Agregar el directorio si se pasó una ruta absoluta.
        $realpath = realpath($template);
        if ($realpath) {
            $dir = dirname($realpath);
            if (!in_array($dir, $loader->getPaths())) {
                $loader->addPath($dir);
            }
            $template = basename($realpath);
        }

        // Entregar nombre de la plantilla.
        return $template;
    }
}
