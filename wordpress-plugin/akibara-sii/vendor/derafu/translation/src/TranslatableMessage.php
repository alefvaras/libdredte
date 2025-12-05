<?php

declare(strict_types=1);

/**
 * Derafu: Translation - Translation Library with Exception Support.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\Translation;

use Derafu\Translation\Contract\TranslatableInterface;
use IntlException;
use MessageFormatter;
use RuntimeException;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * A translatable message that supports ICU message formatting.
 *
 * This class implements TranslatableInterface to provide a message that can be
 * translated using a translator or fallback to ICU formatting when no
 * translator is available.
 */
final class TranslatableMessage implements TranslatableInterface
{
    /**
     * Creates a new translatable message.
     *
     * @param string $message The message used for translation. This should be a
     * valid ICU message format string as it will be used as fallback when no
     * translator is available.
     * @param array<string, mixed> $parameters Parameters for translation
     * placeholders.
     * @param string|null $domain The translation domain or `null` for default
     * domain.
     * @param string $defaultLocale The locale to use for ICU formatting when no
     * translator is available. This must be a valid ICU locale identifier.
     * @throws RuntimeException When ICU message formatting fails due to invalid
     * message format or parameters.
     */
    public function __construct(
        private readonly string $message,
        private readonly array $parameters = [],
        private readonly ?string $domain = null,
        private readonly ?string $defaultLocale = null
    ) {
    }

    /**
     * Translates the message using the provided translator.
     *
     * @param TranslatorInterface $translator The translator to use.
     * @param string|null $locale The locale to translate to or `null` for
     * default.
     * @return string The translated message.
     */
    public function trans(
        TranslatorInterface $translator,
        ?string $locale = null
    ): string {
        return $translator->trans(
            $this->message,
            $this->parameters,
            $this->domain,
            $locale ?? $this->defaultLocale
        );
    }

    /**
     * Converts the message to a string using ICU formatting when no translator
     * is available.
     *
     * @return string The formatted message. If ICU formatting fails, returns
     * the raw message as fallback.
     */
    public function __toString(): string
    {
        try {
            $formatter = new MessageFormatter(
                $this->defaultLocale ?? 'en',
                $this->message
            );
        } catch (IntlException $e) {
            return $this->message;
        }

        $result = $formatter->format($this->parameters);

        if ($result === false) {
            // Log error if needed: intl_get_error_message().
            return $this->message;
        }

        return $result;
    }
}
