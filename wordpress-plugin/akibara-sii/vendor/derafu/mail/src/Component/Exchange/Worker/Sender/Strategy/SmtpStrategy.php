<?php

declare(strict_types=1);

/**
 * Derafu: Mail - Elegant orchestration of email communications for PHP.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\Mail\Component\Exchange\Worker\Sender\Strategy;

use Derafu\Backbone\Attribute\Strategy;
use Derafu\Config\Contract\OptionsInterface;
use Derafu\Mail\Component\Exchange\Worker\Sender\Strategy\Abstract\AbstractMailerStrategy;
use Derafu\Mail\Component\Exchange\Worker\Sender\Strategy\Contract\SenderStrategyInterface;

/**
 * Strategy for sending emails using SMTP.
 */
#[Strategy(name: 'smtp', worker: 'sender', component: 'exchange', package: 'mail')]
class SmtpStrategy extends AbstractMailerStrategy implements SenderStrategyInterface
{
    /**
     * Schema of the options.
     *
     * @var array<string,array>
     */
    protected array $optionsSchema = [
        'strategy' => [
            'types' => 'string',
            'default' => 'smtp',
        ],
        'transport' => [
            'types' => 'array',
            'schema' => [
                'host' => [
                    'types' => 'string',
                    'default' => 'smtp.gmail.com',
                ],
                'port' => [
                    'types' => 'int',
                    'default' => 465,
                ],
                'encryption' => [
                    'types' => ['string', 'null'],
                    'default' => 'ssl',
                ],
                'username' => [
                    'types' => 'string',
                    'required' => true,
                ],
                'password' => [
                    'types' => 'string',
                    'required' => true,
                ],
                'verify_peer' => [
                    'types' => 'bool',
                    'default' => true,
                ],
                'dsn' => [
                    'types' => 'string',
                ],
                'endpoint' => [
                    'types' => 'string',
                ],
            ],
        ],
    ];

    /**
     * {@inheritDoc}
     */
    protected function resolveDsn(OptionsInterface $options): string
    {
        $transportOptions = $options->get('transport');

        if (!empty($transportOptions['dsn'])) {
            return $transportOptions['dsn'];
        }

        $dsn = sprintf(
            'smtp://%s:%s@%s:%d?encryption=%s&verify_peer=%d',
            $transportOptions['username'],
            $transportOptions['password'],
            $transportOptions['host'],
            $transportOptions['port'],
            (string) $transportOptions['encryption'],
            (int) $transportOptions['verify_peer'],
        );

        $options->set('transport.dsn', $dsn);

        return $dsn;
    }

    /**
     * {@inheritDoc}
     */
    protected function resolveEndpoint(OptionsInterface $options): string
    {
        $transportOptions = $options->get('transport');

        if (!empty($transportOptions['endpoint'])) {
            return $transportOptions['endpoint'];
        }

        $endpoint = '';

        if (isset($options['encryption'])) {
            $endpoint .= $options['encryption'] . '://';
        }

        $endpoint .= $options['host'];

        if (isset($options['port'])) {
            $endpoint .= ':' . $options['port'];
        }

        if (isset($options['verify_peer']) && !$options['verify_peer']) {
            $endpoint .= '/novalidate-cert';
        }

        $options->set('transport.endpoint', $endpoint);

        return $endpoint;
    }
}
