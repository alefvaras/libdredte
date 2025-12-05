<?php

declare(strict_types=1);

/**
 * Derafu: Renderer - Unified Template Rendering Made Simple For PHP.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\TestsRenderer;

use Derafu\Renderer\Contract\RendererInterface;
use Derafu\Renderer\Engine\Html\MarkdownHtmlEngine;
use Derafu\Renderer\Engine\Html\PhpHtmlEngine;
use Derafu\Renderer\Engine\Html\TwigHtmlEngine;
use Derafu\Renderer\Engine\Pdf\HtmlPdfEngine;
use Derafu\Renderer\Factory\RendererFactory;
use Derafu\Renderer\Formatter\DataFormatter;
use Derafu\Renderer\Formatter\Extension\TwigFormatterExtension;
use Derafu\Renderer\Formatter\Handler\GenericFormatterHandler;
use Derafu\Renderer\Renderer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Renderer::class)]
#[CoversClass(RendererFactory::class)]
#[CoversClass(DataFormatter::class)]
#[CoversClass(GenericFormatterHandler::class)]
#[CoversClass(TwigFormatterExtension::class)]
#[CoversClass(MarkdownHtmlEngine::class)]
#[CoversClass(PhpHtmlEngine::class)]
#[CoversClass(HtmlPdfEngine::class)]
#[CoversClass(TwigHtmlEngine::class)]
class CustomTemplateTest extends TestCase
{
    private RendererInterface $renderer;

    private array $data;

    public function setUp(): void
    {
        $this->renderer = RendererFactory::create([
            'engines' => ['twig', 'markdown', 'php', 'pdf'],
            'paths' => [__DIR__ . '/../fixtures'],
            'formatters' => [
                'date' => function (string $date): string {
                    $timestamp = strtotime($date);
                    return date('d/m/Y', $timestamp);
                },
            ],
        ]);

        $this->data = [
            'title' => 'Derafu',
            'content' => 'I Love Derafu <3',
            'date' => date('Y-m-d'),
        ];
    }

    public function testRenderCustomTemplateHtmlTwig()
    {
        $template = __DIR__ . '/../fixtures/custom_template';
        $html = $this->renderer->render($template, $this->data);
        $this->assertIsString($html);
        $this->saveRendered($template, 'html', $html);
    }

    public function testRenderCustomTemplateHtmlTwigEnginePdf()
    {
        $template = __DIR__ . '/../fixtures/custom_template';
        $pdf = $this->renderer->render($template, $this->data, ['engine' => 'pdf']);
        $this->assertIsString($pdf);
        $this->saveRendered($template, 'pdf', $pdf);
    }

    public function testRenderCustomTemplatePdfTwig()
    {
        $template = __DIR__ . '/../fixtures/custom_template.pdf.twig';
        $pdf = $this->renderer->render($template, $this->data);
        $this->assertIsString($pdf);
        $this->saveRendered($template, 'pdf', $pdf);
    }

    public function testRenderCustomTemplateMarkdown()
    {
        $template = __DIR__ . '/../fixtures/custom_template.md';
        $html = $this->renderer->render($template, $this->data);
        $this->assertIsString($html);
        $this->saveRendered($template, 'html', $html);
    }

    public function testRenderCustomTemplatePhp()
    {
        $template = __DIR__ . '/../fixtures/custom_template.php';
        $html = $this->renderer->render($template, $this->data);
        $this->assertIsString($html);
        $this->saveRendered($template, 'html', $html);
    }

    private function saveRendered(string $template, string $extension, string $content): void
    {
        $file = str_replace('/fixtures/', '/rendered/', $template) . '.' . $extension;
        $dir = dirname($file);
        if (!is_dir($dir)) {
            mkdir($dir);
        }
        file_put_contents($file, $content);
    }
}
