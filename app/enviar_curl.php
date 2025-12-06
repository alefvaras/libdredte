<?php
/**
 * Envío de boletas al SII usando curl (sin SOAP)
 */

declare(strict_types=1);

require_once __DIR__ . '/../wordpress-plugin/akibara-sii/vendor/autoload.php';

use Derafu\Certificate\Service\CertificateLoader;

$config = require __DIR__ . '/config/config.php';
$emisorConfig = require __DIR__ . '/config/emisor.php';

date_default_timezone_set('America/Santiago');

// Configuración SII
$esProduccion = $config['ambiente'] === 'produccion';
$siiHost = $esProduccion ? 'palena.sii.cl' : 'maullin.sii.cl';

echo "=== ENVIO AL SII VIA CURL ===\n\n";
echo "Ambiente: " . ($esProduccion ? 'PRODUCCION' : 'CERTIFICACION') . "\n";
echo "Host SII: $siiHost\n\n";

// Cargar certificado
echo "1. Cargando certificado... ";
$certificateLoader = new CertificateLoader();
$certificate = $certificateLoader->loadFromFile(
    $config['paths']['certificado'],
    $config['certificado_password']
);
echo "OK\n";
echo "   Titular: " . $certificate->getName() . "\n";
echo "   RUT: " . $certificate->getId() . "\n\n";

// Paso 1: Obtener semilla
echo "2. Obteniendo semilla del SII... ";

$soapEnvelope = '<?xml version="1.0" encoding="UTF-8"?>
<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/">
  <soapenv:Body>
    <getSeed xmlns="http://DefaultNamespace"/>
  </soapenv:Body>
</soapenv:Envelope>';

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://$siiHost/DTEWS/CrSeed.jws");
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $soapEnvelope);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
curl_setopt($ch, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1_2);
curl_setopt($ch, CURLOPT_SSL_CIPHER_LIST, 'DEFAULT@SECLEVEL=1');
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: text/xml; charset=utf-8',
    'SOAPAction: ""',
]);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);

$response = curl_exec($ch);
$error = curl_error($ch);
curl_close($ch);

if ($error) {
    echo "ERROR: $error\n";
    exit(1);
}

$response = html_entity_decode($response);

if (preg_match('/<SEMILLA>(\d+)<\/SEMILLA>/', $response, $matches)) {
    $semilla = $matches[1];
    echo "OK\n";
    echo "   Semilla: $semilla\n\n";
} else {
    echo "ERROR: No se pudo obtener semilla\n";
    exit(1);
}

// Paso 2: Firmar semilla con openssl
echo "3. Firmando semilla... ";

$semillaXml = '<getToken><item><Semilla>' . $semilla . '</Semilla></item></getToken>';

// Calcular digest del contenido
$doc = new DOMDocument('1.0', 'UTF-8');
$doc->loadXML($semillaXml);
$c14n = $doc->C14N();
$digestValue = base64_encode(sha1($c14n, true));

// Crear SignedInfo
$signedInfo = '<SignedInfo xmlns="http://www.w3.org/2000/09/xmldsig#">' .
    '<CanonicalizationMethod Algorithm="http://www.w3.org/TR/2001/REC-xml-c14n-20010315"/>' .
    '<SignatureMethod Algorithm="http://www.w3.org/2000/09/xmldsig#rsa-sha1"/>' .
    '<Reference URI="">' .
    '<Transforms>' .
    '<Transform Algorithm="http://www.w3.org/2000/09/xmldsig#enveloped-signature"/>' .
    '</Transforms>' .
    '<DigestMethod Algorithm="http://www.w3.org/2000/09/xmldsig#sha1"/>' .
    '<DigestValue>' . $digestValue . '</DigestValue>' .
    '</Reference>' .
    '</SignedInfo>';

// Firmar SignedInfo
$signedInfoDoc = new DOMDocument();
$signedInfoDoc->loadXML($signedInfo);
$signedInfoC14n = $signedInfoDoc->C14N();

$privateKey = $certificate->getPrivateKey();
$signature = '';
openssl_sign($signedInfoC14n, $signature, $privateKey, OPENSSL_ALGO_SHA1);
$signatureValue = base64_encode($signature);

// Obtener certificado X509
$x509 = $certificate->getCertificate();
$x509Clean = str_replace(['-----BEGIN CERTIFICATE-----', '-----END CERTIFICATE-----', "\n", "\r"], '', $x509);

// Construir Signature completa
$signatureXml = '<Signature xmlns="http://www.w3.org/2000/09/xmldsig#">' .
    $signedInfo .
    '<SignatureValue>' . $signatureValue . '</SignatureValue>' .
    '<KeyInfo>' .
    '<KeyValue>' .
    '<RSAKeyValue>' .
    '<Modulus>' . $certificate->getModulus() . '</Modulus>' .
    '<Exponent>' . $certificate->getExponent() . '</Exponent>' .
    '</RSAKeyValue>' .
    '</KeyValue>' .
    '<X509Data>' .
    '<X509Certificate>' . $x509Clean . '</X509Certificate>' .
    '</X509Data>' .
    '</KeyInfo>' .
    '</Signature>';

