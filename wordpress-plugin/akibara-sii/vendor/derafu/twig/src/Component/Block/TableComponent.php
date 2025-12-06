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

/**
 * Table Component for displaying tabular data.
 *
 * This component creates a Bootstrap-based table that can be configured with
 * different headers and row data.
 */
#[AsTwigComponent('block-table')]
class TableComponent extends AbstractComponent
{
    /**
     * Table header configuration.
     *
     * Structure per cell:
     *
     *   - text: Cell content (supports HTML).
     *   - scope: Accessibility scope (col, colgroup).
     *   - colspan: Number of columns to span.
     *   - rowspan: Number of rows to span.
     *   - width: Fixed width in pixels or percentage.
     *   - align: Text alignment (left, center, right).
     *   - class: Additional CSS classes.
     *
     * @var array[][] Array of rows, each containing array of cells.
     */
    private array $headers = [];

    /**
     * Table rows data.
     *
     * First key is row index, second is column index.
     *
     * @var array[][]
     */
    private array $rows = [];

    /**
     * Footer rows configuration (same structure as headers).
     *
     * @var array[][]
     */
    private array $footer = [];

    /**
     * Actions menu items.
     * Structure:
     *
     *   - text: Link/button text.
     *   - icon: Optional Font Awesome icon class.
     *   - url: Optional URL for link.
     *   - onclick: Optional JavaScript action.
     *   - type: Optional 'divider' type.
     *
     * @var array[]
     */
    private array $actions = [];

    /**
     * Column configurations.
     *
     * Key is column index.
     *
     * Structure:
     *
     *   - width: Width in pixels or percentage.
     *   - align: Text alignment (left, center, right).
     *   - class: Additional CSS classes.
     *
     * @var array<int, array{
     *     width?: string,
     *     align?: string,
     *     class?: string
     * }>
     */
    private array $columns = [];

    /**
     * Row classes based on column values
     *
     * Structure: [column_index => [value => class_name]]
     *
     * @var array<int, array<string, string>>
     */
    private array $rowClasses = [];

    /**
     * Table caption (optional).
     */
    private ?string $caption = null;

    /**
     * Maximum height for table container in pixels.
     */
    private ?int $maxHeight = null;

    /**
     * Show/hide toggle buttons.
     */
    private bool $toggleable = false;

    /**
     * Initial visibility state if toggleable.
     */
    private bool $visible = true;

    /**
     * Hide empty columns.
     */
    private bool $hideEmptyCols = false;

    /**
     * Add striped background to rows.
     */
    private bool $striped = false;

    /**
     * Add border to table cells.
     */
    private bool $bordered = true;

    /**
     * Add hover effect to rows.
     */
    private bool $hover = true;

    /**
     * Make table smaller.
     */
    private bool $small = false;

    /**
     * Get table headers configuration.
     *
     * @return array[][] Array of rows, each containing array of cells.
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * Set table headers configuration.
     *
     * @param array[][] $headers Array of rows, each containing array of cells.
     * @return static
     */
    public function setHeaders(array $headers): static
    {
        $this->headers = $headers;

        return $this;
    }

    /**
     * Get table rows data.
     *
     * @return array[][] Array of rows with column data.
     */
    public function getRows(): array
    {
        return $this->rows;
    }

    /**
     * Set table rows data.
     *
     * @param array[][] $rows Array of rows with column data.
     * @return static
     */
    public function setRows(array $rows): static
    {
        $this->rows = $rows;

        return $this;
    }

    /**
     * Get footer rows configuration.
     *
     * @return array[][] Array of footer rows configuration.
     */
    public function getFooter(): array
    {
        return $this->footer;
    }

    /**
     * Set footer rows configuration.
     *
     * @param array[][] $footer Array of footer rows configuration.
     * @return static
     */
    public function setFooter(array $footer): static
    {
        $this->footer = $footer;

        return $this;
    }

    /**
     * Get actions menu items.
     *
     * @return array[] Array of action items.
     */
    public function getActions(): array
    {
        return $this->actions;
    }

    /**
     * Set actions menu items.
     *
     * @param array[] $actions Array of action items.
     * @return static
     */
    public function setActions(array $actions): static
    {
        $this->actions = $actions;

        return $this;
    }

    /**
     * Get column configurations.
     *
     * @return array<int, array{width?: string, align?: string, class?: string}> Column configurations indexed by column number.
     */
    public function getColumns(): array
    {
        return $this->columns;
    }

