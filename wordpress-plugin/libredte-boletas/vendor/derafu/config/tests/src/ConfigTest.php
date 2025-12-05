<?php

declare(strict_types=1);

/**
 * Derafu: Config - Yet Another Config Lib.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\TestsConfig;

use Derafu\Config\Configuration;
use Derafu\Config\Contract\ConfigurableInterface;
use Derafu\Config\Contract\OptionsAwareInterface;
use Derafu\Config\Options;
use Derafu\Config\Trait\ConfigurableTrait;
use Derafu\Config\Trait\OptionsAwareTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\Exception\UndefinedOptionsException;

#[CoversClass(Configuration::class)]
#[CoversClass(Options::class)]
#[CoversClass(ConfigurableTrait::class)]
#[CoversClass(OptionsAwareTrait::class)]
class ConfigTest extends TestCase
{
    private array $configuration = [
        'app' => [
            'name' => 'Derafu Config',
        ],
    ];

    private array $options = [
        'strategy' => 'template.pdf',
        'config' => [
            'html' => [
                'footer' => false,
            ],
            'pdf' => [
                'footer' => true,
            ],
        ],
    ];

    public function testConfiguration(): void
    {
        $configuration = new Configuration($this->configuration);

        $this->assertSame('Derafu Config', $configuration['app']['name']);
        $this->assertSame('Derafu Config', $configuration['app.name']);
        $this->assertSame('Derafu Config', $configuration->get('app.name'));
        $this->assertSame('Derafu Config', $configuration->get('app')->get('name'));
    }

    public function testOptions(): void
    {
        $options = new Options($this->options);

        $this->assertSame('template.pdf', $options->get('strategy'));
        $this->assertTrue($options->get('config.pdf')->get('footer'));
    }

    public function testConfigurable(): void
    {
        $configurableClass = new ConfigurableClass();
        $configurableClass->setConfiguration($this->configuration);

        $this->assertSame('Derafu Config', $configurableClass->getConfiguration()->get('app.name'));
        $this->assertSame('Derafu Config', $configurableClass->getConfiguration()->get('app')->get('name'));
    }

    public function testOptionsAware(): void
    {
        $optionsAwareClass = new OptionsAwareClass();
        $optionsAwareClass->setOptions($this->options);

        $this->assertSame('template.pdf', $optionsAwareClass->getOptions()->get('strategy'));
        $this->assertTrue($optionsAwareClass->getOptions()->get('config.pdf')->get('footer'));
    }

    public function testOptionsAwareWithDefaultOptions(): void
    {
        $optionsAwareClass = new OptionsAwareClass();

        $this->assertSame('template.html', $optionsAwareClass->getOptions()->get('strategy'));
        $this->assertFalse($optionsAwareClass->getOptions()->get('config.pdf')->get('footer'));
    }

    public function testOptionsAwareWithWrongOptionName(): void
    {
        $this->expectException(UndefinedOptionsException::class);

        $optionsAwareClass = new OptionsAwareClass();
        $optionsAwareClass->setOptions([
            'strategi' => 'template.pdf',
        ]);
    }

    public function testOptionsAwareWithWrongOptionValue(): void
    {
        $this->expectException(InvalidOptionsException::class);

        $optionsAwareClass = new OptionsAwareClass();
        $optionsAwareClass->setOptions([
            'strategy' => 'template.doc',
        ]);
    }
}

class ConfigurableClass implements ConfigurableInterface
{
    use ConfigurableTrait;

    protected array $configurationSchema = [
        'app' => [
            'types' => 'array',
            'schema' => [
                'name' => [
                    'types' => 'string',
                    'default' => 'Derafu App',
                ],
            ],
        ],
    ];
}

class OptionsAwareClass implements OptionsAwareInterface
{
    use OptionsAwareTrait;

    protected array $optionsSchema = [
        'strategy' => [
            'types' => 'string',
            'choices' => ['template.html', 'template.pdf'],
            'default' => 'template.html',
        ],
        'config' => [
            'types' => 'array',
            'schema' => [
                'html' => [
                    'types' => 'array',
                    'schema' => [
                        'footer' => [
                            'types' => 'bool',
                            'default' => false,
                        ],
                    ],
                ],
                'pdf' => [
                    'types' => 'array',
                    'schema' => [
                        'footer' => [
                            'types' => 'bool',
                            'default' => false,
                        ],
                    ],
                ],
            ],
        ],
    ];
}