// Insertar firma en el documento
$signedXml = str_replace('</getToken>', $signatureXml . '</getToken>', $semillaXml);

echo "OK\n\n";

// Paso 3: Obtener token
echo "4. Obteniendo token del SII... ";

$tokenSoapEnvelope = '<?xml version="1.0" encoding="UTF-8"?>
<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/">
  <soapenv:Body>
    <getToken xmlns="http://DefaultNamespace">
      <pszXml><![CDATA[' . $signedXml . ']]></pszXml>
    </getToken>
  </soapenv:Body>
</soapenv:Envelope>';

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://$siiHost/DTEWS/GetTokenFromSeed.jws");
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $tokenSoapEnvelope);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: text/xml; charset=utf-8',
    'SOAPAction: ""',
]);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);

$response = curl_exec($ch);
$error = curl_error($ch);
curl_close($ch);

if ($error) {
    echo "ERROR: $error\n";
    exit(1);
}

$response = html_entity_decode($response);

if (preg_match('/<TOKEN>([^<]+)<\/TOKEN>/', $response, $matches)) {
    $token = $matches[1];
    echo "OK\n";
    echo "   Token: " . substr($token, 0, 30) . "...\n\n";
} else {
    echo "ERROR: No se pudo obtener token\n";
    echo "Respuesta: " . substr($response, 0, 2000) . "\n";
    exit(1);
}

// Paso 4: Leer boleta y crear sobre
echo "5. Preparando envío de boleta...\n";

$outputDir = $config['paths']['output'];
$xmlFiles = glob($outputDir . 'boleta_39_F*.xml');
if (empty($xmlFiles)) {
    echo "ERROR: No hay boletas generadas\n";
    exit(1);
}

usort($xmlFiles, fn($a, $b) => filemtime($b) - filemtime($a));
$boletaXmlFile = $xmlFiles[0];
$boletaXml = file_get_contents($boletaXmlFile);

echo "   Boleta: " . basename($boletaXmlFile) . "\n";

preg_match('/<Folio>(\d+)<\/Folio>/', $boletaXml, $folioMatch);
$folio = $folioMatch[1] ?? 'unknown';

// Crear sobre EnvioBOLETA (ya firmado porque la boleta ya está firmada)
$fechaResolucion = $emisorConfig['autorizacionDte']['fechaResolucion'];
$numeroResolucion = $emisorConfig['autorizacionDte']['numeroResolucion'];
$rutEmisor = $emisorConfig['RUTEmisor'];
$rutEnvia = $certificate->getId();

$sobreXmlSinFirma = '<EnvioBOLETA xmlns="http://www.sii.cl/SiiDte" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.sii.cl/SiiDte EnvioBOLETA_v11.xsd" version="1.0">
<SetDTE ID="SetDoc">
<Caratula version="1.0">
<RutEmisor>' . $rutEmisor . '</RutEmisor>
<RutEnvia>' . $rutEnvia . '</RutEnvia>
<RutReceptor>60803000-K</RutReceptor>
<FchResol>' . $fechaResolucion . '</FchResol>
<NroResol>' . $numeroResolucion . '</NroResol>
<TmstFirmaEnv>' . date('Y-m-d\TH:i:s') . '</TmstFirmaEnv>
<SubTotDTE>
<TpoDTE>39</TpoDTE>
<NroDTE>1</NroDTE>
</SubTotDTE>
</Caratula>
' . $boletaXml . '
</SetDTE>
</EnvioBOLETA>';

// Firmar el sobre EnvioBOLETA
echo "   Firmando sobre EnvioBOLETA... ";

// Cargar el XML del sobre
$sobreDoc = new DOMDocument('1.0', 'ISO-8859-1');
$sobreDoc->loadXML($sobreXmlSinFirma);

// Obtener el nodo SetDTE para calcular el digest
$setDteNode = $sobreDoc->getElementsByTagName('SetDTE')->item(0);
$setDteC14n = $setDteNode->C14N();
$digestValue = base64_encode(sha1($setDteC14n, true));

// Crear SignedInfo para el sobre
$signedInfoSobre = '<SignedInfo xmlns="http://www.w3.org/2000/09/xmldsig#">' .
    '<CanonicalizationMethod Algorithm="http://www.w3.org/TR/2001/REC-xml-c14n-20010315"/>' .
    '<SignatureMethod Algorithm="http://www.w3.org/2000/09/xmldsig#rsa-sha1"/>' .
    '<Reference URI="#SetDoc">' .
    '<Transforms>' .
    '<Transform Algorithm="http://www.w3.org/TR/2001/REC-xml-c14n-20010315"/>' .
    '</Transforms>' .
    '<DigestMethod Algorithm="http://www.w3.org/2000/09/xmldsig#sha1"/>' .
    '<DigestValue>' . $digestValue . '</DigestValue>' .
    '</Reference>' .
    '</SignedInfo>';

