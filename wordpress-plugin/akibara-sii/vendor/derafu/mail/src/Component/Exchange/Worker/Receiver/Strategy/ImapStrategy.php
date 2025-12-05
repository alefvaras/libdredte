<?php

declare(strict_types=1);

/**
 * Derafu: Mail - Elegant orchestration of email communications for PHP.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\Mail\Component\Exchange\Worker\Receiver\Strategy;

use Derafu\Backbone\Attribute\Strategy;
use Derafu\Config\Contract\OptionsInterface;
use Derafu\Mail\Component\Exchange\Worker\Receiver\Strategy\Abstract\AbstractMailboxStrategy;
use Derafu\Mail\Component\Exchange\Worker\Receiver\Strategy\Contract\ReceiverStrategyInterface;

/**
 * Class for receiving emails using IMAP.
 */
#[Strategy(name: 'imap', worker: 'receiver', component: 'exchange', package: 'mail')]
class ImapStrategy extends AbstractMailboxStrategy implements ReceiverStrategyInterface
{
    /**
     * Schema of the options.
     *
     * @var array<string,array>
     */
    protected array $optionsSchema = [
        'strategy' => [
            'types' => 'string',
            'default' => 'imap',
        ],
        'transport' => [
            'types' => 'array',
            'schema' => [
                'host' => [
                    'types' => 'string',
                    'default' => 'imap.gmail.com',
                ],
                'port' => [
                    'types' => 'int',
                    'default' => 993,
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
                'mailbox' => [
                    'types' => 'string',
                    'default' => 'INBOX',
                ],
                'attachments_dir' => [
                    'types' => ['string', 'null'],
                    'default' => null,
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
                'search' => [
                    'types' => 'array',
                    'schema' => [
                        'criteria' => [
                            'types' => 'string',
                            'default' => 'UNSEEN',
                        ],
                        'markAsSeen' => [
                            'types' => 'bool',
                            'default' => false,
                        ],
                        'attachmentFilters' => [
                            'types' => 'array',
                            'default' => [],
                        ],
                    ],
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
            '{%s:%d/imap%s%s}%s',
            $transportOptions['host'],
            $transportOptions['port'],
            $transportOptions['encryption'] === 'ssl' ? '/ssl' : '',
            (
                isset($transportOptions['verify_peer'])
                && !$transportOptions['verify_peer']
            )
                ? '/novalidate-cert'
                : '',
            $transportOptions['mailbox']
        );

        $options->set('transport.dsn', $dsn);

        return $dsn;
    }
}
