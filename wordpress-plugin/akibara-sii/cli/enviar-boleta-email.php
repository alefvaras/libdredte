<?php
/**
 * CLI: Generar PDF de boleta y enviar por email
 *
 * Uso: php enviar-boleta-email.php [folio] [email] [--smtp]
 * Ejemplo: php enviar-boleta-email.php 2049 ale.fvaras@gmail.com
 *
 * Para enviar via SMTP, configure las variables de entorno:
 *   SMTP_HOST=smtp.gmail.com
 *   SMTP_PORT=587
 *   SMTP_USER=tu-email@gmail.com
 *   SMTP_PASS=tu-app-password
 *
 * O use el archivo smtp_config.php
 */

require_once dirname(__DIR__) . '/vendor/autoload.php';

use Derafu\Certificate\Service\CertificateLoader;
use libredte\lib\Core\Application;
use libredte\lib\Core\Package\Billing\Component\Document\Support\DocumentBag;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Entity\AutorizacionDte;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Address;

// Argumentos CLI
$folio = $argv[1] ?? null;
$email = $argv[2] ?? 'ale.fvaras@gmail.com';

if (!$folio) {
    echo "Uso: php enviar-boleta-email.php [folio] [email]\n";
    echo "Ejemplo: php enviar-boleta-email.php 2049 ale.fvaras@gmail.com\n\n";

    // Listar boletas disponibles
    $outputDir = dirname(__DIR__) . '/uploads/output/';
    $files = glob($outputDir . 'boleta_*_F*.xml');
    if ($files) {
        echo "Boletas disponibles:\n";
        foreach ($files as $file) {
            preg_match('/F(\d+)\.xml$/', $file, $matches);
            if ($matches) {
                echo "  - Folio: {$matches[1]}\n";
            }
        }
    }
    exit(1);
}

// Configuracion
$autorizacionConfig = [
    'fechaResolucion' => '2014-08-22',
    'numeroResolucion' => 80,
];

$outputDir = dirname(__DIR__) . '/uploads/output/';

echo "╔══════════════════════════════════════════════════════════════════════╗\n";
echo "║        GENERAR PDF Y ENVIAR BOLETA POR EMAIL                        ║\n";
echo "╚══════════════════════════════════════════════════════════════════════╝\n\n";

echo "Folio: $folio\n";
echo "Email: $email\n\n";