// Firmar SignedInfo
$signedInfoSobreDoc = new DOMDocument();
$signedInfoSobreDoc->loadXML($signedInfoSobre);
$signedInfoSobreC14n = $signedInfoSobreDoc->C14N();

$signatureSobre = '';
openssl_sign($signedInfoSobreC14n, $signatureSobre, $certificate->getPrivateKey(), OPENSSL_ALGO_SHA1);
$signatureValueSobre = base64_encode($signatureSobre);

// Construir Signature para el sobre
$signatureXmlSobre = '<Signature xmlns="http://www.w3.org/2000/09/xmldsig#">' .
    $signedInfoSobre .
    '<SignatureValue>' . $signatureValueSobre . '</SignatureValue>' .
    '<KeyInfo>' .
    '<KeyValue>' .
    '<RSAKeyValue>' .
    '<Modulus>' . $certificate->getModulus() . '</Modulus>' .
    '<Exponent>' . $certificate->getExponent() . '</Exponent>' .
    '</RSAKeyValue>' .
    '</KeyValue>' .
    '<X509Data>' .
    '<X509Certificate>' . $x509Clean . '</X509Certificate>' .
    '</X509Data>' .
    '</KeyInfo>' .
    '</Signature>';

// Insertar firma en el sobre
$sobreXml = '<?xml version="1.0" encoding="ISO-8859-1"?>' . "\n" .
    str_replace('</EnvioBOLETA>', $signatureXmlSobre . '</EnvioBOLETA>', $sobreXmlSinFirma);

echo "OK\n";

// Guardar sobre firmado
$sobreFile = $outputDir . "EnvioBOLETA_F{$folio}_" . date('Ymd_His') . ".xml";
file_put_contents($sobreFile, $sobreXml);
echo "   Sobre guardado: $sobreFile\n\n";

// Paso 5: Enviar al SII
echo "6. Enviando al SII... ";

[$rutNum, $rutDv] = explode('-', $rutEnvia);
[$rutEmpNum, $rutEmpDv] = explode('-', $rutEmisor);

$tempFile = tempnam(sys_get_temp_dir(), 'dte_') . '.xml';
file_put_contents($tempFile, $sobreXml);

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://$siiHost/cgi_dte/UPL/DTEUpload");
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Cookie: TOKEN=' . $token,
    'User-Agent: Mozilla/5.0 (compatible; LibreDTE)',
]);
curl_setopt($ch, CURLOPT_POSTFIELDS, [
    'rutSender' => $rutNum,
    'dvSender' => $rutDv,
    'rutCompany' => $rutEmpNum,
    'dvCompany' => $rutEmpDv,
    'archivo' => new CURLFile($tempFile, 'application/xml', basename($sobreFile)),
]);
curl_setopt($ch, CURLOPT_TIMEOUT, 60);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

unlink($tempFile);

if ($error) {
    echo "ERROR CURL: $error\n";
    exit(1);
}

echo "Respuesta recibida (HTTP $httpCode)\n\n";

echo "=== RESPUESTA DEL SII ===\n";
echo $response . "\n\n";

// Buscar Track ID en respuesta XML o HTML
$trackId = null;
if (preg_match('/<TRACKID>(\d+)<\/TRACKID>/i', $response, $trackMatch)) {
    $trackId = $trackMatch[1];
} elseif (preg_match('/Identificador de env[^:]*:\s*<strong>(\d+)<\/strong>/i', $response, $trackMatch)) {
    $trackId = $trackMatch[1];
}

if ($trackId) {
    echo "========================================\n";
    echo "  TRACK ID: $trackId\n";
    echo "========================================\n\n";

    $resultFile = $outputDir . "envio_trackid_{$trackId}.json";
    file_put_contents($resultFile, json_encode([
        'track_id' => $trackId,
        'folio' => $folio,
        'fecha_envio' => date('Y-m-d H:i:s'),
        'ambiente' => $esProduccion ? 'produccion' : 'certificacion',
    ], JSON_PRETTY_PRINT));

    echo "Resultado guardado: $resultFile\n";
} elseif (preg_match('/<STATUS>(\d+)<\/STATUS>/', $response, $statusMatch)) {
    echo "Estado: " . $statusMatch[0] . "\n";
} elseif (strpos($response, 'DOCUMENTO TRIBUTARIO ELECTRONICO RECIBIDO') !== false) {
    echo "DOCUMENTO RECIBIDO - revisar respuesta HTML para Track ID\n";
}

echo "\n¡Proceso completado!\n";
