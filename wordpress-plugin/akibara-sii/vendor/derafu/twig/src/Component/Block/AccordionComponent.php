<?php

declare(strict_types=1);

/**
 * Derafu: Twig - UI Component and Extension Library.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\Twig\Component\Block;

use Derafu\Twig\Abstract\AbstractComponent;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent('block-accordion')]
final class AccordionComponent extends AbstractComponent
{
    /**
     * Array of accordion items.
     *
     * Each item should contain:
     *
     *   - `title`.
     *   - `content`.
     *   - `active`.
     *
     * @var array<int,array>
     */
    private array $items = [];

    /**
     * Gets the accordion items.
     *
     * @return array<int,array>
     */
    public function getItems(): array
    {
        return $this->items;
    }

    /**
     * Sets the accordion items.
     *
     * @param array<int,array> $items The accordion items.
     * @return static
     */
    public function setItems(array $items): static
    {
        foreach ($items as &$item) {
            if (empty($item['title'])) {
                $this->error('Item title is required.');
            }

            if (empty($item['content'])) {
                $this->error('Item content is required.');
            }

            $item['active'] = $item['active'] ?? false;
        }

        $this->items = $items;

        return $this;
    }
}