    /**
     * Set column configurations.
     *
     * @param array<int, array{width?: string, align?: string, class?: string}> $columns Column configurations indexed by column number.
     * @return static
     */
    public function setColumns(array $columns): static
    {
        $this->columns = $columns;

        return $this;
    }

    /**
     * Get row classes based on column values.
     *
     * @return array<int, array<string, string>> Row classes configuration.
     */
    public function getRowClasses(): array
    {
        return $this->rowClasses;
    }

    /**
     * Set row classes based on column values.
     *
     * @param array<int, array<string, string>> $rowClasses Row classes configuration.
     * @return static
     */
    public function setRowClasses(array $rowClasses): static
    {
        $this->rowClasses = $rowClasses;

        return $this;
    }

    /**
     * Get table caption.
     *
     * @return string|null Table caption or null if not set.
     */
    public function getCaption(): ?string
    {
        return $this->caption;
    }

    /**
     * Set table caption.
     *
     * @param string|null $caption Table caption or null to remove.
     * @return static
     */
    public function setCaption(?string $caption): static
    {
        $this->caption = $caption;

        return $this;
    }

    /**
     * Get maximum height for table container.
     *
     * @return int|null Maximum height in pixels or null if not set.
     */
    public function getMaxHeight(): ?int
    {
        return $this->maxHeight;
    }

    /**
     * Set maximum height for table container.
     *
     * @param int|null $maxHeight Maximum height in pixels or null to remove.
     * @return static
     */
    public function setMaxHeight(?int $maxHeight): static
    {
        $this->maxHeight = $maxHeight;

        return $this;
    }

    /**
     * Get toggleable state.
     *
     * @return bool Whether toggle buttons are shown.
     */
    public function getToggleable(): bool
    {
        return $this->toggleable;
    }

    /**
     * Set toggleable state.
     *
     * @param bool $toggleable Whether to show toggle buttons.
     * @return static
     */
    public function setToggleable(bool $toggleable): static
    {
        $this->toggleable = $toggleable;

        return $this;
    }

    /**
     * Get visibility state.
     *
     * @return bool Whether table is initially visible.
     */
    public function getVisible(): bool
    {
        return $this->visible;
    }

    /**
     * Set visibility state.
     *
     * @param bool $visible Whether table is initially visible.
     * @return static
     */
    public function setVisible(bool $visible): static
    {
        $this->visible = $visible;

        return $this;
    }

    /**
     * Get hide empty columns setting.
     *
     * @return bool Whether empty columns are hidden.
     */
    public function getHideEmptyCols(): bool
    {
        return $this->hideEmptyCols;
    }

    /**
     * Set hide empty columns setting.
     *
     * @param bool $hideEmptyCols Whether to hide empty columns.
     * @return static
     */
    public function setHideEmptyCols(bool $hideEmptyCols): static
    {
        $this->hideEmptyCols = $hideEmptyCols;

        return $this;
    }

    /**
     * Get striped background setting.
     *
     * @return bool Whether striped background is enabled.
     */
    public function getStriped(): bool
    {
        return $this->striped;
    }

    /**
     * Set striped background setting.
     *
     * @param bool $striped Whether to add striped background to rows.
     * @return static
     */
    public function setStriped(bool $striped): static
    {
        $this->striped = $striped;

        return $this;
    }

    /**
     * Get bordered setting.
     *
     * @return bool Whether table borders are enabled.
     */
    public function getBordered(): bool
    {
        return $this->bordered;
    }

    /**
     * Set bordered setting.
     *
     * @param bool $bordered Whether to add border to table cells.
     * @return static
     */
    public function setBordered(bool $bordered): static
    {
        $this->bordered = $bordered;

        return $this;
    }

    /**
     * Get hover effect setting.
     *
     * @return bool Whether hover effect is enabled.
     */
    public function getHover(): bool
    {
        return $this->hover;
    }

    /**
     * Set hover effect setting.
     *
     * @param bool $hover Whether to add hover effect to rows.
     * @return static
     */
    public function setHover(bool $hover): static
    {
        $this->hover = $hover;

        return $this;
    }

    /**
     * Get small table setting.
     *
     * @return bool Whether table is rendered in small size.
     */
    public function getSmall(): bool
    {
        return $this->small;
    }

    /**
     * Set small table setting.
     *
     * @param bool $small Whether to make table smaller.
     * @return static
     */
    public function setSmall(bool $small): static
    {
        $this->small = $small;

        return $this;
    }
}
