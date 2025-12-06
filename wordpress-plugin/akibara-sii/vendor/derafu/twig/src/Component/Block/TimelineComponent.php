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

use DateTimeInterface;
use Derafu\Twig\Abstract\AbstractComponent;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent('block-timeline')]
class TimelineComponent extends AbstractComponent
{
    /**
     * Array of timeline events configurations.
     *
     * Each event contains:
     * - date: Event date (YYYY-MM-DD, YYYYMM, YYYY).
     * - title: Event title (optional).
     * - content: Event content (required).
     * - icon: Font Awesome icon class (e.g. "fa-solid fa-check-circle fa-fw").
     *
     * @var array
     */
    private array $events = [];

    /**
     * Position of the timeline line (center, left, right).
     *
     * @var string
     */
    private string $linePosition = 'center';

    /**
     * Optional date format for event dates.
     *
     * @var string|null
     */
    private ?string $dateFormat = null;

    /**
     * Optional month format for event dates.
     *
     * @var string|null
     */
    private ?string $monthFormat = null;

    /**
     * Whether to show the full date of the event.
     *
     * @var bool|null
     */
    private ?bool $showFullDate = null;

    /**
     * Whether to show the month and year of the event.
     *
     * @var bool|null
     */
    private ?bool $showMonthYear = null;

    /**
     * Gets the array of timeline events.
     *
     * @return array The array of timeline events.
     */
    public function getEvents(): array
    {
        return $this->events;
    }

    /**
     * Sets the array of timeline events.
     *
     * @param array $events The array of timeline events.
     * @return static
     */
    public function setEvents(array $events): static
    {
        $this->events = $events;

        foreach ($this->events as &$event) {
            // Determine the year of the event.
            if ($event['date'] instanceof DateTimeInterface) {
                $event['year'] = (int) $event['date']->format('Y');
            } elseif (is_string($event['date'])) {
                $event['year'] = (int) substr($event['date'], 0, 4);
            } else {
                $this->error(sprintf(
                    'Invalid date format for event: %s',
                    $event['date'] ?? 'N/A'
                ));
            }

            // Determine if the full date should be shown.
            if (
                (!isset($this->showFullDate) || $this->showFullDate !== false)
                && !isset($event['showFullDate'])
            ) {
                $event['showFullDate'] =
                    $event['date'] instanceof DateTimeInterface
                    || preg_match('/^\d{4}-\d{2}-\d{2}$/', $event['date'])
                ;
            }

            // Determine if the month and year should be shown.
            if (
                (!isset($this->showMonthYear) || $this->showMonthYear !== false)
                && !isset($event['showMonthYear'])
            ) {
                $event['showMonthYear'] =
                    $event['date'] instanceof DateTimeInterface
                    || preg_match('/^\d{4}\d{2}$/', $event['date'])
                    || preg_match('/^\d{4}-\d{2}$/', $event['date'])
                ;
            }
        }

        return $this;
    }

    /**
     * Gets the position of the timeline line.
     *
     * @return string The line position.
     */
    public function getLinePosition(): string
    {
        return $this->linePosition;
    }

    /**
     * Sets the position of the timeline line.
     *
     * @param string $linePosition The line position.
     * @return static
     */
    public function setLinePosition(string $linePosition): static
    {
        $this->linePosition = $linePosition;

        return $this;
    }

    /**
     * Gets the date format for event dates.
     *
     * @return string|null The date format.
     */
    public function getDateFormat(): ?string
    {
        return $this->dateFormat;
    }

    /**
     * Sets the date format for event dates.
     *
     * @param string|null $dateFormat The date format.
     * @return static
     */
    public function setDateFormat(?string $dateFormat): static
    {
        $this->dateFormat = $dateFormat;

        return $this;
    }

    /**
     * Gets the month format for event dates.
     *
     * @return string|null The month format.
     */
    public function getMonthFormat(): ?string
    {
        return $this->monthFormat;
    }

    /**
     * Sets the month format for event dates.
     *
     * @param string|null $monthFormat The month format.
     * @return static
     */
    public function setMonthFormat(?string $monthFormat): static
    {
        $this->monthFormat = $monthFormat;

        return $this;
    }

    /**
     * Gets whether to show the full date of the event.
     *
     * @return bool|null The show full date.
     */
    public function getShowFullDate(): ?bool
    {
        return $this->showFullDate;
    }

    /**
     * Sets whether to show the full date of the event.
     *
     * @param bool|null $showFullDate The show full date.
     * @return static
     */
    public function setShowFullDate(?bool $showFullDate): static
    {
        $this->showFullDate = $showFullDate;

        return $this;
    }

    /**
     * Gets whether to show the month and year of the event.
     *
     * @return bool|null The show month year.
     */
    public function getShowMonthYear(): ?bool
    {
        return $this->showMonthYear;
    }

    /**
     * Sets whether to show the month and year of the event.
     *
     * @param bool|null $showMonthYear The show month year.
     * @return static
     */
    public function setShowMonthYear(?bool $showMonthYear): static
    {
        $this->showMonthYear = $showMonthYear;

        return $this;
    }
}
