<?php

declare(strict_types=1);

/**
 * Derafu: Renderer - Unified Template Rendering Made Simple For PHP.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\Renderer\Engine\Pdf;

use Derafu\Renderer\Contract\EngineInterface;
use Derafu\Renderer\Exception\ConfigurationException;
use Derafu\Twig\Contract\TwigServiceInterface;
use Mpdf\Mpdf;
use Mpdf\Output\Destination;
use Throwable;

/**
 * PDF engine implementation using mPDF.
 */
class HtmlPdfEngine implements EngineInterface
{
    public function __construct(
        private readonly TwigServiceInterface $twigService
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function render(
        string $template,
        array $data = [],
        array $options = []
    ): string {
        // Merge runtime options with template data.
        $context = array_replace_recursive(
            ['options' => $options],
            $data
        );

        // Render template to HTML using the Twig service.
        $html = $this->twigService->render($template, $context);

        // Create PDF from HTML.
        $pdf = $this->getPdf($options['config']['pdf'] ?? []);
        $pdf->WriteHTML($html);

        // Return PDF content as string.
        return $pdf->Output('', Destination::STRING_RETURN);
    }

    /**
     * {@inheritDoc}
     */
    public function getSupportedExtensions(): array
    {
        return ['pdf.twig'];
    }

    /**
     * {@inheritDoc}
     */
    public function getName(): string
    {
        return 'pdf';
    }

    /**
     * Gets or creates the PDF instance.
     *
     * Create new instance for each render to avoid state issues.
     *
     * @param array<string,mixed> $options Runtime options.
     * @return Mpdf
     */
    private function getPdf(array $options = []): Mpdf
    {
        if (empty($options['tempDir'])) {
            $options['tempDir'] = sys_get_temp_dir();
        }

        try {
            return new Mpdf($options);
        } catch (Throwable $e) {
            throw ConfigurationException::forInvalidOption(
                'pdf',
                json_encode($options),
                $e->getMessage()
            );
        }
    }
}
