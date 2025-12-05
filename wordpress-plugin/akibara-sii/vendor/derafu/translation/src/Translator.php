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

use Derafu\Translation\Contract\MessageProviderInterface;
use IntlException;
use MessageFormatter;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Contracts\Translation\TranslatorTrait;

/**
 * A simple translator implementation that leverages Symfony's TranslatorTrait.
 *
 * This implementation provides:
 *
 *   - Locale handling with fallback.
 *   - Pluralization support.
 *   - Interval support in messages.
 *   - ICU message formatting.
 */
final class Translator implements TranslatorInterface
{
    use TranslatorTrait {
        trans as symfonyTrans;
    }

    /**
     * Default fallback chain for locales.
     */
    private const DEFAULT_FALLBACK_LOCALES = ['en', 'en_US', 'es', 'es_CL'];

    /**
     * Fallback chain for locales.
     *
     * E.g.: ['es_CL', 'es', 'en']).
     *
     * @var array<string>
     */
    private array $fallbackLocales = [];

    /**
     * Translator constructor.
     *
     * @param MessageProviderInterface $provider Provider of messages.
     * @param string|null $locale The default locale to use
     * @param array|null $fallbackLocales Fallback locales.
     */
    public function __construct(
        private readonly MessageProviderInterface $provider,
        ?string $locale = null,
        ?array $fallbackLocales = null
    ) {
        $this->setLocale($locale ?? $this->getLocale());
        $this->fallbackLocales = $fallbackLocales ?? self::DEFAULT_FALLBACK_LOCALES;
    }

    /**
     * {@inheritdoc}
     */
    public function trans(
        ?string $id,
        array $parameters = [],
        ?string $domain = null,
        ?string $locale = null
    ): string {
        // First get message from catalog if exists.
        $locale = $locale ?? $this->getLocale();
        $domain = $domain ?? 'messages';

        // Try current locale.
        $message = $this->findMessage($id, $locale, $domain);

        // Try fallback chain.
        if ($message === null) {
            foreach ($this->fallbackLocales as $fallbackLocale) {
                if ($fallbackLocale === $locale) {
                    continue;
                }
                $message = $this->findMessage($id, $fallbackLocale, $domain);
                if ($message !== null) {
                    break;
                }
            }
        }

        // Fallback to Symfony's trans.
        if ($message === null) {
            return $this->symfonyTrans($id, $parameters, $domain, $locale);
        }

        // Use Symfony's pluralization if count parameter exists.
        if (isset($parameters['%count%'])) {
            return $this->symfonyTrans(
                $message,
                $parameters,
                $domain,
                $locale
            );
        }

        // Use ICU formatting for the catalog message.
        try {
            $formatter = new MessageFormatter($locale, $message);
        } catch (IntlException $e) {
            throw new IntlException(sprintf(
                '%s. Message "%s" with locale "%s" using: %s.',
                $e->getMessage(),
                $id,
                $locale,
                $message
            ));
        }
        $result = $formatter->format($parameters);

        if ($result === false) {
            return $message;
        }

        return $result;
    }

    /**
     * Find the message for translation in the message provider.
     *
     * @param string $id
     * @param string $locale
     * @param string $domain
     * @return string|null
     */
    private function findMessage(string $id, string $locale, string $domain): ?string
    {
        $messages = $this->provider->getMessages($locale, $domain);

        return $messages[$id] ?? null;
    }
}