try {
    // Buscar archivo XML de la boleta
    echo "1. Buscando boleta... ";
    $xmlFiles = glob($outputDir . "*F{$folio}.xml");

    if (empty($xmlFiles)) {
        throw new Exception("No se encontró boleta con folio $folio");
    }

    $xmlFile = $xmlFiles[0];
    echo "OK\n";
    echo "   Archivo: " . basename($xmlFile) . "\n";

    // Cargar XML
    $xmlContent = file_get_contents($xmlFile);

    // Inicializar LibreDTE
    echo "2. Inicializando LibreDTE... ";
    $app = Application::getInstance(environment: 'dev', debug: false);
    echo "OK\n";

    // Cargar documento
    echo "3. Cargando documento... ";
    $billingPackage = $app->getPackageRegistry()->getBillingPackage();
    $documentComponent = $billingPackage->getDocumentComponent();
    $loaderWorker = $documentComponent->getLoaderWorker();

    // Crear DocumentBag desde XML
    $documentBag = $loaderWorker->loadXml($xmlContent);

    // Configurar opciones del renderer para PDF
    $documentBag->setOptions([
        'renderer' => [
            'format' => 'pdf',
            'template' => 'estandar',
        ],
    ]);

    // Configurar autorizacion DTE
    $autorizacionDte = new AutorizacionDte(
        $autorizacionConfig['fechaResolucion'],
        (int) $autorizacionConfig['numeroResolucion']
    );

    if ($documentBag->getEmisor()) {
        $documentBag->getEmisor()->setAutorizacionDte($autorizacionDte);
    }

    echo "OK\n";

    // Renderizar a PDF
    echo "4. Generando PDF... ";
    $rendererWorker = $documentComponent->getRendererWorker();
    $pdfContent = $rendererWorker->render($documentBag);

    // Guardar PDF
    $pdfFile = $outputDir . "boleta_F{$folio}_" . date('Ymd_His') . ".pdf";
    file_put_contents($pdfFile, $pdfContent);
    echo "OK\n";
    echo "   Guardado: $pdfFile\n";

    // Enviar por email
    echo "5. Enviando por email a $email... ";

    // Obtener datos del documento para el asunto
    $documentData = $documentBag->getDocumentData();
    $folioDoc = $documentData['Encabezado']['IdDoc']['Folio'] ?? $folio;
    $total = $documentData['Encabezado']['Totales']['MntTotal'] ?? 0;
    $emisor = $documentData['Encabezado']['Emisor']['RznSoc'] ?? 'AKIBARA SPA';

    // Preparar email
    $subject = "Boleta Electrónica N° $folioDoc - $emisor";
    $bodyText = "Estimado cliente,\n\n";
    $bodyText .= "Adjunto encontrará su Boleta Electrónica.\n\n";
    $bodyText .= "Detalles:\n";
    $bodyText .= "- Tipo: Boleta Electrónica\n";
    $bodyText .= "- Folio: $folioDoc\n";
    $bodyText .= "- Total: $" . number_format($total) . "\n";
    $bodyText .= "- Emisor: $emisor\n\n";
    $bodyText .= "Puede verificar este documento en www.sii.cl\n\n";
    $bodyText .= "Saludos cordiales,\n";
    $bodyText .= "$emisor";

    // Cargar configuración SMTP
    $smtpConfigFile = __DIR__ . '/smtp_config.php';
    $smtpConfig = file_exists($smtpConfigFile) ? include($smtpConfigFile) : [];

    // O desde variables de entorno
    $smtpHost = $smtpConfig['host'] ?? getenv('SMTP_HOST') ?: '';
    $smtpPort = $smtpConfig['port'] ?? getenv('SMTP_PORT') ?: 587;
    $smtpUser = $smtpConfig['user'] ?? getenv('SMTP_USER') ?: '';
    $smtpPass = $smtpConfig['pass'] ?? getenv('SMTP_PASS') ?: '';
    $smtpFrom = $smtpConfig['from'] ?? getenv('SMTP_FROM') ?: $smtpUser;
    $smtpFromName = $smtpConfig['from_name'] ?? getenv('SMTP_FROM_NAME') ?: $emisor;

    $emailSent = false;

    // Intentar enviar con Symfony Mailer (SMTP)
    if ($smtpHost && $smtpUser && $smtpPass) {
        try {
            // Construir DSN para SMTP (smtps:// para SSL puerto 465, smtp:// para TLS puerto 587)
            $protocol = ($smtpPort == 465) ? 'smtps' : 'smtp';
            $dsn = sprintf(
                '%s://%s:%s@%s:%d',
                $protocol,
                urlencode($smtpUser),
                urlencode($smtpPass),
                $smtpHost,
                $smtpPort
            );

            $transport = Transport::fromDsn($dsn);
            $mailer = new Mailer($transport);

            // Crear email
            $emailMessage = (new Email())
                ->from(new Address($smtpFrom, $smtpFromName))
                ->to($email)
                ->subject($subject)
                ->text($bodyText)
                ->attach($pdfContent, "Boleta_{$folioDoc}.pdf", 'application/pdf')
                ->attach($xmlContent, "Boleta_{$folioDoc}.xml", 'application/xml');

            $mailer->send($emailMessage);
            $emailSent = true;
            echo "OK\n";
        } catch (Exception $smtpError) {
            echo "ERROR SMTP: " . $smtpError->getMessage() . "\n";
        }
    }

    // Fallback: Intentar con mail() de PHP
    if (!$emailSent) {
        $boundary = md5(time());

        $headers = "From: noreply@akibara.cl\r\n";
        $headers .= "Reply-To: noreply@akibara.cl\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: multipart/mixed; boundary=\"$boundary\"\r\n";

        $message = "--$boundary\r\n";
        $message .= "Content-Type: text/plain; charset=UTF-8\r\n";
        $message .= "Content-Transfer-Encoding: 8bit\r\n\r\n";
        $message .= $bodyText . "\r\n\r\n";

        $message .= "--$boundary\r\n";
        $message .= "Content-Type: application/pdf; name=\"Boleta_$folioDoc.pdf\"\r\n";
        $message .= "Content-Disposition: attachment; filename=\"Boleta_$folioDoc.pdf\"\r\n";
        $message .= "Content-Transfer-Encoding: base64\r\n\r\n";
        $message .= chunk_split(base64_encode($pdfContent)) . "\r\n";

        $message .= "--$boundary\r\n";
        $message .= "Content-Type: application/xml; name=\"Boleta_$folioDoc.xml\"\r\n";
        $message .= "Content-Disposition: attachment; filename=\"Boleta_$folioDoc.xml\"\r\n";
        $message .= "Content-Transfer-Encoding: base64\r\n\r\n";
        $message .= chunk_split(base64_encode($xmlContent)) . "\r\n";

        $message .= "--$boundary--";

        if (@mail($email, $subject, $message, $headers)) {
            $emailSent = true;
            echo "OK (via mail())\n";
        }
    }

    if ($emailSent) {
        echo "\n╔══════════════════════════════════════════════════════════════════════╗\n";
        echo "║  EMAIL ENVIADO EXITOSAMENTE                                         ║\n";
        echo "╚══════════════════════════════════════════════════════════════════════╝\n";
    } else {
        echo "NO ENVIADO\n";
        echo "\n╔══════════════════════════════════════════════════════════════════════╗\n";
        echo "║  PDF GENERADO - EMAIL NO ENVIADO                                    ║\n";
        echo "║  Configure SMTP en smtp_config.php o variables de entorno           ║\n";
        echo "╚══════════════════════════════════════════════════════════════════════╝\n";
        echo "\nPara enviar por SMTP, cree el archivo smtp_config.php:\n";
        echo "  <?php return [\n";
        echo "      'host' => 'smtp.gmail.com',\n";
        echo "      'port' => 587,\n";
        echo "      'user' => 'tu-email@gmail.com',\n";
        echo "      'pass' => 'tu-app-password',\n";
        echo "      'from' => 'tu-email@gmail.com',\n";
        echo "      'from_name' => 'AKIBARA SPA',\n";
        echo "  ];\n";
    }

    echo "\nArchivos generados:\n";
    echo "  PDF: $pdfFile\n";
    echo "  XML: $xmlFile\n";

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}
