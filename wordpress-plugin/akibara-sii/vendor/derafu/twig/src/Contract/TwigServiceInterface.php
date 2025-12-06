<?php

declare(strict_types=1);

/**
 * Derafu: Twig - UI Component and Extension Library.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\Twig\Contract;

use Twig\Environment;

interface TwigServiceInterface
{
    /**
     * Renderiza una plantilla Twig en HTML.
     *
     * @param string $template Plantilla Twig a renderizar.
     * @param array $data Datos que se pasarán a la plantilla Twig.
     * @return string Código HTML con el renderizado de la plantilla Twig.
     */
    public function render(string $template, array &$data = []): string;

    /**
     * Entrega la instancia de Twig.
     *
     * Este método evita crearla en el constructor y se crea solo cuando
     * realmente se utiliza. Útil cuando se usan lazy services.
     *
     * @return Environment
     */
    public function getTwig(): Environment;
}
